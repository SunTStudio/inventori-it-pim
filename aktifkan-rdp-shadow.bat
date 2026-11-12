@echo off
title Aktifkan RDP Shadow (Layar Bersama Tanpa Kunci)
echo ========================================================
echo   AKTIFKAN RDP SHADOW (WINDOWS REMOTE DESKTOP BERSAMA)
echo ========================================================
echo.

net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [!] PERINGATAN: File ini harus dijalankan sebagai Administrator!
    echo     Klik kanan file ini -^> "Run as administrator".
    echo.
    pause
    exit /b
)

echo [*] Mengizinkan Remote Desktop...
reg add "HKLM\SYSTEM\CurrentControlSet\Control\Terminal Server" /v fDenyTSConnections /t REG_DWORD /d 0 /f >nul

echo [*] Mengaktifkan Shadow Policy (Layar bersama tanpa mengunci layar)...
reg add "HKLM\SOFTWARE\Policies\Microsoft\Windows NT\Terminal Services" /v Shadow /t REG_DWORD /d 2 /f >nul

echo [*] Membuka port RDP di Windows Firewall...
netsh advfirewall firewall set rule group="remote desktop" new enable=Yes >nul 2>&1

echo.
echo [+] SUKSES! RDP Shadow telah aktif di PC ini.
echo     Sekarang PC Admin dapat meremote PC ini via RDP Shadow
echo     tanpa mengunci layar fisik pengguna.
echo.
pause

