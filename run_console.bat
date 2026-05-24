@echo off
setlocal

set "PHP_EXE=H:\xampp\php\php.exe"

if not exist "%PHP_EXE%" (
    echo PHP executable not found at %PHP_EXE%
    echo Update PHP_EXE in run_console.bat or add php.exe to your PATH.
    exit /b 1
)

"%PHP_EXE%" "%~dp0console.php"
