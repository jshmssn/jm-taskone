<?php 
include('../php/connection.php');
session_start();

// Ensure the user is logged in
if (empty($_SESSION['id'])) {
    header("Location: index.php");
    exit("Redirecting to index.php");
}

$userId = $_SESSION['id'];
$success_message = '';
$message_type = '';

// Fetch user data
$stmt = $conn->prepare("SELECT name, email, password FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($name, $email, $password_hash);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_name'])) {
        $new_name = $_POST['new_name'];

        $stmt_update = $conn->prepare("UPDATE users SET name = ?, last_update = NOW() WHERE id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("si", $new_name, $userId);
            if ($stmt_update->execute()) {
                $success_message = 'Name updated successfully!';
                $message_type = 'success';
            } else {
                $success_message = 'Error updating name: ' . $stmt_update->error;
                $message_type = 'error';
            }
            $stmt_update->close();
        } else {
            $success_message = "Error preparing update statement: " . $conn->error;
            $message_type = 'error';
        }
    } elseif (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (md5($current_password) !== $password_hash) {
            $success_message = "Current password is incorrect.";
            $message_type = "error";
        } elseif ($new_password !== $confirm_password) {
            $success_message = "New passwords do not match";
            $message_type = "error";
        } else {
            $new_password_hash = md5($new_password);
            $stmt_update = $conn->prepare("UPDATE users SET password = ?, last_update = NOW() WHERE id = ?");
            if ($stmt_update) {
                $stmt_update->bind_param("si", $new_password_hash, $userId);
                if ($stmt_update->execute()) {
                    $success_message = 'Password updated successfully!';
                    $message_type = 'success';
                } else {
                    $success_message = 'Error updating password: ' . $stmt_update->error;
                    $message_type = 'error';
                }
                $stmt_update->close();
            } else {
                $success_message = "Error preparing update statement: " . $conn->error;
                $message_type = 'error';
            }
        }
    }
    $conn->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?success_message=" . urlencode($success_message) . "&message_type=" . urlencode($message_type));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">
    <?php include('../templates/header.php') ?>
    <div class="flex justify-center items-center min-h-screen">
        <div class="w-1/2 space-y-6">
            <!-- Update Name Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h1 class="text-2xl font-semibold text-gray-800 mb-4">Update Name</h1>
                <?php if ($success_message && $message_type === 'success'): ?>
                    <div class="mb-4 p-3 text-sm font-medium text-white bg-green-600 rounded-md">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php elseif ($success_message && $message_type === 'error'): ?>
                    <div class="mb-4 p-3 text-sm font-medium text-white bg-red-600 rounded-md">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                <form action="" method="POST">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
                    <div class="mb-4">
                        <label for="new_name" class="block text-gray-700 text-sm font-bold mb-2">Full Name:</label>
                        <input type="text" id="new_name" name="name" class="form-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label for="" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                        <input type="text" id="" name="" class="form-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 bg-gray-200" disabled value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" name="update_name" class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Save
                        </button>
                    </div>
                </form>
            </div>

            <!-- Update Password Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h1 class="text-2xl font-semibold text-gray-800 mb-4">Update Password</h1>
                <?php if ($success_message && $message_type === 'success'): ?>
                    <div class="mb-4 p-3 text-sm font-medium text-white bg-green-600 rounded-md">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php elseif ($success_message && $message_type === 'error'): ?>
                    <div class="mb-4 p-3 text-sm font-medium text-white bg-red-600 rounded-md">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                <form action="" method="POST">
                    <div class="mb-4">
                        <label for="current_password" class="block text-gray-700 text-sm font-bold mb-2">Current Password:</label>
                        <input type="password" id="current_password" name="current_password" class="form-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50" placeholder="Enter your current password" required>
                    </div>
                    <div class="mb-4">
                        <label for="new_password" class="block text-gray-700 text-sm font-bold mb-2">New Password:</label>
                        <input type="password" id="new_password" name="new_password" class="form-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50" placeholder="Enter your new password">
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Confirm New Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50" placeholder="Confirm your new password">
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" name="update_password" class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const successMessage = urlParams.get('success_message');
            const messageType = urlParams.get('message_type');
            
            if (successMessage && messageType) {
                let title, text, icon;
                
                if (messageType === 'success') {
                    title = 'Success!';
                    text = successMessage;
                    icon = 'success';
                } else if (messageType === 'error') {
                    title = 'Error!';
                    text = successMessage;
                    icon = 'error';
                }
                
                Swal.fire({
                    title: title,
                    text: text,
                    icon: icon,
                    confirmButtonText: '', // Removes the confirm button text
                    showConfirmButton: false, // Hides the confirm button completely
                    timer: 3000 // Optional: Automatically close the alert after 3 seconds
                });
            }
        });
    </script>
</body>
</html>
