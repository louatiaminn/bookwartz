<?php
require '../includes/cnx.php';

if ($_POST) {
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];

    // Server-side validation
    $errors = [];
    
    // Email validation
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    // Username validation
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long.";
    } elseif (strlen($username) > 20) {
        $errors[] = "Username cannot be longer than 20 characters.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores.";
    }
    
    // Password validation
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/^(?=.*[a-zA-Z])(?=.*\d)/', $password)) {
        $errors[] = "Password must contain at least one letter and one number.";
    }
    
    // Confirm password validation
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "This email address is already registered.";
            }
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = "This username is already taken. Please choose a different one.";
            }
            
            // If no duplicates, proceed with registration
            if (empty($errors)) {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
                $stmt->execute([$email, $username, $hashedPassword]);
                header("Location: login.php");
                exit;
            }
        } catch (PDOException $e) {
            // Handle any other database errors
            $errors[] = "Registration failed. Please try again later.";
            // Log the actual error for debugging (don't show to user)
            error_log("Registration error: " . $e->getMessage());
        }
    }
    
    // If there are errors, they will be displayed as popup
    if (!empty($errors)) {
        $error_message = implode("\\n", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BookWartz - Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      font-family: sans-serif;
      min-height: 100vh;
      background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('../images/bgc1r.jpg');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
    }
    .navbar {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.18);
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    }
    .navbar-brand .name {
      color: #027b9a;
      font-weight: bold;
      font-family: Arial, Helvetica, sans-serif;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
    }
    .login-container {
      max-width: 450px;
      margin: 80px auto;
      padding: 40px;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(15px);
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      position: relative;
      overflow: hidden;
    }
    .login-container::before {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 5px;
      background: linear-gradient(90deg, #027b9a, #00bef0);
    }
    .heading {
      color: #027b9a;
      text-align: center;
      margin-bottom: 30px;
      font-size: 1.8rem;
      font-weight: bold;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
      position: relative;
    }
    .heading::after {
      content: "";
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 3px;
      background: linear-gradient(90deg, #027b9a, #00bef0);
      border-radius: 2px;
    }
    .form-group label, .form-label {
      font-weight: bold;
      color: #027b9a;
      margin-bottom: 8px;
      display: block;
    }
    .form-control {
      padding: 15px;
      border: 2px solid #e9ecef;
      border-radius: 10px;
      transition: all 0.3s ease;
      background: rgba(255, 255, 255, 0.9);
      font-size: 16px;
    }
    .form-control:focus {
      border-color: #027b9a;
      box-shadow: 0 0 0 0.2rem rgba(2,123,154,0.25);
      background: #fff;
      transform: translateY(-2px);
    }
    .form-control::placeholder {
      color: #a0a0a0;
      opacity: 0.8;
    }
    .btn-custom {
      background: linear-gradient(135deg, #027b9a 0%, #00bef0 100%);
      border: none;
      padding: 15px 30px;
      border-radius: 25px;
      font-weight: bold;
      font-size: 16px;
      color: white;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      width: 100%;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .btn-custom::before {
      content: "";
      position: absolute;
      top: 0; left: -100%;
      width: 100%; height: 100%;
      background: linear-gradient(135deg, #00bef0 0%, #027b9a 100%);
      transition: left 0.3s ease;
    }
    .btn-custom:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(2,123,154,0.3);
    }
    .btn-custom:hover::before {
      left: 0;
    }
    
    /* Custom popup styles */
    .popup-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
    }
    .popup-content {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: white;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
      max-width: 400px;
      width: 90%;
      text-align: center;
    }
    .popup-header {
      color: #d32f2f;
      font-size: 1.5rem;
      font-weight: bold;
      margin-bottom: 15px;
    }
    .popup-message {
      color: #333;
      line-height: 1.6;
      margin-bottom: 20px;
      white-space: pre-line;
    }
    .popup-btn {
      background: #d32f2f;
      color: white;
      border: none;
      padding: 10px 25px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      transition: background 0.3s ease;
    }
    .popup-btn:hover {
      background: #b71c1c;
    }
    
    .success-popup .popup-header {
      color: #2e7d32;
    }
    .success-popup .popup-btn {
      background: #2e7d32;
    }
    .success-popup .popup-btn:hover {
      background: #1b5e20;
    }
    
    @media (max-width: 768px) {
      .login-container { margin: 40px 20px; padding: 30px 20px; }
      .heading { font-size: 1.5rem; }
      .navbar-brand .name { font-size: 1.2rem; }
    }
    @media (max-width: 480px) {
      .login-container { margin: 20px 10px; padding: 25px 15px; }
      .heading { font-size: 1.3rem; }
      .form-control { padding: 12px; }
      .btn-custom { padding: 12px 25px; font-size: 14px; }
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center" href="../index.php">
        <img src="../images/logo.png" alt="Logo" style="width:50px; height:50px;">
        <h2 class="ms-2 name">BookWartz</h2>
      </a>
    </div>
  </nav>

  <div class="login-container">
    <h1 class="heading">Register at BookWartz</h1>
    <form action="" method="post" onsubmit="return validateForm()">
      <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
      </div>
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" id="username" name="username" class="form-control" placeholder="Choose a username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
      </div>
      <div class="mb-3">
        <label for="confirm-password" class="form-label">Confirm Password</label>
        <input type="password" id="confirm-password" name="confirm-password" class="form-control" placeholder="Confirm your password" required>
      </div>
      <button type="submit" class="btn btn-custom mt-2">Register</button>
      <div class="text-center mt-3">
        <p class="mb-0">Already have an account? <a href="login.php" class="text-decoration-none">Login here</a></p>
      </div>
    </form>
  </div>

  <!-- Custom Popup -->
  <div id="popup" class="popup-overlay">
    <div class="popup-content">
      <div class="popup-header" id="popup-header">Error</div>
      <div class="popup-message" id="popup-message"></div>
      <button class="popup-btn" onclick="closePopup()">OK</button>
    </div>
  </div>

  <footer class="text-white pt-4" style="background: linear-gradient(135deg, #004658);">
    <div class="container">
      <div class="row">
        <div class="col-md-4 mb-3">
          <a class="d-flex align-items-center mb-2" href="../index.php">
            <img src="../images/logo.png" alt="Logo" style="width:50px; height:50px;">
            <span class="ms-2 h5 mb-0">BookWartz</span>
          </a>
          <p>BookWartz is your go-to online bookstore, offering a wide selection of genres. Discover, read, and enjoy from home.</p>
        </div>
        <div class="col-md-4 mb-3 text-center">
          <h5>Useful Links</h5>
          <ul class="list-unstyled">
            <li><a href="#" class="text-white">Home</a></li>
            <li><a href="#" class="text-white">Shop</a></li>
            <li><a href="#" class="text-white">Contact Us</a></li>
            <li><a href="#" class="text-white">About Us</a></li>
          </ul>
        </div>
        <div class="col-md-4 mb-3">
          <h5>Newsletter</h5>
          <form class="d-flex">
            <input type="email" class="form-control me-2" placeholder="Enter your email">
            <button class="btn btn-primary" type="submit">Subscribe</button>
          </form>
        </div>
      </div>
      <hr class="border-light">
      <div class="text-center pb-2">
        <small>&copy; 2024 BookWartz. All rights reserved.</small>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function showPopup(message, isError = true) {
      const popup = document.getElementById('popup');
      const popupContent = popup.querySelector('.popup-content');
      const header = document.getElementById('popup-header');
      const messageEl = document.getElementById('popup-message');
      
      if (isError) {
        popupContent.classList.remove('success-popup');
        header.textContent = 'Registration Error';
      } else {
        popupContent.classList.add('success-popup');
        header.textContent = 'Success';
      }
      
      messageEl.textContent = message;
      popup.style.display = 'block';
    }
    
    function closePopup() {
      document.getElementById('popup').style.display = 'none';
    }
    
    // Close popup when clicking outside
    document.getElementById('popup').addEventListener('click', function(e) {
      if (e.target === this) {
        closePopup();
      }
    });
    
    function validateForm() {
      var email = document.getElementById('email').value.trim();
      var username = document.getElementById('username').value.trim();
      var password = document.getElementById('password').value;
      var confirmPassword = document.getElementById('confirm-password').value;
      
      var errors = [];
      
      // Email validation
      if (email === '') {
        errors.push('Please enter your email address!');
      } else {
        var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
          errors.push('Please enter a valid email address!');
        }
      }
      
      // Username validation
      if (username === '') {
        errors.push('Please enter a username!');
      } else if (username.length < 3) {
        errors.push('Username must be at least 3 characters long!');
      } else if (username.length > 20) {
        errors.push('Username cannot be longer than 20 characters!');
      } else {
        var usernamePattern = /^[a-zA-Z0-9_]+$/;
        if (!usernamePattern.test(username)) {
          errors.push('Username can only contain letters, numbers, and underscores!');
        }
      }
      
      // Password validation
      if (password === '') {
        errors.push('Please enter a password!');
      } else if (password.length < 8) {
        errors.push('Password must be at least 8 characters long!');
      } else {
        var passwordPattern = /^(?=.*[a-zA-Z])(?=.*\d)/;
        if (!passwordPattern.test(password)) {
          errors.push('Password must contain at least one letter and one number!');
        }
      }
      
      // Confirm password validation
      if (confirmPassword === '') {
        errors.push('Please confirm your password!');
      } else if (password !== confirmPassword) {
        errors.push('Passwords do not match!');
      }
      
      if (errors.length > 0) {
        showPopup(errors.join('\n'));
        return false;
      }
      
      return true;
    }
    
    // Show server-side errors as popup
    <?php if (isset($error_message)): ?>
    document.addEventListener('DOMContentLoaded', function() {
      showPopup('<?= addslashes($error_message) ?>');
    });
    <?php endif; ?>
    
    // Real-time validation feedback
    document.getElementById('email').addEventListener('blur', function() {
      var email = this.value.trim();
      if (email.length > 0) {
        var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
          this.style.borderColor = '#d32f2f';
        } else {
          this.style.borderColor = '#e9ecef';
        }
      }
    });
    
    document.getElementById('username').addEventListener('blur', function() {
      var username = this.value.trim();
      if (username.length > 0) {
        if (username.length < 3 || username.length > 20) {
          this.style.borderColor = '#d32f2f';
        } else {
          var usernamePattern = /^[a-zA-Z0-9_]+$/;
          if (!usernamePattern.test(username)) {
            this.style.borderColor = '#d32f2f';
          } else {
            this.style.borderColor = '#e9ecef';
          }
        }
      }
    });
    
    document.getElementById('password').addEventListener('blur', function() {
      var password = this.value;
      if (password.length > 0) {
        if (password.length < 8) {
          this.style.borderColor = '#d32f2f';
        } else {
          var passwordPattern = /^(?=.*[a-zA-Z])(?=.*\d)/;
          if (!passwordPattern.test(password)) {
            this.style.borderColor = '#d32f2f';
          } else {
            this.style.borderColor = '#e9ecef';
          }
        }
      }
      
      // Also validate confirm password
      var confirmPassword = document.getElementById('confirm-password').value;
      var confirmField = document.getElementById('confirm-password');
      if (confirmPassword && password !== confirmPassword) {
        confirmField.style.borderColor = '#d32f2f';
      } else if (confirmPassword) {
        confirmField.style.borderColor = '#e9ecef';
      }
    });
    
    document.getElementById('confirm-password').addEventListener('blur', function() {
      var password = document.getElementById('password').value;
      var confirmPassword = this.value;
      if (confirmPassword.length > 0) {
        if (password !== confirmPassword) {
          this.style.borderColor = '#d32f2f';
        } else {
          this.style.borderColor = '#e9ecef';
        }
      }
    });
    
    // Reset border colors on focus
    document.querySelectorAll('.form-control').forEach(function(input) {
      input.addEventListener('focus', function() {
        this.style.borderColor = '#027b9a';
      });
    });
  </script>
</body>
</html>