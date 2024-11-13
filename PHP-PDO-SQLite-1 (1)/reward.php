<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die('User not logged in.');
}

try {
    $db = new PDO('sqlite:database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $user_id = $_SESSION['user_id'];
    
    // Get current time
    $current_time = new DateTime();
    
    // Retrieve last login time
    $stmt = $db->prepare("SELECT last_login FROM users WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $last_login = new DateTime($user['last_login']);
        
        // Calculate the difference in hours
        $interval = $last_login->diff($current_time);
        $hours = ($interval->days * 24) + $interval->h;
        
        if ($hours >= 24) {
            // Update the balance
            $stmt = $db->prepare("UPDATE users SET balance = balance + 100 WHERE user_id = :user_id");
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $stmt->execute();

            // Update the last login time
            $stmt = $db->prepare("UPDATE users SET last_login = :last_login WHERE user_id = :user_id");
            $stmt->bindValue(':last_login', $current_time->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $stmt->execute();
            
            echo '<p>Congratulations! You have received a $100 reward.</p>';
        } else {
            $next_reward_time = $last_login->modify('+24 hours');
            echo '<p>You can claim your next reward at ' . $next_reward_time->format('Y-m-d H:i:s') . '.</p>';
        }
    } else {
        echo '<p>User not found.</p>';
    }

} catch (PDOException $ex) {
    echo '<p>Error: ' . $ex->getMessage() . '</p>';
}
?>