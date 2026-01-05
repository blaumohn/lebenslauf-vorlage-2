import os


def _list_twig_files():
    watch_dir = os.environ.get("TWIG_WATCH_DIR", "templates")
    if not os.path.isdir(watch_dir):
        return []
    files = []
    for root, _dirs, names in os.walk(watch_dir):
        for name in names:
            if name.endswith(".twig"):
                files.append(os.path.join(root, name))
    return files


def enabled():
    return os.path.isdir(os.environ.get("TWIG_WATCH_DIR", "templates"))


def files_fn():
    return _list_twig_files
