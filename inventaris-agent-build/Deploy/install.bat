@echo off
setlocal
pushd "%~dp0"
title Install Inventaris Agent

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

echo ==========================================
echo   INSTALL INVENTARIS AGENT
echo ==========================================
echo.

if not exist "%~dp0%EXE%" goto noexe
if not exist "%~dp0config.ini" goto nocfg

echo --------------------------------------------------
echo   IDENTITAS PERANGKAT PC KANTOR
echo   (Bisa langsung Enter jika ingin dikosongkan)
echo --------------------------------------------------
set "PC_ALIAS="
set "PC_DEPT="
set "PC_LOC="
set /p "PC_ALIAS=Nama Alias PC (misal: PC Kasir 1 / PC Finance Budi): "
set /p "PC_DEPT=Divisi / Departemen (misal: Finance / HRD / Gudang) : "
set /p "PC_LOC=Lokasi / Ruangan    (misal: Lantai 1 Meja Kasir)    : "
echo --------------------------------------------------
echo.

echo [1/4] Menghentikan agent lama (jika ada)...
schtasks /End /TN "%TASK%" >nul 2>&1
taskkill /F /IM %EXE% >nul 2>&1
timeout /t 2 /nobreak >nul

echo [2/4] Menyalin file ke %APP% ...
if not exist "%APP%" mkdir "%APP%"
copy /y "%~dp0%EXE%" "%APP%\" >nul
if errorlevel 1 goto fail
copy /y "%~dp0config.ini" "%APP%\" >nul
if errorlevel 1 goto fail

if defined PC_ALIAS (
    >> "%APP%\config.ini" echo alias=%PC_ALIAS%
)
if defined PC_DEPT (
    >> "%APP%\config.ini" echo department=%PC_DEPT%
)
if defined PC_LOC (
    >> "%APP%\config.ini" echo location=%PC_LOC%
)

rem Kecualikan folder agent dari Windows Defender (hasil PyInstaller sering salah dideteksi)
powershell -NoProfile -Command "Add-MpPreference -ExclusionPath '%APP%'" >nul 2>&1

echo [3/4] Mendaftarkan agar jalan otomatis saat PC menyala...
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
 "$a = New-ScheduledTaskAction -Execute '%APP%\%EXE%';" ^
 "$t = New-ScheduledTaskTrigger -AtStartup;" ^
 "$s = New-ScheduledTaskSettingsSet -ExecutionTimeLimit ([TimeSpan]::Zero) -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RestartCount 999 -RestartInterval (New-TimeSpan -Minutes 1);" ^
 "$p = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest;" ^
 "Register-ScheduledTask -TaskName '%TASK%' -Action $a -Trigger $t -Settings $s -Principal $p -Force | Out-Null"
if errorlevel 1 goto fail

echo [4/4] Menjalankan agent...
schtasks /Run /TN "%TASK%" >nul 2>&1
timeout /t 5 /nobreak >nul

tasklist /FI "IMAGENAME eq %EXE%" | find /i "%EXE%" >nul
if errorlevel 1 goto notrunning

echo.
echo ==========================================
echo   BERHASIL. Agent sudah berjalan di belakang layar.
echo   Cek dashboard Laravel, PC ini harus muncul Online.
echo   Log: %APP%\agent.log
echo ==========================================
echo.
pause
exit /b 0

:noexe
echo File %EXE% tidak ditemukan di folder ini.
echo Jalankan install.bat dari folder "Deploy" hasil build.bat.
pause
exit /b 1

:nocfg
echo File config.ini tidak ditemukan di folder ini.
echo Jalankan install.bat dari folder "Deploy" hasil build.bat.
pause
exit /b 1

:notrunning
echo.
echo Terpasang, tetapi agent belum terlihat berjalan.
echo Kemungkinan diblokir antivirus. Cek %APP%\agent.log
echo atau coba restart PC ini.
pause
exit /b 1

:fail
echo.
echo GAGAL. Lihat pesan error di atas.
pause
exit /b 1
