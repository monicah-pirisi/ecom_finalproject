<?php
/**
 * TEMPORARY SCRIPT - Delete after use
 * Resets login lockout for admin accounts
 */

require_once 'includes/config.php';

// Clear all lockouts
$conn->query("UPDATE users SET login_attempts = 0, lockout_until = NULL WHERE user_type = 'admin'");

echo "<h2>Admin account lockouts have been cleared!</h2>";
echo "<p>You can now try logging in again.</p>";
echo "<p><strong>IMPORTANT: Delete this file (reset_lockout.php) immediately for security!</strong></p>";
echo "<p><a href='login/login.php'>Go to Login Page</a></p>";

// Show admin accounts for reference
$result = $conn->query("SELECT id, full_name, email, phone FROM users WHERE user_type = 'admin'");
echo "<h3>Admin Accounts:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
