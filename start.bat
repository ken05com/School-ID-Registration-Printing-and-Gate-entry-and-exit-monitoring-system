@echo off
REM ============================================================
REM School ID System - Windows Launcher
REM Starts XAMPP MySQL (if not already running) and serves the app
REM with the PHP built-in server at http://localhost:8000
REM
REM Usage:
REM   start.bat
REM ============================================================
setlocal enabledelayedexpansion
cd /d "%~dp0"

REM --- Locate PHP -------------------------------------------------------
set "PHP="
if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"
if not defined PHP set "PHP=php"

where "%PHP%" >nul 2>nul
if errorlevel 1 if not exist "%PHP%" (
  echo [ERROR] PHP not found. Add PHP to your PATH or use XAMPP.
  pause
  exit /b 1
)

REM --- Start MySQL if XAMPP is installed but mysqld not running --------
if exist "C:\xampp\mysql\bin\mysqld.exe" (
  "%PHP%" -r "exit(@fsockopen('127.0.0.1',3306,$e,$e2)?0:1);" >nul 2>nul
  if errorlevel 1 (
    echo Starting XAMPP MySQL ...
    start "" "C:\xampp\mysql\bin\mysqld.exe"
    REM give it a moment to come up
    timeout /t 4 /nobreak >nul
  )
)

REM --- Serve the app ----------------------------------------------------
echo.
echo Serving the School ID System at http://localhost:8000
echo Press Ctrl+C to stop.
"%PHP%" -S 0.0.0.0:8000 -t "%~dp0public"
pause