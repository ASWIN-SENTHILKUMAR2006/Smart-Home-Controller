<?php
require_once 'auth.php';
require_once 'db.php';

// Check if user is admin
if ($_SESSION['role'] != 'admin') {
    header("Location: dashboard_{$_SESSION['role']}.php");
    exit();
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    switch ($_GET['action']) {
        case 'get_users':
            $result = $conn->query("SELECT user_id, name, email, role FROM users ORDER BY created_at DESC");
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            echo json_encode($users);
            exit();

        case 'get_devices':
            $sql = "SELECT d.*, p.company_name 
                    FROM devices d 
                    LEFT JOIN providers p ON d.provider_id = p.provider_id 
                    ORDER BY d.device_id DESC";
            $result = $conn->query($sql);
            $devices = [];
            while ($row = $result->fetch_assoc()) {
                $devices[] = $row;
            }
            echo json_encode($devices);
            exit();

        case 'get_assignments':
            $sql = "SELECT da.*, u.name as user_name, d.device_name 
                    FROM device_assignments da
                    JOIN users u ON da.user_id = u.user_id
                    JOIN devices d ON da.device_id = d.device_id
                    ORDER BY da.assignment_id DESC";
            $result = $conn->query($sql);
            $assignments = [];
            while ($row = $result->fetch_assoc()) {
                $assignments[] = $row;
            }
            echo json_encode($assignments);
            exit();

        case 'get_logs':
            $sql = "SELECT al.*, u.name as user_name, d.device_name 
                    FROM activity_logs al
                    LEFT JOIN users u ON al.user_id = u.user_id
                    LEFT JOIN devices d ON al.device_id = d.device_id
                    ORDER BY al.timestamp DESC LIMIT 50";
            $result = $conn->query($sql);
            $logs = [];
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
            echo json_encode($logs);
            exit();

        case 'delete_user':
            $user_id = intval($_POST['user_id']);
            if ($conn->query("DELETE FROM users WHERE user_id = $user_id AND role != 'admin'")) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
            exit();

        case 'assign_device':
            $user_id = intval($_POST['user_id']);
            $device_id = intval($_POST['device_id']);

            // Check if already assigned
            $check = $conn->query("SELECT * FROM device_assignments WHERE user_id = $user_id AND device_id = $device_id");
            if ($check->num_rows > 0) {
                echo json_encode(['success' => false, 'error' => 'Device already assigned to this user']);
                exit();
            }

            $sql = "INSERT INTO device_assignments (user_id, device_id) VALUES ($user_id, $device_id)";
            if ($conn->query($sql)) {
                // Log activity
                $admin_id = $_SESSION['user_id'];
                $conn->query("INSERT INTO activity_logs (user_id, device_id, action, executed_by) 
                             VALUES ($admin_id, $device_id, 'ADDED', 'admin')");
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
            exit();

        case 'remove_assignment':
            $assignment_id = intval($_POST['assignment_id']);
            if ($conn->query("DELETE FROM device_assignments WHERE assignment_id = $assignment_id")) {
                $admin_id = $_SESSION['user_id'];
                $conn->query("INSERT INTO activity_logs (user_id, device_id, action, executed_by) 
                             VALUES ($admin_id, NULL, 'REMOVED', 'admin')");
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Home</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="dashboard">
        <nav class="navbar">
            <h1>🏠 Smart Home - Admin</h1>
            <div class="nav-right">
                <span class="user-info">👤 <?php echo $_SESSION['name']; ?></span>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </nav>

        <div class="container">
            <div class="tabs">
                <button class="tab-btn active" data-tab="users">Users</button>
                <button class="tab-btn" data-tab="devices">Devices</button>
                <button class="tab-btn" data-tab="assignments">Assignments</button>
                <button class="tab-btn" data-tab="logs">Activity Logs</button>
            </div>

            <!-- Users Tab -->
            <div class="tab-content active" id="users-tab">
                <h2>Manage Users</h2>
                <div id="users-list" class="card-grid"></div>
            </div>

            <!-- Devices Tab -->
            <div class="tab-content" id="devices-tab">
                <h2>All Devices</h2>
                <div id="devices-list" class="card-grid"></div>
            </div>

            <!-- Assignments Tab -->
            <div class="tab-content" id="assignments-tab">
                <h2>Assign Device to User</h2>
                <div class="card">
                    <form id="assign-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Select User</label>
                                <select id="assign-user" required>
                                    <option value="">Choose user...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Select Device</label>
                                <select id="assign-device" required>
                                    <option value="">Choose device...</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Assign Device</button>
                    </form>
                </div>

                <h3>Current Assignments</h3>
                <div id="assignments-list" class="table-container"></div>
            </div>

            <!-- Logs Tab -->
            <div class="tab-content" id="logs-tab">
                <h2>Activity Logs</h2>
                <div id="logs-list" class="table-container"></div>
            </div>
        </div>
    </div>

    <div id="toast" class="toast"></div>
    <script src="script.js"></script>
    <script>
        const role = 'admin';
        loadUsers();
        loadDevices();
        loadAssignments();
        loadLogs();

        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(btn.dataset.tab + '-tab').classList.add('active');
            });
        });

        // Assign form
        document.getElementById('assign-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const user_id = document.getElementById('assign-user').value;
            const device_id = document.getElementById('assign-device').value;

            const formData = new FormData();
            formData.append('user_id', user_id);
            formData.append('device_id', device_id);

            const response = await fetch('?action=assign_device', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                showToast('Device assigned successfully!', 'success');
                loadAssignments();
                e.target.reset();
            } else {
                showToast(data.error, 'error');
            }
        });
    </script>
</body>

</html>