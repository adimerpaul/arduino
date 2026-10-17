/*
  Lectura de todos los sensores a la vez (cada 1 segundo)

  Board: ESP32 Development Board
  Sensores:
    - DHT11 (temperatura y humedad)  -> GPIO 23
    - LDR (luz)                      -> GPIO 2
    - Sensor de gas MQ (salida A0)   -> GPIO 34
    - MPU6050 (acelerometro/giro)    -> I2C: SDA 21, SCL 22
  Library: https://github.com/adafruit/DHT-sensor-library  (DHT sensor library by Adafruit)

  Cada segundo envia por Serial una linea con este formato:
    DATOS,temp_C,humedad_%,luz,gas,accelX_g,accelY_g,accelZ_g,gyroX_deg,gyroY_deg,gyroZ_deg
  El script guardar_datos.py lee esas lineas y las guarda en un archivo .txt
*/

#include <Wire.h>
#include <DHT.h>

#define DHTPIN 23      // Pin del DHT11
#define DHTTYPE DHT11  // Tipo de sensor
#define LDRPIN 2       // Pin del LDR
#define GASPIN 34      // Pin analogico del sensor de gas (cambialo si usas otro)
#define MPU_ADDR 0x68  // Direccion I2C del MPU6050

#define INTERVALO 1000  // Tiempo entre lecturas (ms)

DHT dht(DHTPIN, DHTTYPE);

float temperatura, humedad;
int luz, gas;
float gForceX, gForceY, gForceZ;
float rotX, rotY, rotZ;

unsigned long ultimaLectura = 0;

void setup() {
  Serial.begin(9600);
  Wire.begin();
  dht.begin();
  setupMPU();
  delay(1000);
  Serial.println(F("# Sensores listos"));
}

void loop() {
  if (millis() - ultimaLectura >= INTERVALO) {
    ultimaLectura = millis();

    leerDHT();
    luz = analogRead(LDRPIN);
    gas = analogRead(GASPIN);
    recordAccelRegisters();
    recordGyroRegisters();

    printData();
  }
}

void leerDHT() {
  // El DHT11 solo mide cada ~1-2 s; si falla se conserva el ultimo valor valido
  float h = dht.readHumidity();
  float t = dht.readTemperature();
  if (!isnan(h) && !isnan(t)) {
    humedad = h;
    temperatura = t;
  }
}

void setupMPU() {
  Wire.beginTransmission(MPU_ADDR);
  Wire.write(0x6B);  // Power Management: despertar el MPU
  Wire.write(0x00);
  Wire.endTransmission();
  Wire.beginTransmission(MPU_ADDR);
  Wire.write(0x1B);  // Giroscopio a +/- 250 deg/s
  Wire.write(0x00);
  Wire.endTransmission();
  Wire.beginTransmission(MPU_ADDR);
  Wire.write(0x1C);  // Acelerometro a +/- 2g
  Wire.write(0x00);
  Wire.endTransmission();
}

// Lee un valor de 16 bits con signo (byte alto primero)
int16_t leer16() {
  uint8_t alto = Wire.read();
  uint8_t bajo = Wire.read();
  return (int16_t)((alto << 8) | bajo);
}

void recordAccelRegisters() {
  Wire.beginTransmission(MPU_ADDR);
  Wire.write(0x3B);  // Registro inicial del acelerometro
  Wire.endTransmission();
  Wire.requestFrom(MPU_ADDR, 6);
  if (Wire.available() < 6) return;  // MPU no responde: no bloquear
  gForceX = leer16() / 16384.0;
  gForceY = leer16() / 16384.0;
  gForceZ = leer16() / 16384.0;
}

void recordGyroRegisters() {
  Wire.beginTransmission(MPU_ADDR);
  Wire.write(0x43);  // Registro inicial del giroscopio
  Wire.endTransmission();
  Wire.requestFrom(MPU_ADDR, 6);
  if (Wire.available() < 6) return;
  rotX = leer16() / 131.0;
  rotY = leer16() / 131.0;
  rotZ = leer16() / 131.0;
}

void printData() {
  Serial.print("DATOS,");
  Serial.print(temperatura); Serial.print(",");
  Serial.print(humedad);     Serial.print(",");
  Serial.print(luz);         Serial.print(",");
  Serial.print(gas);         Serial.print(",");
  Serial.print(gForceX);     Serial.print(",");
  Serial.print(gForceY);     Serial.print(",");
  Serial.print(gForceZ);     Serial.print(",");
  Serial.print(rotX);        Serial.print(",");
  Serial.print(rotY);        Serial.print(",");
  Serial.println(rotZ);
}
