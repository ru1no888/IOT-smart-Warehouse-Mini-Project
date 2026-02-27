<?php
// security_logger.php - ระบบบันทึก Log เพื่อตรวจสอบการเข้าถึง

/**
 * บันทึก Log การเข้าถึงระบบ
 * 
 * @param string $action การกระทำ (login, api_call, control, alert)
 * @param string $detail รายละเอียด
 * @param string $status สถานะ (success, failed, blocked)
 */
function logSecurityEvent($action, $detail, $status = 'success') {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/security_' . date('Y-m-d') . '.log';
    
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
    $user = isset($_SESSION['username']) ? $_SESSION['username'] : 'anonymous';
    
    $log_entry = sprintf(
        "[%s] [%s] [%s] IP: %s | User: %s | Action: %s | Detail: %s | UA: %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($status),
        strtoupper($action),
        $ip,
        $user,
        $action,
        $detail,
        substr($user_agent, 0, 100)
    );
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * บันทึก Failed Login Attempts และบล็อค IP ถ้าพยายามเกินกำหนด
 * 
 * @param string $username
 * @param string $ip
 * @return bool true ถ้า IP ถูกบล็อค
 */
function checkAndBlockFailedLogin($username, $ip) {
    $block_file = sys_get_temp_dir() . '/blocked_ips.json';
    $attempts_file = sys_get_temp_dir() . '/login_attempts_' . md5($ip) . '.txt';
    
    $max_attempts = 5;
    $block_duration = 900; // 15 นาที
    $now = time();
    
    // ตรวจสอบว่า IP ถูกบล็อคอยู่หรือไม่
    if (file_exists($block_file)) {
        $blocked = json_decode(file_get_contents($block_file), true);
        if (isset($blocked[$ip]) && $blocked[$ip] > $now) {
            $remaining = ceil(($blocked[$ip] - $now) / 60);
            logSecurityEvent('login', "Blocked IP attempted login: $username", 'blocked');
            http_response_code(403);
            die("IP ของคุณถูกบล็อคชั่วคราว กรุณารอ $remaining นาที");
        }
    }
    
    // นับจำนวนครั้งที่ล็อกอินผิด
    if (file_exists($attempts_file)) {
        $data = explode('|', file_get_contents($attempts_file));
        $attempts = intval($data[0]);
        $first_attempt = intval($data[1]);
        
        // รีเซ็ตถ้าผ่านมานานกว่า 1 ชั่วโมง
        if ($now - $first_attempt > 3600) {
            $attempts = 1;
            $first_attempt = $now;
        } else {
            $attempts++;
        }
    } else {
        $attempts = 1;
        $first_attempt = $now;
    }
    
    file_put_contents($attempts_file, $attempts . '|' . $first_attempt);
    
    // บล็อค IP ถ้าพยายามเกิน max_attempts
    if ($attempts >= $max_attempts) {
        $blocked = file_exists($block_file) ? json_decode(file_get_contents($block_file), true) : [];
        $blocked[$ip] = $now + $block_duration;
        file_put_contents($block_file, json_encode($blocked));
        
        logSecurityEvent('login', "IP blocked after $attempts failed attempts: $username", 'blocked');
        
        // ส่ง Telegram แจ้งเตือน
        require_once 'telegram_notify.php';
        $msg = "<b>🚨 ตรวจพบการพยายามเข้าสู่ระบบผิดปกติ!</b>\n\n";
        $msg .= "👤 Username: " . htmlspecialchars($username) . "\n";
        $msg .= "🌐 IP: $ip\n";
        $msg .= "🔢 จำนวนครั้ง: $attempts\n";
        $msg .= "⏰ ถูกบล็อคเป็นเวลา 15 นาที";
        sendTelegram($msg, 'warning', true);
        
        return true;
    }
    
    return false;
}

/**
 * ล้าง Login Attempts หลังจากล็อกอินสำเร็จ
 */
function clearLoginAttempts($ip) {
    $attempts_file = sys_get_temp_dir() . '/login_attempts_' . md5($ip) . '.txt';
    if (file_exists($attempts_file)) {
        unlink($attempts_file);
    }
}
?>
