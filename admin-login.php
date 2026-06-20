<?php

session_start();

require_once "includes/db.php";

$errors = [];


if($_SERVER["REQUEST_METHOD"] == "POST"){

    $email = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';

    if(empty($email)){
      $errors[] = "Email address is required";
    }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
      $errors[] = "Please enter a valid email address";
    }
    
    if(empty($password)){
      $errors[] = "Password is required";
    }


    // Check if user exists 
    if(empty($errors)){
      $sql = "SELECT id, full_name, role, password FROM admins WHERE email = ?";
      $stmt = $db->prepare($sql);
      $stmt->execute([$email]);
      $admin = $stmt->fetch();

      if(!$admin || !password_verify($password, $admin["password"])){
        $errors[] = "Invalid Email or password";
      }
    }


    if(empty($errors)){
      $_SESSION["admin_id"] = $admin["id"];
      $_SESSION["admin_name"] = $admin["full_name"];
      $_SESSION["admin_role"] = $admin["role"];

      header("Location: admin/dashboard.php");
    }


}


?>





<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Login — BlogForge</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/css/admin-login.css"/>
</head>
<body>

  <div class="login-wrapper">

    <!-- LOGO -->
    <div class="login-logo">
      <div class="logo-mark">BF</div>
      <div class="logo-text">Blog<span>Forge</span></div>
    </div>

    <!-- CARD -->
    <div class="login-card">

      <!-- CARD HEADER -->
      <div class="card-tag">Admin Access</div>
      <div class="card-title">Welcome back,<br/>Admin.</div>
      <div class="card-subtitle">
        Sign in to access the BlogForge admin panel.
        This area is restricted to authorised personnel only.
      </div>

      <!-- SECURITY NOTE -->
      <div class="security-note">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
        Your session is protected. Never share your login credentials with anyone.
      </div>

        <?php
        
        if(!empty($errors)){ ?>

        <div class="error-box">
            <?php foreach($errors as $error) {?>
            <p><?php echo htmlspecialchars($error); ?></p>
            <?php } ?>
        </div>
        <?php } ?>

      <!-- DEMO: uncomment to preview error state -->
      <!--
      <div class="error-box">
        <p>Invalid email or password. Please try again.</p>
      </div>
      -->

      <!-- FORM -->
      <form action="admin-login.php" method="POST">

        <div class="form-group">
          <label for="email">Email Address</label>
          <div class="input-wrap">
            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
            <input
              type="email"
              id="email"
              name="email"
              placeholder="admin@blogforge.com"
              value="<?php echo htmlspecialchars($email ?? ''); ?>"
              autocomplete="email"
              required
            />
          </div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrap">
            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Your password"
              autocomplete="current-password"
              required
            />
            <button
              type="button"
              class="password-toggle"
              onclick="togglePassword('password', this)"
              title="Show/hide password"
            >
              <!-- Eye open -->
              <svg class="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              <!-- Eye closed -->
              <svg class="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-submit">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
            <polyline points="10,17 15,12 10,7"/>
            <line x1="15" y1="12" x2="3" y2="12"/>
          </svg>
          Sign In to Admin Panel
        </button>

      </form>

      <div class="card-divider"></div>

      <div class="login-footer">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        Not an admin?
        <a href="index.html">Return to website</a>
      </div>

    </div>
  </div>

  <script src="assets/js/admin-login.js"></script>
</body>
</html>
