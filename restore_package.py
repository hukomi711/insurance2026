#!/usr/bin/env python3
import subprocess
import os

os.chdir('d:\\insurance2026')

# Get content from git
result = subprocess.run(
    ['git', 'show', 'HEAD:package.json'],
    capture_output=True,
    text=True
)

if result.returncode == 0:
    # Write to file
    with open('package.json', 'w') as f:
        f.write(result.stdout)
    print("✓ package.json restored successfully")
    print(f"Size: {len(result.stdout)} bytes")
else:
    print(f"ERROR: {result.stderr}")
    exit(1)
