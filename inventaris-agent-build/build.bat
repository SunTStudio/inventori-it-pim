@echo off
setlocal
pushd "%~dp0"
title Build Inventaris Agent

echo ==========================================
echo   BUILD INVENTARIS AGENT
echo   (jalankan di 1 PC yang ada Python-nya)
echo ==========================================
echo.

set PY=
where py >nul 2>&1 && set PY=py
if not defined PY where python >nul 2>&1 && set PY=python
if not defined PY goto nopython
if not exist agent.py goto noagent
if not exist install.bat goto nofiles
if not exist uninstall.bat goto nofiles

echo [1/3] Memasang modul build (psutil, requests, pyinstaller)...
%PY% -m pip install --quiet --upgrade psutil requests pyinstaller
if errorlevel 1 goto fail

echo [2/3] Membuat file exe (tunggu 1-2 menit)...
%PY% -m PyInstaller --onefile --noconsole --clean --noconfirm --name InventarisAgent agent.py
if errorlevel 1 goto fail

echo [3/3] Menyiapkan folder Deploy...
if not exist Deploy mkdir Deploy
copy /y dist\InventarisAgent.exe Deploy\ >nul
copy /y install.bat Deploy\ >nul
copy /y uninstall.bat Deploy\ >nul

if exist Deploy\config.ini goto done
echo.
echo Isi sekali saja, tersimpan di Deploy\config.ini.
echo Tekan Enter langsung jika ingin memakai nilai default.
echo.
set "SRV="
set /p "SRV=Alamat server Laravel [default: 192.168.10.88:8000]: "
if not defined SRV set "SRV=192.168.10.88:8000"

set "KEY="
set /p "KEY=API key (AGENT_API_KEY) [default token proyek ini]: "
if not defined KEY set "KEY=52dd6df51a1a9c66d15dd26f812b6a67412ac2079ae4bd79"

> Deploy\config.ini echo [agent]
>> Deploy\config.ini echo server=%SRV%
>> Deploy\config.ini echo api_key=%KEY%
>> Deploy\config.ini echo poll=30

:done
rmdir /s /q build >nul 2>&1
del /q InventarisAgent.spec >nul 2>&1
echo.
echo ==========================================
echo   SELESAI
echo   Folder "Deploy" siap dipakai. Salin folder itu
echo   ke flashdisk / PC client, lalu klik 2x install.bat
echo ==========================================
echo   Ganti alamat server nanti: edit Deploy\config.ini
echo   lalu jalankan ulang install.bat di PC client.
echo.
explorer Deploy
pause
exit /b 0

:nopython
echo Python tidak ditemukan di PC ini.
echo Install Python 3.10+ dari python.org dan CENTANG "Add python.exe to PATH".
echo Hanya PC untuk build yang butuh Python, PC client tidak.
pause
exit /b 1

:noagent
echo File agent.py tidak ada di folder ini.
pause
exit /b 1

:nofiles
echo File install.bat / uninstall.bat harus ada di folder yang sama dengan build.bat.
pause
exit /b 1

:fail
echo.
echo GAGAL. Lihat pesan error di atas.
pause
exit /b 1
