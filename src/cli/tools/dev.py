#!/usr/bin/env .venv/bin/python3
import argparse
import os
import subprocess
import sys

from process_supervisor import ProcessSupervisor
from watchers import css
from watchers.file_watcher import FileWatcher
from watchers.schedule import schedule_twig, schedule_yaml


def main():
    args = parse_args()
    root_path = resolve_root_path()
    os.chdir(root_path)

    process_env = build_runtime_env(args)
    ensure_initial_build(args, process_env, root_path)

    supervisor = ProcessSupervisor()
    supervisor.install_signal_handlers()

    start_php_server(process_env, root_path, supervisor)
    file_watcher = setup_watchers(supervisor, process_env, root_path, args.demo)

    exit_code = supervisor.run(file_watcher)
    sys.exit(exit_code)


def parse_args():
    parser = argparse.ArgumentParser(description="Dev-Server mit Watchern starten.")
    parser.add_argument("--build", action="store_true", help="CV-Build vor dem Start ausfuehren.")
    parser.add_argument("--demo", action="store_true", help="Demo-Fixtures fuer den CV-Build nutzen.")
    parser.add_argument("--mail-stdout", action="store_true", help="Mail-Ausgabe nach stdout senden.")
    return parser.parse_args()


def setup_watchers(supervisor, process_env, root_path, demo):
    start_css_watch(supervisor)
    file_watcher = FileWatcher()
    yaml_path, yaml_dir = resolve_yaml_inputs(process_env, root_path)
    schedule_yaml(
        file_watcher, process_env, root_path, demo, yaml_path, yaml_dir, run_cv_build
    )
    schedule_twig(file_watcher, process_env, root_path, demo, run_cv_build)
    file_watcher.start()
    return file_watcher


def start_php_server(process_env, root_path, supervisor):
    cmd = ["php", "-S", "127.0.0.1:8080", "-t", "public"]
    return supervisor.start("php-server", cmd, env=process_env, cwd=root_path)


def start_css_watch(supervisor):
    for index, cmd in enumerate(css.COMMANDS, start=1):
        print("CSS-Watch gestartet:", " ".join(cmd), flush=True)
        supervisor.start(f"css-{index}", cmd)


def resolve_yaml_inputs(process_env, root_path):
    yaml_path = get_config_value("LEBENSLAUF_YAML_PFAD", process_env, root_path)
    yaml_dir = get_config_value("LEBENSLAUF_DATEN_PFAD", process_env, root_path)
    return resolve_path(root_path, yaml_path), resolve_path(root_path, yaml_dir)


def get_config_value(key, process_env, root_path):
    cmd = ["php", "bin/cli", "env", "get", key]
    profile = process_env.get("APP_ENV")
    if profile:
        cmd.extend(["--profile", profile])
    result = subprocess.run(cmd, capture_output=True, text=True, env=process_env, cwd=root_path)
    if result.returncode != 0:
        return ""
    return result.stdout.strip()


def resolve_path(root_path, value):
    if not value:
        return value
    if os.path.isabs(value):
        return value
    return os.path.join(root_path, value)


def build_runtime_env(args):
    process_env = dict(os.environ)
    if args.mail_stdout:
        process_env["MAIL_STDOUT"] = "1"
    return process_env


def ensure_initial_build(args, process_env, root_path):
    if args.build:
        run_cv_build(process_env, root_path, args.demo)


def run_cv_build(process_env, root_path, demo=False):
    build_env = dict(process_env)
    if demo:
        build_env.update(demo_env(root_path))
    run_checked(["php", "bin/cli", "cv", "build"], build_env, root_path)


def run_checked(cmd, process_env, root_path):
    result = subprocess.run(cmd, env=process_env, cwd=root_path)
    if result.returncode != 0:
        sys.exit(result.returncode)


def demo_env(root_path):
    return {
        "CONTENT_INI_PATH": os.path.join(root_path, "tests", "fixtures", "content.ini"),
        "LEBENSLAUF_YAML_PFAD": os.path.join(
            root_path, "tests", "fixtures", "lebenslauf", "daten-gueltig.yaml"
        ),
        "LEBENSLAUF_DATEN_PFAD": os.path.join(root_path, "tests", "fixtures", "lebenslauf"),
    }


def resolve_root_path():
    return os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", ".."))


if __name__ == "__main__":
    main()
