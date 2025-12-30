#!/usr/bin/env .venv/bin/python3
import json
import sys

try:
    import yaml
except ImportError:
    print("PyYAML missing. Install with: pip install pyyaml", file=sys.stderr)
    sys.exit(1)

def main():
    if len(sys.argv) < 3:
        print("Usage: yaml_to_json.py <input.yaml> <output.json>")
        sys.exit(1)

    input_path = sys.argv[1]
    output_path = sys.argv[2]

    with open(input_path, "r", encoding="utf-8") as f:
        data = yaml.safe_load(f)

    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

if __name__ == "__main__":
    main()
