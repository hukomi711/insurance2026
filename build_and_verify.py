#!/usr/bin/env python3
import subprocess
import os
import sys
import shutil

os.chdir('d:\\insurance2026')

print("=" * 60)
print("STEP 1: Verify package.json exists...")
print("=" * 60)
if os.path.exists('package.json'):
    size = os.path.getsize('package.json')
    print(f"✓ package.json exists ({size} bytes)")
else:
    print("✗ package.json NOT FOUND")
    sys.exit(1)

print("\n" + "=" * 60)
print("STEP 2: Check npm executable...")
print("=" * 60)
npm_path = shutil.which('npm')
if npm_path:
    print(f"✓ npm found at: {npm_path}")
else:
    print("✗ npm not found in PATH")

print("\n" + "=" * 60)
print("STEP 3: Get npm version...")
print("=" * 60)
result = subprocess.run(['npm', '--version'], capture_output=True, text=True)
print(f"npm version: {result.stdout.strip()}")

print("\n" + "=" * 60)
print("STEP 4: Running npm run build...")
print("=" * 60)
result = subprocess.run(['npm', 'run', 'build'], cwd='d:\\insurance2026', capture_output=False, text=True, timeout=600)

print(f"\n{'=' * 60}")
print(f"BUILD COMPLETED WITH EXIT CODE: {result.returncode}")
print(f"{'=' * 60}")
sys.exit(result.returncode)
