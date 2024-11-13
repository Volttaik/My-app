<?php
session_start();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme_color = $_POST['theme_color'] ?? '#007bff'; // Default to blue if not set
    $_SESSION['theme_color'] = $theme_color;
    header('Location: settings.php'); // Redirect to the same page to avoid resubmission
    exit;
}

// Get the current theme color from the session
$current_color = $_SESSION['theme_color'] ?? '#007bff'; // Default to blue
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Skyways Banking</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
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
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .container button {
            background-color: #007bff;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }
        .container button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Settings</h1>
        <form method="POST" action="">
            <label for="theme_color">Select Theme Color:</label>
            <input type="color" id="theme_color" name="theme_color" value="<?php echo htmlspecialchars($current_color); ?>">
            <button type="submit">Save Changes</button>
        </form>
    </div>
</body>
    <div class="container"> 
    
        <a href="dashboard.php" class=button>back to home</a> 
</html>