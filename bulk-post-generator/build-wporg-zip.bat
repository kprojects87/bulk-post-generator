@echo off
REM ============================================================
REM Builds a clean plugin zip for submission to WordPress.org.
REM Excludes GitHub/dev-only files (.git, .gitignore, README.md,
REM this script itself, etc.) that WordPress.org's Plugin Check
REM does not allow.
REM
REM Usage: just double-click this file, or run it from cmd.exe
REM        while inside the plugin folder.
REM ============================================================

setlocal
set "SRC=%~dp0"
set "PLUGIN_NAME=bulk-post-generator"
set "DEST=%TEMP%\%PLUGIN_NAME%-build"
set "ZIPFILE=%~dp0%PLUGIN_NAME%-wporg.zip"

echo Building clean WordPress.org package...
echo.

if exist "%DEST%" rmdir /s /q "%DEST%"
mkdir "%DEST%\%PLUGIN_NAME%"

REM Copy everything except dev/GitHub-only files and folders.
robocopy "%SRC%." "%DEST%\%PLUGIN_NAME%" /E ^
	/XD ".git" ".github" "node_modules" ^
	/XF ".gitignore" ".distignore" "README.md" "*.zip" "build-wporg-zip.bat"

if exist "%ZIPFILE%" del "%ZIPFILE%"

powershell -NoProfile -Command "Compress-Archive -Path '%DEST%\%PLUGIN_NAME%' -DestinationPath '%ZIPFILE%' -Force"

echo.
echo Done. Clean submission zip created at:
echo %ZIPFILE%
echo.
echo Upload this file at https://wordpress.org/plugins/developers/add/
pause
