#!/usr/bin/env python3
import argparse
import os
import signal
import shutil
import subprocess
import sys
import tempfile
import time


ROOT = os.path.abspath(os.path.dirname(os.path.dirname(__file__)))


def run(cmd, cwd=None, env=None):
    result = subprocess.run(cmd, cwd=cwd, env=env)
    if result.returncode != 0:
        sys.exit(result.returncode)


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
    cache_root = ensure_cache_dir(os.path.join(ROOT, "var", "cache", "qa"))
    env = dict(base_env)
    env.update(
        {
            "COMPOSER_CACHE_DIR": ensure_cache_dir(os.path.join(cache_root, "composer")),
            "NPM_CONFIG_CACHE": ensure_cache_dir(os.path.join(cache_root, "npm")),
            "PIP_CACHE_DIR": ensure_cache_dir(os.path.join(cache_root, "pip")),
            "LEBENSLAUF_DATEN_PFAD": os.path.join(clone_path, "tests", "fixtures"),
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


def setup_repo(clone_path, env):
    run(["composer", "install", "--no-interaction", "--prefer-dist"], cwd=clone_path, env=env)
    run(["composer", "run", "setup"], cwd=clone_path, env=env)


def build_mock_cv(clone_path, env):
    mock_dir = env.get("LEBENSLAUF_DATEN_PFAD", "")
    if not os.path.isdir(mock_dir):
        print(f"Mock data directory missing: {mock_dir}", file=sys.stderr)
        sys.exit(1)
    run(["composer", "run", "cv:build"], cwd=clone_path, env=env)


def run_tests(clone_path, env):
    run(["composer", "run", "test"], cwd=clone_path, env=env)


def wait_for_server(url, retries=20, delay=0.5):
    for _ in range(retries):
        result = subprocess.run(
            ["curl", "--fail", "--silent", "--show-error", url],
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
        )
        if result.returncode == 0:
            return
        time.sleep(delay)
    print(f"Server not reachable: {url}", file=sys.stderr)
    sys.exit(1)


def curl_check(url):
    run(["curl", "--fail", "--silent", "--show-error", url])


def run_dev_check(clone_path, env):
    host = "127.0.0.1:8080"
    popen_kwargs = {"cwd": clone_path, "env": env}
    if os.name == "nt":
        popen_kwargs["creationflags"] = subprocess.CREATE_NEW_PROCESS_GROUP
    else:
        popen_kwargs["preexec_fn"] = os.setsid
    proc = subprocess.Popen(["composer", "run", "dev"], **popen_kwargs)
    try:
        wait_for_server(f"http://{host}/")
        curl_check(f"http://{host}/")
        curl_check(f"http://{host}/cv")
    finally:
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


def main():
    parser = argparse.ArgumentParser(description="Isolated QA environment runner.")
    parser.add_argument("action", choices=["setup", "dev"])
    parser.add_argument("--source", default=os.environ.get("CLONE_SOURCE", "local"))
    parser.add_argument("--expected-github-user", default=os.environ.get("EXPECTED_GITHUB_USER", ""))
    parser.add_argument("--keep", action="store_true", default=os.environ.get("KEEP_QA_CLONE") == "1")
    args = parser.parse_args()

    source = resolve_source(args.source)
    origin_url = resolve_origin_url(source)
    if not expected_owner_ok(origin_url, args.expected_github_user):
        print(
            "Origin mismatch: expected GitHub owner "
            f"{args.expected_github_user}, got {origin_url or 'missing'}",
            file=sys.stderr,
        )
        sys.exit(1)

    temp_dir = tempfile.mkdtemp(prefix="lebenslauf-qa-")
    clone_path = os.path.join(temp_dir, "repo")
    try:
        clone_repo(source, clone_path)
        env = build_env(os.environ, clone_path)
        setup_repo(clone_path, env)
        if args.action == "dev":
            build_mock_cv(clone_path, env)
            run_tests(clone_path, env)
            run_dev_check(clone_path, env)
    finally:
        if args.keep:
            print(f"QA clone kept at: {clone_path}")
        else:
            shutil.rmtree(temp_dir, ignore_errors=True)


if __name__ == "__main__":
    main()
