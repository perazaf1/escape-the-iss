#include <Arduino.h>

/**
 * Escape Game ISS — Salle de Stockage (G5E)
 * Lecture capteur de proximite Sharp GP2Y0A21YK0F
 * Envoi des donnees via serie (UART) vers le PC
 *
 * Carte : Tiva TM4C123GH6PM (EK-TM4C123GXL LaunchPad)
 *
 * Cablage :
 *   Jaune (Vo)  -> PE_3 (broche analogique)
 *   Noir  (GND) -> GND
 *   Rouge (Vcc) -> 5V (VBUS)
 */

#define CAPTEUR_PIN PE_3
#define LED_RED RED_LED
#define LED_GREEN GREEN_LED
#define LED_BLUE BLUE_LED

// Eteindre toutes les LEDs
void ledsOff() {
  analogWrite(LED_RED, 0);
  analogWrite(LED_GREEN, 0);
  analogWrite(LED_BLUE, 0);
}

// Conversion valeur ADC 12-bit -> distance en cm
// Courbe non-lineaire du GP2Y0A21YK0F
float adcToDistance(int adcVal) {
  float voltage = adcVal * 3.3 / 4095.0;

  if (voltage < 0.4) return 80.0;
  if (voltage > 3.1) return 10.0;

  float distance = 27.86 / (voltage - 0.12);

  if (distance < 10.0) distance = 10.0;
  if (distance > 80.0) distance = 80.0;

  return distance;
}

void setup() {
  Serial.begin(9600);

  pinMode(LED_RED, OUTPUT);
  pinMode(LED_GREEN, OUTPUT);
  pinMode(LED_BLUE, OUTPUT);

  ledsOff();

  Serial.println("ISS_CARGO_START");
}

void loop() {
  int adcVal = analogRead(CAPTEUR_PIN);

  float distance = adcToDistance(adcVal);
  int distInt = (int)distance;

  ledsOff();

  if (distance < 15) {
    // Rouge — trop proche
    analogWrite(LED_RED, 255);
  } else if (distance < 30) {
    // Orange — RED max + GREEN faible
    analogWrite(LED_RED, 255);
    analogWrite(LED_GREEN, 40);
  } else if (distance < 50) {
    // Bleu — zone moyenne
    analogWrite(LED_BLUE, 255);
  } else if (distance < 65) {
    // Cyan — GREEN + BLUE
    analogWrite(LED_GREEN, 255);
    analogWrite(LED_BLUE, 255);
  } else {
    // Vert — loin
    analogWrite(LED_GREEN, 255);
  }

  Serial.print("ADC:");
  Serial.print(adcVal);
  Serial.print(";DIST:");
  Serial.println(distInt);

  delay(200);
}
