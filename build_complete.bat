@echo off
setlocal enabledelayedexpansion

cd /d d:\insurance2026

echo [1/3] Verifying configuration files...
if not exist package.json (
    echo ERROR: package.json not found
    exit /b 1
)
if not exist vite.config.js (
    echo ERROR: vite.config.js not found
    exit /b 1
)
if not exist tailwind.config.js (
    echo ERROR: tailwind.config.js not found
    exit /b 1
)
echo [✓] Configuration files OK

echo.
echo [2/3] Checking npm is accessible...
where npm >nul 2>&1
if !errorlevel! neq 0 (
    echo ERROR: npm not found in PATH
    exit /b 1
)
npm --version
echo [✓] npm is accessible

echo.
echo [3/3] Running build...
echo.
call npm run build

if !errorlevel! equ 0 (
    echo.
    echo [✓] BUILD SUCCESSFUL!
    exit /b 0
) else (
    echo.
    echo [✗] BUILD FAILED with exit code !errorlevel!
    exit /b !errorlevel!
)
