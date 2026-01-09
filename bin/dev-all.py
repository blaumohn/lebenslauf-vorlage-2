#!/usr/bin/env .venv/bin/python3
import signal
import subprocess
import sys
import time

from watchers import css, manager, twig, yaml_data

php_args = sys.argv[1:]
processes = []


def terminate_processes(exit_code):
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


def handle_signal(_signum, _frame):
    terminate_processes(0)


def run_build(reason):
    print(f"{reason} change detected; running cv:build...", flush=True)
    subprocess.run(["composer", "run", "cv:build"])
    print(f"{reason} build done.", flush=True)


def get_env_value(key):
    result = subprocess.run(
        ["php", "bin/console", "env:get", key],
        capture_output=True,
        text=True,
    )
    if result.returncode != 0:
        return ""
    return result.stdout.strip()


signal.signal(signal.SIGINT, handle_signal)
signal.signal(signal.SIGTERM, handle_signal)

watch_manager = manager.WatchManager()

processes.extend(css.start())
processes.append(subprocess.Popen(["php", "bin/dev", *php_args]))

yaml_path = get_env_value("LEBENSLAUF_YAML_PFAD")
yaml_dir = get_env_value("LEBENSLAUF_DATEN_PFAD")
if yaml_path or yaml_dir:
    watched = yaml_data.files_fn(yaml_path, yaml_dir)()
    print(f"yaml watch enabled: {len(watched)} file(s)", flush=True)
    watch_manager.register("yaml", yaml_data.files_fn(yaml_path, yaml_dir), lambda: run_build("yaml"))
else:
    print("yaml watch disabled (set LEBENSLAUF_DATEN_PFAD or LEBENSLAUF_YAML_PFAD).", flush=True)

if twig.enabled():
    watched = twig.files_fn()()
    print(f"twig watch enabled: {len(watched)} file(s)", flush=True)
    watch_manager.register("twig", twig.files_fn(), lambda: run_build("twig"))
else:
    print("twig watch disabled (missing templates directory).", flush=True)

watched = css.files_fn()()
print(f"css watch enabled: {len(watched)} file(s)", flush=True)
watch_manager.register("css", css.files_fn(), lambda: print("css change detected; postcss watch rebuild triggered.", flush=True))

try:
    while True:
        for proc in processes:
            exit_code = proc.poll()
            if exit_code is not None:
                terminate_processes(exit_code)

        watch_manager.poll()
        time.sleep(0.2)
except KeyboardInterrupt:
    terminate_processes(0)
