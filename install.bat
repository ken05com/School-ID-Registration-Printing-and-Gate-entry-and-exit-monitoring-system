@echo off
REM ============================================================
REM School ID System - Windows Installer
REM One-command setup: detects MySQL, creates the database,
REM writes .env, and runs migrations.
REM
REM Usage:
REM   install.bat
REM   install.bat --host=127.0.0.1 --port=3306 --user=root --pass= --yes
REM ============================================================
setlocal enabledelayedexpansion
cd /d "%~dp0"

REM --- Locate PHP -------------------------------------------------------
set "PHP="
if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"
if not defined PHP set "PHP=php"   REM try the PATH

where "%PHP%" >nul 2>nul
if errorlevel 1 if not exist "%PHP%" (
  echo [ERROR] PHP not found.
  echo   Add PHP to your PATH, or run this from XAMPP, e.g.:
  echo   "C:\xampp\php\php.exe" install.php
  pause
  exit /b 1
)

echo Using PHP: %PHP%

REM --- Run the installer ------------------------------------------------
"%PHP%" install.php %*
if errorlevel 1 (
  echo.
  echo [ERROR] Setup failed. See the messages above.
  pause
  exit /b 1
)

echo.
echo Setup complete. If your XAMPP MySQL has a password, set it in .env.
pause