import time

from watchers import common


class WatchManager:
    def __init__(self, poll_interval=0.2):
        self._poll_interval = poll_interval
        self._watchers = []

    def register(self, name, files_fn, on_change):
        watcher = common.FileWatcher(files_fn)
        self._watchers.append((name, watcher, on_change))

    def poll(self):
        for _name, watcher, on_change in self._watchers:
            if watcher.poll():
                on_change()

    def run(self):
        while True:
            self.poll()
            time.sleep(self._poll_interval)
