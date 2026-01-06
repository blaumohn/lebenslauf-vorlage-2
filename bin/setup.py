#!/usr/bin/env python3
import os
import subprocess
import sys
import venv


def run(cmd, cwd=None):
    result = subprocess.run(cmd, cwd=cwd)
    if result.returncode != 0:
        sys.exit(result.returncode)


def should_prompt():
    return sys.stdin.isatty() and sys.stdout.isatty()


def ensure_local_env(root):
    local_dir = os.path.join(root, ".local")
    common_path = os.path.join(local_dir, "env-common.ini")
    profile = os.environ.get("APP_ENV", "dev")
    profile_path = os.path.join(local_dir, f"env-{profile}.ini")
    if os.path.exists(common_path) or os.path.exists(profile_path):
        return
    if not should_prompt() and os.environ.get("AUTO_ENV_SETUP") != "1":
        return
    fixture_path = os.path.join(root, "tests", "fixtures", "env-gueltig.ini")
    if not os.path.exists(fixture_path):
        return
    if should_prompt() and os.environ.get("AUTO_ENV_SETUP") != "1":
        answer = input("Keine .local/env-*.ini gefunden. Demo-Umgebung aus tests/fixtures/env-gueltig.ini anlegen? [y/N] ").strip().lower()
        if answer != "y":
            return
    os.makedirs(local_dir, exist_ok=True)
    target_path = os.path.join(local_dir, "env-dev.ini")
    with open(fixture_path, "r", encoding="utf-8") as handle:
        content = handle.read()
    with open(target_path, "w", encoding="utf-8") as handle:
        handle.write(content)
    print(f"{target_path} erstellt.")


def ensure_venv(venv_path):
    if not os.path.isdir(venv_path):
        builder = venv.EnvBuilder(with_pip=True)
        builder.create(venv_path)


def venv_bin(venv_path, name):
    if os.name == "nt":
        return os.path.join(venv_path, "Scripts", f"{name}.exe")
    return os.path.join(venv_path, "bin", name)


def main():
    root = os.path.abspath(os.path.dirname(os.path.dirname(__file__)))
    venv_path = os.path.join(root, ".venv")

    ensure_local_env(root)

    ensure_venv(venv_path)
    pip_path = venv_bin(venv_path, "pip")

    if not os.path.exists(pip_path):
        print("pip not found in .venv", file=sys.stderr)
        sys.exit(1)

    run([pip_path, "install", "pyyaml"], cwd=root)
    run(["npm", "install"], cwd=root)


if __name__ == "__main__":
    main()
