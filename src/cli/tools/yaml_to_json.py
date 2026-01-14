#!/usr/bin/env .venv/bin/python3
import json
import sys


def require_yaml():
    try:
        import yaml  # type: ignore
    except ImportError:
        print("PyYAML fehlt. Installiere mit: pip install pyyaml", file=sys.stderr)
        sys.exit(1)
    return yaml


def parse_args():
    if len(sys.argv) < 3:
        print("Usage: yaml_to_json.py <input.yaml> <output.json>")
        sys.exit(1)
    return sys.argv[1], sys.argv[2]


def read_yaml(yaml_module, input_path):
    with open(input_path, "r", encoding="utf-8") as handle:
        return yaml_module.safe_load(handle)


def write_json(output_path, data):
    with open(output_path, "w", encoding="utf-8") as handle:
        json.dump(data, handle, ensure_ascii=False, indent=2)


def main():
    yaml_module = require_yaml()
    input_path, output_path = parse_args()
    data = read_yaml(yaml_module, input_path)
    write_json(output_path, data)


if __name__ == "__main__":
    main()
