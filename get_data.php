<?php
require 'config.php';
checkAuth(); 

// ✅ เพิ่ม Rate Limiting สำหรับ Dashboard
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
checkRateLimit($ip);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require 'db.php';

$res_latest = $conn->query("SELECT temp_value, hum_value, timestamp FROM warehouse_logs ORDER BY id DESC LIMIT 1");
$latest = $res_latest->fetch_assoc();

$res_status = $conn->query("SELECT fan_manual, fan_state, buzzer_manual, buzzer_state, gate_trigger FROM system_control WHERE id = 1");
$status = $res_status->fetch_assoc();

// เพิ่มเป็น 30-50 แถว เพื่อให้ตาราง Log มีข้อมูลย้อนหลังดูเท่ๆ
$res_graph = $conn->query("SELECT temp_value, hum_value, timestamp FROM (SELECT id, temp_value, hum_value, timestamp FROM warehouse_logs ORDER BY id DESC LIMIT 30) var ORDER BY id ASC");

$graph = [];
if ($res_graph) {
    while($row = $res_graph->fetch_assoc()) { 
        $graph[] = $row; 
    }
}

echo json_encode(["latest" => $latest, "status" => $status, "graph" => $graph]);
$conn->close();
?>