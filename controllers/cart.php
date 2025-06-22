<?php
session_start();
require '../includes/cnx.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to view your cart.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cart) {
    $cart_id = null;
} else {
    $cart_id = $cart['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    $cart_item_id = $_POST['cart_item_id'];
    $new_quantity = max(1, intval($_POST['quantity']));

    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
    $stmt->execute([$new_quantity, $cart_item_id]);

    $_SESSION['success_message'] = "Cart updated successfully!";
    header("Location: cart.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $cart_item_id = $_POST['cart_item_id'];

    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ?");
    $stmt->execute([$cart_item_id]);

    $_SESSION['success_message'] = "Item removed from cart!";
    header("Location: cart.php");
    exit();
}

$cart_items = [];
$total_price = 0;
if ($cart_id) {
    $stmt = $pdo->prepare("
        SELECT ci.id AS cart_item_id, ci.book_id, b.title, b.price, b.image, ci.quantity
        FROM cart_items ci
        JOIN books b ON ci.book_id = b.id
        WHERE ci.cart_id = ?
    ");
    $stmt->execute([$cart_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cart_items as $item) {
        $total_price += $item['price'] * $item['quantity'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    // Retrieve and sanitize form input
    $name            = trim($_POST['name']);
    $card_number     = trim($_POST['card_number']);
    $expiry_date     = trim($_POST['expiry_date']);
    $cvv             = trim($_POST['cvv']);
    $billing_address = trim($_POST['billing_address']);


    if (empty($name) || empty($card_number) || empty($expiry_date) || empty($cvv) || empty($billing_address)) {
        $_SESSION['error_message'] = "All checkout fields are required.";
        header("Location: cart.php");
        exit();
    }

    $orderQuery = "INSERT INTO orders (user_id, total, status, created_at) VALUES (?, ?, 'Pending', NOW())";
    $stmt = $pdo->prepare($orderQuery);
    $stmt->execute([$user_id, $total_price]);


    $order_id = $pdo->lastInsertId();

    $orderItemQuery = "INSERT INTO order_items (order_id, book_id, quantity, price) VALUES (?, ?, ?, ?)";
    $stmtOrderItem = $pdo->prepare($orderItemQuery);

    foreach ($cart_items as $item) {
        $stmtOrderItem->execute([
            $order_id,
            $item['book_id'],
            $item['quantity'],
            $item['price']
        ]);
    }

    if ($cart_id) {
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $stmt->execute([$cart_id]);
    }


    $_SESSION['success_message'] = "Your order has been placed successfully!";
    header("Location: ../views/order_confirmation.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookWartz - Cart</title>
  <link rel="stylesheet" href="../assets/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
</head>
<body>
    <div class="banner banner-cart">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <a class="navbar-brand d-flex align-items-center" href="../index.php">
                    <img src="../images/logo.png" alt="BookWartz Logo" class="logo" style="width: 50px; height: 50px;">
                    <h2 class="name ms-2">BookWartz</h2>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation" style="background-color: #007bff; border: none; border-radius: 5px;">
                    <span class="navbar-toggler-icon" style="background-color: #fff; width: 30px; height: 30px;"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarContent">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <li class="nav-item"><a class="nav-link active" href="../index.php" style="color: white; padding: 10px;">Home</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: white; padding: 10px;">Categories</a>
                            <ul class="dropdown-menu" aria-labelledby="categoriesDropdown">
                            <li><a class="dropdown-item" href="../categories/bestsell.php" style="color: #027b9a; padding: 10px;">Bestsellers</a></li>
                <li><a class="dropdown-item" href="../categories/newArrival.php" style="color: #027b9a; padding: 10px;">New Arrivals</a></li>
                <li><a class="dropdown-item" href="../categories/mostpopular.php" style="color: #027b9a; padding: 10px;">Most Popular</a></li>
                <li><a class="dropdown-item" href="../categories/featured.php" style="color: #027b9a; padding: 10px;">Featured</a></li>
                <li><a class="dropdown-item" href="../categories/toprated.php" style="color: #027b9a; padding: 10px;">Top Rated</a></li>
                <li><a class="dropdown-item" href="../categories/fiction.php" style="color: #027b9a; padding: 10px;">Fiction</a></li>
                <li><a class="dropdown-item" href="../categories/nonfiction.php" style="color: #027b9a; padding: 10px;">Non-Fiction</a></li>
                <li><a class="dropdown-item" href="../categories/children.php" style="color: #027b9a; padding: 10px;">Children</a></li>
                <li><a class="dropdown-item" href="../categories/science.php" style="color: #027b9a; padding: 10px;">Science</a></li>
                <li><a class="dropdown-item" href="../categories/history.php" style="color: #027b9a; padding: 10px;">History</a></li>
                <li><a class="dropdown-item" href="../categories/anime.php" style="color: #027b9a; padding: 10px;">Manga</a></li>
              </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php" style="color: white; padding: 10px;">Cart</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="hero-section">
            <h1>Your Cart</h1>
            <p>Review your selected items and proceed to checkout.</p>
        </div>
    </div>

    <div class="container mt-5">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="text-center">
                <p class="lead">Your cart is empty. <a href="../index.php" class="btn btn-primary">Continue shopping</a></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td><img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Book Cover" class="img-thumbnail" width="50"></td>
                                <td><?php echo htmlspecialchars($item['title']); ?></td>
                                <td><?php echo number_format($item['price'], 2); ?> dt</td>
                                <td>
                                    <form action="cart.php" method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                        <input type="number" class="form-control w-50 me-2" name="quantity" value="<?php echo $item['quantity']; ?>" min="1">
                                        <button type="submit" name="update_cart" class="btn btn-warning btn-sm">Update</button>
                                    </form>
                                </td>
                                <td><?php echo number_format($item['price'] * $item['quantity'], 2); ?> dt</td>
                                <td>
                                    <form action="cart.php" method="POST">
                                        <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                        <button type="submit" name="remove_item" class="btn btn-danger btn-sm">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="4" class="text-end fw-bold">Total:</td>
                            <td><strong><?php echo number_format($total_price, 2); ?> dt</strong></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="text-center mt-4 mb-4">
                <a href="../index.php" class="btn btn-primary btn-lg">Continue Shopping</a>
                <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#checkoutModal">Proceed to Checkout</button>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="checkoutModalLabel">Checkout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post">
                
                        <div class="mb-3">
                            <label for="name" class="form-label">Name on Card</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="card_number" class="form-label">Card Number</label>
                            <input type="text" class="form-control" id="card_number" name="card_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="expiry_date" class="form-label">Expiry Date</label>
                            <input type="text" class="form-control" id="expiry_date" name="expiry_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="cvv" class="form-label">CVV</label>
                            <input type="text" class="form-control" id="cvv" name="cvv" required>
                        </div>
                        <div class="mb-3">
                            <label for="billing_address" class="form-label">Billing Address</label>
                            <input type="text" class="form-control" id="billing_address" name="billing_address" required>
                        </div>
                        <button type="submit" name="checkout" class="btn btn-primary">Proceed</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-white pt-4" style="background-color: #004658;">
        <div class="container">
            <div class="row">
                <div class="col-md-4 pt-3">
                    <a class="navbar-brand d-flex align-items-center" href="../index.php">
          <img src="../images/logo.png" alt="BookWartz Logo" class="logo col-md-2" style="max-width: 50px; max-height: 50px;">
                        <h2 class="name">BookWartz</h2>
                    </a>
                    <p>BookWartz is your go-to online bookstore, offering a wide selection of books for all genres. Discover, read, and enjoy from the comfort of your home.</p>
                </div>
                <div class="col-md-4 pt-3 text-center">
                    <h5>Useful Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="../index.php" class="text-white text-decoration-none">Home</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Shop</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Contact Us</a></li>
                        <li><a href="#" class="text-white text-decoration-none">About Us</a></li>
                    </ul>
                </div>
                <div class="col-md-4 pt-3">
                    <h5>Newsletter</h5>
                    <p>Subscribe to our newsletter for the latest updates and offers.</p>
                    <form>
                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="Enter your email" aria-label="Email">
                        </div>
                        <button type="submit" class="btn btn-primary">Subscribe</button>
                    </form>
                </div>
            </div>
            <hr class="border-light">
            <div class="text-center pb-2">
                <p class="mb-0">&copy; 2024 BookWartz. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>