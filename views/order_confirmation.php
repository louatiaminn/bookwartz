<?php
session_start();
require_once '../includes/cnx.php';
if (!isset($_SESSION['order_id'])) {
    header("Location: ../index.php");
    exit();
}

$order_id = $_SESSION['order_id'];
unset($_SESSION['order_id']);

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['error_message'] = "Order not found.";
    header("Location: ../index.php");
    exit();
}

$stmtItems = $pdo->prepare("
    SELECT oi.*, b.title, b.image 
    FROM order_items oi
    JOIN books b ON oi.book_id = b.id
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
  <title>Order Confirmation - BookWartz</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <div class="container my-5">
    <div class="text-center">
      <h1 class="mb-4">Thank You for Your Order!</h1>
      <p class="lead">Your order has been placed successfully.</p>
    </div>
    
    <div class="card mb-4">
      <div class="card-header">
        <h4>Order Details</h4>
      </div>
      <div class="card-body">
        <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order['id']); ?></p>
        <p><strong>Total:</strong> <?php echo number_format($order['total'], 2); ?> dt</p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
        <p><strong>Placed on:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
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
            <table class="table table-bordered">
              <thead class="table-light">
                <tr>
                  <th>Image</th>
                  <th>Title</th>
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
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Book Cover" width="50">
                      <?php else: ?>
                        N/A
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($item['title']); ?></td>
                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                    <td><?php echo number_format($item['price'], 2); ?> dt</td>
                    <td><?php echo number_format($item['price'] * $item['quantity'], 2); ?> dt</td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="4" class="text-end">Grand Total:</th>
                  <th><?php echo number_format($order['total'], 2); ?> dt</th>
                </tr>
              </tfoot>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
    
    <div class="text-center mt-4">
      <a href="index.php" class="btn btn-primary">Continue Shopping</a>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
