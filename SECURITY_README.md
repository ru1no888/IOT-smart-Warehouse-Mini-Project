# 🔐 IOT Warehouse Security & Telegram Alert System

## ระบบรักษาความปลอดภัยที่ติดตั้งแล้ว

### 1. 🔑 Authentication & Authorization
- ✅ **Username/Password** - เข้ารหัสด้วย `password_hash()` 
- ✅ **Session Management** - Auto logout หลัง 5 นาที (ปรับได้ใน .env)
- ✅ **API Key Authentication** - สำหรับ ESP32
- ✅ **CSRF Protection** - ป้องกัน Cross-Site Request Forgery

### 2. 🚫 Rate Limiting
- ✅ **API Rate Limit** - จำกัด 3 requests/วินาที (ปรับได้)
- ✅ **Login Rate Limit** - บล็อค IP หลังพยายาม login ผิด 5 ครั้ง (15 นาที)
- ✅ **Telegram Rate Limit** - Cooldown 60 วินาที ป้องกันโดนบล็อค

### 3. 🛡️ SQL Injection Protection
- ✅ **Prepared Statements** - ใช้ทุกไฟล์ที่เข้าถึงฐานข้อมูล
- ✅ **Input Validation** - ตรวจสอบข้อมูลที่รับเข้ามา

### 4. 📝 Security Logging
- ✅ **Access Logs** - บันทึกการเข้าถึงทุกครั้ง
- ✅ **Failed Login Logs** - บันทึก login ที่ผิดพลาด
- ✅ **API Access Logs** - บันทึกการเรียกใช้ API
- ไฟล์ log: `/logs/security_YYYY-MM-DD.log`

### 5. 📱 Telegram Alert System
- ✅ **Cooldown System** - ส่งแจ้งเตือนห่าง 60 วินาที (ปรับได้)
- ✅ **Queue System** - เก็บข้อความที่รอส่ง
- ✅ **Force Send** - สำหรับข้อความสำคัญ (เปิดประตู, IP บล็อค)
- ✅ **HTML Format** - รองรับ Bold, Italic
- ✅ **Smart Grouping** - รวมข้อความเหมือนกันไม่ส่งซ้ำ

---

## 📦 การติดตั้ง

### 1. แก้ไขไฟล์ `.env`
```bash
# คัดลอกจาก example
cp .env.example .env

# แก้ไขค่าต่างๆ
nano .env
```

### 2. ตั้งค่า Environment Variables
```env
# Database
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=iot_warehouse

# API Security
API_KEY=your_secret_api_key_change_this

# Telegram Bot
TELEGRAM_BOT_TOKEN=your_bot_token_from_botfather
TELEGRAM_CHAT_ID=your_chat_id

# Settings
SESSION_TIMEOUT=300
RATE_LIMIT_PER_SECOND=3
TELEGRAM_COOLDOWN_SECONDS=60
```

### 3. สร้างโฟลเดอร์ Logs
```bash
mkdir logs
chmod 755 logs
```

### 4. ตั้งค่า ESP32
เพิ่ม API Key ใน Header:
```cpp
http.addHeader("X-API-KEY", "your_secret_api_key_change_this");
```

---

## 🔔 ระบบแจ้งเตือน Telegram

### การทำงาน
1. **Alert Mode (Cooldown)** - ส่งครั้งแรกทันที ครั้งต่อไปรอ 60 วินาที
2. **Queue System** - เก็บข้อความที่รอส่ง (สูงสุด 10 ข้อความ)
3. **Auto Process** - ประมวลผล queue อัตโนมัติทุก 10 requests
4. **Force Send** - ข้อความสำคัญส่งทันทีไม่รอ cooldown

### ประเภทการแจ้งเตือน
| ประเภท | Cooldown | Force | ตัวอย่าง |
|--------|----------|-------|----------|
| 🚨 Alert | ✅ 60s | ❌ | ค่าเกินกำหนด (อุณหภูมิ/ความชื้น) |
| ⚠️ Warning | ✅ 60s | ✅ | Login ผิด 5 ครั้ง, IP บล็อค |
| ℹ️ Info | ✅ 60s | ❌ | Login สำเร็จ |
| 🚧 Gate | ❌ | ✅ | เปิดประตูจาก Dashboard |

