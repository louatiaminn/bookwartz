<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>BookWartz - Home</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
  <link rel="stylesheet" href="assets/style.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
</head>
<body>
  <div class="banner banner-index">
    <nav class="navbar navbar-expand-lg">
      <div class="container-fluid col-md-12">
        <a class="navbar-brand d-flex align-items-center" href="#">
          <img src="images/logo.png" alt="BookWartz Logo" class="logo col-md-2" style="max-width: 50px; max-height: 50px;">
          <h2 class="name">BookWartz</h2>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" 
                aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation" 
                style="border: none; border-radius: 5px;">
          <span class="navbar-toggler-icon" style="background-color: #007bff; width: 30px; height: 30px;"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
          <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <a class="nav-link active" href="index.php" style="color: white; padding: 10px;">Home</a>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" 
                 data-bs-toggle="dropdown" aria-expanded="false" style="color: white; padding: 10px;">Categories</a>
              <ul class="dropdown-menu" aria-labelledby="categoriesDropdown">
                <li><a class="dropdown-item" href="categories/bestsell.php" style="color: #027b9a; padding: 10px;">Bestsellers</a></li>
                <li><a class="dropdown-item" href="categories/newArrival.php" style="color: #027b9a; padding: 10px;">New Arrivals</a></li>
                <li><a class="dropdown-item" href="categories/mostpopular.php" style="color: #027b9a; padding: 10px;">Most Popular</a></li>
                <li><a class="dropdown-item" href="categories/featured.php" style="color: #027b9a; padding: 10px;">Featured</a></li>
                <li><a class="dropdown-item" href="categories/toprated.php" style="color: #027b9a; padding: 10px;">Top Rated</a></li>
                <li><a class="dropdown-item" href="categories/fiction.php" style="color: #027b9a; padding: 10px;">Fiction</a></li>
                <li><a class="dropdown-item" href="categories/nonfiction.php" style="color: #027b9a; padding: 10px;">Non-Fiction</a></li>
                <li><a class="dropdown-item" href="categories/children.php" style="color: #027b9a; padding: 10px;">Children</a></li>
                <li><a class="dropdown-item" href="categories/science.php" style="color: #027b9a; padding: 10px;">Science</a></li>
                <li><a class="dropdown-item" href="categories/history.php" style="color: #027b9a; padding: 10px;">History</a></li>
                <li><a class="dropdown-item" href="categories/anime.php" style="color: #027b9a; padding: 10px;">Manga</a></li>
              </ul>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="controllers/cart.php" style="color: white; padding: 10px;">Cart</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#about" style="color: white; padding: 10px;">About Us</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#contact" style="color: white; padding: 10px;">Contact Us</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    <div class="container text-center my-5 py-5">
      <?php if (isset($_SESSION['username'])): ?>
        <h1 class="display-4 fw-bold mb-3 my-4" style="color: honeydew;">
          Welcome, <?= htmlspecialchars($_SESSION['username']); ?>!
        </h1>
        <p class="lead mb-4" style="color: honeydew;">Let the perfect story cast its spell upon you from our treasure trove of tales.</p>
        <a href="views/logout.php" class="btn btn-danger btn-lg me-3 btn-sm hero-button">Logout</a>
        <a href="views/userdash.php" class="btn btn-outline-success me-3 btn-lg btn-sm hero-button">Dashboard</a>
      <?php else: ?>
        <h1 class="display-4 fw-bold mb-3 my-4" style="color: honeydew;">Discover Your Next Read</h1>
        <p class="lead mb-4" style="color: honeydew;">Step through the pages into realms of wonder and infinite possibility.</p>
        <a href="controllers/login.php" class="btn btn-primary btn-lg me-3 btn-sm hero-button">Login</a>
        <a href="controllers/register.php" class="btn btn-outline-primary me-3 btn-lg btn-sm hero-button">Register</a>
      <?php endif; ?>
    </div>
  </div>

  <hr class="section-divider">
  <h2 class="heading text-center">Explore Our Categories</h2>
  <section class="categories-section bg-light" id="categories">
    <div class="container my-4">
      <div class="swiper">
        <div class="swiper-wrapper">
          <div class="swiper-slide text-center" style="background-image: url('images/bestsjpeg.jpeg');">
            <h3><a class="text-decoration-none bg-light px-3" href="categories/bestsell.php">Bestsellers</a></h3>
          </div>
          <div class="swiper-slide text-center" style="background-image: url('images/new.webp');">
            <h3><a class="text-decoration-none bg-light px-3" href="categories/newArrival.php">New Arrivals</a></h3>
          </div>
          <div class="swiper-slide text-center" style="background-image: url('images/3.jpg');">
            <h3><a class="text-decoration-none bg-light px-3" href="categories/mostpopular.php">Most Popular</a></h3>
          </div>
          <div class="swiper-slide text-center" style="background-image: url('images/feat.webp');">
            <h3><a class="text-decoration-none bg-light px-3" href="categories/featured.php">Featured</a></h3>
          </div>
          <div class="swiper-slide text-center" style="background-image: url('images/5.jpg');">
            <h3><a class="text-decoration-none bg-light px-3" href="categories/toprated.php">Top Rated</a></h3>
          </div>
          <div class="swiper-slide text-center" style="background-image: url('images/6.jpg');">
            <h3><a class="text-decoration-none bg-light px-3" href="categories/fiction.php">Fiction</a></h3>
          </div>
          <div class="swiper-slide text-center" style="background-image: url('images/nonf.jpg');">
            <h3><a class="text-decoration-none bg-light px-3" href="categories/nonfiction.php">Non-Fiction</a></h3>
          </div>
          <div class="swiper-slide text-center" style="background-image: url('images/child.jpg');">
            <h3><a class="text-decoration-none bg-light px-3" href="categories/children.php">Children</a></h3>
          </div>
          <div class="swiper-slide text-center" style="background-image: url('images/science.jpg');">
            <h3><a class="text-decoration-none bg-light px-3" href="categories/science.php">Science</a></h3>
          </div>
          <div class="swiper-slide text-center" style="background-image: url('images/hist.webp');">
            <h3><a class="text-decoration-none bg-light px-3" href="categories/history.php">History</a></h3>
          </div>
          <div class="swiper-slide text-center" style="background-image: url('images/anime.jpg');">
            <h3><a class="text-decoration-none bg-light px-3" href="categories/anime.php">Manga</a></h3>
          </div>
        </div>
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-pagination"></div>
      </div>
    </div>
  </section>

  <hr class="section-divider">
  <h2 class="heading text-center" id="about">About BookWartz</h2>
  <section class="about bg-light py-5">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-4">
          <img class="img-fluid" src="images/about.png" alt="About Us Image">
        </div>
        <div class="col-md-8">
          <p>
            Welcome to BookWartz! We are your go-to online bookstore, committed to delivering exceptional books across all genres. Discover, read, and enjoy from the comfort of your home.
          </p>
        </div>
      </div>
    </div>
  </section>

  <hr class="section-divider">
  <h2 class="heading text-center" id="quotes">Famous Quotes</h2>
  <section id="quote" class="my-5 bg-light py-5">
    <div id="quotesCarousel" class="carousel slide" data-bs-ride="carousel">
      <div class="carousel-inner">
        <div class="carousel-item active">
          <div class="d-flex flex-column justify-content-center align-items-center" style="height: 300px;">
            <div class="text-center">
              <p class="fs-4">"The soul is healed by being with children."</p>
              <footer class="blockquote-footer mt-2">Fyodor Dostoevsky</footer>
            </div>
          </div>
        </div>
        <div class="carousel-item">
          <div class="d-flex flex-column justify-content-center align-items-center" style="height: 300px;">
            <div class="text-center">
              <p class="fs-4">"A reader lives a thousand lives before he dies."</p>
              <footer class="blockquote-footer mt-2">George R.R. Martin</footer>
            </div>
          </div>
        </div>
        <div class="carousel-item">
          <div class="d-flex flex-column justify-content-center align-items-center" style="height: 300px;">
            <div class="text-center">
              <p class="fs-4">"It is not what we read, but what we remember, that makes us learned."</p>
              <footer class="blockquote-footer mt-2">Lord Chesterfield</footer>
            </div>
          </div>
        </div>
        <div class="carousel-item">
          <div class="d-flex flex-column justify-content-center align-items-center" style="height: 300px;">
            <div class="text-center">
              <p class="fs-4">"Imagination is more important than knowledge."</p>
              <footer class="blockquote-footer mt-2">Albert Einstein</footer>
            </div>
          </div>
        </div>
        <div class="carousel-item">
          <div class="d-flex flex-column justify-content-center align-items-center" style="height: 300px;">
            <div class="text-center">
              <p class="fs-4">"It does not do to dwell on dreams and forget to live."</p>
              <footer class="blockquote-footer mt-2">J.K. Rowling</footer>
            </div>
          </div>
        </div>
        <div class="carousel-item">
          <div class="d-flex flex-column justify-content-center align-items-center" style="height: 300px;">
            <div class="text-center">
              <p class="fs-4">"This above all: to thine own self be true."</p>
              <footer class="blockquote-footer mt-2">William Shakespeare</footer>
            </div>
          </div>
        </div>
        <div class="carousel-item">
          <div class="d-flex flex-column justify-content-center align-items-center" style="height: 300px;">
            <div class="text-center">
              <p class="fs-4">"Be yourself; everyone else is already taken."</p>
              <footer class="blockquote-footer mt-2">Oscar Wilde</footer>
            </div>
          </div>
        </div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#quotesCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#quotesCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
      </button>
    </div>
  </section>

  <hr class="section-divider">
  <h2 class="heading text-center" id="highlights">Highlights</h2>
  <div class="container mt-5">
    <ul class="nav nav-tabs" id="bookTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="bestsellers-tab" data-bs-toggle="tab" data-bs-target="#bestsellers" type="button" role="tab" aria-controls="bestsellers" aria-selected="true">Bestsellers</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="authors-tab" data-bs-toggle="tab" data-bs-target="#authors" type="button" role="tab" aria-controls="authors" aria-selected="false">Top Authors</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="releases-tab" data-bs-toggle="tab" data-bs-target="#releases" type="button" role="tab" aria-controls="releases" aria-selected="false">Upcoming Releases</button>
      </li>
    </ul>
    <div class="tab-content mt-4" id="bookTabsContent">
      <div class="tab-pane fade show active" id="bestsellers" role="tabpanel" aria-labelledby="bestsellers-tab">
        <div class="row g-4">
          <div class="col-md-4">
            <div class="card">
              <img src="images/mdnlb.jpg" class="card-img-top" alt="Bestseller Book">
              <div class="card-body">
                <h5 class="card-title">The Midnight Library</h5>
                <p class="card-text">By Matt Haig</p>
                <p class="text-muted">Rating: ★★★★☆ (4.5)</p>
                <a href="#" class="btn btn-primary">Buy Now</a>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card">
              <img src="images/crawdads.jpeg" class="card-img-top" alt="Bestseller Book">
              <div class="card-body">
                <h5 class="card-title">Where the Crawdads Sing</h5>
                <p class="card-text">By Delia Owens</p>
                <p class="text-muted">Rating: ★★★★★ (4.8)</p>
                <a href="#" class="btn btn-primary">Buy Now</a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="tab-pane fade" id="authors" role="tabpanel" aria-labelledby="authors-tab">
        <div class="row g-4">
          <div class="col-md-4">
            <div class="card">
              <img src="images/rowling.webp" class="card-img-top" alt="Author">
              <div class="card-body">
                <h5 class="card-title">J.K. Rowling</h5>
                <p class="card-text">A bestselling novelist known worldwide for her captivating fantasy world.</p>
                <a href="#" class="btn btn-outline-primary">View Books</a>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card">
              <img src="images/king.jpg" class="card-img-top" alt="Author">
              <div class="card-body">
                <h5 class="card-title">Stephen King</h5>
                <p class="card-text">The master of suspense and horror, author of *The Shining* and *It*.</p>
                <a href="#" class="btn btn-outline-primary">View Books</a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="tab-pane fade" id="releases" role="tabpanel" aria-labelledby="releases-tab">
        <div class="row g-4">
          <div class="col-md-4">
            <div class="card">
              <img src="images/atlasparadox.webp" class="card-img-top" alt="Upcoming Release">
              <div class="card-body">
                <h5 class="card-title">The Atlas Paradox</h5>
                <p class="card-text">By Olivie Blake</p>
                <p class="text-muted">Release Date: March 15, 2025</p>
                <a href="#" class="btn btn-primary">Pre-order Now</a>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card">
              <img src="images/lessoninchemistery.jpeg" class="card-img-top" alt="Upcoming Release">
              <div class="card-body">
                <h5 class="card-title">Lessons in Chemistry</h5>
                <p class="card-text">By Bonnie Garmus</p>
                <p class="text-muted">Release Date: April 10, 2025</p>
                <a href="#" class="btn btn-primary">Pre-order Now</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <hr class="section-divider">
  <h2 class="heading text-center" id="contact">Contact</h2>
  <p class="text-center text-muted">Contactez-nous par téléphone ou par email</p>
  <div class="container py-5 bg-light">
    <div class="row g-4">
      <div class="col-md-9">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d12776.130586739748!2d10.19003265653679!3d36.8177376026181!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x12fd347ad13b22f3%3A0x4201bea3db6e2b0f!2z2KfZhNmF2YPYqtio2Kkg2KfZhNi02KfZhdmE2Kk!5e0!3m2!1sar!2stn!4v1734085719779!5m2!1sar!2stn" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
      <div class="col-md-3">
        <div class="d-flex align-items-start mb-4">
          <i class="fa-solid fa-location-dot fs-4 me-3"></i>
          <div>
            <h5>Address</h5>
            <p class="mb-0">7 Rue de La Palestine, Tunis 1002, Tunisia</p>
          </div>
        </div>
        <div class="d-flex align-items-start mb-4">
          <i class="fa-solid fa-phone fs-4 me-3"></i>
          <div>
            <h5>Call Us</h5>
            <p class="mb-0">+216 21 903 988</p>
          </div>
        </div>
        <div class="d-flex align-items-start mb-4">
          <i class="fa-solid fa-envelope fs-4 me-3"></i>
          <div>
            <h5>Email Us</h5>
            <p class="mb-0">louati773@gmail.com</p>
          </div>
        </div>
      </div>
      <div class="col-md-12">
        <form action="#" method="post" class="p-3">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <input type="text" name="name" class="form-control" placeholder="Your Name" required>
            </div>
            <div class="col-md-6">
              <input type="email" name="email" class="form-control" placeholder="Your Email" required>
            </div>
          </div>
          <div class="mb-3">
            <input type="text" name="subject" class="form-control" placeholder="Subject" required>
          </div>
          <div class="mb-3">
            <textarea name="message" class="form-control" rows="5" placeholder="Message" required></textarea>
          </div>
          <button type="submit" class="btn btn-primary text-white px-4">Send Message</button>
        </form>
      </div>
    </div>
  </div>

  <hr class="section-divider">
  <footer class="text-white pt-4" style="background-color: #004658;">
    <div class="container">
      <div class="row">
        <div class="col-md-4 pt-3">
          <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="images/logo.png" alt="BookWartz Logo" class="logo col-md-2" style="max-width: 50px; max-height: 50px;">
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

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const swiper = new Swiper('.categories-section .swiper', {
        slidesPerView: 3,
        spaceBetween: 10,
        loop: true,
        navigation: {
          nextEl: '.categories-section .swiper-button-next',
          prevEl: '.categories-section .swiper-button-prev',
        },
        pagination: {
          el: '.categories-section .swiper-pagination',
          clickable: true,
        },
        breakpoints: {
          640: { slidesPerView: 1 },
          768: { slidesPerView: 2 },
          1024: { slidesPerView: 3 },
        },
      });
    });
  </script>
</body>
</html>
