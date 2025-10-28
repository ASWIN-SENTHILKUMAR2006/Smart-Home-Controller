<?php
require_once 'auth.php';
require_once 'db.php';

if ($_SESSION['role'] != 'user') {
    header("Location: dashboard_{$_SESSION['role']}.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    switch ($_GET['action']) {
        case 'get_devices':
            $sql = "SELECT d.*, da.assignment_id 
                    FROM devices d
                    JOIN device_assignments da ON d.device_id = da.device_id
                    WHERE da.user_id = $user_id
                    ORDER BY d.device_name";
            $result = $conn->query($sql);
            $devices = [];
            while ($row = $result->fetch_assoc()) {
                $devices[] = $row;
            }
            echo json_encode($devices);
            exit();

        case 'toggle_device':
            $device_id = intval($_POST['device_id']);
            $new_status = $_POST['status'];

            // Check if device is assigned to user
            $check = $conn->query("SELECT * FROM device_assignments WHERE user_id = $user_id AND device_id = $device_id");
            if ($check->num_rows == 0) {
                echo json_encode(['success' => false, 'error' => 'Device not assigned to you']);
                exit();
            }

            $sql = "UPDATE devices SET status = '$new_status' WHERE device_id = $device_id";
            if ($conn->query($sql)) {
                // Log activity
                $conn->query("INSERT INTO activity_logs (user_id, device_id, action, executed_by) 
                             VALUES ($user_id, $device_id, '$new_status', 'user')");
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
            exit();

        case 'add_schedule':
            $device_id = intval($_POST['device_id']);
            $action = $_POST['action'];
            $scheduled_time = $_POST['scheduled_time'];

            // Check if device is assigned to user
            $check = $conn->query("SELECT * FROM device_assignments WHERE user_id = $user_id AND device_id = $device_id");
            if ($check->num_rows == 0) {
                echo json_encode(['success' => false, 'error' => 'Device not assigned to you']);
                exit();
            }

            $sql = "INSERT INTO schedules (user_id, device_id, action, scheduled_time) 
                    VALUES ($user_id, $device_id, '$action', '$scheduled_time')";
            if ($conn->query($sql)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
            exit();

        case 'get_schedules':
            $sql = "SELECT s.*, d.device_name 
                    FROM schedules s
                    JOIN devices d ON s.device_id = d.device_id
                    WHERE s.user_id = $user_id
                    ORDER BY s.scheduled_time DESC";
            $result = $conn->query($sql);
            $schedules = [];
            while ($row = $result->fetch_assoc()) {
                $schedules[] = $row;
            }
            echo json_encode($schedules);
            exit();

        case 'delete_schedule':
            $schedule_id = intval($_POST['schedule_id']);
            $sql = "DELETE FROM schedules WHERE schedule_id = $schedule_id AND user_id = $user_id";
            if ($conn->query($sql)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
            exit();

        case 'get_logs':
            $sql = "SELECT al.*, d.device_name 
                    FROM activity_logs al
                    LEFT JOIN devices d ON al.device_id = d.device_id
                    WHERE al.user_id = $user_id
                    ORDER BY al.timestamp DESC LIMIT 20";
            $result = $conn->query($sql);
            $logs = [];
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
            echo json_encode($logs);
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Smart Home</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="dashboard">
        <nav class="navbar">
            <h1>🏠 Smart Home</h1>
            <div class="nav-right">
                <span class="user-info">👤 <?php echo $_SESSION['name']; ?></span>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </nav>

        <div class="container">
            <div class="tabs">
                <button class="tab-btn active" data-tab="devices">My Devices</button>
                <button class="tab-btn" data-tab="schedules">Schedules</button>
                <button class="tab-btn" data-tab="logs">My Activity</button>
            </div>

            <!-- Devices Tab -->
            <div class="tab-content active" id="devices-tab">
                <h2>Control Your Devices</h2>
                <div id="devices-list" class="card-grid"></div>
            </div>

            <!-- Schedules Tab -->
            <div class="tab-content" id="schedules-tab">
                <h2>Schedule Device Action</h2>
                <div class="card">
                    <form id="schedule-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Select Device</label>
                                <select id="schedule-device" required>
                                    <option value="">Choose device...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Action</label>
                                <select id="schedule-action" required>
                                    <option value="ON">Turn ON</option>
                                    <option value="OFF">Turn OFF</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Scheduled Time</label>
                                <input type="datetime-local" id="schedule-time" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Schedule Action</button>
                    </form>
                </div>

                <h3>Upcoming Schedules</h3>
                <div id="schedules-list" class="table-container"></div>
            </div>

            <!-- Logs Tab -->
            <div class="tab-content" id="logs-tab">
                <h2>My Activity History</h2>
                <div id="logs-list" class="table-container"></div>
            </div>
        </div>
    </div>

    <div id="toast" class="toast"></div>
    <script src="script.js"></script>
    <script>
        const role = 'user';
        loadUserDevices();
        loadSchedules();
        loadLogs();

        // Start scheduler polling
        startSchedulerPolling();

        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(btn.dataset.tab + '-tab').classList.add('active');
            });
        });

        // Schedule form
        document.getElementById('schedule-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData();
            formData.append('device_id', document.getElementById('schedule-device').value);
            formData.append('action', document.getElementById('schedule-action').value);
            formData.append('scheduled_time', document.getElementById('schedule-time').value);

            const response = await fetch('?action=add_schedule', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                showToast('Schedule created successfully!', 'success');
                loadSchedules();
                e.target.reset();
            } else {
                showToast(data.error, 'error');
            }
        });
    </script>
</body>

</html>