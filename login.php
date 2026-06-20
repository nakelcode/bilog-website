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
      $sql = "SELECT id, full_name, avatar, password FROM users WHERE email = ?";
      $stmt = $db->prepare($sql);
      $stmt->execute([$email]);
      $user = $stmt->fetch();

      if(!$user || !password_verify($password, $user["password"])){
        $errors[] = "Invalid Email or password";
      }
    }


    if(empty($errors)){
      $_SESSION["user_id"] = $user["id"];
      $_SESSION["user_name"] = $user["full_name"];
      $_SESSION["user_avatar"] = $user["avatar"];

      header("Location: profile.php");
    }


}


?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login — BlogForge</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/css/auth.css"/>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar">
    <a href="index.php" class="nav-logo">Blog<span>Forge</span></a>
    <a href="index.php" class="nav-back">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="19" y1="12" x2="5" y2="12"/>
        <polyline points="12,19 5,12 12,5"/>
      </svg>
      Back to home
    </a>
  </nav>

  <!-- MAIN -->
  <div class="auth-wrapper">
    <div class="auth-container">

      <!-- LEFT PANEL -->
      <div class="auth-left">
        <div class="auth-left-tag">Welcome back</div>
        <h1 class="auth-left-heading">
          Good to see<br/>you <em>again.</em>
        </h1>
        <p class="auth-left-text">
          Log in to access your profile, track your submitted posts,
          and keep writing for the BlogForge community.
        </p>
        <div class="auth-left-divider"></div>
        <ul class="auth-left-points">
          <li>See the status of all your posts</li>
          <li>Submit new articles for review</li>
          <li>Manage your profile and avatar</li>
        </ul>
      </div>

      <!-- RIGHT PANEL -->
      <div class="auth-right">
        <div class="auth-form-tag">User Login</div>
        <div class="auth-form-title">Sign in to your account</div>
        <div class="auth-form-subtitle">
          Don't have an account?
          <a href="register.php" style="color:var(--grey-faint);font-weight:600;text-decoration:none">Register here</a>
        </div>

        
        <?php
        
        if(!empty($errors)){ ?>

        <div class="error-box">
            <?php foreach($errors as $error) {?>
            <p><?php echo htmlspecialchars($error); ?></p>
            <?php } ?>
        </div>
        <?php } ?>


        <form action="login.php" method="POST">

          <div class="form-group">
            <label for="email">Email Address</label>
            <div class="input-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
              </svg>
              <input
                type="email"
                id="email"
                name="email"
                placeholder="joan@email.com"
                value="<?php echo htmlspecialchars($email ?? ''); ?>"
                autocomplete="email"
              />
            </div>
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
              <input
                type="password"
                id="password"
                name="password"
                placeholder="Your password"
                autocomplete="current-password"
              />
              <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                <!-- Eye open (shown by default) -->
                <svg class="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
                <!-- Eye closed (hidden by default) -->
                <svg class="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                  <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                  <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
              </button>
            </div>
          </div>

          <button type="submit" class="btn-submit">Sign In</button>

        </form>

        <div class="auth-switch">
          Don't have an account?
          <a href="register.php">Create one here</a>
        </div>

      </div>
    </div>
  </div>

  <script src="assets/js/auth.js"></script>
</body>
</html>