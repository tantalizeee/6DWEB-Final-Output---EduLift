<?php
session_start();
include("../config/database.php");

// Only admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Require a scholarship ID in the URL
if (!isset($_GET['id'])) {
    header("Location: manage_scholarships.php");
    exit();
}

$scholarship_id = (int) $_GET['id'];

// Verify the scholarship exists
$stmt = mysqli_prepare($conn, "SELECT scholarship_id FROM scholarships WHERE scholarship_id = ?");
mysqli_stmt_bind_param($stmt, "i", $scholarship_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) == 0) {
    header("Location: manage_scholarships.php?error=not_found");
    exit();
}

// Step 1: Delete related fields first to satisfy the foreign key constraint
$stmt2 = mysqli_prepare($conn, "DELETE FROM scholarship_fields WHERE scholarship_id = ?");
mysqli_stmt_bind_param($stmt2, "i", $scholarship_id);
mysqli_stmt_execute($stmt2);

// Step 2: Delete the scholarship itself
$stmt3 = mysqli_prepare($conn, "DELETE FROM scholarships WHERE scholarship_id = ?");
mysqli_stmt_bind_param($stmt3, "i", $scholarship_id);

if (mysqli_stmt_execute($stmt3)) {
    header("Location: manage_scholarships.php?deleted=1");
} else {
    header("Location: manage_scholarships.php?error=delete_failed");
}
exit();
?>