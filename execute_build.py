#!/usr/bin/env python3
"""
Execute build_complete.bat and display output
"""
import subprocess
import sys
import os

os.chdir('d:\\insurance2026')

print("=" * 70)
print("Building Insurance 2026 - Dark Mode Removal Verification")
print("=" * 70)
print()

try:
    # Run the batch file with output streaming
    result = subprocess.run(
        ['cmd.exe', '/c', 'build_complete.bat'],
        cwd='d:\\insurance2026',
        capture_output=False,
        text=True
    )

    sys.exit(result.returncode)

except Exception as e:
    print(f"ERROR: {e}")
    sys.exit(1)
