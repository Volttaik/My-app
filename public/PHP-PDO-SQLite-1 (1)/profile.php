<?php
require 'db.php'; // Ensure this file sets up the $db connection
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $new_password = $_POST['new_password'];

        if (empty($username) || empty($password)) {
            $error = 'Username and current password are required.';
        } else {
            try {
                // Check current password
                $stmt = $db->prepare("SELECT password FROM users WHERE user_id = :user_id");
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (password_verify($password, $user['password'])) {
                    // Update username and/or password
                    $db->beginTransaction();

                    if (!empty($new_password)) {
                        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE users SET username = :username, password = :password WHERE user_id = :user_id");
                        $stmt->bindValue(':password', $new_password_hash, PDO::PARAM_STR);
                    } else {
                        $stmt = $db->prepare("UPDATE users SET username = :username WHERE user_id = :user_id");
                    }

                    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                    $stmt->execute();

                    $db->commit();

                    $success = 'Profile updated successfully.';
                } else {
                    $error = 'Current password is incorrect.';
                }
            } catch (PDOException $ex) {
                $db->rollBack();
                $error = 'Error: ' . $ex->getMessage();
            }
        }
    }
}

// Fetch current user data
$stmt = $db->prepare("SELECT username FROM users WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { width: 80%; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #fff; border-radius: 10px; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2); }
        h1 { color: #007bff; }
        label { display: block; margin: 10px 0 5px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin: 5px 0 20px; border: 1px solid #ddd; border-radius: 5px; }
        button { background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .status { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Profile</h1>
        <?php if ($error) { ?>
            <p class="status"><?php echo htmlspecialchars($error); ?></p>
        <?php } ?>
        <?php if ($success) { ?>
            <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
        <?php } ?>
        <form method="POST" action="">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

            <label for="password">Current Password:</label>
            <input type="password" id="password" name="password" required>

            <label for="new_password">New Password (leave blank if not changing):</label>
            <input type="password" id="new_password" name="new_password">

            <button type="submit" name="update_profile">Update Profile</button>
        </form>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>