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


def resolve_origin_url(source):
    if os.path.exists(source):
        return output(["git", "-C", source, "config", "--get", "remote.origin.url"])
    return source


def expected_owner_ok(url, owner):
    if not owner:
        return True
    if not url:
        return False
    lower = url.lower()
    owner = owner.lower()
    return f"github.com/{owner}/" in lower or f"github.com:{owner}/" in lower


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
            "APP_ENV": "dev",
            "AUTO_ENV_SETUP": "1",
            "DEFAULT_CV_PROFILE": "default",
            "CV_PROFILE": "default",
            "APP_LANG": "de",
        }
    )
    return env


def clone_repo(source, target):
    if os.path.exists(source):
        run(["git", "clone", "--local", source, target])
        return
    run(["git", "clone", source, target])


def wait_for_server(url, retries=20, delay=0.5):
    for _ in range(retries):
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


def start_dev_server(clone_path, env):
    popen_kwargs = {"cwd": clone_path, "env": env}
    if os.name == "nt":
        popen_kwargs["creationflags"] = subprocess.CREATE_NEW_PROCESS_GROUP
    else:
        popen_kwargs["preexec_fn"] = os.setsid
    return subprocess.Popen(["php", "bin/cli", "run", "dev"], **popen_kwargs)


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
        origin_url = resolve_origin_url(source)
        expected_owner = os.environ.get("EXPECTED_GITHUB_USER", "")
        if not expected_owner_ok(origin_url, expected_owner):
            raise RuntimeError(
                f"Origin mismatch: expected GitHub owner {expected_owner}, got {origin_url or 'missing'}"
            )

        cls.temp_dir = tempfile.mkdtemp(prefix="lebenslauf-smoke-")
        cls.clone_path = os.path.join(cls.temp_dir, "repo")
        clone_repo(source, cls.clone_path)
        cls.env = build_env(os.environ, cls.clone_path)
        run(["composer", "install", "--no-interaction", "--prefer-dist"], cwd=cls.clone_path, env=cls.env)
        run(["php", "bin/cli", "setup", "dev"], cwd=cls.clone_path, env=cls.env)

    @classmethod
    def tearDownClass(cls):
        if os.environ.get("KEEP_SMOKE_CLONE") == "1":
            print(f"Smoke clone kept at: {cls.clone_path}")
            return
        shutil.rmtree(cls.temp_dir, ignore_errors=True)

    def test_smoke_flow(self):
        """clone -> setup -> tests -> dev-server -> /cv check."""
        env_path = os.path.join(self.clone_path, ".local", "env-dev.ini")
        self.assertTrue(os.path.isfile(env_path), "Expected .local/env-dev.ini to exist")

        run(["php", "bin/cli", "cv", "build", "dev"], cwd=self.clone_path, env=self.env)

        run(["composer", "run", "test"], cwd=self.clone_path, env=self.env)

        proc = start_dev_server(self.clone_path, self.env)
        try:
            wait_for_server("http://127.0.0.1:8080/")
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
