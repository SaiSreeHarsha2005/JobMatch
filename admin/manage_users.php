<?php
// SESSION SETUP for localhost (no domain, no HTTPS)
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',      // localhost compatibility
    'secure' => false,   // no HTTPS on localhost
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
session_regenerate_id(true);

// ADMIN AUTH CHECK
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once 'header.php';
require_once '../includes/db_connect.php';
require_once '../includes/csrf_token.php'; // Assumes this sets/generates $_SESSION['csrf_token']

// Handle user deletion (POST with CSRF check)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'], $_POST['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $user_id = intval($_POST['delete_user_id']);

        // Prevent admin from deleting their own account
        if ($user_id === $_SESSION['user_id']) {
            echo '<div class="alert alert-warning">You cannot delete your own account.</div>';
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            echo '<div class="alert alert-success">User deleted successfully.</div>';
        }
    } else {
        echo '<div class="alert alert-danger">Invalid CSRF token.</div>';
    }
}

// Fetch all users
$stmt = $pdo->query("SELECT id, username, email, role FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Manage Users</h2>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['id']) ?></td>
            <td><?= htmlspecialchars($user['username']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= htmlspecialchars($user['role']) ?></td>
            <td>
                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                    <form method="POST" style="display:inline-block;">
                        <input type="hidden" name="delete_user_id" value="<?= $user['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?');">Delete</button>
                    </form>
                <?php else: ?>
                    <em>Cannot delete self</em>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require_once 'footer.php'; ?>
