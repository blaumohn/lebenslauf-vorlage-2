#!/usr/bin/env python3
import signal
import subprocess
import sys
import time

from watchers import css

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


signal.signal(signal.SIGINT, handle_signal)
signal.signal(signal.SIGTERM, handle_signal)

processes.extend(css.start())

try:
    while True:
        for proc in processes:
            exit_code = proc.poll()
            if exit_code is not None:
                terminate_processes(exit_code)
        time.sleep(0.2)
except KeyboardInterrupt:
    terminate_processes(0)
