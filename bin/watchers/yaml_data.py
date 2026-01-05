import os


def _list_yaml_files(watch_file, watch_dir):
    if watch_file:
        return [watch_file]
    if not watch_dir or not os.path.isdir(watch_dir):
        return []
    files = []
    for name in os.listdir(watch_dir):
        if name.endswith(".yaml") or name.endswith(".yml"):
            files.append(os.path.join(watch_dir, name))
    return files


def files_fn(watch_file, watch_dir):
    return lambda: _list_yaml_files(watch_file, watch_dir)
