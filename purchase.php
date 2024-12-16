<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST['type'];
    $provider = $_POST['provider'];
    $amount = $_POST['amount'];

    // Process the purchase
    if (!empty($type) && !empty($amount)) {
        echo "<p style='color:green;'>Success! You purchased $amount of $type from $provider.</p>";
    } else {
        echo "<p style='color:red;'>Please select a package.</p>";
    }
} else {
    echo "<p style='color:red;'>Invalid request method.</p>";
}
?>
