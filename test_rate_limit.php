<?php
/**
 * 🧪 Rate Limit Test Script
 * ใช้ทดสอบว่า Rate Limiting ทำงานได้ถูกต้อง
 * 
 * วิธีใช้:
 * 1. เปิด Browser: http://localhost/IOT-finalproject/test_rate_limit.php
 * 2. หรือรัน: php test_rate_limit.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// โหลด Rate Limit Config
require_once 'config.php';

$isWeb = php_sapi_name() !== 'cli';

if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Rate Limit Test</title>";
    echo "<style>
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; background: #1a1a2e; color: #fff; }
        .success { color: #00ff88; }
        .blocked { color: #ff4757; }
        .info { color: #f1c40f; }
        pre { background: #16213e; padding: 15px; border-radius: 8px; overflow-x: auto; }
        button { padding: 12px 24px; background: #0e8bf1; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px; font-size: 14px; }
        button:hover { background: #0965b5; }
        #results { margin-top: 20px; }
        .test-card { background: #16213e; padding: 15px; border-radius: 8px; margin: 10px 0; }
    </style></head><body>";
    echo "<h1>🧪 Rate Limit Test Tool</h1>";
    
    $rateLimit = isset($_ENV['RATE_LIMIT_PER_SECOND']) ? intval($_ENV['RATE_LIMIT_PER_SECOND']) : 10;
    echo "<div class='test-card'>";
    echo "<p class='info'>📋 <strong>Rate Limit Config:</strong> {$rateLimit} requests/second</p>";
    echo "<p class='info'>💡 Tip: กดปุ่มรัวๆ เพื่อทดสอบว่า Rate Limit ทำงาน</p>";
    echo "</div>";
}

// ========= TEST: ทดสอบการยิง requests รัวๆ =========
function testRapidRequests($endpoint, $numRequests = 10) {
    global $isWeb;
    
    $baseUrl = "http://127.0.0.1/IOT-finalproject/";
    $url = $baseUrl . $endpoint;
    
    $results = [];
    $blocked = 0;
    $success = 0;
    
    for ($i = 1; $i <= $numRequests; $i++) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Cookie: PHPSESSID=' . session_id() // ส่ง session เดียวกัน
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $isBlocked = ($httpCode == 429);
        if ($isBlocked) $blocked++; else $success++;
        
        $results[] = [
            'request' => $i,
            'status' => $httpCode,
            'blocked' => $isBlocked,
            'response' => substr($response, 0, 100)
        ];
    }
    
    return ['results' => $results, 'blocked' => $blocked, 'success' => $success];
}

// ========= AJAX Handler =========
if (isset($_GET['ajax_test'])) {
    header('Content-Type: application/json');
    $endpoint = $_GET['endpoint'] ?? 'get_data.php';
    $count = intval($_GET['count'] ?? 10);
    
    $testResult = testRapidRequests($endpoint, $count);
    echo json_encode($testResult);
    exit;
}

// ========= Simple Counter Test =========
if (isset($_GET['counter_test'])) {
    header('Content-Type: application/json');
    
    // ตรวจสอบว่าถูก rate limit หรือยัง
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $limit = isset($_ENV['RATE_LIMIT_PER_SECOND']) ? intval($_ENV['RATE_LIMIT_PER_SECOND']) : 10;
    $file = sys_get_temp_dir() . '/rate_limit_' . md5($ip) . '.txt';
    
    $count = 0;
    $second = 0;
    if (file_exists($file)) {
        $data = explode('|', file_get_contents($file));
        $second = $data[0];
        $count = $data[1];
    }
    
    echo json_encode([
        'ip' => $ip,
        'limit' => $limit,
        'current_count' => $count,
        'current_second' => $second,
        'now' => time(),
        'would_block' => ($count >= $limit && $second == time())
    ]);
    exit;
}

// ========= Main Web UI =========
if ($isWeb) {
?>
<div class="test-card">
    <h3>🎯 Test 1: กดปุ่มยิง Request รัวๆ</h3>
    <p>กดปุ่มด้านล่างหลายๆ ครั้งติดๆ กัน เพื่อดูว่าถูก Block หรือไม่</p>
    <button onclick="testClick()">🚀 Click Me! (ยิง get_data.php)</button>
    <button onclick="testLoginPage()">🔐 Test Login Page</button>
    <span id="clickCounter" style="margin-left: 15px; font-size: 18px;">Clicks: 0 | Blocked: 0</span>
</div>

<div class="test-card">
    <h3>⚡ Test 2: Rapid Fire Test (Auto)</h3>
    <p>ยิง 15 requests รวดเดียว เพื่อทดสอบ rate limit</p>
    <button onclick="rapidFire('get_data.php', 15)">🔥 Rapid Fire: get_data.php</button>
    <button onclick="rapidFire('login.php', 15)">🔥 Rapid Fire: login.php</button>
</div>

<div class="test-card">
    <h3>📊 Current Rate Limit Status</h3>
    <button onclick="checkStatus()">🔄 Refresh Status</button>
    <pre id="statusOutput">กดปุ่มเพื่อดูสถานะ...</pre>
</div>

<div id="results">
    <h3>📋 Test Results:</h3>
    <pre id="resultsOutput">ยังไม่มีผลลัพธ์...</pre>
</div>

<script>
let clickCount = 0;
let blockedCount = 0;

function updateCounter() {
    document.getElementById('clickCounter').innerHTML = 
        `Clicks: <span class="success">${clickCount}</span> | Blocked: <span class="blocked">${blockedCount}</span>`;
}

async function testClick() {
    clickCount++;
    try {
        const res = await fetch('get_data.php');
        const status = res.status;
        
        if (status === 429) {
            blockedCount++;
            log(`❌ Request #${clickCount}: BLOCKED (429 Too Many Requests)`);
        } else {
            log(`✅ Request #${clickCount}: OK (${status})`);
        }
    } catch (e) {
        log(`⚠️ Request #${clickCount}: Error - ${e.message}`);
    }
    updateCounter();
}

async function testLoginPage() {
    clickCount++;
    try {
        const res = await fetch('login.php');
        const status = res.status;
        
        if (status === 429) {
            blockedCount++;
            log(`❌ Login Request #${clickCount}: BLOCKED (429)`);
        } else {
            log(`✅ Login Request #${clickCount}: OK (${status})`);
        }
    } catch (e) {
        log(`⚠️ Login Request #${clickCount}: Error - ${e.message}`);
    }
    updateCounter();
}

async function rapidFire(endpoint, count) {
    log(`🔥 Starting Rapid Fire Test: ${endpoint} x ${count}`);
    
    const results = [];
    for (let i = 1; i <= count; i++) {
        try {
            const res = await fetch(endpoint);
            const status = res.status;
            const isBlocked = status === 429;
            results.push({ request: i, status, blocked: isBlocked });
            
            if (isBlocked) {
                log(`  ❌ #${i}: BLOCKED (429)`);
            } else {
                log(`  ✅ #${i}: OK (${status})`);
            }
        } catch (e) {
            log(`  ⚠️ #${i}: Error - ${e.message}`);
        }
    }
    
    const blocked = results.filter(r => r.blocked).length;
    const success = results.filter(r => !r.blocked).length;
    log(`📊 Summary: ${success} success, ${blocked} blocked`);
    
    if (blocked > 0) {
        log(`✅ Rate Limiting is WORKING!`);
    } else {
        log(`⚠️ Rate Limiting might not be working - no requests were blocked`);
    }
}

async function checkStatus() {
    try {
        const res = await fetch('test_rate_limit.php?counter_test=1');
        const data = await res.json();
        document.getElementById('statusOutput').innerHTML = JSON.stringify(data, null, 2);
    } catch (e) {
        document.getElementById('statusOutput').innerHTML = 'Error: ' + e.message;
    }
}

function log(msg) {
    const output = document.getElementById('resultsOutput');
    const time = new Date().toLocaleTimeString();
    output.innerHTML = `[${time}] ${msg}\n` + output.innerHTML;
}

// Check status on load
checkStatus();
</script>

<?php
    echo "</body></html>";
} else {
    // CLI Mode
    echo "🧪 Rate Limit CLI Test\n";
    echo "=====================\n";
    
    $rateLimit = isset($_ENV['RATE_LIMIT_PER_SECOND']) ? intval($_ENV['RATE_LIMIT_PER_SECOND']) : 10;
    echo "Rate Limit: {$rateLimit} requests/second\n\n";
    
    echo "Testing rapid requests to get_data.php...\n";
    $result = testRapidRequests('get_data.php', 10);
    
    echo "Results:\n";
    foreach ($result['results'] as $r) {
        $status = $r['blocked'] ? "❌ BLOCKED" : "✅ OK";
        echo "  Request #{$r['request']}: HTTP {$r['status']} - {$status}\n";
    }
    
    echo "\nSummary: {$result['success']} success, {$result['blocked']} blocked\n";
    
    if ($result['blocked'] > 0) {
        echo "✅ Rate Limiting is WORKING!\n";
    } else {
        echo "⚠️ Rate Limiting might need adjustment\n";
    }
}
?>
