#!/usr/bin/env python3
import os
import subprocess
import sys
import venv


def run(cmd, cwd=None):
    result = subprocess.run(cmd, cwd=cwd)
    if result.returncode != 0:
        sys.exit(result.returncode)


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

    ensure_venv(venv_path)
    pip_path = venv_bin(venv_path, "pip")

    if not os.path.exists(pip_path):
        print("pip not found in .venv", file=sys.stderr)
        sys.exit(1)

    run([pip_path, "install", "pyyaml"], cwd=root)
    run(["npm", "install"], cwd=root)


if __name__ == "__main__":
    main()
