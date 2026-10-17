"""
Lee los datos que envia el ESP32 por el puerto serie y los guarda en datos_sensores.txt
(una linea por segundo, con fecha y hora).

Requisito:  pip install pyserial
Uso:        python guardar_datos.py COM3
            (cambia COM3 por el puerto de tu ESP32; si no pones nada intenta detectarlo)
Detener:    Ctrl + C
"""

import sys
from datetime import datetime
from pathlib import Path

import serial
import serial.tools.list_ports

BAUDIOS = 9600
ARCHIVO = Path(__file__).with_name("datos_sensores.txt")
ENCABEZADO = "fecha_hora\ttemp_C\thumedad_%\tluz\tgas\taccelX_g\taccelY_g\taccelZ_g\tgyroX_deg\tgyroY_deg\tgyroZ_deg\n"


def elegir_puerto():
    if len(sys.argv) > 1:
        return sys.argv[1]
    puertos = list(serial.tools.list_ports.comports())
    if not puertos:
        sys.exit("No se encontro ningun puerto. Conecta el ESP32 o indica el puerto: python guardar_datos.py COM3")
    for p in puertos:
        print(f"  {p.device} - {p.description}")
    print(f"Usando {puertos[0].device}")
    return puertos[0].device


def main():
    puerto = elegir_puerto()
    nuevo = not ARCHIVO.exists()
    with serial.Serial(puerto, BAUDIOS, timeout=2) as esp, open(ARCHIVO, "a", encoding="utf-8") as f:
        if nuevo:
            f.write(ENCABEZADO)
        print(f"Guardando en {ARCHIVO}  (Ctrl+C para detener)")
        while True:
            linea = esp.readline().decode("utf-8", errors="ignore").strip()
            if not linea.startswith("DATOS,"):
                continue
            valores = linea.split(",")[1:]
            if len(valores) != 10:
                continue
            fecha = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
            f.write(fecha + "\t" + "\t".join(valores) + "\n")
            f.flush()
            print(fecha, " ".join(valores))


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\nDetenido.")
    except serial.SerialException as e:
        sys.exit(f"Error con el puerto serie: {e}\n(Cierra el Monitor Serie del Arduino IDE si esta abierto)")
