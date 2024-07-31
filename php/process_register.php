<?php
include("connection.php"); // Make sure this path is correct

// Ensure form data is set
if (isset($_POST['name']) && isset($_POST['email']) && isset($_POST['password'])) {
    // Retrieve form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate form data (basic validation example)
    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    // Prepare SQL statement to check if email already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    if ($stmt) {
        // Bind parameter and execute
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($emailCount);
        $stmt->fetch();
        $stmt->close();

        // Check if email is already registered
        if ($emailCount > 0) {
            echo json_encode(['success' => false, 'message' => 'Email is already registered.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
        exit;
    }

    // Hash the password using MD5 (Note: MD5 is not recommended for security purposes; consider using more secure hashing algorithms like bcrypt or Argon2)
    $hashedPassword = md5($password);

    // Prepare SQL statement to insert new user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, last_update) VALUES (?, ?, ?, NOW())");
    if ($stmt) {
        // Bind parameters and execute
        $stmt->bind_param("sss", $name, $email, $hashedPassword);
        if ($stmt->execute()) {
            // Registration successful
            echo json_encode(['success' => true, 'redirectUrl' => '../page/success.php']);
        } else {
            // Database error
            echo json_encode(['success' => false, 'message' => 'Failed to register. Please try again.']);
        }
        $stmt->close();
    } else {
        // SQL preparation error
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }

    // Close the database connection
    $conn->close();
} else {
    // Form data is not set
    echo json_encode(['success' => false, 'message' => 'Invalid form submission.']);
}
?>
