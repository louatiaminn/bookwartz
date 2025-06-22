<?php
session_start();
require_once '../includes/cnx.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../controllers/login.php");
    exit();
}

// Handle book creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];

    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = basename($_FILES['image']['name']);
        $filePath = $uploadDir . uniqid() . "_" . $fileName;
        
        // Check if it's an image
        $imageInfo = getimagesize($_FILES['image']['tmp_name']);
        if ($imageInfo !== false) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                $stmt = $pdo->prepare("INSERT INTO books (title, author, price, category_id, image, description) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $author, $price, $category_id, $filePath, $description]);
                header("Location: dashboard.php?section=books&success=1");
                exit();
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = "Please upload a valid image file.";
        }
    } else {
        $error = "Please select an image file.";
    }
}

// Handle book update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_book'])) {
    $book_id = $_POST['book_id'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Handle new image upload
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = basename($_FILES['image']['name']);
        $filePath = $uploadDir . uniqid() . "_" . $fileName;
        
        $imageInfo = getimagesize($_FILES['image']['tmp_name']);
        if ($imageInfo !== false) {
            // Delete old image
            $stmt = $pdo->prepare("SELECT image FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            $oldBook = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($oldBook && !empty($oldBook['image']) && file_exists($oldBook['image'])) {
                unlink($oldBook['image']);
            }

            if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                $stmt = $pdo->prepare("UPDATE books SET title = ?, author = ?, price = ?, category_id = ?, image = ?, description = ? WHERE id = ?");
                $stmt->execute([$title, $author, $price, $category_id, $filePath, $description, $book_id]);
            } else {
                $error = "Failed to upload new image.";
            }
        } else {
            $error = "Please upload a valid image file.";
        }
    } else {
        // Update without changing the image
        $stmt = $pdo->prepare("UPDATE books SET title = ?, author = ?, price = ?, category_id = ?, description = ? WHERE id = ?");
        $stmt->execute([$title, $author, $price, $category_id, $description, $book_id]);
    }

    if (!isset($error)) {
        header("Location: dashboard.php?section=books&updated=1");
        exit();
    }
}

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    if ($_SESSION['role'] !== 'admin') {
        header("Location: dashboard.php");
        exit();
    }

    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->rowCount() > 0) {
        $error = "Username or email already exists.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$username, $email, $password, $role])) {
            header("Location: dashboard.php?section=users&user_added=1");
            exit();
        } else {
            $error = "Failed to add user.";
        }
    }
}

