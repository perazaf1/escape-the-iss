/**
 * Escape Game ISS — Salle de Stockage (G5E)
 * Lecture capteur de proximité Sharp GP2Y0A21YK0F
 * Envoi des données via série (UART) vers le PC
 *
 * IDE : Energia
 * Carte : Tiva TM4C123GH6PM (EK-TM4C123GXL LaunchPad)
 *
 * Câblage :
 *   Jaune (Vo)  → PE_3 (broche analogique)
 *   Noir  (GND) → GND
 *   Rouge (Vcc) → 5V (VBUS)
 */

#define CAPTEUR_PIN PE_3
#define LED_RED RED_LED
#define LED_GREEN GREEN_LED
#define LED_BLUE BLUE_LED

// Conversion valeur ADC 12-bit → distance en cm
// Courbe non-linéaire du GP2Y0A21YK0F
float adcToDistance(int adcVal) {
  // Conversion en tension : 0-4095 → 0-3.3V
  float voltage = adcVal * 3.3 / 4095.0;

  if (voltage < 0.4) return 80.0;  // trop loin
  if (voltage > 3.1) return 10.0;  // trop près (zone instable)

  // Formule empirique pour ce capteur
  float distance = 27.86 / (voltage - 0.12);

  // Borner entre 10 et 80 cm
  if (distance < 10.0) distance = 10.0;
  if (distance > 80.0) distance = 80.0;

  return distance;
}

void setup() {
  // Liaison série vers le PC — 9600 bauds
  Serial.begin(9600);

  // LEDs intégrées du LaunchPad comme feedback visuel
  pinMode(LED_RED, OUTPUT);
  pinMode(LED_GREEN, OUTPUT);
  pinMode(LED_BLUE, OUTPUT);

  // Eteindre toutes les LEDs au démarrage
  digitalWrite(LED_RED, LOW);
  digitalWrite(LED_GREEN, LOW);
  digitalWrite(LED_BLUE, LOW);

  // Message de démarrage
  Serial.println("ISS_CARGO_START");
}

void loop() {
  // Lecture ADC 12-bit (0-4095)
  int adcVal = analogRead(CAPTEUR_PIN);

  // Conversion en distance
  float distance = adcToDistance(adcVal);
  int distInt = (int)distance;

  // Feedback LED selon la distance
  if (distance < 20) {
    // Rouge — objet très proche (alerte)
    digitalWrite(LED_RED, HIGH);
    digitalWrite(LED_GREEN, LOW);
    digitalWrite(LED_BLUE, LOW);
  } else if (distance < 50) {
    // Bleu — objet à distance moyenne
    digitalWrite(LED_RED, LOW);
    digitalWrite(LED_GREEN, LOW);
    digitalWrite(LED_BLUE, HIGH);
  } else {
    // Vert — rien de proche
    digitalWrite(LED_RED, LOW);
    digitalWrite(LED_GREEN, HIGH);
    digitalWrite(LED_BLUE, LOW);
  }

  // Envoi série au format : ADC:1234;DIST:45\n
  Serial.print("ADC:");
  Serial.print(adcVal);
  Serial.print(";DIST:");
  Serial.println(distInt);

  // 5 lectures par seconde
  delay(200);
}
