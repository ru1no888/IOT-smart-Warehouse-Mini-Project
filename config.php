<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php'; 

// Load .env file
function loadEnv($path = __DIR__ . '/.env') {
    if (!file_exists($path)) {
        // ถ้าไม่มี .env ให้ใช้ค่า default
        $_ENV['API_KEY'] = 'MY_SECRET_KEY_888';
        $_ENV['TELEGRAM_BOT_TOKEN'] = '';
        $_ENV['TELEGRAM_CHAT_ID'] = '';
        $_ENV['SESSION_TIMEOUT'] = 1800; // เพิ่มเป็น 30 นาที
        $_ENV['RATE_LIMIT_PER_SECOND'] = 3;
        $_ENV['TELEGRAM_COOLDOWN_SECONDS'] = 60;
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) == 2) {
            $_ENV[trim($parts[0])] = trim($parts[1]);
        }
    }
}
loadEnv();

$api_key_secure = $_ENV['API_KEY']; 

// --- ระบบ Auto Logout 5 นาที ---
$timeout = isset($_ENV['SESSION_TIMEOUT']) ? intval($_ENV['SESSION_TIMEOUT']) : 300;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
$_SESSION['last_activity'] = time();
// ----------------------------

// --- ระบบ Rate Limit ป้องกันการยิง Request รัวๆ ---
function checkRateLimit($ip) {
    $limit = isset($_ENV['RATE_LIMIT_PER_SECOND']) ? intval($_ENV['RATE_LIMIT_PER_SECOND']) : 10;
    $file = sys_get_temp_dir() . '/rate_limit_' . md5($ip) . '.txt';
    $now = time();

    if (file_exists($file)) {
        $data = explode('|', file_get_contents($file));
        if ($data[0] == $now) {
            if ($data[1] >= $limit) {
                // Log rate limit exceeded
                if (function_exists('logSecurityEvent')) {
                    logSecurityEvent('rate_limit', "Rate limit exceeded from IP: $ip (count: {$data[1]}/$limit)", 'blocked');
                }
                error_log("[🚫 Rate Limit] IP: $ip exceeded $limit requests/second");
                http_response_code(429);
                header('Content-Type: application/json');
                die(json_encode(["error" => "Rate Limit Exceeded", "message" => "รอ {$limit} วินาทีก่อนลองใหม่"]));
            }
            $count = $data[1] + 1;
        } else {
            $count = 1;
        }
    } else {
        $count = 1;
    }
    file_put_contents($file, $now . '|' . $count);
}

function verifyAPI($conn, $received_key) {
    global $api_key_secure;
    
    // ตรวจสอบ Rate Limit ก่อน (ดึง IP คนส่ง)
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    checkRateLimit($ip);

    if ($received_key !== $api_key_secure) {
        // Log unauthorized access
        if (function_exists('logSecurityEvent')) {
            logSecurityEvent('api', "Unauthorized API access with key: $received_key", 'failed');
        }
        http_response_code(403);
        die(json_encode(["error" => "Unauthorized: Invalid API Key"]));
    }
    
    // Log successful API access
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent('api', "Valid API access from IP: $ip", 'success');
    }
}

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            http_response_code(401);
            die(json_encode(["error" => "Unauthorized"]));
        }
        header("Location: login.php");
        exit();
    }
}

// --- ระบบ CSRF Protection ---
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>