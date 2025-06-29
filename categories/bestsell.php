<?php
session_start();
require '../includes/cnx.php';

// Fetch best-selling books
$stmt = $pdo->prepare("
    SELECT id, title, author, price, image, description 
    FROM books 
    WHERE category_id = 1
");
$stmt->execute();
$bestselling_books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// RECOMMENDATION SYSTEM FUNCTION - Added from anime.php
function getRecommendations($bookTitle) {
    $scriptPath = __DIR__ . '/../python_scripts/recommend.py';
    
    if (!file_exists($scriptPath)) {
        return [
            'error' => 'Python script not found at: ' . $scriptPath,
            'debug_info' => [
                'script_path' => $scriptPath,
                'current_dir' => __DIR__
            ]
        ];
    }
    
    $pythonCommands = ['python3', 'python', '/usr/bin/python3', '/usr/bin/python'];
    
    $lastOutput = '';
    $lastError = '';
    
    foreach ($pythonCommands as $pythonCmd) {
        $command = $pythonCmd . " " . escapeshellarg($scriptPath) . " " . escapeshellarg($bookTitle) . " 2>&1";
        
        $output = shell_exec($command);
        $lastOutput = $output;
        
        if ($output !== null && !empty(trim($output))) {
            error_log("Python command '$pythonCmd': " . $command);
            error_log("Python raw output: " . $output);
            
            $lines = explode("\n", trim($output));
            $jsonLines = [];
            
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (!empty($trimmed) && (
                    $trimmed[0] === '{' || 
                    $trimmed[0] === '}' || 
                    $trimmed[0] === '"' || 
                    strpos($trimmed, '":') !== false ||
                    $trimmed === '[' || 
                    $trimmed === ']'
                )) {
                    $jsonLines[] = $trimmed;
                }
            }
            
            if (!empty($jsonLines)) {
                $jsonString = implode('', $jsonLines);
                $result = json_decode($jsonString, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $result;
                }
            }
            
            $result = json_decode(trim($output), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $result;
            }
            
            $lastError = "Invalid JSON output from Python script";
        } else {
            $lastError = "No output from Python script";
        }
    }
    
    return [
        'error' => 'Failed to execute Python script or get valid JSON response',
        'debug_info' => [
            'last_output' => $lastOutput,
            'last_error' => $lastError,
            'script_path' => $scriptPath,
            'tried_commands' => $pythonCommands,
            'json_last_error' => json_last_error_msg()
        ]
    ];
}

// RECOMMENDATION AJAX HANDLER - Added from anime.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_recommendations'])) {
    header('Content-Type: application/json');
    
    if (!isset($_POST['book_title']) || empty($_POST['book_title'])) {
        echo json_encode(['error' => 'Book title is required']);
        exit();
    }
    
    $bookTitle = trim($_POST['book_title']);
    
    if (strlen($bookTitle) < 2) {
        echo json_encode(['error' => 'Book title too short']);
        exit();
    }
    
    try {
        $recommendations = getRecommendations($bookTitle);
        echo json_encode($recommendations, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode([
            'error' => 'Server error: ' . $e->getMessage(),
            'debug_info' => ['exception' => $e->getTraceAsString()]
        ]);
    }
    exit();
}

// FIND BOOK ID HANDLER - Added from anime.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['find_book_id'])) {
    header('Content-Type: application/json');
    
    $bookTitle = trim($_POST['book_title']);
    $bookAuthor = trim($_POST['book_author']);
    
    // Try to find the book by title and author
    $stmt = $pdo->prepare("SELECT id FROM books WHERE title LIKE ? AND author LIKE ?");
    $stmt->execute(['%' . $bookTitle . '%', '%' . $bookAuthor . '%']);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        // Try just by title if author search fails
        $stmt = $pdo->prepare("SELECT id FROM books WHERE title LIKE ?");
        $stmt->execute(['%' . $bookTitle . '%']);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if ($book) {
        echo json_encode(['book_id' => $book['id']]);
    } else {
        echo json_encode(['error' => 'Book not found']);
    }
    exit();
}

