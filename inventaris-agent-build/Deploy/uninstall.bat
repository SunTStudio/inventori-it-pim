@echo off
setlocal
pushd "%~dp0"
title Uninstall Inventaris Agent

net session >nul 2>&1
if %errorlevel% neq 0 goto elevate
goto main

:elevate
echo Meminta hak Administrator. Klik "Yes" pada jendela yang muncul...
powershell -NoProfile -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
exit /b

:main
set "APP=C:\ProgramData\InventarisAgent"
set "EXE=InventarisAgent.exe"
set "TASK=InventarisAgent"

echo Menghapus Inventaris Agent dari PC ini...
schtasks /End /TN "%TASK%" >nul 2>&1
schtasks /Delete /TN "%TASK%" /F >nul 2>&1
taskkill /F /IM %EXE% >nul 2>&1
timeout /t 2 /nobreak >nul
powershell -NoProfile -Command "Remove-MpPreference -ExclusionPath '%APP%'" >nul 2>&1
if exist "%APP%" rmdir /s /q "%APP%"

echo.
echo SELESAI. Agent sudah dihapus.
echo (Data PC ini masih ada di dashboard Laravel sampai dihapus dari sana.)
echo.
pause
exit /b 0
