<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "databasewarehouse"; // แก้ให้ตรงตามรูป image_c00b6b.png แล้วครับ

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}
$conn->set_charset("utf8");
?>