<?php
require_once 'config.php'; 

// ✅ เพิ่ม Rate Limiting สำหรับทุก action
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
checkRateLimit($ip);

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'set') {
    checkAuth();
    $column = $_GET['column'];
    $value = intval($_GET['value']);
    
    $allowed = ['fan_manual', 'fan_state', 'buzzer_manual', 'buzzer_state', 'gate_trigger', 'reset_wifi'];
    if (in_array($column, $allowed)) {
        // ✅ ใช้ Prepared Statement
        $stmt = $conn->prepare("UPDATE system_control SET $column = ? WHERE id = 1");
        $stmt->bind_param("i", $value);
        $stmt->execute();
        $stmt->close();
        
        // ✅ ส่ง Telegram แบบ force เมื่อมีการเปิดประตู (สำคัญ)
        if ($column == 'gate_trigger' && $value == 1) {
            require_once 'telegram_notify.php';
            $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown User';
            $message = "<b>🚧 เปิดประตูจาก Dashboard</b>\n\n";
            $message .= "👤 ผู้ใช้: " . htmlspecialchars($username) . "\n";
            $message .= "📅 เวลา: " . date('d/m/Y H:i:s');
            sendTelegram($message, 'gate', true); // force send
        }
        echo "OK";
    }
} else if ($action == 'get') {
    $headers = apache_request_headers();
    $received_key = isset($headers['X-API-KEY']) ? $headers['X-API-KEY'] : '';
    verifyAPI($conn, $received_key);

    $res = $conn->query("SELECT * FROM system_control WHERE id = 1");
    $row = $res->fetch_assoc();
    echo "F_M:".$row['fan_manual'].",F_S:".$row['fan_state'].",B_M:".$row['buzzer_manual'].",B_S:".$row['buzzer_state'].",G_T:".$row['gate_trigger'].",R_W:".$row['reset_wifi'];
}
$conn->close();
?>