### ฟังก์ชันสำคัญ

```php
// ส่งแจ้งเตือนอุณหภูมิ/ความชื้น (มี cooldown)
sendTelegramAlert($temp, $hum);

// ส่งข้อความทั่วไป (มี cooldown)
sendTelegram($message, $type = 'alert', $force = false);

// ส่งแบบบังคับ (ไม่มี cooldown)
sendTelegram($message, 'gate', true);

// ประมวลผล queue
processTelegramQueue();
```

---

## 📊 Monitoring & Logs

### ดู Security Logs
```bash
# Log วันนี้
tail -f logs/security_$(date +%Y-%m-%d).log

# ดู Failed Login
grep "FAILED" logs/security_*.log

# ดู Blocked IP
grep "BLOCKED" logs/security_*.log
```

### ตรวจสอบ Telegram Queue
```bash
cat /tmp/telegram_queue.json
```

### ดู Rate Limit Status
```bash
ls -la /tmp/rate_limit_*.txt
ls -la /tmp/telegram_last_send.txt
```

---

## 🔧 การปรับแต่ง

### เปลี่ยน Cooldown ของ Telegram
แก้ไขใน `.env`:
```env
TELEGRAM_COOLDOWN_SECONDS=30  # ส่งได้ทุก 30 วินาที
```

### เปลี่ยน Rate Limit
แก้ไขใน `.env`:
```env
RATE_LIMIT_PER_SECOND=5  # ยอมให้ 5 requests/วินาที
```

### เปลี่ยนเวลา Session Timeout
แก้ไขใน `.env`:
```env
SESSION_TIMEOUT=600  # 10 นาที
```

### ปิด Login Alert
แก้ไขใน `login.php`:
```php
// ลบหรือ comment บรรทัดนี้
// sendTelegram($msg, 'info', false);
```

---

## 🚨 Troubleshooting

### ไม่ได้รับแจ้งเตือน Telegram
1. ตรวจสอบ Token และ Chat ID ใน `.env`
2. ทดสอบส่งด้วย cURL:
```bash
curl -X POST "https://api.telegram.org/bot<TOKEN>/sendMessage" \
     -d "chat_id=<CHAT_ID>" \
     -d "text=Test"
```
3. ตรวจสอบ cooldown timer:
```bash
cat /tmp/telegram_last_send.txt
```

### Rate Limit บล็อค ESP32
- เพิ่มค่า `RATE_LIMIT_PER_SECOND` ใน `.env`
- หรือเพิ่มช่วงเวลาการส่งข้อมูลใน ESP32

### IP ถูกบล็อค
ลบ block ด้วยตนเอง:
```bash
rm /tmp/blocked_ips.json
rm /tmp/login_attempts_*.txt
```

---

## 📝 Best Practices

### Security
1. ✅ เปลี่ยน API Key ใน `.env` ทันที
2. ✅ใช้ HTTPS ในโปรดักชัน
3. ✅ เปลี่ยน DB Password
4. ✅ สำรองฐานข้อมูลเป็นประจำ
5. ✅ ตรวจสอบ logs เป็นประจำ
6. ❌ อย่า commit `.env` เข้า Git

### Telegram
1. ✅ ตั้ง cooldown ไม่ต่ำกว่า 30 วินาที
2. ✅ ใช้ `force=true` เฉพาะข้อความสำคัญ
3. ✅ ตรวจสอบ queue เป็นประจำ
4. ✅ อย่าใช้ bot token ผู้อื่นเห็น

---

## 📞 Support
หากพบปัญหา:
1. ตรวจสอบ logs ก่อน
2. อ่าน error message ใน console
3. ทดสอบด้วย Postman หรือ cURL

---

## 🎉 สรุป

ระบบนี้มีความปลอดภัยครบถ้วน:
- 🔐 Authentication & Authorization
- 🚫 Rate Limiting (API, Login, Telegram)
- 🛡️ SQL Injection Protection
- 📝 Security Logging
- 📱 Smart Telegram Alerts
- 🔒 CSRF Protection
- 🚪 Auto Session Timeout

**ระบบ Telegram Alert จะไม่โดนบล็อคแล้ว!** 🎯
