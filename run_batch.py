#!/usr/bin/env python3
import subprocess
import sys

result = subprocess.run(['d:\\insurance2026\\build.bat'], cwd='d:\\insurance2026')
sys.exit(result.returncode)
