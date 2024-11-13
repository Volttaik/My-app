<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new PDO('sqlite:database.sqlite');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $user_id = uniqid('user_', true);

        // Get referrer ID from hidden field
        $referrer_id = $_POST['referrer_id'];

        // Insert new user into the database
        $stmt = $db->prepare(
            "INSERT INTO users (username, password, user_id, referrer_id) 
            VALUES (:username, :password, :user_id, :referrer_id)"
        );
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':password', $password, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
        $stmt->bindValue(':referrer_id', $referrer_id, PDO::PARAM_STR);
        $stmt->execute();

        // Insert referral record if referrer_id is not empty
        if (!empty($referrer_id)) {
            $stmt = $db->prepare(
                "INSERT INTO referrals (referrer_id, referee_id) 
                VALUES (:referrer_id, :referee_id)"
            );
            $stmt->bindValue(':referrer_id', $referrer_id, PDO::PARAM_STR);
            $stmt->bindValue(':referee_id', $user_id, PDO::PARAM_STR);
            $stmt->execute();
        }

        echo '<p>Registration successful! <a href="login.php">Login</a></p>';
    } catch (PDOException $ex) {
        echo '<p>Error: ' . $ex->getMessage() . '</p>';
    }
} else {
    // Retrieve referrer ID from URL
    $referrer_id = isset($_GET['referral_code']) ? $_GET['referral_code'] : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }
        .container {
            background-color: #007bff;
            color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            width: 300px;
            text-align: center;
        }
        .container h1 {
            margin-top: 0;
        }
        .container label {
            display: block;
            margin: 10px 0 5px;
        }
        .container input {
            width: 100%;
            padding: 10px;
            margin: 5px 0 20px;
            border: none;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .container button {
            background-color: #0056b3;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }
        .container button:hover {
            background-color: #003d80;
        }
        .container a {
            color: #e3f2fd;
            text-decoration: none;
        }
        .container a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Register</h1>
        <form method="POST" action="">
            <!-- Hidden field to store referrer ID -->
            <input type="hidden" name="referrer_id" value="<?php echo htmlspecialchars($referrer_id); ?>">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Register</button>
        </form>
        <p><a href="login.php">Already have an account? Login here</a></p>
    </div>
</body>
</html>