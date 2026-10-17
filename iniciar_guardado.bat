@echo off
title Guardando datos de sensores (no cerrar)
echo Cierra el Monitor Serie del Arduino IDE antes de continuar.
echo Panel: http://localhost/arduino/
echo.
cd /d "%~dp0sensores_general"
py guardar_datos.py COM3
pause
