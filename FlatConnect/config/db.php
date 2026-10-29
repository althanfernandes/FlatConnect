<?php
/**
 * FlatConnect - Database Connection Configuration
 * 
 * Uses PHP Data Objects (PDO) for secure, prepared SQL interactions.
 * Configured for XAMPP default settings (host: localhost, user: root, password: '').
 */

$host     = '127.0.0.1'; // Using 127.0.0.1 avoids DNS lookup delays on Windows
$db_name  = 'flatconnect_db';
$username = 'root';
$password = ''; // Default XAMPP MySQL password is empty
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on SQL errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use genuine prepared statements
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Student-friendly error message if MySQL is not running
    die('<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 60px auto; padding: 25px; border: 1px solid #ffccd5; background: #fff0f3; border-radius: 8px; color: #a3001e;">
            <h2 style="margin-top:0;">Database Connection Error</h2>
            <p>Could not connect to the MySQL database <strong>flatconnect_db</strong>.</p>
            <p><strong>Troubleshooting steps:</strong></p>
            <ol>
                <li>Open the <strong>XAMPP Control Panel</strong>.</li>
                <li>Make sure the <strong>MySQL</strong> service is started (green indicator).</li>
                <li>Ensure the database <code>flatconnect_db</code> is created and imported.</li>
            </ol>
            <small style="color: #666;">Technical details: ' . htmlspecialchars($e->getMessage()) . '</small>
        </div>');
}
