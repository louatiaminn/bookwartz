<?php
session_start();
require '../includes/cnx.php';

// Fetch Fiction books
$stmt = $pdo->prepare("
    SELECT title, author, price, image, description, id 
    FROM books 
    WHERE category_id = 6
");
$stmt->execute();
$fiction_books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle "Add to Cart" action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error_message'] = "You must be logged in to add items to the cart.";
        header("Location: login.php"); // Redirect to login page
        exit();
    }

    $book_id = $_POST['book_id'];
    $user_id = $_SESSION['user_id'];

    // Ensure that the book exists in the database
    $stmt = $pdo->prepare("SELECT id FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        $_SESSION['error_message'] = "The book you are trying to add does not exist.";
        header("Location: fiction.php"); // Redirect back to the fiction page
        exit();
    }

    // Check if user has a cart
    $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart) {
        // Create a new cart for the user
        $stmt = $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $stmt->execute([$user_id]);
        $cart_id = $pdo->lastInsertId();
    } else {
        $cart_id = $cart['id'];
    }

    // Check if the item is already in the cart
    $stmt = $pdo->prepare("SELECT quantity FROM cart_items WHERE cart_id = ? AND book_id = ?");
    $stmt->execute([$cart_id, $book_id]);
    $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingItem) {
        // Update quantity if item already exists
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = quantity + 1 WHERE cart_id = ? AND book_id = ?");
        $stmt->execute([$cart_id, $book_id]);
    } else {
        // Add new item to cart
        $stmt = $pdo->prepare("INSERT INTO cart_items (cart_id, book_id, quantity) VALUES (?, ?, 1)");
        $stmt->execute([$cart_id, $book_id]);
    }

    $_SESSION['success_message'] = "Item added to cart successfully!";
    header("Location: ../controllers/cart.php"); // Redirect to cart page
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>BookWartz - Fiction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="banner banner-fiction">
    <nav class="navbar navbar-expand-lg";>
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
                    <li class="nav-item">
                        <a class="nav-link active" href="../index.php" style="color: white; padding: 10px;">Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: white; padding: 10px;">Categories</a>
                        <ul class="dropdown-menu" aria-labelledby="categoriesDropdown">
                          <li><a class="dropdown-item" href="bestsell.php" style="color: #027b9a; padding: 10px;">Bestsellers</a></li>
                <li><a class="dropdown-item" href="newArrival.php" style="color: #027b9a; padding: 10px;">New Arrivals</a></li>
                <li><a class="dropdown-item" href="mostpopular.php" style="color: #027b9a; padding: 10px;">Most Popular</a></li>
                <li><a class="dropdown-item" href="featured.php" style="color: #027b9a; padding: 10px;">Featured</a></li>
                <li><a class="dropdown-item" href="toprated.php" style="color: #027b9a; padding: 10px;">Top Rated</a></li>
                <li><a class="dropdown-item" href="fiction.php" style="color: #027b9a; padding: 10px;">Fiction</a></li>
                <li><a class="dropdown-item" href="nonfiction.php" style="color: #027b9a; padding: 10px;">Non-Fiction</a></li>
                <li><a class="dropdown-item" href="children.php" style="color: #027b9a; padding: 10px;">Children</a></li>
                <li><a class="dropdown-item" href="science.php" style="color: #027b9a; padding: 10px;">Science</a></li>
                <li><a class="dropdown-item" href="history.php" style="color: #027b9a; padding: 10px;">History</a></li>
                <li><a class="dropdown-item" href="anime.php" style="color: #027b9a; padding: 10px;">Manga</a></li>
              </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../controllers/cart.php" style="color: white; padding: 10px;">Cart</a>
                    </li>
                    <li class="nav-item">
                        <form class="d-flex align-items-center">
                            <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
                            <button class="btn btn-outline-primary" type="submit">Search</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="hero-section text-center bg-light py-5">
        <h1>Fiction Books</h1>
        <p>Get deep into the world of fiction with these engaging books.</p>
    </div>
        <div class="container my-5">
            <div class="row g-4">
                <?php if ($fiction_books): ?>
                    <?php foreach ($fiction_books as $index => $book): ?>
                    <div class="col-md-4">
                        <div class="card h-100 shadow">
                            <img src="<?= htmlspecialchars($book['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($book['title']) ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($book['author']) ?></p>
                                <p class="card-text"><strong>Price:</strong> <?= number_format($book['price'], 2) ?> DT</p>
                                <p class="card-text"><strong>Rating:</strong> ⭐⭐⭐⭐⭐</p>
                                <div class="d-flex">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#infoModal<?= $index ?>">More Info</button>
                                    <form action="fiction.php" method="POST">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <button type="submit" name="add_to_cart" class="btn btn-danger ms-2">Add to Cart</button>
                                  </form>                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="infoModal<?= $index ?>" tabindex="-1" aria-labelledby="infoModal<?= $index ?>Label" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title" id="infoModal<?= $index ?>Label"><?= htmlspecialchars($book['title']) ?></h4>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?= nl2br(htmlspecialchars($book['description'])) ?>
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div> 
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center pt-4" >
                        <p>No fiction books available at the moment. Check back later!</p>
                    </div>
                <?php endif; ?>
          <div class="modal fade" id="addtocart" tabindex="-1" aria-labelledby="addtocartLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addtocartLabel">Success</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-success">Item added to the cart successfully!</p>
                    </div>
                </div>
            </div>
        </div>
      </div>
  </div>
  <div class="d-flex justify-content-center my-4">
      <button class="btn btn-outline-primary">Load More</button>
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
                    <li><a href="#" class="text-white text-decoration-none">Home</a></li>
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
