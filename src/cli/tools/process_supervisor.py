import signal
import subprocess
import time


class ProcessSupervisor:
    def __init__(self):
        self._processes = {}
        self._shutdown_requested = False

    def start(self, name, cmd, env=None, cwd=None):
        self._ensure_free(name)
        process = subprocess.Popen(cmd, env=env, cwd=cwd)
        self._processes[name] = process
        return process

    def add(self, name, process):
        self._ensure_free(name)
        self._processes[name] = process

    def stop(self, name):
        process = self._get(name)
        self._stop_process(process)
        self._wait_for([process])

    def shutdown(self):
        processes = list(self._processes.values())
        self._stop_processes(processes)
        self._wait_for(processes)

    def status(self, name):
        process = self._get(name)
        return process.poll()

    def status_all(self):
        return {name: process.poll() for name, process in self._processes.items()}

    def install_signal_handlers(self):
        def handle(_signum, _frame):
            self._shutdown_requested = True

        signal.signal(signal.SIGINT, handle)
        signal.signal(signal.SIGTERM, handle)

    def run(self, observer=None):
        try:
            while True:
                exit_code = self._check_processes()
                if exit_code is not None:
                    self._stop_observer(observer)
                    self.shutdown()
                    return exit_code
                if self._shutdown_requested:
                    self._stop_observer(observer)
                    self.shutdown()
                    return 0
                time.sleep(0.2)
        except KeyboardInterrupt:
            self._stop_observer(observer)
            self.shutdown()
            return 0

    def _ensure_free(self, name):
        if name in self._processes and self._processes[name].poll() is None:
            raise RuntimeError(f"Prozess läuft bereits: {name}")

    def _get(self, name):
        if name not in self._processes:
            raise KeyError(f"Unbekannter Prozess: {name}")
        return self._processes[name]

    def _check_processes(self):
        for process in self._processes.values():
            exit_code = process.poll()
            if exit_code is not None:
                return exit_code
        return None

    def _stop_observer(self, observer):
        if not observer:
            return
        observer.stop()
        observer.join(timeout=5)

    def _stop_process(self, process):
        if process.poll() is None:
            process.terminate()

    def _stop_processes(self, processes):
        for process in processes:
            self._stop_process(process)

    def _wait_for(self, processes):
        for process in processes:
            if process.poll() is None:
                try:
                    process.wait(timeout=5)
                except subprocess.TimeoutExpired:
                    process.kill()
