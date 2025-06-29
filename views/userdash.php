<?php
session_start();
require '../includes/cnx.php';

// Ensure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    header("Location: ../controllers/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch current user information.
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // User not found, force logout.
    header("Location: logout.php");
    exit();
}

// Handle profile update form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    
    // If password is provided, update it.
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
        $stmt->execute([$username, $email, $password, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $stmt->execute([$username, $email, $user_id]);
    }
    $_SESSION['username'] = $username;
    $success_message = "Profile updated successfully!";
    
    // Refresh user info.
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Retrieve the current user's cart.
$stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cartData = $stmt->fetch(PDO::FETCH_ASSOC);
$cart_items = [];
$total_price = 0;
if ($cartData) {
    $cart_id = $cartData['id'];
    $stmt = $pdo->prepare("
        SELECT ci.id AS cart_item_id, ci.quantity, b.id AS book_id, b.title, b.price, b.image
        FROM cart_items ci
        JOIN books b ON ci.book_id = b.id
        WHERE ci.cart_id = ?
    ");
    $stmt->execute([$cart_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cart_items as $item) {
        $total_price += $item['price'] * $item['quantity'];
    }
} else {
    $cart_id = null;
}

// Fetch orders for the current user.
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>User Dashboard - BookWartz</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
  <style>
      body {
          background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
          font-family: sans-serif;
          min-height: 100vh;
      }
      
      .dashboard-container {
          background: rgba(255, 255, 255, 0.95);
          border-radius: 15px;
          box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
          backdrop-filter: blur(10px);
          border: 1px solid rgba(255, 255, 255, 0.18);
          overflow: hidden;
      }
      
      .dashboard-header {
          background: linear-gradient(135deg, #027b9a 0%, #005a73 100%);
          color: white;
          padding: 2rem;
          text-align: center;
          position: relative;
      }
      
      .dashboard-header::before {
          content: "";
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: url('../images/Background.jpg') center/cover;
          opacity: 0.1;
      }
      
      .dashboard-header h1 {
          font-weight: bold;
          text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
          margin: 0;
          position: relative;
          z-index: 1;
      }
      
      .dashboard-header .welcome-text {
          margin-top: 0.5rem;
          font-size: 1.1rem;
          opacity: 0.9;
          position: relative;
          z-index: 1;
      }
      
      .action-buttons {
          display: flex;
          gap: 1rem;
          justify-content: center;
          margin-top: 1.5rem;
          position: relative;
          z-index: 1;
      }
      
      .dashboard-btn {
          padding: 12px 25px;
          border-radius: 25px;
          font-weight: bold;
          border: 2px solid #fff;
          background: transparent;
          color: #fff;
          text-decoration: none;
          cursor: pointer;
          position: relative;
          overflow: hidden;
          transition: all 0.3s ease;
          display: inline-flex;
          align-items: center;
          gap: 0.5rem;
      }
      
      .dashboard-btn::before {
          content: "";
          position: absolute;
          top: 0;
          left: -100%;
          width: 100%;
          height: 100%;
          background: rgba(255, 255, 255, 0.2);
          transition: left 0.3s ease;
      }
      
      .dashboard-btn:hover {
          background: rgba(255, 255, 255, 0.1);
          transform: translateY(-2px);
          box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
          color: #fff;
          text-decoration: none;
      }
      
      .dashboard-btn:hover::before {
          left: 100%;
      }
      
      .dashboard-btn.btn-danger {
          border-color: #dc3545;
          background: #dc3545;
      }
      
      .dashboard-btn.btn-danger:hover {
          background: #c82333;
          border-color: #c82333;
      }
      
      .nav-tabs {
          border: none;
          background: #f8f9fa;
          padding: 1rem;
          border-radius: 0;
      }
      
      .nav-tabs .nav-link {
          color: #027b9a;
          border: 2px solid #027b9a;
          border-radius: 25px;
          padding: 12px 25px;
          margin: 0 5px;
          font-weight: bold;
          background: transparent;
          transition: all 0.3s ease;
          position: relative;
          overflow: hidden;
      }
      
      .nav-tabs .nav-link::before {
          content: "";
          position: absolute;
          top: 0;
          left: -100%;
          width: 100%;
          height: 100%;
          background: #027b9a;
          transition: left 0.3s ease;
          z-index: -1;
      }
      
      .nav-tabs .nav-link:hover::before,
      .nav-tabs .nav-link.active::before {
          left: 0;
      }
      
      .nav-tabs .nav-link.active {
          background-color: #027b9a;
          color: #fff;
          border-color: #027b9a;
      }
      
      .nav-tabs .nav-link:hover {
          color: #fff;
          border-color: #027b9a;
      }
      
      .tab-content {
          background: #fff;
          padding: 2rem;
          border-radius: 0 0 15px 15px;
      }
      
      .section-title {
          color: #027b9a;
          font-weight: bold;
          margin-bottom: 1.5rem;
          text-align: center;
          position: relative;
      }
      
      .section-title::after {
          content: "";
          position: absolute;
          bottom: -5px;
          left: 50%;
          transform: translateX(-50%);
          width: 80px;
          height: 3px;
          background-color: #027b9a;
      }
      
      .form-control {
          border-radius: 8px;
          border: 2px solid #e9ecef;
          padding: 12px;
          transition: border-color 0.3s ease;
      }
      
      .form-control:focus {
          border-color: #027b9a;
          box-shadow: 0 0 0 0.2rem rgba(2, 123, 154, 0.25);
      }
      
      .btn-primary {
          background: #027b9a;
          border-color: #027b9a;
          border-radius: 25px;
          padding: 12px 30px;
          font-weight: bold;
          transition: all 0.3s ease;
      }
      
      .btn-primary:hover {
          background: #005a73;
          border-color: #005a73;
          transform: translateY(-2px);
          box-shadow: 0 5px 15px rgba(2, 123, 154, 0.3);
      }
      
      .table {
          border-radius: 10px;
          overflow: hidden;
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      }
      
      .table thead th {
          background: #027b9a;
          color: white;
          font-weight: bold;
          border: none;
          text-align: center;
      }
      
      .table tbody tr {
          transition: background-color 0.3s ease;
      }
      
      .table tbody tr:hover {
          background-color: #f8f9fa;
      }
      
      .book-cover {
          width: 60px;
          height: 90px;
          object-fit: cover;
          border-radius: 5px;
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
      }
      
      .alert-success {
          background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
          border: 1px solid #b8dabc;
          border-radius: 10px;
          color: #155724;
      }
      
      .btn-sm {
          border-radius: 15px;
          font-weight: bold;
      }
      
      .empty-state {
          text-align: center;
          padding: 3rem;
          color: #6c757d;
      }
      
      .empty-state i {
          font-size: 4rem;
          margin-bottom: 1rem;
          opacity: 0.5;
      }
      
      @media (max-width: 768px) {
          .action-buttons {
              flex-direction: column;
              align-items: center;
          }
          
          .dashboard-btn {
              width: 200px;
              justify-content: center;
          }
          
          .dashboard-header {
              padding: 1.5rem;
          }
          
          .tab-content {
              padding: 1rem;
          }
      }
  </style>
</head>
<body>
<div class="container my-5">
  <div class="dashboard-container">
    <div class="dashboard-header">
      <h1>Welcome to Your Dashboard</h1>
      <p class="welcome-text">Hello, <?= htmlspecialchars($user['username']); ?>!</p>
      <div class="action-buttons">
        <a href="../index.php" class="dashboard-btn">
         Return to Home
        </a>
        <a href="logout.php" class="dashboard-btn btn-danger">
        Logout
        </a>
      </div>
    </div>

    <ul class="nav nav-tabs" id="dashboardTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
          👤 Profile
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="cart-tab" data-bs-toggle="tab" data-bs-target="#cart" type="button" role="tab" aria-controls="cart" aria-selected="false">
          🛒 Cart (<?= count($cart_items); ?>)
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="false">
          📦 Orders
        </button>
      </li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
        <h2 class="section-title">Update Profile</h2>
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <div class="row justify-content-center">
          <div class="col-md-8">
            <form method="POST" action="">
              <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($user['username']); ?>" required>
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>" required>
              </div>
              <div class="mb-3">
                <label for="password" class="form-label">New Password (leave empty to keep current):</label>
                <input type="password" name="password" id="password" class="form-control">
              </div>
              <div class="text-center">
                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="tab-pane fade" id="cart" role="tabpanel" aria-labelledby="cart-tab">
        <h2 class="section-title">Your Shopping Cart</h2>
        <?php if (!empty($cart_items)): ?>
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead>
              <tr>
                <th>Cover</th>
                <th>Title</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cart_items as $item): ?>
              <tr>
                <td class="text-center">
                  <?php if (!empty($item['image'])): ?>
                    <img src="<?= htmlspecialchars($item['image']); ?>" alt="Cover" class="book-cover">
                  <?php else: ?>
                    <div class="book-cover d-flex align-items-center justify-content-center bg-light">
                      <span class="text-muted">No Image</span>
                    </div>
                  <?php endif; ?>
                </td>
                <td class="fw-bold"><?= htmlspecialchars($item['title']); ?></td>
                <td class="text-center"><?= number_format($item['price'], 2); ?> dt</td>
                <td>
                  <form action="controllers/cart.php" method="POST" class="d-flex justify-content-center">
                    <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id']; ?>">
                    <input type="number" name="quantity" value="<?= $item['quantity']; ?>" min="1" class="form-control me-2" style="width: 80px;">
                    <button type="submit" name="update_cart" class="btn btn-warning btn-sm">Update</button>
                  </form>
                </td>
                <td class="text-center fw-bold"><?= number_format($item['price'] * $item['quantity'], 2); ?> dt</td>
                <td class="text-center">
                  <form action="controllers/cart.php" method="POST">
                    <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id']; ?>">
                    <button type="submit" name="remove_item" class="btn btn-outline-danger btn-sm">Remove</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
              <tr class="table-info">
                <td colspan="4" class="text-end fw-bold fs-5">Total:</td>
                <td colspan="2" class="fw-bold fs-5 text-center"><?= number_format($total_price, 2); ?> dt</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="text-center mt-3">
          <a href="../controllers/cart.php" class="btn btn-primary">Manage Cart</a>
        </div>
        <?php else: ?>
          <div class="empty-state">
            <div>🛒</div>
            <h4>Your cart is empty</h4>
            <p>Add some books to your cart to see them here.</p>
            <a href="../index.php" class="btn btn-primary">Start Shopping</a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Orders Section -->
      <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
        <h2 class="section-title">Your Order History</h2>
        <?php if (!empty($orders)): ?>
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Total (dt)</th>
                <th>Status</th>
                <th>Placed On</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $order): ?>
              <tr>
                <td class="text-center fw-bold">#<?= htmlspecialchars($order['id']); ?></td>
                <td class="text-center"><?= number_format($order['total'], 2); ?></td>
                <td class="text-center">
                  <button class="badge bg-<?= $order['status'] === 'completed' ? 'success' : ($order['status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                    <?= htmlspecialchars(ucfirst($order['status'])); ?>
              </button>
                </td>
                <td class="text-center"><?= htmlspecialchars($order['created_at']); ?></td>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
          <div class="empty-state">
            <div>📦</div>
            <h4>No orders yet</h4>
            <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
            <a href="../index.php" class="btn btn-primary">Start Shopping</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>