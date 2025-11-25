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
            <div class="tab-content " id="schedules-tab">
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
        // document.addEventListener('DOMContentLoaded', function () {
        //     // 1. Get the table element directly by its ID.
        //     const table = document.getElementById('user_table_hide');

        //     if (table) {
        //         // 2. Select all rows (<tr>) within that table.
        //         const rows = table.querySelectorAll('tr');

        //         // 3. Loop through each row found.
        //         rows.forEach(function (row) {
        //             // 4. Select the FIRST *ELEMENT* CHILD (which is guaranteed to be <th> or <td>),
        //             // ignoring potential whitespace/text nodes.
        //             const firstCell = row.firstElementChild;

        //             // 5. Hide the cell if it exists.
        //             if (firstCell) {
        //                 firstCell.style.display = 'none';
        //             }
        //         });
        //     }
        // });
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
<!-- ========================================
     🤖 AI CHATBOT WIDGET
     Copy this entire block and paste it BEFORE the closing </body> tag
     in any dashboard file (dashboard_user.php, dashboard_admin.php, dashboard_provider.php)
     ======================================== -->

<!-- Chatbot Floating Button -->
<div id="chatbot-toggle" class="chatbot-toggle">
    🤖 AI Assistant
</div>

<!-- Chatbot Window -->
<div id="chatbot-window" class="chatbot-window">
    <div class="chatbot-header">
        <h3>🤖 Smart Home AI Assistant</h3>
        <button id="chatbot-close" class="chatbot-close-btn">&times;</button>
    </div>

    <div id="chatbot-messages" class="chatbot-messages">
        <div class="chatbot-message bot-message">
            <strong>AI Assistant:</strong>
            <p>Hi! 👋 I'm your Smart Home AI Assistant. I can help you with:</p>
            <ul>
                <li>Device control tips</li>
                <li>Scheduling recommendations</li>
                <li>Energy saving advice</li>
                <li>Troubleshooting</li>
            </ul>
            <p>Ask me anything about your smart home!</p>
        </div>
    </div>

    <div class="chatbot-input-area">
        <input type="text" id="chatbot-input" placeholder="Ask me anything..." />
        <button id="chatbot-send" class="btn btn-primary">Send</button>
    </div>
</div>

<!-- Chatbot Styles -->
<style>
    /* Floating Toggle Button */
    .chatbot-toggle {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 25px;
        border-radius: 50px;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        cursor: pointer;
        font-weight: 600;
        z-index: 999;
        transition: all 0.3s;
    }

    .chatbot-toggle:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
    }

    /* Chatbot Window */
    .chatbot-window {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 400px;
        height: 600px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        display: none;
        flex-direction: column;
        z-index: 1000;
        overflow: hidden;
    }

    .chatbot-window.active {
        display: flex;
    }

    /* Header */
    .chatbot-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chatbot-header h3 {
        margin: 0;
        font-size: 1.2em;
    }

    .chatbot-close-btn {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }

    .chatbot-close-btn:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    /* Messages Area */
    .chatbot-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        background: #f9fafb;
    }

    .chatbot-message {
        margin-bottom: 15px;
        padding: 12px 15px;
        border-radius: 12px;
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .user-message {
        background: #667eea;
        color: white;
        margin-left: 20%;
        text-align: right;
    }

    .bot-message {
        background: white;
        color: #333;
        border: 1px solid #e5e7eb;
        margin-right: 20%;
    }

    .bot-message strong {
        color: #667eea;
        display: block;
        margin-bottom: 5px;
    }

    .bot-message ul {
        margin: 10px 0;
        padding-left: 20px;
    }

    .bot-message li {
        margin: 5px 0;
    }

    .chatbot-typing {
        background: white;
        border: 1px solid #e5e7eb;
        padding: 12px 15px;
        border-radius: 12px;
        margin-right: 20%;
        display: none;
    }

    .chatbot-typing.active {
        display: block;
    }

    .typing-indicator {
        display: flex;
        gap: 5px;
    }

    .typing-dot {
        width: 8px;
        height: 8px;
        background: #667eea;
        border-radius: 50%;
        animation: typing 1.4s infinite;
    }

    .typing-dot:nth-child(2) {
        animation-delay: 0.2s;
    }

    .typing-dot:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes typing {

        0%,
        60%,
        100% {
            transform: translateY(0);
        }

        30% {
            transform: translateY(-10px);
        }
    }

    /* Input Area */
    .chatbot-input-area {
        padding: 15px;
        background: white;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 10px;
    }

    #chatbot-input {
        flex: 1;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 14px;
    }

    #chatbot-input:focus {
        outline: none;
        border-color: #667eea;
    }

    #chatbot-send {
        padding: 12px 20px;
        white-space: nowrap;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .chatbot-window {
            width: 90%;
            height: 80%;
            bottom: 10px;
            right: 5%;
        }

        .chatbot-toggle {
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
        }
    }
</style>

<!-- Chatbot JavaScript -->
<script>
    (function () {
        const toggle = document.getElementById('chatbot-toggle');
        const window = document.getElementById('chatbot-window');
        const closeBtn = document.getElementById('chatbot-close');
        const input = document.getElementById('chatbot-input');
        const sendBtn = document.getElementById('chatbot-send');
        const messagesContainer = document.getElementById('chatbot-messages');

        // Toggle chatbot
        toggle.addEventListener('click', () => {
            window.classList.add('active');
            toggle.style.display = 'none';
            input.focus();
        });

        // Close chatbot
        closeBtn.addEventListener('click', () => {
            window.classList.remove('active');
            toggle.style.display = 'block';
        });

        // Send message
        async function sendMessage() {
            const message = input.value.trim();
            if (!message) return;

            // Add user message
            addMessage(message, 'user');
            input.value = '';

            // Show typing indicator
            const typingDiv = document.createElement('div');
            typingDiv.className = 'chatbot-typing active';
            typingDiv.innerHTML = `
            <div class="typing-indicator">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
        `;
            messagesContainer.appendChild(typingDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;

            // Call API
            try {
                const formData = new FormData();
                formData.append('message', message);

                const response = await fetch('chatbot.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                // Remove typing indicator
                typingDiv.remove();

                if (data.success) {
                    addMessage(data.response, 'bot');
                } else {
                    addMessage('Sorry, I encountered an error. Please try again.', 'bot');
                }
            } catch (error) {
                typingDiv.remove();
                addMessage('Connection error. Please check your API key in chatbot.php', 'bot');
            }
        }

        // Add message to chat
        function addMessage(text, sender) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `chatbot-message ${sender}-message`;

            if (sender === 'user') {
                messageDiv.innerHTML = `<p>${text}</p>`;
            } else {
                messageDiv.innerHTML = `
                <strong>AI Assistant:</strong>
                <p>${text}</p>
            `;
            }

            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Event listeners
        sendBtn.addEventListener('click', sendMessage);
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });
    })();
</script>

</html>