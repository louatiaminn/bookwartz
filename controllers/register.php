<?php
require '../includes/cnx.php';

if ($_POST) {
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];

    if ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        try {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
            $stmt->execute([$email, $username, $hashedPassword]);
            header("Location: login.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
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
      background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('images/bgc1.jpg');
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
    <?php if (isset($error)): ?>
      <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form action="" method="post">
      <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
      </div>
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" id="username" name="username" class="form-control" placeholder="Choose a username" required>
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
</body>
</html>
