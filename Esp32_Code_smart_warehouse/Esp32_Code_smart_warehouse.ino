#include <SPI.h>
#include <MFRC522.h>
#include <ESP32Servo.h>
#include <LiquidCrystal_I2C.h> 
#include "DHT.h"               
#include <WiFiManager.h> 
#include <Ticker.h>      
#include <HTTPClient.h>

// --- ตั้งค่าซอฟต์แวร์ (Software Settings) ---
// ⚠️ สำคัญ: ตรวจสอบ IP เครื่องคอมพิวเตอร์คุณด่วนว่ายังเป็น 10.46.167.135 อยู่หรือไม่!
const char* php_post_url = "http://10.46.167.135/IOT-finalproject/post_data.php";
const char* php_get_url = "http://10.46.167.135/IOT-finalproject/api_control.php?action=get";

// *** ส่วนที่เพิ่ม: Security Token / API Key ***
const char* api_key = "MY_SECRET_KEY_888";// รหัสลับสำหรับคุยกับ Server

// --- Pin Assignment ---
#define SS_PIN      5
#define RST_PIN     4  
#define SERVO_PIN   13    
#define FAN_PIN     14    
#define BUZZER      12    
#define DHTPIN      15    
#define DHTTYPE     DHT22 
#define RGB_R       25
#define RGB_G       26
#define RGB_B       27
#define STATUS_LED  2      

// --- สร้างออบเจกต์ ---
MFRC522 rfid(SS_PIN, RST_PIN);
Servo myServo;
DHT dht(DHTPIN, DHTTYPE);
LiquidCrystal_I2C lcd(0x27, 16, 2); 
Ticker ticker; 

// --- ตัวแปรควบคุม ---
bool isGateOpening = false; 
int speedDelay = 15;        
float humThreshold = 80.0; 
unsigned long previousMillis = 0;
const long interval = 2000; // Client-side Rate Limit (ส่งทุก 2 วินาที)

// ตัวแปรควบคุมโหมด Manual 
bool isManualFan = false; 
bool fanWebState = false;
bool isManualBuzzer = false; 
bool buzzerWebState = false;

// ตัวแปรจอ LCD
int lcdState = 0; 
bool wasConnected = true; 

// ฟังก์ชันไฟกระพริบ
void tickLogic() {
  int state = digitalRead(STATUS_LED);  
  digitalWrite(STATUS_LED, !state);     
}

void configModeCallback (WiFiManager *myWiFiManager) {
  Serial.println("Entered config mode (AP Mode)");
  lcd.clear();
  lcd.setCursor(0, 0); lcd.print("Connect AP:");
  lcd.setCursor(0, 1); lcd.print("AutoGate_Setup");
  ticker.attach(0.2, tickLogic); 
}

void openGate() {
  isGateOpening = true;
  lcd.clear(); lcd.setCursor(0, 0); lcd.print("Access Granted!"); lcd.setCursor(0, 1); lcd.print(">> GATE OPEN <<");
  if(!isManualBuzzer) tone(BUZZER, 1500, 200); 
  for (int pos = 0; pos <= 90; pos += 5) { myServo.write(pos); delay(speedDelay); }
  delay(3000); 
  for (int pos = 90; pos >= 0; pos -= 5) { myServo.write(pos); delay(speedDelay); }
  lcd.clear(); 
  if(!isManualBuzzer) noTone(BUZZER); 
  isGateOpening = false; 
}

void fetchCommands() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(php_get_url);
    
    // ส่ง API Key ไปยืนยันตัวตน
    http.addHeader("X-API-KEY", api_key); 
    
    int httpCode = http.GET();
    
    // --- ระบบ Debug แจ้งเตือนสถานะการดึงคำสั่ง (GET) ---
    Serial.print("[GET] Server Code: ");
    Serial.println(httpCode);

    if (httpCode > 0) {
      String payload = http.getString();
      Serial.println("[GET] Payload: " + payload);

      int fm = 0, fs = 0, bm = 0, bs = 0, gt = 0, rst_wifi = 0;
      if (sscanf(payload.c_str(), "F_M:%d,F_S:%d,B_M:%d,B_S:%d,G_T:%d,R_W:%d", &fm, &fs, &bm, &bs, &gt, &rst_wifi) >= 5) {
        isManualFan = (fm == 1);
        fanWebState = (fs == 1);
        isManualBuzzer = (bm == 1);
        buzzerWebState = (bs == 1);
        
        if (gt == 1 && !isGateOpening) {
          openGate();
        }

        if (rst_wifi == 1) {
          lcd.clear();
          lcd.setCursor(0,0); lcd.print("Cmd: Reset WiFi!");
          lcd.setCursor(0,1); lcd.print("Rebooting...");
          delay(2000);
          WiFiManager wm;
          wm.resetSettings(); 
          ESP.restart();      
        }
      }
    }
    http.end();
  }
}

