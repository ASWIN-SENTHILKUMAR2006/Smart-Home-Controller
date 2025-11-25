<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'applicationsettings.php';

header('Content-Type: application/json');

// ============================================
// 🔑 REPLACE THIS WITH YOUR HUGGING FACE API KEY
// Get free API key from: https://huggingface.co/settings/tokens
// ============================================
$HUGGINGFACE_API_KEY = $api_key; // add your Hugging face api key here
// ============================================

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}

$user_message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (empty($user_message)) {
    echo json_encode(['error' => 'Message cannot be empty']);
    exit();
}

// ============================================
// STEP 1: Gather System Context
// ============================================

$context = getSystemContext($conn, $user_id, $role);

// ============================================
// STEP 2: Build Intelligent Prompt
// ============================================

$system_prompt = buildSystemPrompt($context, $role);
$full_prompt = $system_prompt . "\n\nUser Question: " . $user_message . "\n\nAssistant:";

// ============================================
// STEP 3: Call Hugging Face API
// ============================================

$response = callHuggingFaceAPI($full_prompt, $HUGGINGFACE_API_KEY);

echo json_encode([
    'success' => true,
    'response' => $response,
    'timestamp' => date('Y-m-d H:i:s')
]);

// ============================================
// FUNCTIONS
// ============================================

