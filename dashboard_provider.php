<?php
require_once 'auth.php';
require_once 'db.php';

if ($_SESSION['role'] != 'provider') {
    header("Location: dashboard_{$_SESSION['role']}.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get provider_id
$provider_result = $conn->query("SELECT provider_id FROM providers WHERE user_id = $user_id");
$provider_id = null;
if ($provider_result->num_rows > 0) {
    $provider_id = $provider_result->fetch_assoc()['provider_id'];
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    switch ($_GET['action']) {
        case 'get_devices':
            if (!$provider_id) {
                echo json_encode([]);
                exit();
            }
            $sql = "SELECT * FROM devices WHERE provider_id = $provider_id ORDER BY device_id DESC";
            $result = $conn->query($sql);
            $devices = [];
            while ($row = $result->fetch_assoc()) {
                $devices[] = $row;
            }
            echo json_encode($devices);
            exit();

        case 'add_device':
            if (!$provider_id) {
                echo json_encode(['success' => false, 'error' => 'Provider not registered']);
                exit();
            }

            $device_name = $conn->real_escape_string($_POST['device_name']);
            $compatibility_version = $conn->real_escape_string($_POST['compatibility_version']);

            $sql = "INSERT INTO devices (device_name, provider_id, compatibility_version) 
                    VALUES ('$device_name', $provider_id, '$compatibility_version')";
            if ($conn->query($sql)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
            exit();

        case 'update_device':
            if (!$provider_id) {
                echo json_encode(['success' => false, 'error' => 'Provider not registered']);
                exit();
            }

            $device_id = intval($_POST['device_id']);
            $compatibility_version = $conn->real_escape_string($_POST['compatibility_version']);

            $sql = "UPDATE devices SET compatibility_version = '$compatibility_version' 
                    WHERE device_id = $device_id AND provider_id = $provider_id";
            if ($conn->query($sql)) {
                $conn->query("INSERT INTO activity_logs (user_id, device_id, action, executed_by) 
                             VALUES ($user_id, $device_id, 'UPDATED', 'provider')");
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
            exit();

        case 'add_update':
            if (!$provider_id) {
                echo json_encode(['success' => false, 'error' => 'Provider not registered']);
                exit();
            }

            $device_id = intval($_POST['device_id']);
            $update_description = $conn->real_escape_string($_POST['update_description']);

            $sql = "INSERT INTO updates (provider_id, device_id, update_description) 
                    VALUES ($provider_id, $device_id, '$update_description')";
            if ($conn->query($sql)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
            exit();

        case 'get_updates':
            if (!$provider_id) {
                echo json_encode([]);
                exit();
            }
            $sql = "SELECT u.*, d.device_name 
                    FROM updates u
                    JOIN devices d ON u.device_id = d.device_id
                    WHERE u.provider_id = $provider_id
                    ORDER BY u.update_date DESC";
            $result = $conn->query($sql);
            $updates = [];
            while ($row = $result->fetch_assoc()) {
                $updates[] = $row;
            }
            echo json_encode($updates);
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provider Dashboard - Smart Home</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="dashboard">
        <nav class="navbar">
            <h1>🏠 Smart Home - Provider</h1>
            <div class="nav-right">
                <span class="user-info">👤 <?php echo $_SESSION['name']; ?></span>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </nav>

        <div class="container">
            <div class="tabs">
                <button class="tab-btn active" data-tab="devices">My Devices</button>
                <button class="tab-btn" data-tab="updates">Updates</button>
            </div>

            <!-- Devices Tab -->
            <div class="tab-content active" id="devices-tab">
                <h2>Add New Device</h2>
                <div class="card">
                    <form id="add-device-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Device Name</label>
                                <input type="text" id="device-name" required placeholder="e.g., Smart LED Bulb">
                            </div>
                            <div class="form-group">
                                <label>Compatibility Version</label>
                                <input type="text" id="device-version" required placeholder="e.g., v1.0">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Device</button>
                    </form>
                </div>

                <h3>My Registered Devices</h3>
                <div id="devices-list" class="card-grid"></div>
            </div>

            <!-- Updates Tab -->
            <div class="tab-content" id="updates-tab">
                <h2>Post Update</h2>
                <div class="card">
                    <form id="add-update-form">
                        <div class="form-group">
                            <label>Select Device</label>
                            <select id="update-device" required>
                                <option value="">Choose device...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Update Description</label>
                            <textarea id="update-description" rows="3" required
                                placeholder="e.g., Firmware v2.0 released with bug fixes"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Post Update</button>
                    </form>
                </div>

                <h3>Published Updates</h3>
                <div id="updates-list" class="table-container"></div>
            </div>
        </div>
    </div>

    <div id="toast" class="toast"></div>
    <script src="script.js"></script>
    <script>
        const role = 'provider';
        loadProviderDevices();
        loadUpdates();

        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(btn.dataset.tab + '-tab').classList.add('active');
            });
        });

        // Add device form
        document.getElementById('add-device-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData();
            formData.append('device_name', document.getElementById('device-name').value);
            formData.append('compatibility_version', document.getElementById('device-version').value);

            const response = await fetch('?action=add_device', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                showToast('Device added successfully!', 'success');
                loadProviderDevices();
                e.target.reset();
            } else {
                showToast(data.error, 'error');
            }
        });

        // Add update form
        document.getElementById('add-update-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData();
            formData.append('device_id', document.getElementById('update-device').value);
            formData.append('update_description', document.getElementById('update-description').value);

            const response = await fetch('?action=add_update', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                showToast('Update posted successfully!', 'success');
                loadUpdates();
                e.target.reset();
            } else {
                showToast(data.error, 'error');
            }
        });
    </script>
</body>

</html>