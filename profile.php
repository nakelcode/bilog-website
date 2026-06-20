<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

$errors  = [];
$success = '';

// ─── Fetch current user from database ───
$stmt = $db->prepare("
    SELECT id, full_name, email, avatar, password, created_at
    FROM users WHERE id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// ─── If user not found, log them out ───
if (!$user) {
    header('Location: logout.php');
    exit;
}

// ─── Handle POST actions ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    // ─── UPDATE PROFILE ───
    if ($action === 'update_profile') {

        $full_name = trim($_POST['full_name'] ?? '');

        if (empty($full_name)) {
            $errors[] = "Full name is required.";
        }

        // ─── Handle avatar upload ───
        $new_avatar = $user['avatar'];

        if (!empty($_FILES['avatar']['name'])) {

            $file     = $_FILES['avatar'];
            $allowed  = ['image/jpeg', 'image/png', 'image/webp'];
            $max_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($file['type'], $allowed)) {
                $errors[] = "Avatar must be JPG, PNG or WEBP.";

            } elseif ($file['size'] > $max_size) {
                $errors[] = "Avatar must be smaller than 2MB.";

            } else {
                $extension    = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid('avatar_') . '.' . $extension;
                $upload_path  = 'uploads/avatars/' . $new_filename;

                if (move_uploaded_file($file['tmp_name'], $upload_path)) {

                    // Delete old avatar if exists
                    if (!empty($user['avatar']) && file_exists('uploads/avatars/' . $user['avatar'])) {
                        unlink('uploads/avatars/' . $user['avatar']);
                    }

                    $new_avatar = $new_filename;
                } else {
                    $errors[] = "Avatar upload failed. Please try again.";
                }
            }
        }

        if (empty($errors)) {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, avatar = ? WHERE id = ?");
            $stmt->execute([$full_name, $new_avatar, $_SESSION['user_id']]);

            // Update session immediately
            $_SESSION['user_name']   = $full_name;
            $_SESSION['user_avatar'] = $new_avatar;

            $success = "Profile updated successfully.";

            // Re-fetch updated user
            $stmt = $db->prepare("SELECT id, full_name, email, avatar, password, created_at FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
        }
    }

    // ─── CHANGE PASSWORD ───
    elseif ($action === 'change_password') {

        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password']     ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password)) {
            $errors[] = "Current password is required.";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect.";
        }

        if (empty($new_password)) {
            $errors[] = "New password is required.";
        } elseif (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters.";
        }

        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match.";
        }

        if (empty($errors)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt   = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $_SESSION['user_id']]);
            $success = "Password changed successfully.";
        }
    }

    // ─── DELETE POST ───
    elseif ($action === 'delete_post') {
        $post_id = (int)($_POST['post_id'] ?? 0);

        if ($post_id > 0) {
            $stmt = $db->prepare("
                DELETE FROM posts
                WHERE id = ? AND user_id = ? AND status = 'pending'
            ");
            $stmt->execute([$post_id, $_SESSION['user_id']]);
            $success = "Post deleted successfully.";
        }
    }
}

// ─── Active tab ───
$tab          = $_GET['tab'] ?? 'all';
$allowed_tabs = ['all', 'approved', 'pending', 'declined'];
if (!in_array($tab, $allowed_tabs)) $tab = 'all';

// ─── Count user posts per status ───
$stmt = $db->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$count_all = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ? AND status = 'approved'");
$stmt->execute([$_SESSION['user_id']]);
$count_approved = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$_SESSION['user_id']]);
$count_pending = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ? AND status = 'declined'");
$stmt->execute([$_SESSION['user_id']]);
$count_declined = $stmt->fetchColumn();

