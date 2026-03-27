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

// Verify this scholarship belongs to the logged-in provider
$stmt = mysqli_prepare($conn, "SELECT * FROM scholarships
                                WHERE scholarship_id = ? AND provider_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $scholarship_id, $provider_id);
mysqli_stmt_execute($stmt);
$verify_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($verify_result) == 0) {
    header("Location: provider_dashboard.php?error=unauthorized");
    exit();
}

$scholarship = mysqli_fetch_assoc($verify_result);

// Fetch the current fields of study for this scholarship
$stmt2 = mysqli_prepare($conn, "SELECT field_of_study FROM scholarship_fields WHERE scholarship_id = ?");
mysqli_stmt_bind_param($stmt2, "i", $scholarship_id);
mysqli_stmt_execute($stmt2);
$fields_result  = mysqli_stmt_get_result($stmt2);
$existing_fields = [];
while ($f = mysqli_fetch_assoc($fields_result)) {
    $existing_fields[] = $f['field_of_study'];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $scholarship_name     = trim($_POST['scholarship_name']);
    $scholarship_type     = $_POST['scholarship_type'];
    $description          = trim($_POST['description']);
    $amount               = trim($_POST['amount']);
    $education_level      = $_POST['education_level'];
    $gpa_requirement      = !empty($_POST['gpa_requirement']) ? (float) $_POST['gpa_requirement'] : null;
    $requirements         = trim($_POST['requirements']);
    $application_deadline = $_POST['application_deadline'];
    $status               = $_POST['status'];

    // Update the scholarship record
    $stmt3 = mysqli_prepare($conn, "UPDATE scholarships SET
                                    scholarship_name = ?, scholarship_type = ?, description = ?,
                                    amount = ?, education_level = ?, gpa_requirement = ?,
                                    requirements = ?, application_deadline = ?, status = ?
                                    WHERE scholarship_id = ?");
    mysqli_stmt_bind_param($stmt3, "sssssdsssi",
        $scholarship_name, $scholarship_type, $description,
        $amount, $education_level, $gpa_requirement,
        $requirements, $application_deadline, $status,
        $scholarship_id
    );

    if (mysqli_stmt_execute($stmt3)) {
        // Remove old fields and re-insert the newly selected ones
        $stmt4 = mysqli_prepare($conn, "DELETE FROM scholarship_fields WHERE scholarship_id = ?");
        mysqli_stmt_bind_param($stmt4, "i", $scholarship_id);
        mysqli_stmt_execute($stmt4);

        if (!empty($_POST['fields'])) {
            $stmt5 = mysqli_prepare($conn, "INSERT INTO scholarship_fields (scholarship_id, field_of_study)
                                            VALUES (?, ?)");
            foreach ($_POST['fields'] as $field) {
                $field = trim($field);
                mysqli_stmt_bind_param($stmt5, "is", $scholarship_id, $field);
                mysqli_stmt_execute($stmt5);
            }
        }

        header("Location: provider_dashboard.php?updated=1");
        exit();

    } else {
        $error = "Error updating scholarship. Please try again.";
    }

    // Keep the newly selected fields if save fails
    $existing_fields = $_POST['fields'] ?? [];
}

$all_fields = ['Engineering', 'Medicine', 'Business', 'Science', 'Arts', 'Law', 'Education', 'All Fields'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Scholarship - EduLift</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/img/eduliftlogo.ico">
</head>
<body>
    <header class="header">
        <a class="header-brand" href="#"><img src="../assets/img/eduliftlogo.png" alt="EduLift Logo" class="header-logo"><h1 class="logo">EduLift</h1></a>
        <button class="nav-toggle" onclick="this.nextElementSibling.classList.toggle('open')" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>
        <nav>
            <a href="provider_dashboard.php">Dashboard</a>
            <a href="../auth/logout.php">Logout</a>
        </nav>
    </header>

    <div class="form-page">
        <div class="form-card">
            <h2>Edit Scholarship</h2>

            <!-- Show error if update failed -->
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="scholarship-form">
                <div class="form-group">
                    <label>Scholarship Name</label>
                    <input type="text" name="scholarship_name" value="<?php echo htmlspecialchars($scholarship['scholarship_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Scholarship Type</label>
                    <select name="scholarship_type" required>
                        <option value="">Select Type</option>
                        <option value="Full Scholarship"  <?php echo $scholarship['scholarship_type'] == 'Full Scholarship'  ? 'selected' : ''; ?>>Full Scholarship</option>
                        <option value="Partial Scholarship" <?php echo $scholarship['scholarship_type'] == 'Partial Scholarship' ? 'selected' : ''; ?>>Partial Scholarship</option>
                        <option value="Tuition Discount"  <?php echo $scholarship['scholarship_type'] == 'Tuition Discount'  ? 'selected' : ''; ?>>Tuition Discount</option>
                        <option value="Financial Grant"   <?php echo $scholarship['scholarship_type'] == 'Financial Grant'   ? 'selected' : ''; ?>>Financial Grant</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4" required><?php echo htmlspecialchars($scholarship['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Amount</label>
                    <input type="text" name="amount" value="<?php echo htmlspecialchars($scholarship['amount']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Education Level</label>
                    <select name="education_level" required>
                        <option value="">Select Education Level</option>
                        <option value="Senior High School"   <?php echo $scholarship['education_level'] == 'Senior High School'   ? 'selected' : ''; ?>>Senior High School</option>
                        <option value="College Undergraduate" <?php echo $scholarship['education_level'] == 'College Undergraduate' ? 'selected' : ''; ?>>College Undergraduate</option>
                        <option value="Graduate Studies"     <?php echo $scholarship['education_level'] == 'Graduate Studies'     ? 'selected' : ''; ?>>Graduate Studies</option>
                        <option value="All Levels"           <?php echo $scholarship['education_level'] == 'All Levels'           ? 'selected' : ''; ?>>All Levels</option>
                    </select>
                </div>
                <!-- Checkboxes pre-checked based on saved fields in scholarship_fields table -->
                <div class="form-group">
                    <label>Fields of Study</label>
                    <div class="checkbox-group" id="fields-group">
                        <?php foreach ($all_fields as $field): ?>
                            <label>
                                <input type="checkbox" name="fields[]"
                                       value="<?php echo $field; ?>"
                                       class="field-checkbox"
                                       <?php echo $field === 'All Fields' ? 'id="all-fields-check"' : ''; ?>
                                       <?php echo in_array($field, $existing_fields) ? 'checked' : ''; ?>>
                                <?php echo $field; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>GPA Requirement</label>
                    <input type="number" step="0.01" min="1.00" max="4.00" name="gpa_requirement"
                           value="<?php echo htmlspecialchars($scholarship['gpa_requirement']); ?>"
                           placeholder="e.g., 3.50"
                           oninput="this.setCustomValidity('')"
                           oninvalid="this.setCustomValidity('Please enter a valid GPA between 1.00 and 4.00.')">
                </div>
                <div class="form-group">
                    <label>Requirements</label>
                    <textarea name="requirements" rows="4" required><?php echo htmlspecialchars($scholarship['requirements']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Application Deadline</label>
                    <input type="date" name="application_deadline"
                           value="<?php echo htmlspecialchars($scholarship['application_deadline']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="active"   <?php echo $scholarship['status'] == 'active'   ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $scholarship['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    <small>Set to "Inactive" to hide this scholarship from students</small>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn">Save Changes</button>
                    <a href="provider_dashboard.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        // When "All Fields" is checked, uncheck all other options
        // When any other option is checked, uncheck "All Fields"
        const allFieldsCheck = document.getElementById('all-fields-check');
        const fieldCheckboxes = document.querySelectorAll('.field-checkbox:not(#all-fields-check)');

        allFieldsCheck.addEventListener('change', function () {
            if (this.checked) {
                fieldCheckboxes.forEach(cb => cb.checked = false);
            }
        });

        fieldCheckboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                if (this.checked) {
                    allFieldsCheck.checked = false;
                }
            });
        });
    </script>
</body>
</html>