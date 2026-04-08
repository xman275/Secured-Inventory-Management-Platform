<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$username = '';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($username)) {
        $errors[] = 'Username is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    // If no validation errors, attempt login
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? AND active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Update last login time using prepared statement
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Redirect to dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $errors[] = 'Invalid username or password';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error occurred. Please try again.';
            error_log('Login error: ' . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>
    
    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <form method="POST">
        <p>
            <label>Username:</label><br>
            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
        </p>
        
        <p>
            <label>Password:</label><br>
            <input type="password" name="password" required>
        </p>
        
        <p>
            <button type="submit">Login</button>
        </p>
    </form>
    
    <p><strong>Test accounts:</strong> admin/password, user1/password</p>
</body>
</html>