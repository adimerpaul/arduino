#define LDRPIN 2

void setup() {
  Serial.begin(9600);
  delay(1000);
}

void loop() {
  int val = analogRead(LDRPIN);
  Serial.println("Value: ");
  Serial.println(val);
  delay(500);
}