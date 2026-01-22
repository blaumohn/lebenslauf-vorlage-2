#!/usr/bin/env .venv/bin/python3
import os
import shutil
import signal
import subprocess
import sys
import tempfile
import time
import unittest
from urllib.request import Request, urlopen


ROOT = os.path.abspath(os.path.dirname(os.path.dirname(__file__)))


def run(cmd, cwd=None, env=None):
    result = subprocess.run(cmd, cwd=cwd, env=env)
    if result.returncode != 0:
        raise RuntimeError(f"Command failed: {' '.join(cmd)}")


def output(cmd, cwd=None):
    result = subprocess.run(cmd, cwd=cwd, capture_output=True, text=True)
    if result.returncode != 0:
        return ""
    return result.stdout.strip()


def resolve_source(value):
    if value in ("", "local", "repo"):
        return ROOT
    if os.path.exists(value):
        return os.path.abspath(value)
    return value


def ensure_cache_dir(path):
    os.makedirs(path, exist_ok=True)
    return path


def build_env(base_env, clone_path):
    cache_root = ensure_cache_dir(os.path.join(ROOT, "var", "cache", "smoke"))
    env = dict(base_env)
    env.update(
        {
            "COMPOSER_CACHE_DIR": ensure_cache_dir(os.path.join(cache_root, "composer")),
            "NPM_CONFIG_CACHE": ensure_cache_dir(os.path.join(cache_root, "npm")),
            "PIP_CACHE_DIR": ensure_cache_dir(os.path.join(cache_root, "pip")),
            "LEBENSLAUF_DATEN_PFAD": os.path.join(clone_path, ".local", "lebenslauf"),
            "APP_BASE_PATH": "",
            "PIPELINE": "dev",
            "AUTO_ENV_SETUP": "1",
            "LEBENSLAUF_PUBLIC_PROFILE": "default",
            "LEBENSLAUF_LANG_DEFAULT": "de",
            "LEBENSLAUF_LANGS": "de,en",
            "IP_SALT": "change-me",
            "CAPTCHA_MAX_GET": "5",
            "CONTACT_MAX_POST": "3",
            "CONTACT_TO_EMAIL": "test@example.com",
            "CONTACT_FROM_EMAIL": "web@example.com",
            "RATE_LIMIT_WINDOW_SECONDS": "600",
            "MAIL_STDOUT": "1",
            "SMTP_FROM_NAME": "Web",
        }
    )
    return env


def clone_repo(source, target):
    if os.path.exists(source):
        run(["git", "clone", "--local", source, target])
        return
    run(["git", "clone", source, target])


def wait_for_server(url, process, retries=20, delay=0.5):
    for _ in range(retries):
        exit_code = process.poll()
        if exit_code is not None:
            stdout, stderr = process.communicate(timeout=2)
            raise RuntimeError(
                "Dev-Server ist beendet.\n"
                f"Exit-Code: {exit_code}\n"
                f"STDOUT:\n{stdout}\n"
                f"STDERR:\n{stderr}"
            )
        try:
            fetch(url)
            return
        except Exception:
            time.sleep(delay)
    raise RuntimeError(f"Server nicht erreichbar: {url}")


def fetch(url):
    req = Request(url, headers={"User-Agent": "lebenslauf-smoke"})
    with urlopen(req, timeout=5) as response:
        body = response.read()
    return body.decode("utf-8", errors="replace")


def start_dev_server(clone_path, env, demo=False):
    popen_kwargs = {"cwd": clone_path, "env": env}
    if os.name == "nt":
        popen_kwargs["creationflags"] = subprocess.CREATE_NEW_PROCESS_GROUP
    else:
        popen_kwargs["preexec_fn"] = os.setsid
    cmd = ["php", "bin/cli", "run", "dev"]
    if demo:
        cmd.append("--demo")
    return subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, **popen_kwargs)


def ensure_env_local(clone_path):
    env_path = os.path.join(clone_path, ".env.local")
    if os.path.isfile(env_path):
        return
    fixture = os.path.join(clone_path, "tests", "fixtures", "env.local")
    if not os.path.isfile(fixture):
        raise RuntimeError("Missing env.local fixture.")
    shutil.copyfile(fixture, env_path)


def stop_dev_server(proc):
    if os.name == "nt":
        proc.terminate()
        try:
            proc.wait(timeout=10)
        except subprocess.TimeoutExpired:
            proc.kill()
        return
    try:
        os.killpg(os.getpgid(proc.pid), signal.SIGTERM)
    except ProcessLookupError:
        return
    try:
        proc.wait(timeout=10)
    except subprocess.TimeoutExpired:
        os.killpg(os.getpgid(proc.pid), signal.SIGKILL)


class SmokeTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        source = resolve_source(os.environ.get("CLONE_SOURCE", "local"))
        cls.temp_dir = tempfile.mkdtemp(prefix="lebenslauf-smoke-")
        cls.clone_path = os.path.join(cls.temp_dir, "repo")
        clone_repo(source, cls.clone_path)
        cls.env = build_env(os.environ, cls.clone_path)
        run(["composer", "install", "--no-interaction", "--prefer-dist"], cwd=cls.clone_path, env=cls.env)

    @classmethod
    def tearDownClass(cls):
        if os.environ.get("KEEP_SMOKE_CLONE") == "1":
            print(f"Smoke clone kept at: {cls.clone_path}")
            return
        shutil.rmtree(cls.temp_dir, ignore_errors=True)

    def run_setup(self, create_templates=False):
        cmd = ["php", "bin/cli", "setup", "dev"]
        if create_templates:
            cmd.append("--create-demo-content")
        run(cmd, cwd=self.clone_path, env=self.env)

    def test_smoke_create_templates(self):
        """setup --create-demo-content -> tests -> dev-server -> /cv check."""
        self.run_setup(create_templates=True)
        ensure_env_local(self.clone_path)

        env_path = os.path.join(self.clone_path, ".env.local")
        self.assertTrue(os.path.isfile(env_path), "Expected .env.local to exist")

        run(["php", "bin/cli", "build", "dev", "cv"], cwd=self.clone_path, env=self.env)

        run(["composer", "run", "test"], cwd=self.clone_path, env=self.env)

        proc = start_dev_server(self.clone_path, self.env)
        try:
            wait_for_server("http://127.0.0.1:8080/", proc)
            html = fetch("http://127.0.0.1:8080/cv")
            self.assertIn("Lebenslauf", html)
        finally:
            stop_dev_server(proc)

    def test_smoke_demo(self):
        """setup -> dev-server --demo -> /cv check."""
        self.run_setup()
        ensure_env_local(self.clone_path)

        proc = start_dev_server(self.clone_path, self.env, demo=True)
        try:
            wait_for_server("http://127.0.0.1:8080/", proc)
            html = fetch("http://127.0.0.1:8080/cv")
            self.assertIn("Lebenslauf", html)
        finally:
            stop_dev_server(proc)


class SmokeTestResult(unittest.TextTestResult):
    def getDescription(self, test):
        desc = test.shortDescription()
        name = f"{test.__class__.__name__}.{test._testMethodName}"
        if desc:
            return f"{name} - {desc}"
        return name


if __name__ == "__main__":
    runner = unittest.TextTestRunner(verbosity=2, resultclass=SmokeTestResult)
    suite = unittest.defaultTestLoader.loadTestsFromTestCase(SmokeTests)
    result = runner.run(suite)
    sys.exit(0 if result.wasSuccessful() else 1)
