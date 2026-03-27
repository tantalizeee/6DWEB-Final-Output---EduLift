<?php
session_start();
include("../config/database.php");

// Only admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Require both an ID and an action in the URL
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$provider_id = (int) $_GET['id'];
$action      = $_GET['action'];

// Only allow approve or reject as valid actions
if (!in_array($action, ['approve', 'reject'])) {
    header("Location: admin_dashboard.php");
    exit();
}

// Check that the provider actually exists
$stmt = mysqli_prepare($conn, "SELECT pp.provider_id, i.institution_name
                                FROM provider_profiles pp
                                JOIN institutions i ON pp.institution_id = i.institution_id
                                WHERE pp.provider_id = ?");
mysqli_stmt_bind_param($stmt, "i", $provider_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: admin_dashboard.php?error=not_found");
    exit();
}

// Set the new status based on the action
$new_status = ($action === 'approve') ? 'verified' : 'rejected';

// Update the provider's status in the database
$stmt2 = mysqli_prepare($conn, "UPDATE provider_profiles SET status = ? WHERE provider_id = ?");
mysqli_stmt_bind_param($stmt2, "si", $new_status, $provider_id);

if (mysqli_stmt_execute($stmt2)) {
    header("Location: admin_dashboard.php?verified=$action");
} else {
    header("Location: admin_dashboard.php?error=verify_failed");
}
exit();
?>