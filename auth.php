<?php
// Start session once at the top
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in. Redirect to login page if not.
 */
function checkAuthentication() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../admin/login.php');
        exit();
    }
}

/**
 * Return true if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Log user activity
 */
function logActivity($userId, $activityType, $description) {
    global $pdo;

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

    $stmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action) VALUES (?, ?)
    ");
    $stmt->execute([$userId, $description]);
}

/**
 * Log inventory changes
 */
function logInventoryChange($productId, $quantityChange, $referenceType, $referenceId, $notes, $userId) {
    global $pdo;

    // Make sure inventory_logs table exists
    $stmt = $pdo->prepare("
        INSERT INTO inventory_logs (product_id, quantity_change, reference_type, reference_id, notes, user_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$productId, $quantityChange, $referenceType, $referenceId, $notes, $userId]);
}

/**
 * Human-readable time elapsed
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);

    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
