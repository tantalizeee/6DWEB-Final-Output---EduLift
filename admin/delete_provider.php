<?php
session_start();
include("../config/database.php");

// Only admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Require a provider ID in the URL
if (!isset($_GET['id'])) {
    header("Location: manage_providers.php");
    exit();
}

$provider_id = (int) $_GET['id'];

// Fetch the provider's user_id and institution_id before deleting
$stmt = mysqli_prepare($conn, "SELECT user_id, institution_id FROM provider_profiles WHERE provider_id = ?");
mysqli_stmt_bind_param($stmt, "i", $provider_id);
mysqli_stmt_execute($stmt);
$provider = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$provider) {
    header("Location: manage_providers.php?error=not_found");
    exit();
}

$user_id        = (int) $provider['user_id'];
$institution_id = (int) $provider['institution_id'];

// Step 1: Delete scholarship_fields linked to this provider's scholarships
$stmt2 = mysqli_prepare($conn, "DELETE FROM scholarship_fields WHERE scholarship_id IN (
                                    SELECT scholarship_id FROM scholarships WHERE provider_id = ?
                                )");
mysqli_stmt_bind_param($stmt2, "i", $provider_id);
mysqli_stmt_execute($stmt2);

// Step 2: Delete all scholarships posted by this provider
$stmt3 = mysqli_prepare($conn, "DELETE FROM scholarships WHERE provider_id = ?");
mysqli_stmt_bind_param($stmt3, "i", $provider_id);
mysqli_stmt_execute($stmt3);

// Step 3: Delete the provider profile
$stmt4 = mysqli_prepare($conn, "DELETE FROM provider_profiles WHERE provider_id = ?");
mysqli_stmt_bind_param($stmt4, "i", $provider_id);
mysqli_stmt_execute($stmt4);

// Step 4: Delete the institution record
$stmt5 = mysqli_prepare($conn, "DELETE FROM institutions WHERE institution_id = ?");
mysqli_stmt_bind_param($stmt5, "i", $institution_id);
mysqli_stmt_execute($stmt5);

// Step 5: Delete the user account
$stmt6 = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt6, "i", $user_id);

if (mysqli_stmt_execute($stmt6)) {
    header("Location: manage_providers.php?deleted=1");
} else {
    header("Location: manage_providers.php?error=delete_failed");
}
exit();
?>