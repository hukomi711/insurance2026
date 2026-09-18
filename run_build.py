import subprocess
import os

os.chdir('d:\\insurance2026')
result = subprocess.run(['npm', 'run', 'build'], capture_output=False)
exit(result.returncode)
