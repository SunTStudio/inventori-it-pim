@echo off
:: Script untuk mendaftarkan URL Protocol radmin:// di Windows Registry
:: Jalankan sekali sebagai Administrator di komputer tim IT / Admin
echo =========================================================
echo   PENDAFTARAN PROTOKOL URL radmin:// UNTUK RADMIN VIEWER
echo =========================================================
echo.

net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [!] PERINGATAN: File ini harus dijalankan sebagai Administrator!
    echo     Klik kanan file ini -^> "Run as administrator".
    echo.
    pause
    exit /b
)

set "RADMIN_PATH="
if exist "C:\Program Files (x86)\Radmin VPN\Radmin.exe" (
    set "RADMIN_PATH=C:\Program Files (x86)\Radmin VPN\Radmin.exe"
) else if exist "C:\Program Files (x86)\Radmin Viewer 3\Radmin.exe" (
    set "RADMIN_PATH=C:\Program Files (x86)\Radmin Viewer 3\Radmin.exe"
) else if exist "C:\Program Files\Radmin Viewer 3\Radmin.exe" (
    set "RADMIN_PATH=C:\Program Files\Radmin Viewer 3\Radmin.exe"
)

if "%RADMIN_PATH%"=="" (
    echo [X] Radmin Viewer tidak ditemukan di Program Files.
    echo     Pastikan Radmin VPN atau Radmin Viewer 3 sudah terpasang.
    pause
    exit /b
)

echo [*] Ditemukan Radmin Viewer di:
echo     "%RADMIN_PATH%"
echo.

:: Buat script perantara di folder Windows agar tidak terbentur escaping shell
(
    echo @echo off
    echo set "URL=%%~1"
    echo set "IP=%%URL:radmin://=%%"
    echo set "IP=%%IP:/=%%"
    echo set "IP=%%IP: =%%"
    echo if not "%%IP%%"=="" (
    echo     start "" "%RADMIN_PATH%" /connect:%%IP%%
    echo ^)
) > "%WINDIR%\radmin-open.bat"

reg add "HKCR\radmin" /ve /d "URL:Radmin Protocol" /f >nul
reg add "HKCR\radmin" /v "URL Protocol" /d "" /f >nul
reg add "HKCR\radmin\shell\open\command" /ve /d "\"%WINDIR%\radmin-open.bat\" \"%%1\"" /f >nul

if %errorlevel% equ 0 (
    echo [+] SUKSES! Protokol radmin:// berhasil diperbaiki dan didaftarkan di Windows.
    echo     Script perantara terpasang di: %WINDIR%\radmin-open.bat
    echo     Sekarang tombol 'Buka Browser Protocol (radmin://)' di web akan langsung
    echo     membuka Radmin dan otomatis menghubungkan ke IP target dengan benar.
) else (
    echo [X] Gagal mendaftarkan ke registry.
)

echo.
pause