// Handle "Add to Cart" action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error_message'] = "You must be logged in to add items to the cart.";
        header("Location: ../controllers/login.php"); // Updated path to match anime.php
        exit();
    }

    $book_id = $_POST['book_id'];
    $user_id = $_SESSION['user_id'];

    // Check if the book exists - Added validation from anime.php
    $stmt = $pdo->prepare("SELECT id FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        $_SESSION['error_message'] = "The book you are trying to add does not exist.";
        header("Location: bestsell.php"); 
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
        // Insert new item
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
    <title>BookWartz - Best Seller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <link rel="stylesheet" href="../assets/style.css">
    <!-- RECOMMENDATION SYSTEM STYLES - Added from anime.php -->
    <style>
        .recommendation-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 30px;
            border-radius: 15px;
            margin-top: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .recommendation-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .recommendation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .loading-spinner {
            display: none;
        }
        .debug-info {
            background-color: #f1f1f1;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin-top: 15px;
            max-height: 300px;
            overflow-y: auto;
        }
        .method-badge {
            font-size: 0.7em;
            padding: 2px 6px;
            border-radius: 10px;
        }
        .similarity-score {
            font-size: 0.8em;
            color: #6c757d;
        }
        .recommendation-source {
            font-size: 0.75em;
            opacity: 0.8;
        }
    </style>
</head>
<body>
<div class="banner banner-bestsell">
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <img src="../images/logo.png" alt="BookWartz Logo" class="logo col-md-2" style="max-width: 50px; max-height: 50px;">
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
        <h1>Bestselling Books</h1>
        <p>Explore our collection of the top-selling books of the year.</p>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <p style="color: green;"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
    <?php endif; ?>

    <div class="container my-5">
        <div class="row g-4">
            <?php if ($bestselling_books): ?>
                <?php foreach ($bestselling_books as $index => $book): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <img src="<?= htmlspecialchars($book['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($book['title']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($book['author']) ?></p>
                            <p class="card-text"><strong>Price:</strong> <?= number_format($book['price'], 2) ?> DT</p>
                            <p class="card-text"><strong>Rating:</strong> ⭐⭐⭐⭐⭐</p>
                            <!-- UPDATED BUTTON SECTION - Added recommendation button from anime.php -->
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#infoModal<?= $index ?>">More Info</button>
                                <button class="btn btn-info btn-sm" onclick="getRecommendations('<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>')">Get Recommendations</button>
                                <form action="bestsell.php" method="POST" class="d-inline">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <button type="submit" name="add_to_cart" class="btn btn-danger">Add to Cart</button>
                                </form>
                            </div>
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
                <div class="col-12 text-center">
                    <p>No bestsellers available at the moment. Check back later!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RECOMMENDATION SECTION - Added entire section from anime.php -->
    <div class="container my-5">
        <div id="recommendationsSection" class="recommendation-section" style="display: none;">
            <h3 class="text-center mb-4">📚 Recommended Books</h3>
            <div class="text-center loading-spinner" id="loadingSpinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Getting personalized recommendations...</p>
            </div>
            <div id="recommendationsContent" class="row g-3"></div>
            <div id="recommendationsError" class="alert alert-warning" style="display: none;"></div>
            <div id="debugInfo" class="debug-info" style="display: none;"></div>
            <div class="text-center mt-3">
            </div>
        </div>
    </div>

    <!-- SUCCESS MODAL - Added from anime.php -->
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

  

<footer class="text-white pt-4" style="background-color: #004658;">
    <div class="container">
        <div class="row">
            <div class="col-md-4 pt-3">
                <a class="navbar-brand d-flex align-items-center" href="../index.php">
                    <img src="../images/logo.png" alt="BookWartz Logo" class="logo" style="width: 50px; height: 50px;">
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
<!-- RECOMMENDATION SYSTEM JAVASCRIPT - Added entire script section from anime.php -->
<script>
    function getRecommendations(bookTitle) {
        const recommendationsSection = document.getElementById('recommendationsSection');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const recommendationsContent = document.getElementById('recommendationsContent');
        const recommendationsError = document.getElementById('recommendationsError');
        const debugInfo = document.getElementById('debugInfo');
        
        recommendationsSection.style.display = 'block';
        loadingSpinner.style.display = 'block';
        recommendationsContent.innerHTML = '';
        recommendationsError.style.display = 'none';
        debugInfo.style.display = 'none';
        
        recommendationsSection.scrollIntoView({ behavior: 'smooth' });
        
        fetch('bestsell.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'get_recommendations=1&book_title=' + encodeURIComponent(bookTitle)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text();
        })
        .then(text => {
            loadingSpinner.style.display = 'none';
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                throw new Error(`Invalid JSON response: ${text.substring(0, 200)}...`);
            }
            
            if (data.debug_info) {
                debugInfo.innerHTML = '<strong>Debug Info:</strong><br><pre>' + JSON.stringify(data.debug_info, null, 2) + '</pre>';
            }
            
            if (data.error) {
                let errorMessage = data.error;
                if (data.debug_info) {
                    errorMessage += '<br><br><strong>Debug Info:</strong><br><pre>' + JSON.stringify(data.debug_info, null, 2) + '</pre>';
                }
                recommendationsError.innerHTML = errorMessage;
                recommendationsError.style.display = 'block';
            } else if (data.recommendations && data.recommendations.length > 0) {
                displayRecommendations(data.recommendations, data.original_input || bookTitle, data.method_used);
            } else {
                recommendationsError.innerHTML = 'No recommendations found for this book. The recommendation system may need more data or the book title might not be recognized.';
                recommendationsError.style.display = 'block';
            }
        })
        .catch(error => {
            loadingSpinner.style.display = 'none';
            recommendationsError.innerHTML = `
                <strong>Error fetching recommendations:</strong><br>
                ${error.message}<br><br>
                <small>This could be due to:</small>
                <ul>
                    <li>Python script not found or not executable</li>
                    <li>Missing Python dependencies (pandas, sklearn, numpy, etc.)</li>
                    <li>Database connection issues</li>
                    <li>Server configuration problems</li>
                </ul>
            `;
            recommendationsError.style.display = 'block';
            console.error('Error:', error);
        });
    }
    
    function displayRecommendations(recommendations, originalBook, methods) {
        const recommendationsContent = document.getElementById('recommendationsContent');
        
        let methodBadges = '';
        if (methods && methods.length > 0) {
            methodBadges = methods.map(method => 
                `<span class="badge bg-info method-badge">${method.replace('_', ' ')}</span>`
            ).join(' ');
        }
        
        let html = `<div class="col-12 mb-3">
            <h5 class="text-center">Because you're interested in "${originalBook}", you might also like:</h5>
        </div>`;
        
        recommendations.forEach(rec => {
            const title = rec.title || rec;
            const author = rec.author || '';
            const score = rec.similarity_score || 0;
            const image = rec.image || '';
            const source = rec.source || '';

            html += `
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="card recommendation-card h-100">
                        <div class="card-body text-center p-2">
                            ${image ? `<img src="${image}" class="card-img-top" alt="${title}">` : ''}
                            <h6 class="card-title small">${title}</h6>
                            ${author ? `<p class="card-text small text-muted">${author}</p>` : ''}
                            ${score > 0 ? `<small class="similarity-score">Score: ${(score * 100).toFixed(1)}%</small><br>` : ''}
                            ${source ? `<small class="recommendation-source">${source}</small><br>` : ''}
                            <button class="btn btn-danger btn-sm mt-1" onclick="addRecommendedBookToCart('${title}', '${author}')">
                             Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        recommendationsContent.innerHTML = html;
    }
    
    function addRecommendedBookToCart(bookTitle, bookAuthor) {
        // First, we need to find the book ID from our database
        fetch('bestsell.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'find_book_id=1&book_title=' + encodeURIComponent(bookTitle) + '&book_author=' + encodeURIComponent(bookAuthor)
        })
        .then(response => response.json())
        .then(data => {
            if (data.book_id) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'bestsell.php';
                
                const bookIdInput = document.createElement('input');
                bookIdInput.type = 'hidden';
                bookIdInput.name = 'book_id';
                bookIdInput.value = data.book_id;
                
                const addToCartInput = document.createElement('input');
                addToCartInput.type = 'hidden';
                addToCartInput.name = 'add_to_cart';
                addToCartInput.value = '1';
                
                form.appendChild(bookIdInput);
                form.appendChild(addToCartInput);
                document.body.appendChild(form);
                form.submit();
            } else {
                alert('Sorry, this book is not available in our store.');
            }
        })
        .catch(error => {
            alert('Error adding book to cart: ' + error.message);
        });
    }

    function toggleDebugInfo() {
        const debugInfo = document.getElementById('debugInfo');
        const toggleText = document.getElementById('debugToggleText');
        
        if (debugInfo.style.display === 'none' || debugInfo.style.display === '') {
            debugInfo.style.display = 'block';
            toggleText.textContent = 'Hide Debug Info';
        } else {
            debugInfo.style.display = 'none';
            toggleText.textContent = 'Show Debug Info';
        }
    }
</script>
</body>
</html>