// Handle user update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    if ($_SESSION['role'] !== 'admin') {
        header("Location: dashboard.php");
        exit();
    }
    
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    // Check if password is being updated
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
        $stmt->execute([$username, $email, $password, $role, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
        $stmt->execute([$username, $email, $role, $user_id]);
    }

    header("Location: dashboard.php?section=users&user_updated=1");
    exit();
}
// Export logic: Export Users as CSV
if (isset($_GET['export_csv']) && $_GET['export_csv'] == 1) {
    // Optional: Restrict export to admin only.
    if ($_SESSION['role'] !== 'admin') {
        header("Location: dashboard.php");
        exit();
    }
    
    // Fetch users data.
    $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers to force download of CSV file.
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=users.csv');
    
    // Open output stream.
    $output = fopen('php://output', 'w');
    
    // Write column headers if data exists.
    if (!empty($users)) {
        fputcsv($output, array_keys($users[0]));
    }
    
    // Write each row.
    foreach ($users as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}
// Export logic: Export Books as JSON
if (isset($_GET['export_json']) && $_GET['export_json'] == 1) {
    // Fetch books data.
    $stmt = $pdo->query("SELECT * FROM books ORDER BY id");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers to prompt file download.
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="books.json"');
    
    // Output JSON data.
    echo json_encode($books, JSON_PRETTY_PRINT);
    exit();
}

// Handle book/user deletion
if (isset($_GET['delete_book'])) {
    $id = $_GET['delete_book'];
    
    // Get the image path before deleting
    $stmt = $pdo->prepare("SELECT image FROM books WHERE id = ?");
    $stmt->execute([$id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($book && !empty($book['image']) && file_exists($book['image'])) {
        unlink($book['image']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: dashboard.php?section=books&deleted=1");
    } else {
        $error = "Failed to delete book.";
    }
    exit();
}

if (isset($_GET['delete_user'])) {
    if ($_SESSION['role'] !== 'admin') {
        header("Location: dashboard.php");
        exit();
    }
    $id = $_GET['delete_user'];
    if ($id == $_SESSION['user_id']) {
        header("Location: dashboard.php?section=users&error=cannot_delete_self");
        exit();
    }   
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: dashboard.php?section=users&user_deleted=1");
    } else {
        header("Location: dashboard.php?section=users&error=delete_failed");
    }
    exit();
}
$stmt = $pdo->query("SELECT b.*, c.name AS category 
                     FROM books b 
                     LEFT JOIN categories c ON b.category_id = c.id 
                     ORDER BY b.id DESC");
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id, username, email, role FROM users ORDER BY id");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$current_section = isset($_GET['section']) ? $_GET['section'] : 'books';

if ($current_section === 'orders') {
    if ($_SESSION['role'] === 'admin') {
        $stmt = $pdo->query("SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.id DESC");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Store Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
         body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #027b9a, #005f73);
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }
        .sidebar h2 {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            margin-bottom: 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        .sidebar a.active {
            background: rgba(255, 255, 255, 0.2);
            font-weight: bold;
        }
        .content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .description-cell {
            max-width: 300px;
        }
        .description-text {
            max-height: 100px;
            overflow: hidden;
            position: relative;
            transition: max-height 0.3s ease;
        }
        .description-text.expanded {
            max-height: none;
        }
        .expand-btn {
            color: #027b9a;
            cursor: pointer;
            font-size: 0.9em;
            text-decoration: underline;
        }
        .book-cover {
            width: 80px;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .form-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h2>Admin Panel</h2>
            <nav>
                <a href="?section=books" class="<?= $current_section === 'books' ? 'active' : '' ?>">Books</a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="?section=users" class="<?= $current_section === 'users' ? 'active' : '' ?>">Users</a>
                <?php endif; ?>
                <a href="?section=orders" class="<?= $current_section === 'orders' ? 'active' : '' ?>">Orders</a>
                <a href="dashboard.php?export_csv=1">Export Users (CSV)</a>
                <a href="dashboard.php?export_json=1">Export Books (JSON)</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>

        <div class="content">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Book added successfully!</div>
            <?php endif; ?>

            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">Book updated successfully!</div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-info">Book deleted successfully!</div>
            <?php endif; ?>

            <section class="<?= $current_section === 'books' ? '' : 'd-none' ?>">
                <h2 class="mb-4">Book Management</h2>
                
                <div class="form-container">
                    <h3 class="mb-3">Add New Book</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Title</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Author</label>
                                    <input type="text" name="author" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Price (DT)</label>
                                    <input type="number" name="price" class="form-control" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select name="category_id" class="form-control" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Book Cover</label>
                                    <input type="file" name="image" class="form-control" accept="image/*" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="4" required></textarea>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="add_book" class="btn btn-primary">Add Book</button>
                    </form>
                </div>

                <!-- Books Table -->
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title mb-4">Book List</h3>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cover</th>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Price</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($books as $book): ?>
                                    <tr>
                                        <td><?= $book['id'] ?></td>
                                        <td>
                                            <img src="<?= htmlspecialchars($book['image']) ?>" 
                                                 alt="Cover" 
                                                 class="book-cover">
                                        </td>
                                        <td><?= htmlspecialchars($book['title']) ?></td>
                                        <td><?= htmlspecialchars($book['author']) ?></td>
                                        <td><?= number_format($book['price'], 3) ?> DT</td>
                                        <td><?= htmlspecialchars($book['category']) ?></td>
                                        <td class="description-cell">
                                            <div class="description-text">
                                                <?= nl2br(htmlspecialchars($book['description'])) ?>
                                            </div>
                                            <span class="expand-btn">Show more</span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm mb-1" 
                                                    onclick='openUpdateModal(<?= json_encode($book) ?>)'>
                                                Update
                                            </button>
                                            <a href="dashboard.php?delete_book=<?= $book['id'] ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Are you sure you want to delete this book?')">
                                                Delete
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Users Section (only for admin) -->
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <section class="<?= $current_section === 'users' ? '' : 'd-none' ?>">
                <h2 class="mb-4">User Management</h2>
                
                <div class="form-container">
                    <h3 class="mb-3">Add New User</h3>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <select name="role" class="form-control" required>
                                        <option value="user">User</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                    </form>
                </div>

                <!-- Users Table -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h3 class="card-title mb-4">User List</h3>
                        <input type="text" id="search-bar" class="form-control mb-3" placeholder="Search ...">
                        <div class="table-responsive">
                            <table class="table table-striped" id="contactTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'primary' : 'secondary' ?>">
                                                <?= htmlspecialchars($user['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm mb-1" 
                                                    onclick='openUpdateUserModal(<?= json_encode($user) ?>)'>
                                                Update
                                            </button>
                                            <a href="dashboard.php?delete_user=<?= $user['id'] ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Are you sure you want to delete this user?')">
                                                Delete
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- Orders Section -->
            <section class="<?= $current_section === 'orders' ? '' : 'd-none' ?>">
                <h2 class="mb-4">Order Management</h2>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                           <table class="table table-striped">
                              <thead>
                                <tr>
                                   <th>ID</th>
                                   <?php if ($_SESSION['role'] === 'admin'): ?>
                                       <th>User</th>
                                   <?php endif; ?>
                                   <th>Total (DT)</th>
                                   <th>Status</th>
                                   <th>Created At</th>
                                   <th>Actions</th>
                                </tr>
                              </thead>
                              <tbody>
                                  <?php if(isset($orders) && count($orders) > 0): ?>
                                    <?php foreach($orders as $order): ?>
                                    <tr>
                                       <td><?= $order['id'] ?></td>
                                       <?php if ($_SESSION['role'] === 'admin'): ?>
                                          <td><?= htmlspecialchars($order['username']) ?></td>
                                       <?php endif; ?>
                                       <td><?= number_format($order['total'], 2) ?></td>
                                       <td><?= htmlspecialchars($order['status']) ?></td>
                                       <td><?= htmlspecialchars($order['created_at']) ?></td>
                                       <td>
                                          <a href="../controllers/order_details.php?order_id=<?= $order['id'] ?>" class="btn btn-info btn-sm">View</a>
                                       </td>
                                    </tr>
                                    <?php endforeach; ?>
                                  <?php else: ?>
                                    <tr>
                                        <td colspan="<?= ($_SESSION['role'] === 'admin') ? 6 : 5 ?>">No orders found.</td>
                                    </tr>
                                  <?php endif; ?>
                              </tbody>
                           </table>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>

    <!-- Update Book Modal -->
    <div class="modal fade" id="updateBookModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data" id="updateBookForm">
                        <input type="hidden" name="book_id" id="update_book_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Title</label>
                                    <input type="text" name="title" id="update_title" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Author</label>
                                    <input type="text" name="author" id="update_author" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Price (DT)</label>
                                    <input type="number" name="price" id="update_price" class="form-control" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select name="category_id" id="update_category_id" class="form-control" required>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Book Cover (Leave empty to keep current image)</label>
                                    <input type="file" name="image" class="form-control" accept="image/*">
                                    <div id="current_image_preview" class="mt-2"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" id="update_description" class="form-control" rows="4" required></textarea>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="update_book" class="btn btn-primary">Update Book</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Update User Modal -->
    <div class="modal fade" id="updateUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="updateUserForm">
                        <input type="hidden" name="user_id" id="update_user_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" id="update_username" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" id="update_email" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Password (Leave empty to keep current password)</label>
                                    <input type="password" name="password" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <select name="role" id="update_role" class="form-control" required>
                                        <option value="user">User</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Required Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to handle description expansion
        document.querySelectorAll('.expand-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const descriptionText = this.previousElementSibling;
                if (descriptionText.style.maxHeight) {
                    descriptionText.style.maxHeight = null;
                    this.textContent = 'Show more';
                } else {
                    descriptionText.style.maxHeight = 'none';
                    this.textContent = 'Show less';
                }
            });
        });

        // Function to handle book update modal
        function openUpdateModal(book) {
            document.getElementById('update_book_id').value = book.id;
            document.getElementById('update_title').value = book.title;
            document.getElementById('update_author').value = book.author;
            document.getElementById('update_price').value = book.price;
            document.getElementById('update_category_id').value = book.category_id;
            document.getElementById('update_description').value = book.description;
            
            const imagePreview = document.getElementById('current_image_preview');
            imagePreview.innerHTML = book.image ? 
                `<img src="${book.image}" alt="Current cover" style="max-width: 100px;">` : '';
            
            new bootstrap.Modal(document.getElementById('updateBookModal')).show();
        }

        function openUpdateUserModal(user) {
            document.getElementById('update_user_id').value = user.id;
            document.getElementById('update_username').value = user.username;
            document.getElementById('update_email').value = user.email;
            document.getElementById('update_role').value = user.role;
            
            new bootstrap.Modal(document.getElementById('updateUserModal')).show();
        }
    </script>
    <script src="../assets/recherche.js"></script>
</body>
</html>
