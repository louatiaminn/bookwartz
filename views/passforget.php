<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookwartz - Forget Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
   <link href="../assets/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
            background-image: url('images/bgc1.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
        }

        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background-color: rgba(255, 255, 255, 0.9); 
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-custom {
            background-color: #027b9a;
            border-color: #027b9a;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .btn-custom:hover {
            background-color: #00bef0;
            border-color: #00bef0;
        }

        .heading {
            color: #027b9a;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2rem;
            font-weight: 700;
        }

        .form-control {
            border-color: #004658;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: #00796b;
            box-shadow: 0 0 5px rgba(0, 191, 240, 0.5);
        }

        .form-group label {
            font-weight: 600;
            color: #027b9a;
        }

        .form-group input {
            padding: 12px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .form-group input:focus {
            background-color: #f0f8ff;
        }

        .forgot-password {
            color: #027b9a;
            text-decoration: none;
            font-weight: 600;
        }

        .forgot-password:hover {
            color: #00bef0;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg"; border-radius: 8px;">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                    <img src="../images/logo.png" alt="BookWartz Logo" class="logo" style="width: 50px; height: 50px;">
                <h2 class="name ms-2 bg-white">BookWartz</h2>
            </a>
    </nav>
    <div class="login-container">
        <h1 class="heading">Bookwartz - Password Reset</h1>
        <form action="/submit" method="post" class="col-md-12">
            <div class="form-group col-md-12">
                <label for="email">Email:</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
            </div>
            <div class="form-group text-center">
                <button type="submit" class="btn btn-custom btn-block">Reset Password</button>
            </div>
            <div class="form-group text-center">
                <p class="forgot-password"><a href="../controllers/login.php">Back to login</a></p>
            </div>
        </form>
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
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    </body>
    </html>
    