void setup() {
  Serial.begin(115200);
  SPI.begin(); rfid.PCD_Init(); dht.begin();
  
  pinMode(BUZZER, OUTPUT); pinMode(FAN_PIN, OUTPUT); pinMode(STATUS_LED, OUTPUT); 
  digitalWrite(FAN_PIN, LOW); 

  pinMode(RGB_R, OUTPUT); pinMode(RGB_G, OUTPUT); pinMode(RGB_B, OUTPUT);
  digitalWrite(RGB_R, HIGH); digitalWrite(RGB_G, HIGH); digitalWrite(RGB_B, HIGH);

  lcd.init(); lcd.backlight();
  lcd.setCursor(0, 0); lcd.print("System Ready..."); delay(1000);

  lcd.clear(); lcd.print("Connecting WiFi");
  ticker.attach(0.5, tickLogic);

  WiFiManager wm; wm.setAPCallback(configModeCallback); 
  if (!wm.autoConnect("NIGGA")) { delay(3000); ESP.restart(); }

  ticker.detach(); digitalWrite(STATUS_LED, HIGH); 
  lcd.clear(); lcd.print("WiFi Connected!"); delay(1500); lcd.clear();

  ESP32PWM::allocateTimer(0);
  myServo.setPeriodHertz(50); myServo.attach(SERVO_PIN, 500, 2400); myServo.write(0); 
}

void loop() {
  unsigned long currentMillis = millis();
  
  if (currentMillis - previousMillis >= interval && !isGateOpening) {
    previousMillis = currentMillis;

    if (WiFi.status() == WL_CONNECTED) {
      fetchCommands();
    }

    float h = dht.readHumidity();
    float t = dht.readTemperature();

    if (isnan(h) || isnan(t)) {
      ticker.detach();
      digitalWrite(STATUS_LED, LOW); 
      digitalWrite(RGB_R, HIGH); digitalWrite(RGB_G, HIGH); digitalWrite(RGB_B, HIGH);
      lcd.clear(); lcd.setCursor(0, 0); lcd.print("Sensor Error!   "); lcd.setCursor(0, 1); lcd.print("Check DHT22     ");
    } 
    else {
      String fanStatus = ""; String buzzStatus = "";

      if (WiFi.status() != WL_CONNECTED) {
        if (wasConnected) { 
          ticker.attach(0.5, tickLogic); 
          wasConnected = false;
        }
        isManualFan = false;
        isManualBuzzer = false;
      } else {
        if (!wasConnected) { 
          ticker.detach();
          digitalWrite(STATUS_LED, HIGH); 
          wasConnected = true;
        }
      }

      if (!isManualFan) { 
        if (h >= humThreshold) { digitalWrite(FAN_PIN, HIGH); fanStatus = "Auto: ON "; } 
        else { digitalWrite(FAN_PIN, LOW); fanStatus = "Auto: OFF"; }
      } else { 
        digitalWrite(FAN_PIN, fanWebState ? HIGH : LOW);
        fanStatus = fanWebState ? "Manu: ON " : "Manu: OFF";
      }

      digitalWrite(RGB_R, HIGH); digitalWrite(RGB_G, HIGH); digitalWrite(RGB_B, HIGH);

      if (t < 27.0) { 
        digitalWrite(RGB_G, LOW); 
        if (!isManualBuzzer) { noTone(BUZZER); buzzStatus = "Auto: OFF"; }
      } 
      else if (t >= 27.0 && t <= 28.0) { 
        digitalWrite(RGB_R, LOW); digitalWrite(RGB_G, LOW);
        if (!isManualBuzzer) { 
          noTone(BUZZER); delay(50); tone(BUZZER, 1000, 100); delay(150); tone(BUZZER, 1000, 100); 
          buzzStatus = "Auto: WARN";
        }
      } 
      else { 
        digitalWrite(RGB_R, LOW); 
        if (!isManualBuzzer) { tone(BUZZER, 1000); buzzStatus = "Auto: ALARM"; }
      }

      if (isManualBuzzer) {
        if (buzzerWebState) { tone(BUZZER, 1000); buzzStatus = "Manu: ON "; }
        else { noTone(BUZZER); buzzStatus = "Manu: OFF"; }
      }

      lcdState++;
      if (WiFi.status() != WL_CONNECTED) {
        if (lcdState > 2) lcdState = 0; 
      } else {
        if (lcdState > 1) lcdState = 0; 
      }

      lcd.clear();
      if (lcdState == 0) {
        lcd.setCursor(0, 0); lcd.print("Temp: "); lcd.print(t, 1); lcd.print(" C");
        lcd.setCursor(0, 1); lcd.print("Hum : "); lcd.print(h, 1); lcd.print(" %");
      } 
      else if (lcdState == 1) {
        lcd.setCursor(0, 0); lcd.print("F:"); lcd.print(fanStatus);
        lcd.setCursor(0, 1); lcd.print("B:"); lcd.print(buzzStatus);
      } 
      else if (lcdState == 2) {
        lcd.setCursor(0, 0); lcd.print(" !! OFFLINE !! ");
        lcd.setCursor(0, 1); lcd.print("Auto Mode Active");
      }

      if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http; 
        http.begin(php_post_url); 
        http.addHeader("Content-Type", "application/x-www-form-urlencoded");
        http.addHeader("X-API-KEY", api_key); 
        
        int httpCode = http.POST("temp=" + String(t) + "&hum=" + String(h)); 
        
        // --- ระบบ Debug แจ้งเตือนสถานะการส่งข้อมูล (POST) ---
        Serial.print("[POST] Server Code: ");
        Serial.println(httpCode);

        if(httpCode > 0) {
            String response = http.getString();
            Serial.println("[POST] Response: " + response);
        } else {
            Serial.println("[POST] Error: " + http.errorToString(httpCode));
        }

        http.end();
      }
    }
  }

  if (!isGateOpening && rfid.PICC_IsNewCardPresent() && rfid.PICC_ReadCardSerial()) {
    openGate(); 
    rfid.PICC_HaltA(); 
    rfid.PCD_StopCrypto1(); 
  }
}