<?php
session_start();
require '../includes/cnx.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: ../views/dashboard.php?section=orders");
    exit();
}

$order_id = (int) $_GET['order_id'];
$user_id  = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['error_message'] = "Order not found.";
    header("Location: ../views/dashboard.php?section=orders");
    exit();
}
if ($user_role !== 'admin' && $order['user_id'] != $user_id) {
    $_SESSION['error_message'] = "You are not authorized to view this order.";
    header("Location: ../views/dashboard.php?section=orders");
    exit();
}

// Fetch order items with book details
$stmtItems = $pdo->prepare("
    SELECT oi.*, b.title, b.author, b.image 
    FROM order_items oi
    LEFT JOIN books b ON oi.book_id = b.id
    WHERE oi.order_id = ?
");
$stmtItems->execute([$order_id]);
$order_items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Order Details - BookWartz</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
  <style>
      .book-cover {
          width: 60px;
          height: 90px;
          object-fit: cover;
      }
  </style>
</head>
<body>
  <div class="container my-5">
    <div class="mb-4">
      <a href="../views/dashboard.php?section=orders" class="btn btn-secondary">&laquo; Back to Orders</a>
    </div>
    <div class="card mb-4">
      <div class="card-header">
        <h3>Order #<?= htmlspecialchars($order['id']) ?> Details</h3>
      </div>
      <div class="card-body">
        <p><strong>Total:</strong> <?= number_format($order['total'], 2) ?> DT</p>
        <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
        <p><strong>Placed on:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h4>Ordered Items</h4>
      </div>
      <div class="card-body">
        <?php if (empty($order_items)): ?>
          <p>No items found for this order.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead class="table-light">
                <tr>
                  <th>Cover</th>
                  <th>Title</th>
                  <th>Author</th>
                  <th>Quantity</th>
                  <th>Price per Item</th>
                  <th>Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($order_items as $item): ?>
                  <tr>
                    <td>
                      <?php if (!empty($item['image'])): ?>
                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="Cover" class="book-cover">
                      <?php else: ?>
                        N/A
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($item['title']) ?></td>
                    <td><?= htmlspecialchars($item['author']) ?></td>
                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                    <td><?= number_format($item['price'], 2) ?> DT</td>
                    <td><?= number_format($item['price'] * $item['quantity'], 2) ?> DT</td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="5" class="text-end">Grand Total:</th>
                  <th><?= number_format($order['total'], 2) ?> DT</th>
                </tr>
              </tfoot>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
