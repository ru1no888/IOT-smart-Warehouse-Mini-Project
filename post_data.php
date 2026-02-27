<?php
require_once 'config.php';
$headers = apache_request_headers();
$received_key = isset($headers['X-API-KEY']) ? $headers['X-API-KEY'] : '';
verifyAPI($conn, $received_key); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $temp = isset($_POST['temp']) ? floatval($_POST['temp']) : 0;
    $hum = isset($_POST['hum']) ? floatval($_POST['hum']) : 0;

    // ✅ ใช้ Prepared Statement เพื่อป้องกัน SQL Injection
    $stmt = $conn->prepare("INSERT INTO warehouse_logs (temp_value, hum_value, timestamp) VALUES (?, ?, NOW())");
    $stmt->bind_param("dd", $temp, $hum);
    
    if ($stmt->execute()) {
        echo "OK";
        
        require_once 'telegram_notify.php';

        // ✅ ใช้ฟังก์ชันใหม่ที่มีระบบ cooldown
        // ส่งแจ้งเตือนเฉพาะเมื่อค่าเกิน และมีระบบป้องกันส่งบ่อยเกินไป
        if ($temp > 28 || $hum > 80) { 
            sendTelegramAlert($temp, $hum);
        }
        
        // ทุก 10 request ลองประมวลผล queue ที่ค้างอยู่
        if (rand(1, 10) == 1) {
            processTelegramQueue();
        }

    } else {
        echo "Error: " . $stmt->error;
    }
    
    $stmt->close();
}
$conn->close();
?>