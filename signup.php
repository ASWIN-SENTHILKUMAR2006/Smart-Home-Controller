<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $conn->real_escape_string($_POST['password']);
    $role = $conn->real_escape_string($_POST['role']);

    // Check if email already exists
    $check = $conn->query("SELECT user_id FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $error = 'Email already registered';
    } else {
        // Insert user
        $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";

        if ($conn->query($sql)) {
            $user_id = $conn->insert_id;

            // If provider -> auto add default provider record
            if ($role === 'provider') {
                $stmt = $conn->prepare("INSERT INTO providers (company_name, support_contact, user_id) VALUES (?, ?, ?)");

                $defaultCompany = 'New Provider Company';
                $defaultContact = $email;

                $stmt->bind_param("ssi", $defaultCompany, $defaultContact, $user_id);
                $stmt->execute();
            }

            $success = 'Account created successfully! You can now login.';
        } else {
            $error = 'Registration failed: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Home - Sign Up</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1>🏠 Smart Home</h1>
            <h2>Sign Up</h2>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required placeholder="John Doe">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="your@email.com">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter password">
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="user">User</option>
                        <option value="provider">Provider</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Sign Up</button>
            </form>

            <p class="auth-link">Already have an account? <a href="index.php">Login</a></p>
        </div>
    </div>
</body>

</html>