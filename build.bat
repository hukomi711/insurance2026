@echo off
cd /d d:\insurance2026
echo Checking package.json exists...
if not exist package.json (
    echo ERROR: package.json not found!
    exit /b 1
)

echo Checking npm...
where npm
if errorlevel 1 (
    echo ERROR: npm not found in PATH!
    exit /b 1
)

echo Running npm run build...
npm run build
exit /b %errorlevel%
