import os
import time

from watchdog.events import PatternMatchingEventHandler

class ThrottledRunner:
    def __init__(self, interval, action):
        self._interval = interval
        self._action = action
        self._last = 0.0

    def __call__(self):
        now = time.monotonic()
        if now - self._last < self._interval:
            return
        self._last = now
        self._action()


class FileWatcher:
    def __init__(self):
        self._observer = None

    def start(self):
        self._ensure_observer()
        self._observer.start()

    def stop(self):
        if not self._observer:
            return
        self._observer.stop()

    def join(self, timeout=5):
        if not self._observer:
            return
        self._observer.join(timeout=timeout)

    def schedule_watch(self, watch_path, patterns, action, recursive=False):
        if not watch_path or not os.path.exists(watch_path):
            return
        self._ensure_observer()
        handler = PatternMatchingEventHandler(patterns=patterns, ignore_directories=True)
        handler.on_any_event = ThrottledRunner(0.5, action)
        self._observer.schedule(handler, watch_path, recursive=recursive)

    def _ensure_observer(self):
        if self._observer is None:
            from watchdog.observers import Observer as WatchdogObserver

            self._observer = WatchdogObserver()
