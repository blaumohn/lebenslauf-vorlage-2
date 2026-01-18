#!/usr/bin/env .venv/bin/python3
import argparse
import os
import signal
import subprocess
import sys
import time

from watchers import css, manager, twig, yaml_data


def parse_args():
    parser = argparse.ArgumentParser(description="Run dev server with watchers.")
    parser.add_argument("--build", action="store_true", help="Run cv build before starting dev.")
    parser.add_argument("--mail-stdout", action="store_true", help="Send mail output to stdout.")
    return parser.parse_args()


def run_checked(cmd, process_env, root_path):
    result = subprocess.run(cmd, env=process_env, cwd=root_path)
    if result.returncode != 0:
        sys.exit(result.returncode)


def run_cv_build(process_env, root_path):
    profile = require_profile(process_env)
    run_checked(["php", "bin/cli", "cv", "build", profile], process_env, root_path)


def get_config_value(key, process_env, root_path):
    cmd = ["php", "bin/cli", "env", "get", key]
    profile = process_env.get("APP_ENV")
    if profile:
        cmd.extend(["--profile", profile])
    result = subprocess.run(cmd, capture_output=True, text=True, env=process_env, cwd=root_path)
    if result.returncode != 0:
        return ""
    return result.stdout.strip()


def start_php_server(process_env, root_path):
    return subprocess.Popen(
        ["php", "-S", "127.0.0.1:8080", "-t", "public"],
        env=process_env,
        cwd=root_path,
    )


def terminate_processes(processes, exit_code):
    for proc in processes:
        if proc.poll() is None:
            proc.terminate()
    for proc in processes:
        if proc.poll() is None:
            try:
                proc.wait(timeout=5)
            except subprocess.TimeoutExpired:
                proc.kill()
    sys.exit(exit_code)


def resolve_yaml_inputs(process_env, root_path):
    yaml_path = get_config_value("LEBENSLAUF_YAML_PFAD", process_env, root_path)
    yaml_dir = get_config_value("LEBENSLAUF_DATEN_PFAD", process_env, root_path)
    return resolve_path(root_path, yaml_path), resolve_path(root_path, yaml_dir)


def resolve_path(root_path, value):
    if not value:
        return value
    if os.path.isabs(value):
        return value
    return os.path.join(root_path, value)


def register_yaml_watch(watch_manager, process_env, root_path):
    yaml_path, yaml_dir = resolve_yaml_inputs(process_env, root_path)
    if not (yaml_path or yaml_dir):
        print("yaml watch disabled (set LEBENSLAUF_DATEN_PFAD or LEBENSLAUF_YAML_PFAD).", flush=True)
        return

    watched = yaml_data.files_fn(yaml_path, yaml_dir)()
    print(f"yaml watch enabled: {len(watched)} file(s)", flush=True)
    watch_manager.register(
        "yaml",
        yaml_data.files_fn(yaml_path, yaml_dir),
        lambda: run_cv_build(process_env, root_path),
    )


def register_twig_watch(watch_manager, process_env, root_path):
    if not twig.enabled():
        print("twig watch disabled (missing templates directory).", flush=True)
        return

    watched = twig.files_fn()()
    print(f"twig watch enabled: {len(watched)} file(s)", flush=True)
    watch_manager.register(
        "twig",
        twig.files_fn(),
        lambda: run_cv_build(process_env, root_path),
    )


def register_css_watch(watch_manager):
    watched = css.files_fn()()
    print(f"css watch enabled: {len(watched)} file(s)", flush=True)
    watch_manager.register(
        "css",
        css.files_fn(),
        lambda: print("css change detected; postcss watch rebuild triggered.", flush=True),
    )


def build_runtime_env(args):
    process_env = dict(os.environ)
    if args.mail_stdout:
        process_env["MAIL_STDOUT"] = "1"
    return process_env


def require_profile(process_env):
    profile = process_env.get("APP_ENV", "")
    if profile:
        return profile
    print("APP_ENV ist erforderlich (nutze: bin/cli run <profil>).", file=sys.stderr)
    sys.exit(1)


def ensure_initial_build(args, process_env, root_path):
    if args.build:
        run_cv_build(process_env, root_path)


def register_watchers(watch_manager, process_env, root_path):
    register_yaml_watch(watch_manager, process_env, root_path)
    register_twig_watch(watch_manager, process_env, root_path)
    register_css_watch(watch_manager)


def run_event_loop(processes, watch_manager):
    try:
        while True:
            for proc in processes:
                exit_code = proc.poll()
                if exit_code is not None:
                    terminate_processes(processes, exit_code)
            watch_manager.poll()
            time.sleep(0.2)
    except KeyboardInterrupt:
        terminate_processes(processes, 0)


def resolve_root_path():
    return os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", ".."))


def setup_processes(process_env, root_path):
    processes = []
    processes.extend(css.start())
    processes.append(start_php_server(process_env, root_path))
    return processes


def register_signal_handlers(processes):
    def handle_signal(_signum, _frame):
        terminate_processes(processes, 0)

    signal.signal(signal.SIGINT, handle_signal)
    signal.signal(signal.SIGTERM, handle_signal)


def main():
    args = parse_args()
    root_path = resolve_root_path()
    os.chdir(root_path)

    process_env = build_runtime_env(args)
    ensure_initial_build(args, process_env, root_path)

    watch_manager = manager.WatchManager()
    processes = setup_processes(process_env, root_path)
    register_signal_handlers(processes)

    register_watchers(watch_manager, process_env, root_path)
    run_event_loop(processes, watch_manager)


if __name__ == "__main__":
    main()