function getSystemContext($conn, $user_id, $role)
{
    $context = [];

    // Get user info
    $user = $conn->query("SELECT name, email FROM users WHERE user_id = $user_id")->fetch_assoc();
    $context['user'] = $user;

    if ($role === 'user') {
        // Get assigned devices
        $devices_result = $conn->query("
            SELECT d.device_id, d.device_name, d.status, d.compatibility_version
            FROM devices d
            JOIN device_assignments da ON d.device_id = da.device_id
            WHERE da.user_id = $user_id
        ");
        $context['devices'] = [];
        while ($row = $devices_result->fetch_assoc()) {
            $context['devices'][] = $row;
        }

        // Get pending schedules
        $schedules_result = $conn->query("
            SELECT s.*, d.device_name
            FROM schedules s
            JOIN devices d ON s.device_id = d.device_id
            WHERE s.user_id = $user_id AND s.status = 'pending'
            ORDER BY s.scheduled_time ASC
            LIMIT 5
        ");
        $context['schedules'] = [];
        while ($row = $schedules_result->fetch_assoc()) {
            $context['schedules'][] = $row;
        }

        // Get recent activity
        $logs_result = $conn->query("
            SELECT al.*, d.device_name
            FROM activity_logs al
            LEFT JOIN devices d ON al.device_id = d.device_id
            WHERE al.user_id = $user_id
            ORDER BY al.timestamp DESC
            LIMIT 5
        ");
        $context['recent_activity'] = [];
        while ($row = $logs_result->fetch_assoc()) {
            $context['recent_activity'][] = $row;
        }

    } elseif ($role === 'admin') {
        // Admin gets system-wide stats
        $context['total_users'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='user'")->fetch_assoc()['count'];
        $context['total_devices'] = $conn->query("SELECT COUNT(*) as count FROM devices")->fetch_assoc()['count'];
        $context['total_assignments'] = $conn->query("SELECT COUNT(*) as count FROM device_assignments")->fetch_assoc()['count'];
        $context['pending_schedules'] = $conn->query("SELECT COUNT(*) as count FROM schedules WHERE status='pending'")->fetch_assoc()['count'];

    } elseif ($role === 'provider') {
        // Provider gets their devices
        $provider_id = $conn->query("SELECT provider_id FROM providers WHERE user_id = $user_id")->fetch_assoc()['provider_id'];
        $devices_result = $conn->query("SELECT * FROM devices WHERE provider_id = $provider_id");
        $context['devices'] = [];
        while ($row = $devices_result->fetch_assoc()) {
            $context['devices'][] = $row;
        }
    }

    return $context;
}

function buildSystemPrompt($context, $role)
{
    $prompt = "You are a helpful AI assistant for a Smart Home Controller System. ";
    $prompt .= "You help users manage their smart home devices, schedules, and provide tips.\n\n";

    $prompt .= "Current User: " . $context['user']['name'] . " (Role: $role)\n";

    if ($role === 'user') {
        $prompt .= "\nUser's Devices:\n";
        if (empty($context['devices'])) {
            $prompt .= "- No devices assigned yet\n";
        } else {
            foreach ($context['devices'] as $device) {
                $prompt .= "- {$device['device_name']} (Status: {$device['status']}, Version: {$device['compatibility_version']})\n";
            }
        }

        $prompt .= "\nPending Schedules:\n";
        if (empty($context['schedules'])) {
            $prompt .= "- No pending schedules\n";
        } else {
            foreach ($context['schedules'] as $schedule) {
                $prompt .= "- {$schedule['device_name']}: {$schedule['action']} at {$schedule['scheduled_time']}\n";
            }
        }

        $prompt .= "\nRecent Activity:\n";
        if (!empty($context['recent_activity'])) {
            foreach ($context['recent_activity'] as $log) {
                $device_info = $log['device_name'] ?? 'Unknown Device';
                $prompt .= "- {$device_info}: {$log['action']} (by {$log['executed_by']}) at {$log['timestamp']}\n";
            }
        }

        $prompt .= "\nYou can help with:\n";
        $prompt .= "- Turning devices on/off\n";
        $prompt .= "- Creating schedules\n";
        $prompt .= "- Energy saving tips\n";
        $prompt .= "- Automation suggestions\n";
        $prompt .= "- Device usage patterns\n";

    } elseif ($role === 'admin') {
        $prompt .= "\nSystem Statistics:\n";
        $prompt .= "- Total Users: {$context['total_users']}\n";
        $prompt .= "- Total Devices: {$context['total_devices']}\n";
        $prompt .= "- Active Assignments: {$context['total_assignments']}\n";
        $prompt .= "- Pending Schedules: {$context['pending_schedules']}\n";

        $prompt .= "\nYou can help with:\n";
        $prompt .= "- Managing users and devices\n";
        $prompt .= "- System optimization\n";
        $prompt .= "- Assignment strategies\n";
        $prompt .= "- Security recommendations\n";

    } elseif ($role === 'provider') {
        $prompt .= "\nYour Registered Devices:\n";
        if (empty($context['devices'])) {
            $prompt .= "- No devices registered yet\n";
        } else {
            foreach ($context['devices'] as $device) {
                $prompt .= "- {$device['device_name']} (Version: {$device['compatibility_version']}, Status: {$device['status']})\n";
            }
        }

        $prompt .= "\nYou can help with:\n";
        $prompt .= "- Device registration tips\n";
        $prompt .= "- Version management\n";
        $prompt .= "- Update strategies\n";
        $prompt .= "- Market insights\n";
    }

    $prompt .= "\nProvide helpful, concise answers (2-4 sentences). Be friendly and actionable.\n";

    return $prompt;
}

function callHuggingFaceAPI($prompt, $api_key)
{
    // Using NEW Hugging Face Router API
    $api_url = "https://huggingface.co/api/mistralai/Mistral-7B-Instruct-v0.3";

    $data = [
        'model' => 'mistralai/Mistral-7B-Instruct-v0.3',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a helpful AI assistant for a Smart Home system. Provide concise, actionable answers in 2-4 sentences.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => 200,
        'temperature' => 0.7,
        'stream' => false
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        return "Connection error: " . $curl_error . ". Please check your internet connection.";
    }

    if ($http_code !== 200) {
        if ($http_code == 401) {
            return "API key is invalid. Please check your Hugging Face API key.";
        } elseif ($http_code == 503) {
            return "The AI model is loading. Please wait 30 seconds and try again.";
        } elseif ($http_code == 410) {
            return "API endpoint outdated. Please update chatbot.php with the new router URL.";
        }
        return "I'm having trouble connecting (Error: $http_code). Please try again later.";
    }

    $result = json_decode($response, true);

    // New API response format
    if (isset($result['choices'][0]['message']['content'])) {
        return trim($result['choices'][0]['message']['content']);
    }

    // Fallback formats
    if (isset($result[0]['generated_text'])) {
        return trim($result[0]['generated_text']);
    }

    if (isset($result['generated_text'])) {
        return trim($result['generated_text']);
    }

    return "I received your question but couldn't process it properly. Could you rephrase that?";
}
?>