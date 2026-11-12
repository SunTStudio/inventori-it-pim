@echo off
title Build Agent Executable (.exe)
echo ========================================================
echo   Membuat agent.exe menggunakan PyInstaller...
echo ========================================================
echo.
python -m PyInstaller --onefile --noconsole agent.py
echo.
echo ========================================================
echo   Selesai! File executable tersimpan di: dist\agent.exe
echo ========================================================
pause

