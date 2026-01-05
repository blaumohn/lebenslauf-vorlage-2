import os


class FileWatcher:
    def __init__(self, files_fn):
        self._files_fn = files_fn
        self._last = self._snapshot()

    def _snapshot(self):
        state = {}
        for path in self._files_fn():
            try:
                state[path] = os.path.getmtime(path)
            except OSError:
                continue
        return state

    def poll(self):
        current = self._snapshot()
        if current != self._last:
            self._last = current
            return True
        return False