// ─── Fetch posts based on active tab ───
if ($tab === 'all') {
    $stmt = $db->prepare("
        SELECT p.id, p.title, p.status, p.created_at, c.name AS category_name
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);

} elseif ($tab === 'approved') {
    $stmt = $db->prepare("
        SELECT p.id, p.title, p.status, p.created_at, c.name AS category_name
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.user_id = ? AND p.status = 'approved'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);

} elseif ($tab === 'pending') {
    $stmt = $db->prepare("
        SELECT p.id, p.title, p.status, p.created_at, c.name AS category_name
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.user_id = ? AND p.status = 'pending'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);

} elseif ($tab === 'declined') {
    $stmt = $db->prepare("
        SELECT p.id, p.title, p.status, p.created_at, c.name AS category_name
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.user_id = ? AND p.status = 'declined'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
}

$posts = $stmt->fetchAll();

// ─── Avatar initials helper ───
$initials = strtoupper(substr($user['full_name'], 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Profile — BlogForge</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="assets/css/profile.css"/>
    <link rel="stylesheet" href="assets/css/bilog.css"/>
</head>
<body>

<?php require_once 'includes/header.php'; ?>

<div class="page-wrapper">

    <!-- SUCCESS TOAST -->
    <?php if ($success || isset($_GET['success'])): ?>
        <div class="toast toast-success" id="successToast" style="display:block">
            <?php if (isset($_GET['success'])): ?>
                Your post was submitted successfully and is now pending review.
            <?php else: ?>
                <?= htmlspecialchars($success) ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ERROR TOAST -->
    <?php if (!empty($errors)): ?>
        <div class="toast toast-error" style="display:block">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ── PROFILE HEADER ── -->
    <div class="profile-header">
        <div class="avatar-wrap">
            <?php if (!empty($user['avatar'])): ?>
                <img
                    src="uploads/avatars/<?= htmlspecialchars($user['avatar']) ?>"
                    alt="Avatar"
                    class="avatar-img"
                    id="avatarDisplay"
                    style="width:100%;height:100%;object-fit:cover;border-radius:50%"
                />
            <?php else: ?>
                <div class="avatar-img" id="avatarDisplay">
                    <?= $initials ?>
                </div>
            <?php endif; ?>
            <div class="avatar-edit-btn" onclick="triggerAvatarInput()" title="Change photo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                    <circle cx="12" cy="13" r="4"/>
                </svg>
            </div>
            <input type="file" id="avatarInput" accept="image/*" style="display:none" onchange="handleAvatarChange(this)"/>
        </div>

        <div class="profile-info">
            <div class="profile-name" id="displayName">
                <?= htmlspecialchars($user['full_name']) ?>
            </div>
            <div class="profile-email">
                <?= htmlspecialchars($user['email']) ?>
            </div>
            <div class="profile-meta">
                <div class="profile-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    Joined <strong><?= date('F Y', strtotime($user['created_at'])) ?></strong>
                </div>
                <div class="profile-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    </svg>
                    <strong><?= $count_all ?></strong> post<?= $count_all !== 1 ? 's' : '' ?> submitted
                </div>
                <div class="profile-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20,6 9,17 4,12"/>
                    </svg>
                    <strong><?= $count_approved ?></strong> approved
                </div>
            </div>
        </div>

        <div class="profile-actions">
            <button class="btn btn-white" id="editBtn" onclick="toggleEditPanel()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Edit Profile
            </button>
        </div>
    </div>

    <!-- ── EDIT PROFILE PANEL ── -->
    <div class="edit-panel" id="editPanel">
        <div class="edit-panel-header">
            <div class="edit-panel-title">Edit Profile</div>
        </div>

        <!-- UPDATE PROFILE FORM -->
        <form method="POST" action="profile.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_profile"/>
            <div class="form-grid cols-2">

                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <input
                        type="text"
                        name="full_name"
                        id="inputName"
                        value="<?= htmlspecialchars($user['full_name']) ?>"
                        placeholder="Your full name"
                    />
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input
                        type="email"
                        value="<?= htmlspecialchars($user['email']) ?>"
                        readonly
                    />
                    <span class="input-hint">Email cannot be changed.</span>
                </div>

                <div class="form-group full">
                    <label>Profile Picture</label>
                    <input
                        type="file"
                        name="avatar"
                        id="avatarInputForm"
                        accept="image/*"
                        onchange="handleAvatarChange(this)"
                    />
                    <span class="input-hint">JPG or PNG · Max 2MB</span>
                </div>

            </div>

            <div class="form-footer">
                <button type="button" class="btn btn-outline" onclick="toggleEditPanel()">Cancel</button>
                <button type="submit" class="btn btn-white">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="20,6 9,17 4,12"/>
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>

        <div class="divider"></div>

        <!-- CHANGE PASSWORD FORM -->
        <form method="POST" action="profile.php">
            <input type="hidden" name="action" value="change_password"/>
            <div style="margin-bottom:16px">
                <div class="section-label">Change Password</div>
                <div class="form-grid cols-2">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input
                            type="password"
                            name="current_password"
                            id="inputCurrentPass"
                            placeholder="Enter current password"
                        />
                    </div>
                    <div class="form-group" style="grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr;gap:16px">
                        <div class="form-group">
                            <label>New Password</label>
                            <input
                                type="password"
                                name="new_password"
                                id="inputNewPass"
                                placeholder="Min. 8 characters"
                            />
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input
                                type="password"
                                name="confirm_password"
                                id="inputConfirmPass"
                                placeholder="Repeat new password"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-footer">
                <button type="button" class="btn btn-outline" onclick="toggleEditPanel()">Cancel</button>
                <button type="submit" class="btn btn-white">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="20,6 9,17 4,12"/>
                    </svg>
                    Update Password
                </button>
            </div>
        </form>

    </div>

    <!-- ── MY POSTS ── -->
    <div class="posts-section">
        <div class="posts-header">
            <div class="posts-title">My Posts</div>
            <span class="posts-count"><?= $count_all ?> total</span>
        </div>

        <!-- TABS -->
        <div class="tabs">
            <div class="tab <?= $tab === 'all'      ? 'active' : '' ?>" onclick="switchTab(this,'all')">
                All (<?= $count_all ?>)
            </div>
            <div class="tab <?= $tab === 'approved' ? 'active' : '' ?>" onclick="switchTab(this,'approved')">
                Approved (<?= $count_approved ?>)
            </div>
            <div class="tab <?= $tab === 'pending'  ? 'active' : '' ?>" onclick="switchTab(this,'pending')">
                Pending (<?= $count_pending ?>)
            </div>
            <div class="tab <?= $tab === 'declined' ? 'active' : '' ?>" onclick="switchTab(this,'declined')">
                Declined (<?= $count_declined ?>)
            </div>
        </div>

        <!-- POST LIST -->
        <div class="post-list" id="postList">
            <?php foreach ($posts as $post): ?>
                <div class="post-card" data-status="<?= $post['status'] ?>">
                    <div class="post-card-left">
                        <div class="post-card-cat">
                            <?= htmlspecialchars($post['category_name'] ?? 'Uncategorised') ?>
                        </div>
                        <div class="post-card-title">
                            <?= htmlspecialchars($post['title']) ?>
                        </div>
                        <div class="post-card-meta">
                            <div class="post-card-date">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                <?= date('M j, Y', strtotime($post['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                    <div class="post-card-right">
                        <span class="badge badge-<?= $post['status'] ?>">
                            <?= ucfirst($post['status']) ?>
                        </span>
                        <?php if ($post['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action"  value="delete_post"/>
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>"/>
                                <button
                                    type="submit"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this post?')"
                                >Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- EMPTY STATE -->
        <div class="empty-state" id="emptyState" style="<?= empty($posts) ? 'display:block' : 'display:none' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14,2 14,8 20,8"/>
            </svg>
            <h3>No posts here</h3>
            <p>
                <?php if ($tab === 'all'): ?>
                    You haven't submitted any posts yet.
                    <a href="write.php">Write your first post →</a>
                <?php else: ?>
                    You have no <?= $tab ?> posts yet.
                <?php endif; ?>
            </p>
        </div>

    </div>
</div>

<script src="assets/js/profile.js"></script>
<script src="assets/js/bilog.js"></script>
</body>
</html>