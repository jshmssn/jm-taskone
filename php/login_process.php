<?php
session_start(); // Ensure session is started

if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    include 'connection.php';

    // Fetch user data from database
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id = $row['id']; // Fetch the user ID
        $name = $row['name']; // Fetch the user name
        $isValid = $row['isValid'];
        $hashed_password = $row['password'];

        if ($isValid == 0) {
            echo json_encode(['success' => false, 'message' => 'Account is not valid.']);
        } else {
            // Verify the password
            if (md5($password) === $hashed_password) {
                $_SESSION['id'] = $id; // Set session variable on successful login
                $_SESSION['email'] = $email; // Store the email in session
                $_SESSION['name'] = $name; // Store the name in session
                echo json_encode(['success' => true, 'message' => 'Login successful!']);
                // You can start a session or perform other actions here
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid password.']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No user found with that email.']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
