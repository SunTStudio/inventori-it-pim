@echo off
title Pasang Agent Inventaris PC di Startup
echo ========================================================
echo   Pemasangan Agent Inventaris PC Kantor (Auto-Startup)
echo ========================================================
echo.

:: Buat direktori kerja di ProgramData
if not exist "C:\ProgramData\InventarisAgent" (
    mkdir "C:\ProgramData\InventarisAgent"
)

:: Salin agent.py jika ada di folder saat ini
if exist "%~dp0agent.py" (
    copy /Y "%~dp0agent.py" "C:\ProgramData\InventarisAgent\agent.py"
)

:: Daftarkan ke Task Scheduler agar jalan saat PC Boot / Startup tanpa jendela
echo Mendaftarkan Task Scheduler...
schtasks /Create /TN "InventarisAgent" /TR "python.exe \"C:\ProgramData\InventarisAgent\agent.py\"" /SC ONSTART /RU SYSTEM /RL HIGHEST /F

echo Menjalankan agent pertama kali...
schtasks /Run /TN "InventarisAgent"

echo.
echo ========================================================
echo   Sukses! Agent telah aktif dan akan otomatis
echo   mengirim spesifikasi setiap kali PC ini menyala.
echo ========================================================
pause

