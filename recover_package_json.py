#!/usr/bin/env python3
import subprocess
import sys

try:
    result = subprocess.run(
        ['git', '-C', 'd:\\insurance2026', 'show', 'HEAD:package.json'],
        capture_output=True,
        text=True,
        check=True
    )
    print(result.stdout)
except subprocess.CalledProcessError as e:
    print(f"Error: {e.stderr}", file=sys.stderr)
    sys.exit(1)
