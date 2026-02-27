<?php
// telegram_notify.php

// Load environment variables if not loaded
if (!isset($_ENV['TELEGRAM_BOT_TOKEN'])) {
    require_once __DIR__ . '/config.php';
}

/**
 * ส่ง Telegram แบบมี Rate Limiting เพื่อป้องกันโดนบล็อค
 * - ใช้ระบบ Cooldown: ถ้าส่งครั้งล่าสุดยังไม่ครบ X วินาที จะไม่ส่งซ้ำ
 * - ใช้ระบบ Queue: เก็บข้อความที่รอส่ง
 * 
 * @param string $message ข้อความที่จะส่ง
 * @param string $type ประเภท (alert, info, warning)
 * @param bool $force บังคับส่งทันที (ใช้กับข้อความสำคัญ เช่น gate trigger)
 * @return bool สถานะการส่ง
 */
function sendTelegram($message, $type = 'alert', $force = false) {
    $token = $_ENV['TELEGRAM_BOT_TOKEN'];
    $chat_id = $_ENV['TELEGRAM_CHAT_ID'];
    $cooldown = isset($_ENV['TELEGRAM_COOLDOWN_SECONDS']) ? intval($_ENV['TELEGRAM_COOLDOWN_SECONDS']) : 60;
    
    // ไฟล์เก็บข้อมูล last send time
    $cache_file = sys_get_temp_dir() . '/telegram_last_send.txt';
    $queue_file = sys_get_temp_dir() . '/telegram_queue.json';
    
    $now = time();
    $last_send_time = file_exists($cache_file) ? intval(file_get_contents($cache_file)) : 0;
    $time_since_last = $now - $last_send_time;
    
    // ถ้าไม่ใช่ force และยังไม่ครบ cooldown ให้เก็บลง queue
    if (!$force && $time_since_last < $cooldown) {
        // เก็บข้อความลง queue (แต่ไม่เก็บซ้ำ)
        $queue = file_exists($queue_file) ? json_decode(file_get_contents($queue_file), true) : [];
        
        // ตรวจสอบว่ามีข้อความนี้อยู่ใน queue แล้วหรือยัง
        $message_hash = md5($message . $type);
        $exists = false;
        foreach ($queue as $item) {
            if ($item['hash'] === $message_hash) {
                $exists = true;
                break;
            }
        }
        
        if (!$exists && count($queue) < 10) { // จำกัด queue ไม่เกิน 10 ข้อความ
            $queue[] = [
                'message' => $message,
                'type' => $type,
                'time' => $now,
                'hash' => $message_hash
            ];
            file_put_contents($queue_file, json_encode($queue));
        }
        
        return false; // บอกว่ายังไม่ส่ง
    }
    
    // ส่งข้อความ
    $url = "https://api.telegram.org/bot$token/sendMessage";
    
    // เพิ่ม emoji ตามประเภท
    $icon = [
        'alert' => '🚨',
        'warning' => '⚠️',
        'info' => 'ℹ️',
        'success' => '✅',
        'gate' => '🚧'
    ];
    $prefix = isset($icon[$type]) ? $icon[$type] . ' ' : '';
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $prefix . $message,
        'parse_mode' => 'HTML'
    ];

    // ใช้ cURL พร้อม timeout สั้น
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); // เพิ่ม timeout เป็น 3 วินาที
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // บันทึกเวลาที่ส่ง
    if ($http_code == 200) {
        file_put_contents($cache_file, $now);
        
        // ลบข้อความนี้ออกจาก queue ถ้ามี
        if (file_exists($queue_file)) {
            unlink($queue_file); // ลบ queue ทั้งหมดเมื่อส่งสำเร็จ
        }
        
        return true;
    }
    
    return false;
}

/**
 * ประมวลผล Queue ของ Telegram - เรียกใช้เป็นครั้งคราว
 * รวมข้อความทั้งหมดใน queue ส่งเป็นครั้งเดียว
 */
function processTelegramQueue() {
    $queue_file = sys_get_temp_dir() . '/telegram_queue.json';
    
    if (!file_exists($queue_file)) {
        return false;
    }
    
    $queue = json_decode(file_get_contents($queue_file), true);
    
    if (empty($queue)) {
        return false;
    }
    
    // สรุปข้อความที่รอส่ง
    $summary = "📊 <b>สรุปการแจ้งเตือนที่พลาด</b>\n\n";
    
    $alerts = array_filter($queue, function($item) { return $item['type'] === 'alert'; });
    $warnings = array_filter($queue, function($item) { return $item['type'] === 'warning'; });
    
    if (count($alerts) > 0) {
        $summary .= "🚨 แจ้งเตือนสำคัญ: " . count($alerts) . " ครั้ง\n";
    }
    if (count($warnings) > 0) {
        $summary .= "⚠️ คำเตือน: " . count($warnings) . " ครั้ง\n";
    }
    
    $summary .= "\nข้อความล่าสุด:\n" . $queue[count($queue)-1]['message'];
    
    // ส่งแบบ force
    return sendTelegram($summary, 'info', true);
}

/**
 * ส่ง Telegram แบบรวมค่าเพื่อลด request
 * 
 * @param float $temp อุณหภูมิ
 * @param float $hum ความชื้น
 * @return bool
 */
function sendTelegramAlert($temp, $hum) {
    $temp_alert = $temp > 28;
    $hum_alert = $hum > 80;
    
    if (!$temp_alert && !$hum_alert) {
        return false; // ไม่มีอะไรผิดปกติ
    }
    
    $message = "<b>⚠️ ค่าผิดปกติตรวจพบ!</b>\n\n";
    $message .= "🌡️ อุณหภูมิ: <b>" . number_format($temp, 1) . " °C</b>";
    if ($temp_alert) {
        $message .= " ⚠️ <i>(เกิน 28°C)</i>";
    }
    $message .= "\n";
    
    $message .= "💧 ความชื้น: <b>" . number_format($hum, 1) . " %</b>";
    if ($hum_alert) {
        $message .= " ⚠️ <i>(เกิน 80%)</i>";
    }
    $message .= "\n\n";
    $message .= "📅 " . date('d/m/Y H:i:s');
    
    return sendTelegram($message, 'alert', false);
}
?>