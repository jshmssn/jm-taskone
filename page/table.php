<?php
session_start();
include("../php/connection.php");

// Ensure the user is logged in
if (empty($_SESSION['id'])) {
    header("Location: index.php");
    exit("Redirecting to index.php");
}

$globalUserId = isset($_SESSION['id']);

// Initialize success message
$success_message = '';
$message_type = '';

// Handle record addition
if (isset($_POST["add"])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = md5($_POST['password']); // Use password_hash() instead of md5

    // Check if email already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if ($count > 0) {
                // Email already exists
                $success_message = 'Email already exists. Please use a different email address.';
                $message_type = 'error';
            } else {
                // Insert new record
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, last_update) VALUES (?, ?, ?, NOW())");
                if ($stmt) {
                    $stmt->bind_param("sss", $name, $email, $password);
                    if ($stmt->execute()) {
                        $success_message = 'Record has been inserted successfully!';
                        $message_type = 'success';
                    } else {
                        $success_message = 'Error adding record: ' . $stmt->error;
                        $message_type = 'error';
                    }
                    $stmt->close();
                } else {
                    $success_message = "Error preparing insert statement: " . $conn->error;
                    $message_type = 'error';
                }
            }
        } else {
            $success_message = "Error executing check email statement: " . $stmt->error;
            $message_type = 'error';
        }
    } else {
        $success_message = "Error preparing check email statement: " . $conn->error;
        $message_type = 'error';
    }
    $conn->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?success_message=" . urlencode($success_message) . "&message_type=" . urlencode($message_type));
    exit();
}

// Handle record deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt_delete = $conn->prepare("UPDATE users SET isValid = 0, last_update = NOW() WHERE id = ?");
    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $delete_id);
        if ($stmt_delete->execute()) {
            $success_message = 'Record deleted successfully!';
            $message_type = 'success';
        } else {
            $success_message = 'Error deleting record: ' . $stmt_delete->error;
            $message_type = 'error';
        }
        $stmt_delete->close();
    } else {
        $success_message = "Error preparing delete statement: " . $conn->error;
        $message_type = 'error';
    }
    $conn->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?success_message=" . urlencode($success_message) . "&message_type=" . urlencode($message_type));
    exit();
}

// Handle record fetching for editing
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt_fetch = $conn->prepare("SELECT id, name, email FROM users WHERE id = ?");
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $edit_id);
        $stmt_fetch->execute();
        $stmt_fetch->bind_result($id, $name, $email);
        $stmt_fetch->fetch();
        $stmt_fetch->close();
    } else {
        echo "Error preparing fetch statement: " . $conn->error;
    }
}

// Handle record update
if (isset($_POST['update'])) {
    $new_name = $_POST['new_name'];
    $new_email = $_POST['new_email'];
    $edit_id = $_GET['edit_id'];
    $stmt_update = $conn->prepare("UPDATE users SET name = ?, email = ?, last_update = NOW() WHERE id = ?");
    if ($stmt_update) {
        $stmt_update->bind_param("ssi", $new_name, $new_email, $edit_id);
        if ($stmt_update->execute()) {
            $success_message = 'Record updated successfully!';
            $message_type = 'success';
        } else {
            $success_message = 'Error updating record: ' . $stmt_update->error;
            $message_type = 'error';
        }
        $stmt_update->close();
    } else {
        $success_message = "Error preparing update statement: " . $conn->error;
        $message_type = 'error';
    }
    $conn->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?success_message=" . urlencode($success_message) . "&message_type=" . urlencode($message_type));
    exit();
}

// Fetch records for display
$sql = "SELECT * FROM users WHERE isValid = 1 AND id != ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

// Bind the user ID parameter
$stmt->bind_param("i", $globalUserId);

// Execute the query
$stmt->execute();

// Fetch results
$result = $stmt->get_result();

if ($result === false) {
    echo "Error executing query: " . $stmt->error;
    $stmt->close();
    $conn->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD</title>
    <!-- SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <style>
        body {
            background-color: #eae7e6;
            font-family: Arial, sans-serif;
        }

        #add_form {
            display: none; /* Hide the form initially */
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            top: 17rem;
            width: 50%;
            max-width: 400px;
            position: fixed; /* Fix the form position */
            left: 15%; /* Center the form horizontally */
            transform: translate(-50%, -50%); /* Centering with CSS transform */
            z-index: 1000; /* Ensure the form is on top */
            overflow: auto; /* Add scroll if content overflows */
        }

        #add_form h1 {
            margin-top: 0;
            color: #333;
        }

        #add_form form input {
            width: calc(100% - 22px);
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        #add_form form button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }

        #add_form form button:hover {
            background-color: #0056b3;
        }

        #add_form form .cancel-button {
            background-color: #6c757d;
        }

        #add_form form .cancel-button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <?php include("../templates/header.php") ?>

    <div>
        <button class="bg-blue-500 text-white font-semibold py-2 px-4 rounded shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300 transition duration-150 ease-in-out" onclick="toggleForm()">
            New Record
        </button>

        <div>
            <?php
            if ($result->num_rows > 0) {
                echo "<table id='dataTable' class='display'>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Last Update</th>
                                <th>Actions</th>
                                </tr>
                        </thead>
                        <tbody>";

                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>" . $row["id"] . "</td>
                            <td>" . $row["name"] . "</td>
                            <td>" . $row["email"] . "</td>
                            <td>" . $row["last_update"] . "</td>
                            <td>
                                <a href='javascript:void(0);' onclick='confirmDelete(" . $row["id"] . ")'>Delete</a> |
                                <a href='?edit_id=" . $row["id"] . "'>Edit</a>
                            </td>
                        </tr>";

                    if (isset($_GET['edit_id']) && $_GET['edit_id'] == $row['id']) {
                        echo "<tr>
                                <td colspan='4'>
                                    <form id='editForm-" . $row['id'] . "' action='' method='POST'>
                                        <input type='text' name='new_name' value='" . $row['name'] . "' required>
                                        <input type='email' name='new_email' value='" . $row['email'] . "' required>
                                        <input type='submit' name='update' value='Update' class='update-button'>
                                        <a href='javascript:void(0);' onclick='cancelEdit(" . $row['id'] . ")'>Cancel</a>
                                    </form>
                                </td>
                            </tr>";
                    }
                }

                echo "</tbody>
                    </table>";
            } else {
                echo "<p class='text-center'>0 Data Found</p>";
            }
            ?>
        </div>

        <!-- Hidden form initially -->
        <div id="add_form">
            <h1>Add New Record</h1>
            <form method="POST">
                <input type="text" name="name" placeholder="Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="add">Add</button>
                <button type="button" class="cancel-button" onclick="toggleForm()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable();

            // Check for success message and message type in URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success_message') && urlParams.has('message_type')) {
                const successMessage = urlParams.get('success_message');
                const messageType = urlParams.get('message_type');

                let iconType = 'info'; // Default to info
                let title = 'Notification';

                if (messageType === 'success') {
                    iconType = 'success';
                    title = 'Success';
                } else if (messageType === 'error') {
                    iconType = 'error';
                    title = 'Error';
                }

                Swal.fire({
                    icon: iconType,
                    title: title,
                    text: successMessage,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });

        function toggleForm() {
            const form = document.getElementById('add_form');
            form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
        }

        function cancelEdit(rowId) {
            const editForm = document.getElementById('editForm-' + rowId);
            if (editForm) {
                editForm.style.display = 'none';
            }
        }

        function confirmDelete(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?delete_id=' + id;
                }
            });
        }
    </script>
</body>
</html>
