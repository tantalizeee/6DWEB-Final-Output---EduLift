<?php
session_start();
include("../config/database.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    // Look up user by email using a prepared statement
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user   = mysqli_fetch_assoc($result);

    // Check if user exists and password matches
    if ($user && $password === $user['password']) {

        // Redirect based on user role
        if ($user['user_type'] == 'admin') {
            // Store session and redirect to admin dashboard
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['user_type'] = $user['user_type'];
            header("Location: ../admin/admin_dashboard.php");
            exit();

        } elseif ($user['user_type'] == 'provider') {
            // Fetch provider profile including verification status
            $stmt2 = mysqli_prepare($conn, "SELECT provider_id, status FROM provider_profiles WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt2, "i", $user['user_id']);
            mysqli_stmt_execute($stmt2);
            $provider = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));

            // Block access if the provider account is still pending or rejected
            // Do NOT set session — leave them logged out
            if ($provider['status'] === 'pending') {
                $error_message = "pending";
            } elseif ($provider['status'] === 'rejected') {
                $error_message = "rejected";
            } else {
                // Only set session once verified
                $_SESSION['user_id']     = $user['user_id'];
                $_SESSION['user_type']   = $user['user_type'];
                $_SESSION['provider_id'] = $provider['provider_id'];
                header("Location: ../provider/provider_dashboard.php");
                exit();
            }

        } else {
            // Store session and redirect to student dashboard
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['user_type'] = $user['user_type'];
            header("Location: ../student/dashboard.php");
            exit();
        }

    } else {
        $error_message = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EduLift</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/img/eduliftlogo.ico">
</head>
<body>
    <header class="header">
        <a class="header-brand" href="../index.php"><img src="../assets/img/eduliftlogo.png" alt="EduLift Logo" class="header-logo"><h1 class="logo">EduLift</h1></a>
        <button class="nav-toggle" onclick="this.nextElementSibling.classList.toggle('open')" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>
        <nav>
            <a href="../index.php">Home</a>
            <a href="../index.php#about">About Us</a>
        </nav>
    </header>

    <div class="auth-page">
        <div class="auth-card">
            <h2>Login to EduLift</h2>

            <!-- Show success message after registration -->
            <?php if (isset($_GET['registered'])): ?>
                <div class="success-message">
                    Registration successful! Your account is pending verification. You can login once approved.
                </div>
            <?php endif; ?>

            <!-- Show error if login failed -->
            <?php if (isset($error_message)): ?>
                <?php if ($error_message === 'pending'): ?>
                    <div class="error-message modal-message">
                        <strong>Account Pending Review</strong>
                        <p>Your institution account is still being reviewed by our admin. You will be able to log in once your account has been approved. Please check back later.</p>
                    </div>
                <?php elseif ($error_message === 'rejected'): ?>
                    <div class="error-message modal-message">
                        <strong>Account Not Approved</strong>
                        <p>Unfortunately your institution account was not approved. Please contact us for more information.</p>
                    </div>
                <?php else: ?>
                    <div class="error-message">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" class="btn">Login</button>
            </form>
            <p class="auth-footer">Don't have an account?
                <a href="register.php">Register here</a>
            </p>
        </div>
    </div>
</body>
</html>