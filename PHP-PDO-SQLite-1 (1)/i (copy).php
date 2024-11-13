l<?php
try {
    $db = new PDO('sqlite:database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Drop old tables if they exist
    $db->exec("DROP TABLE IF EXISTS users");
    $db->exec("DROP TABLE IF EXISTS transactions");
    $db->exec("DROP TABLE IF EXISTS notifications");
    $db->exec("DROP TABLE IF EXISTS sessions");
    $db->exec("DROP TABLE IF EXISTS settings");
    $db->exec("DROP TABLE IF EXISTS referrals");

    // Create new tables
    $db->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            balance REAL DEFAULT 0,
            user_id TEXT UNIQUE NOT NULL,
            referrer_id TEXT,
            FOREIGN KEY (referrer_id) REFERENCES users(user_id)
        )"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            transaction_id TEXT UNIQUE NOT NULL,
            sender_id TEXT NOT NULL,
            receiver_id TEXT NOT NULL,
            transaction_type TEXT NOT NULL,
            amount REAL NOT NULL,
            date DATE DEFAULT (date('now')),
            time TIME DEFAULT (time('now')),
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(user_id),
            FOREIGN KEY (receiver_id) REFERENCES users(user_id)
        )"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id TEXT NOT NULL,
            message TEXT NOT NULL,
            transaction_id TEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id),
            FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id)
        )"
    );

    $db->exec(
        "CREATE TABLE IF NOT EXISTS sessions (
            session_id TEXT PRIMARY KEY,
            user_id TEXT NOT NULL,
            last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        )"
    );

    // Create settings table
    $db->exec(
        "CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id TEXT NOT NULL,
            font_size TEXT DEFAULT '16px',
            icon_color TEXT DEFAULT '#000000',
            dashboard_color TEXT DEFAULT '#ffffff',
            brightness INTEGER DEFAULT 100,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        )"
    );

    // Create referrals table
    $db->exec(
        "CREATE TABLE IF NOT EXISTS referrals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            referrer_id TEXT NOT NULL,
            referee_id TEXT NOT NULL,
            referral_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (referrer_id) REFERENCES users(user_id),
            FOREIGN KEY (referee_id) REFERENCES users(user_id)
        )"
    );

    // Insert a sample user
    $stmt = $db->prepare(
        "INSERT INTO users (username, password, user_id) 
        VALUES (:username, :password, :user_id)"
    );
    $stmt->bindValue(':username', 'testuser', PDO::PARAM_STR);
    $stmt->bindValue(':password', password_hash('password123', PASSWORD_DEFAULT), PDO::PARAM_STR); // Hash the password
    $stmt->bindValue(':user_id', 'user123', PDO::PARAM_STR);
    $stmt->execute();

    // Generate a sample transaction ID
    $transaction_id = uniqid('txn_', true);

    // Insert a sample transaction
    $stmt = $db->prepare(
        "INSERT INTO transactions (transaction_id, sender_id, receiver_id, transaction_type, amount) 
        VALUES (:transaction_id, :sender_id, :receiver_id, :transaction_type, :amount)"
    );
    $stmt->bindValue(':transaction_id', $transaction_id, PDO::PARAM_STR);
    $stmt->bindValue(':sender_id', 'user123', PDO::PARAM_STR);
    $stmt->bindValue(':receiver_id', 'user456', PDO::PARAM_STR); // Assuming 'user456' is another user ID
    $stmt->bindValue(':transaction_type', 'transfer', PDO::PARAM_STR);
    $stmt->bindValue(':amount', 500, PDO::PARAM_STR);
    $stmt->execute();

    // Insert a sample notification
    $stmt = $db->prepare(
        "INSERT INTO notifications (user_id, message, transaction_id) 
        VALUES (:user_id, :message, :transaction_id)"
    );
    $stmt->bindValue(':user_id', 'user123', PDO::PARAM_STR);
    $stmt->bindValue(':message', 'Your balance has been updated.', PDO::PARAM_STR);
    $stmt->bindValue(':transaction_id', $transaction_id, PDO::PARAM_STR);
    $stmt->execute();

    // Insert a sample setting
    $stmt = $db->prepare(
        "INSERT INTO settings (user_id, font_size, icon_color, dashboard_color, brightness) 
        VALUES (:user_id, :font_size, :icon_color, :dashboard_color, :brightness)"
    );
    $stmt->bindValue(':user_id', 'user123', PDO::PARAM_STR);
    $stmt->bindValue(':font_size', '16px', PDO::PARAM_STR);
    $stmt->bindValue(':icon_color', '#000000', PDO::PARAM_STR);
    $stmt->bindValue(':dashboard_color', '#ffffff', PDO::PARAM_STR);
    $stmt->bindValue(':brightness', 100, PDO::PARAM_INT);
    $stmt->execute();

    // Insert a sample referral
    $stmt = $db->prepare(
        "INSERT INTO referrals (referrer_id, referee_id) 
        VALUES (:referrer_id, :referee_id)"
    );
    $stmt->bindValue(':referrer_id', 'user123', PDO::PARAM_STR);
    $stmt->bindValue(':referee_id', 'user456', PDO::PARAM_STR);
    $stmt->execute();

    // Close the database connection
    $db = null;

    echo "Database setup completed successfully!";
} catch (PDOException $ex) {
    echo "Error: " . $ex->getMessage();
}
?>