<?php
session_start();

require_once "includes/db.php";

$errors = [];
$success = '';


if($_SERVER["REQUEST_METHOD"] == "POST"){


  // 1. Collect and sanitize inputs 
  $full_name = trim($_POST["full_name"] ?? '');
  $email = trim($_POST["email"] ?? '');
  $password = $_POST["password"] ?? '';
  $confirm_password = $_POST["confirm_password"] ?? '';

  // Validate Inputs 
  if(empty($full_name)){
      $errors[] = "Full name is required";
  }

  if(empty($email)){
    $errors[] = "Email is required";
  }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
    $errors[] = "Please enter a valid email address";
  }

  if(empty($password)){
    $errors[] = "Password is required";
  }elseif(strlen($password) < 8){
    $errors[] = "Password must be at least 8 characters";
  }

  if($password !== $confirm_password){
    $errors[] = "Passwords do not match";
  }

  
  // Check if email exists 
  if(empty($errors)){
    $sql = "SELECT id FROM users WHERE email = :email";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if($stmt->fetch()){
      $errors[] = "An account with this email already exists";
    }
  }

  // Insert Into Database 
  if(empty($errors)){

  $hash_pswd = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (full_name, email, password) VALUES (:full, :email, :pass)";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':full', $full_name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':pass', $hash_pswd);
    $stmt->execute();

    // Log them in immediately after registration 
    $new_user_id = $db->lastInsertId();

    $_SESSION["user_id"] = $new_user_id;
    $_SESSION["user_name"] = $full_name;

    header("Location: profile.php");
    exit;
    


  }


}


?>





<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register — BlogForge</title>
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
        <div class="auth-left-tag">Join BlogForge</div>
        <h1 class="auth-left-heading">
          Your story<br/>starts <em>here.</em>
        </h1>
        <p class="auth-left-text">
          Create a free account and become part of a growing community
          of writers sharing ideas on business, technology, and life.
        </p>
        <div class="auth-left-divider"></div>
        <ul class="auth-left-points">
          <li>Write and submit posts for review</li>
          <li>Track approval status in your profile</li>
          <li>Build your author presence</li>
        </ul>
      </div>

      <!-- RIGHT PANEL -->
      <div class="auth-right">
        <div class="auth-form-tag">New Account</div>
        <div class="auth-form-title">Create your account</div>
        <div class="auth-form-subtitle">
          Already have an account?
          <a href="login.php" style="color:var(--grey-faint);font-weight:600;text-decoration:none">Login here</a>
        </div>

        <!-- ERRORS — PHP will output here -->


        <?php
        
        if(!empty($errors)){ ?>

        <div class="error-box">
            <?php foreach($errors as $error) {?>
            <p><?php echo htmlspecialchars($error); ?></p>
            <?php } ?>
        </div>
        <?php } ?>
      

        <form action="register.php" method="POST">

          <div class="form-group">
            <label for="full_name">Full Name</label>
            <div class="input-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
              <input
                type="text"
                id="full_name"
                name="full_name"
                placeholder="e.g. Joan Wallace"
                value="<?php echo htmlspecialchars($full_name ?? ''); ?>"
                autocomplete="name"
              />
            </div>
          </div>

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
                placeholder="Min. 8 characters"
                autocomplete="new-password"
              />
              <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                <svg class="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
                <svg class="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                  <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                  <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
              </button>
            </div>
          </div>

          <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <div class="input-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
              <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Repeat your password"
                autocomplete="new-password"
              />
              <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                <svg class="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
                <svg class="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                  <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                  <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
              </button>
            </div>
          </div>

          <button type="submit" class="btn-submit">Create Account</button>

        </form>

        <div class="auth-switch">
          Already have an account?
          <a href="login.php">Sign in here</a>
        </div>

      </div>
    </div>
  </div>

  <script src="assets/js/auth.js"></script>
</body>
</html>
