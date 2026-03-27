<?php
include("../config/database.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form input
    $username          = trim($_POST['username']);
    $email             = trim($_POST['email']);
    $password          = $_POST['password'];
    $full_name         = trim($_POST['full_name']);
    $location          = trim($_POST['location']);
    $field_of_interest = trim($_POST['field_of_interest']);

    // Check if the email is already registered
    $check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($check, "s", $email);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        $error = "This email address is already registered. Please use a different email or login instead.";
    } else {
        // Insert the new user into the users table
        $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, user_type)
                                       VALUES (?, ?, ?, 'student')");
        mysqli_stmt_bind_param($stmt, "sss", $username, $email, $password);

        if (mysqli_stmt_execute($stmt)) {
            // Get the new user's ID
            $user_id = mysqli_insert_id($conn);

            // Create a student profile linked to the new user
            $stmt2 = mysqli_prepare($conn, "INSERT INTO student_profiles (user_id, full_name, location, field_of_interest)
                                            VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt2, "isss", $user_id, $full_name, $location, $field_of_interest);
            mysqli_stmt_execute($stmt2);

            // Redirect to login after successful registration
            header("Location: login.php");
            exit();

        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - EduLift</title>
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
            <a href="login.php">Login</a>
        </nav>
    </header>

    <div class="auth-page">
        <div class="auth-card">
            <h2>Register as Student</h2>

            <!-- Show error if registration failed -->
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <input type="text"     name="username"          placeholder="Username"                              required>
                <input type="email"    name="email"             placeholder="Email"                                 required>
                <input type="password" name="password"          placeholder="Password"                              required>
                <input type="text"     name="full_name"         placeholder="Full Name"                             required>
                <input type="text"     name="location"          placeholder="Location (City, Province)"             required>
                <input type="text"     name="field_of_interest" placeholder="Field of Interest (e.g., Engineering)" required>
                <button type="submit" class="btn">Register</button>
            </form>
            <p class="auth-footer">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</body>
</html>