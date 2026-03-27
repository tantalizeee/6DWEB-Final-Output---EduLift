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
    header("Location: manage_scholarships.php");
    exit();
}

$scholarship_id = (int) $_GET['id'];
$action         = $_GET['action'];

// Only allow activate or deactivate as valid actions
if (!in_array($action, ['activate', 'deactivate'])) {
    header("Location: manage_scholarships.php");
    exit();
}

// Set the new status based on the action
$new_status = ($action === 'activate') ? 'active' : 'inactive';

// Update the scholarship status in the database
$stmt = mysqli_prepare($conn, "UPDATE scholarships SET status = ? WHERE scholarship_id = ?");
mysqli_stmt_bind_param($stmt, "si", $new_status, $scholarship_id);

if (mysqli_stmt_execute($stmt)) {
    header("Location: manage_scholarships.php?toggled=$action");
} else {
    header("Location: manage_scholarships.php?error=toggle_failed");
}
exit();
?>