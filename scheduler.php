<?php
require_once 'db.php';

header('Content-Type: application/json');

// Get current time and time 30 seconds from now
$now = date('Y-m-d H:i:s');
$next_30_seconds = date('Y-m-d H:i:s', strtotime('+30 seconds'));

// Find schedules that should be executed (within next 30 seconds)
$sql = "SELECT s.*, d.device_name 
        FROM schedules s
        JOIN devices d ON s.device_id = d.device_id
        WHERE s.status = 'pending' 
        AND s.scheduled_time <= '$next_30_seconds'
        AND s.scheduled_time >= '$now'";

$result = $conn->query($sql);
$executed = [];
$errors = [];

if ($result->num_rows > 0) {
    while ($schedule = $result->fetch_assoc()) {
        $schedule_id = $schedule['schedule_id'];
        $device_id = $schedule['device_id'];
        $user_id = $schedule['user_id'];
        $action = $schedule['action'];

        // Update device status
        $update_sql = "UPDATE devices SET status = '$action' WHERE device_id = $device_id";
        if ($conn->query($update_sql)) {
            // Mark schedule as executed
            $conn->query("UPDATE schedules SET status = 'executed', executed_by = 'scheduler' 
                         WHERE schedule_id = $schedule_id");

            // Log activity
            $conn->query("INSERT INTO activity_logs (user_id, device_id, action, executed_by) 
                         VALUES ($user_id, $device_id, '$action', 'scheduler')");

            $executed[] = [
                'schedule_id' => $schedule_id,
                'device_name' => $schedule['device_name'],
                'action' => $action
            ];
        } else {
            $errors[] = [
                'schedule_id' => $schedule_id,
                'error' => $conn->error
            ];
        }
    }
}

echo json_encode([
    'success' => true,
    'executed_count' => count($executed),
    'executed' => $executed,
    'errors' => $errors,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>