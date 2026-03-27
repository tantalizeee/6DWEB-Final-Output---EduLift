<?php
session_start();
include("../config/database.php");

// Only providers can access this page
if (!isset($_SESSION['provider_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Require a scholarship ID in the URL
if (!isset($_GET['id'])) {
    header("Location: provider_dashboard.php");
    exit();
}

$scholarship_id = (int) $_GET['id'];
$provider_id    = (int) $_SESSION['provider_id'];

// Verify that this scholarship belongs to the logged-in provider
$stmt = mysqli_prepare($conn, "SELECT scholarship_id FROM scholarships
                                WHERE scholarship_id = ? AND provider_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $scholarship_id, $provider_id);
mysqli_stmt_execute($stmt);
$verify_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($verify_result) > 0) {
    // Delete related fields first to satisfy the foreign key constraint
    $stmt2 = mysqli_prepare($conn, "DELETE FROM scholarship_fields WHERE scholarship_id = ?");
    mysqli_stmt_bind_param($stmt2, "i", $scholarship_id);
    mysqli_stmt_execute($stmt2);

    // Now delete the scholarship itself
    $stmt3 = mysqli_prepare($conn, "DELETE FROM scholarships WHERE scholarship_id = ?");
    mysqli_stmt_bind_param($stmt3, "i", $scholarship_id);

    if (mysqli_stmt_execute($stmt3)) {
        header("Location: provider_dashboard.php?deleted=1");
    } else {
        header("Location: provider_dashboard.php?error=delete_failed");
    }
} else {
    // Scholarship doesn't belong to this provider
    header("Location: provider_dashboard.php?error=unauthorized");
}
exit();
?>