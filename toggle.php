<?php
// toggle.php
require_once 'db.php';
session_start();
// allow admin or user (provider cannot toggle)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'not logged']);
    exit();
}
$uid = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';

$data = [];
parse_str(file_get_contents("php://input"), $data);
$device_id = intval($data['device_id'] ?? 0);
if (!$device_id) {
    echo json_encode(['ok' => false, 'msg' => 'bad device']);
    exit();
}

// permission check
$allowed = false;
if ($role === 'admin')
    $allowed = true;
elseif ($role === 'user') {
    $stmt = mysqli_prepare($conn, "SELECT assignment_id FROM device_assignments WHERE user_id = ? AND device_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $uid, $device_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0)
        $allowed = true;
    mysqli_stmt_close($stmt);
}

if (!$allowed) {
    echo json_encode(['ok' => false, 'msg' => 'not allowed']);
    exit();
}

// toggle safely
$stmt = mysqli_prepare($conn, "SELECT status FROM devices WHERE device_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $device_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $status);
if (!mysqli_stmt_fetch($stmt)) {
    echo json_encode(['ok' => false, 'msg' => 'device not found']);
    exit();
}
mysqli_stmt_close($stmt);

$new = ($status === 'ON') ? 'OFF' : 'ON';
$ust = mysqli_prepare($conn, "UPDATE devices SET status = ? WHERE device_id = ?");
mysqli_stmt_bind_param($ust, 'si', $new, $device_id);
mysqli_stmt_execute($ust);
mysqli_stmt_close($ust);

// log action
$actor_role = ($role === 'admin') ? 'admin' : 'user';
$stmtL = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, device_id, action, executed_by) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmtL, 'iiss', $uid, $device_id, $new, $actor_role);
mysqli_stmt_execute($stmtL);
mysqli_stmt_close($stmtL);

echo json_encode(['ok' => true, 'status' => $new]);