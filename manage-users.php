<?php
session_start();
require_once 'config.php';

requireRole('manager');

$message = '';
$error = '';

// Handle user actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        // Check if username or email exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $error = "Username or email already exists";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, is_approved) VALUES (?, ?, ?, ?, TRUE)");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
            
            if ($stmt->execute()) {
                $message = "User added successfully";
            } else {
                $error = "Error adding user";
            }
            $stmt->close();
        }
        $check->close();
    }
    
    if (isset($_POST['approve_user'])) {
        $id = intval($_POST['id']);
        $manager_id = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("UPDATE users SET is_approved = TRUE, approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $manager_id, $id);
        if ($stmt->execute()) {
            $message = "User approved successfully";
        } else {
            $error = "Error approving user";
        }
        $stmt->close();
    }
    
    if (isset($_POST['reject_user'])) {
        $id = intval($_POST['id']);
        
        // Delete the unapproved user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND is_approved = FALSE");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "User registration rejected";
        } else {
            $error = "Error rejecting user";
        }
        $stmt->close();
    }
    
    if (isset($_POST['delete_user'])) {
        $id = intval($_POST['id']);
        
        // Don't allow deleting yourself
        if ($id == $_SESSION['user_id']) {
            $error = "You cannot delete your own account";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = "User deleted successfully";
            } else {
                $error = "Error deleting user";
            }
            $stmt->close();
        }
    }
    
    if (isset($_POST['reset_password'])) {
        $id = intval($_POST['id']);
        $new_password = password_hash('Cashier@123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password, $id);
        if ($stmt->execute()) {
            $message = "Password reset to default (Cashier@123)";
        } else {
            $error = "Error resetting password";
        }
        $stmt->close();
    }
}

// Get pending approvals
$pending_users = $conn->query("SELECT * FROM users WHERE is_approved = FALSE ORDER BY created_at DESC");

// Get all approved users
$approved_users = $conn->query("SELECT u.*, a.username as approved_by_name 
                                FROM users u 
                                LEFT JOIN users a ON u.approved_by = a.id 
                                WHERE u.is_approved = TRUE 
                                ORDER BY u.role, u.username");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Coffee POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #000;
            min-height: 100vh;
            padding: 20px;
            position: relative;
        }
        #video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -2;
        }
        .video-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            z-index: -1;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            backdrop-filter: blur(15px);
            flex-wrap: wrap;
            gap: 20px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .brand a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
        }
        .logo {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
        }
        .brand-text h1 {
            color: white;
            font-size: 2rem;
        }
        .btn-add {
            background: rgba(76,175,80,0.3);
            color: #4CAF50;
            border: 1px solid rgba(76,175,80,0.5);
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
        }
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .success-message {
            background: rgba(76,175,80,0.2);
            color: #4CAF50;
            border-left: 4px solid #4CAF50;
        }
        .error-message {
            background: rgba(244,67,54,0.2);
            color: #f44336;
            border-left: 4px solid #f44336;
        }
        .section-title {
            color: white;
            font-size: 1.5rem;
            margin: 30px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255,215,0,0.3);
        }
        .users-table {
            width: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 20px;
            backdrop-filter: blur(15px);
            overflow-x: auto;
            margin-bottom: 40px;
        }
        .users-table table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        .users-table th {
            color: rgba(255,255,255,0.9);
            text-align: left;
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .users-table td {
            color: white;
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .role-badge {
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
        }
        .role-manager {
            background: rgba(255,215,0,0.2);
            color: #ffd700;
        }
        .role-cashier {
            background: rgba(33,150,243,0.2);
            color: #2196F3;
        }
        .status-pending {
            background: rgba(255,152,0,0.2);
            color: #ff9800;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
        }
        .btn-approve, .btn-reject, .btn-reset, .btn-delete {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 5px;
        }
        .btn-approve {
            background: rgba(76,175,80,0.3);
            color: #4CAF50;
        }
        .btn-reject {
            background: rgba(244,67,54,0.3);
            color: #f44336;
        }
        .btn-reset {
            background: rgba(255,193,7,0.3);
            color: #ffc107;
        }
        .btn-delete {
            background: rgba(244,67,54,0.3);
            color: #f44336;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 30px;
            width: 90%;
            max-width: 400px;
        }
        .modal-content h2 {
            color: white;
            margin-bottom: 20px;
        }
        .modal-content input,
        .modal-content select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 5px;
            color: white;
        }
        .modal-buttons {
            display: flex;
            gap: 10px;
        }
        .modal-buttons button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="video-background">
        <source src="videos/coffee-bg2.mp4" type="video/mp4">
    </video>
    <div class="video-overlay"></div>
    
    <div class="container">
        <div class="header">
            <div class="brand">
                <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Back</a>
                <div class="logo"><i class="fas fa-users-cog"></i></div>
                <div class="brand-text">
                    <h1>Manage Users</h1>
                </div>
            </div>
            <button class="btn-add" onclick="openAddModal()"><i class="fas fa-plus"></i> Add User</button>
        </div>
        
        <?php if($message): ?>
        <div class="message success-message"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
        <div class="message error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Pending Approvals -->
        <?php if ($pending_users && $pending_users->num_rows > 0): ?>
        <h2 class="section-title">Pending Approvals</h2>
        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = $pending_users->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="approve_user" class="btn-approve" onclick="return confirm('Approve this user?')">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="reject_user" class="btn-reject" onclick="return confirm('Reject this registration?')">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Approved Users -->
        <h2 class="section-title">Approved Users</h2>
        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Last Login</th>
                        <th>Approved By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = $approved_users->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td><?php echo $user['last_login'] ? date('M d, h:i A', strtotime($user['last_login'])) : 'Never'; ?></td>
                        <td><?php echo $user['approved_by_name'] ?? 'System'; ?></td>
                        <td>
                            <?php if($user['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="reset_password" class="btn-reset" onclick="return confirm('Reset password to default?')">
                                    <i class="fas fa-key"></i> Reset
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="delete_user" class="btn-delete" onclick="return confirm('Delete this user?')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2>Add New User</h2>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <select name="role" required>
                    <option value="cashier">Cashier</option>
                    <option value="manager">Manager</option>
                </select>
                <div class="modal-buttons">
                    <button type="submit" name="add_user" style="background:#4CAF50;color:white;">Add User</button>
                    <button type="button" onclick="closeModal()" style="background:#f44336;color:white;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>