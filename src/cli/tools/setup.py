#!/usr/bin/env .venv/bin/python3
import os
import subprocess
import sys
import venv


def run_checked(cmd, cwd=None):
    result = subprocess.run(cmd, cwd=cwd)
    if result.returncode != 0:
        sys.exit(result.returncode)


def ensure_venv(venv_path):
    if os.path.isdir(venv_path):
        return
    builder = venv.EnvBuilder(with_pip=True)
    builder.create(venv_path)


def venv_bin(venv_path, name):
    if os.name == "nt":
        return os.path.join(venv_path, "Scripts", f"{name}.exe")
    return os.path.join(venv_path, "bin", name)


def resolve_root_path():
    return os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", ".."))


def resolve_venv_path(root_path):
    return os.path.join(root_path, ".venv")


def resolve_pip_path(venv_path):
    return venv_bin(venv_path, "pip")


def require_pip(pip_path):
    if os.path.exists(pip_path):
        return
    print("pip not found in .venv", file=sys.stderr)
    sys.exit(1)


def install_python_dependencies(root_path, pip_path):
    run_checked([pip_path, "install", "pyyaml"], cwd=root_path)


def install_node_dependencies(root_path):
    run_checked(["npm", "install"], cwd=root_path)


def main():
    root_path = resolve_root_path()
    venv_path = resolve_venv_path(root_path)

    ensure_venv(venv_path)
    pip_path = resolve_pip_path(venv_path)
    require_pip(pip_path)

    install_python_dependencies(root_path, pip_path)
    install_node_dependencies(root_path)

if __name__ == "__main__":
    main()
