# 🏭 IOT Smart Warehouse System

<p align="center">
  <img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
  <img src="https://img.shields.io/badge/ESP32-E7352C?style=for-the-badge&logo=espressif&logoColor=white" />
  <img src="https://img.shields.io/badge/Bootstrap-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" />
  <img src="https://img.shields.io/badge/Telegram-26A5E4?style=for-the-badge&logo=telegram&logoColor=white" />
</p>

> 🎓 Final Project IOT - ระบบควบคุมคลังสินค้าอัจฉริยะ ด้วย ESP32 + Web Dashboard + Telegram Bot

## 📋 สารบัญ

- [✨ Features](#-features)
- [🛠️ Tech Stack](#️-tech-stack)
- [📦 ติดตั้ง](#-ติดตั้ง)
- [⚙️ การตั้งค่า](#️-การตั้งค่า)
- [🗄️ Database Schema](#️-database-schema)
- [🔌 API Endpoints](#-api-endpoints)
- [🔒 Security Features](#-security-features)
- [📱 Telegram Bot](#-telegram-bot)
- [🖼️ Screenshots](#️-screenshots)

---

## ✨ Features

### 🎯 Core Features
- 📊 **Real-time Dashboard** - แสดงผลอุณหภูมิ/ความชื้นแบบ Real-time
- 🌡️ **Temperature & Humidity Monitoring** - ตรวจจับอุณหภูมิและความชื้นด้วย DHT22
- 💨 **Fan Control** - ควบคุมพัดลมอัตโนมัติ/Manual
- 🚨 **Buzzer Alert** - แจ้งเตือนเมื่ออุณหภูมิเกินกำหนด
- 🚪 **Gate Control** - ควบคุมประตูคลังสินค้าผ่าน Dashboard
- 📈 **Chart Visualization** - กราฟแสดงข้อมูลย้อนหลัง (Chart.js)
- 📱 **Telegram Notification** - แจ้งเตือนผ่าน Telegram Bot

### 🔒 Security Features
- 🔐 **User Authentication** - ระบบ Login/Register ด้วย Password Hash
- 🔑 **API Key Protection** - ป้องกัน API ด้วย Secret Key
- 🚫 **Rate Limiting** - ป้องกัน DoS Attack (จำกัด requests/วินาที)
- ⏱️ **Session Timeout** - Auto logout เมื่อไม่มีการใช้งาน
- 🛡️ **SQL Injection Protection** - ใช้ Prepared Statements
- 📝 **Security Logging** - บันทึก Log การเข้าถึงทุกครั้ง

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|------------|
| **Hardware** | ESP32, DHT22, Relay Module, Servo Motor |
| **Backend** | PHP 7.4+, MySQL |
| **Frontend** | HTML5, CSS3, JavaScript, Chart.js |
| **Server** | XAMPP (Apache + MySQL) |
| **Notification** | Telegram Bot API |

---

## 📦 ติดตั้ง

### 1. Clone Repository
```bash
git clone https://github.com/ru1no888/IOT-smart-Warehouse-Mini-Project.git
cd IOT-smart-Warehouse-Mini-Project
```

### 2. ย้ายไฟล์ไปยัง XAMPP
```bash
# Windows
xcopy /E /I . C:\xampp\htdocs\IOT-finalproject

# หรือ copy ทั้งโฟลเดอร์ไปวางใน htdocs
```

### 3. สร้าง Database
```sql
CREATE DATABASE databasewarehouse;
USE databasewarehouse;

-- ตาราง Users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตาราง Warehouse Logs (ข้อมูล Sensor)
CREATE TABLE warehouse_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    temp_value FLOAT,
    hum_value FLOAT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตาราง System Control
CREATE TABLE system_control (
    id INT PRIMARY KEY DEFAULT 1,
    fan_manual TINYINT DEFAULT 0,
    fan_state TINYINT DEFAULT 0,
    buzzer_manual TINYINT DEFAULT 0,
    buzzer_state TINYINT DEFAULT 0,
    gate_trigger TINYINT DEFAULT 0,
    reset_wifi TINYINT DEFAULT 0
);

-- Insert default row
INSERT INTO system_control (id) VALUES (1);

-- ตาราง API Settings
CREATE TABLE api_settings (
    id INT PRIMARY KEY DEFAULT 1,
    api_key VARCHAR(255) DEFAULT 'MY_SECRET_KEY_888'
);
```

### 4. ตั้งค่า Environment
```bash
# Copy .env.example เป็น .env
copy .env.example .env

# แก้ไขค่าใน .env ตามต้องการ
```

---

## ⚙️ การตั้งค่า

### ไฟล์ `.env`
```dotenv
# Database Configuration
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=databasewarehouse

# API Security
API_KEY=MY_SECRET_KEY_888

# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN=YOUR_BOT_TOKEN
TELEGRAM_CHAT_ID=YOUR_CHAT_ID

# Session Timeout (วินาที)
SESSION_TIMEOUT=300

# Rate Limit Settings
RATE_LIMIT_PER_SECOND=3
TELEGRAM_COOLDOWN_SECONDS=60
```

### สร้าง Telegram Bot
1. ค้นหา `@BotFather` ใน Telegram
2. พิมพ์ `/newbot` และตั้งชื่อ Bot
3. Copy **Bot Token** มาใส่ใน `.env`
4. ค้นหา `@userinfobot` เพื่อหา **Chat ID**

---

## 🗄️ Database Schema

```
databasewarehouse
├── users              # ข้อมูลผู้ใช้
├── warehouse_logs     # Log อุณหภูมิ/ความชื้น
├── system_control     # สถานะอุปกรณ์
└── api_settings       # ตั้งค่า API Key
```

---

## 🔌 API Endpoints

### 📤 POST Data (ESP32 → Server)
```http
POST /post_data.php
Header: X-API-KEY: YOUR_API_KEY
Body: temp=28.5&hum=65.2
```

### 📥 GET Data (Server → Dashboard)
```http
GET /get_data.php
Response: {"latest": {...}, "status": {...}, "graph": [...]}
```

### ⚙️ Control API (ESP32 ← Server)
```http
GET /api_control.php?action=get
Header: X-API-KEY: YOUR_API_KEY
Response: F_M:0,F_S:1,B_M:0,B_S:0,G_T:0,R_W:0
```

### 🎛️ Set Control (Dashboard → Server)
```http
GET /api_control.php?action=set&column=fan_state&value=1
```

---

## 🔒 Security Features

| Feature | Description |
|---------|-------------|
| **Password Hashing** | bcrypt (password_hash) |
| **API Key** | X-API-KEY Header |
| **Rate Limiting** | 3 requests/second/IP |
| **Session Timeout** | Auto logout หลัง 5 นาที |
| **SQL Injection** | Prepared Statements |
| **XSS Protection** | htmlspecialchars() |
| **Login Brute Force** | Block IP หลังผิด 5 ครั้ง |

### ทดสอบ Rate Limit
```
http://localhost/IOT-finalproject/test_rate_limit.php
```

---

## 📱 Telegram Bot

### แจ้งเตือนอัตโนมัติ
- 🌡️ อุณหภูมิเกิน Threshold
- 🚪 เปิดประตูจาก Dashboard
- ⚠️ แจ้งเตือน Alert สำคัญ

### ตัวอย่างข้อความ
```
🌡️ อุณหภูมิสูง: 35.5°C
💧 ความชื้น: 70%
📅 เวลา: 27/02/2026 14:30:00
```

---

## 📂 Project Structure

```
IOT-finalproject/
├── 📄 index.html          # Main Dashboard (Futuristic UI)
├── 📄 login.php           # หน้า Login
├── 📄 register.php        # หน้าสมัครสมาชิก
├── 📄 logout.php          # Logout Handler
├── 📄 config.php          # Config & Security Functions
├── 📄 db.php              # Database Connection
├── 📄 get_data.php        # API: ดึงข้อมูล
├── 📄 post_data.php       # API: รับข้อมูลจาก ESP32
├── 📄 api_control.php     # API: ควบคุมอุปกรณ์
├── 📄 telegram_notify.php # Telegram Bot Handler
├── 📄 security_logger.php # Security Logging
├── 📄 test_rate_limit.php # ทดสอบ Rate Limit
├── 📄 .env                # Environment Variables
├── 📄 .env.example        # Template สำหรับ .env
├── 📄 .htaccess           # Apache Config
├── 📄 .gitignore          # Git Ignore
└── 📁 logs/               # Security Logs
```

---

## 🖼️ Screenshots

### Dashboard
- 🌡️ แสดงอุณหภูมิ/ความชื้น Real-time
- 📊 กราฟแสดงข้อมูลย้อนหลัง
- 🎛️ ปุ่มควบคุม Fan/Buzzer/Gate
- 🌌 Futuristic UI with Aurora Effect

### Login Page
- 🔐 Cyberpunk Design
- ✨ Animated Background

---

## 🔧 ESP32 Configuration

```cpp
// WiFi Config
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";

// Server Config
const char* serverUrl = "http://YOUR_SERVER_IP/IOT-finalproject/";
const char* apiKey = "MY_SECRET_KEY_888";
```

---

## 👥 Contributors

- **Thanakorn** - Developer

---

## 📄 License

This project is licensed under the MIT License.

---

## 🙏 Acknowledgments

- Chart.js for beautiful charts
- Telegram Bot API
- XAMPP Development Environment
- ESP32 Community

---

<p align="center">
  Made with ❤️ for IOT Final Project 2026
</p>
