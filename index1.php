<?php 
session_start();
require_once "includes/db.php";

?>


<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilog Blog</title>
</head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/bilog.css">
</head>

<body>

<!-- NAVBAR -->
<?php require_once "includes/header.php";?>


<!-- HERO SECTION -->
<section class="hero">

<h1>
Hey, we’re bilog.<br>
Checkout trends, travel tips, and tech news.
</h1>

<div class="search-bar">

<input type="text" placeholder="Search...">

<button>Search</button>

</div>

</section>

   <!-- ================= FEATURED BLOG ================= -->
<section class="featured-section">

    <div class="featured-container">

        <div class="featured-image">
            <img src="assets/images/laptop.jpg" alt="Laptop workspace">
        </div>

        <div class="featured-content">

            <div class="featured-meta">
                <span class="category">Content Strategy</span>
                <span class="date">September 15, 2024</span>
            </div>

            <h2 class="featured-title">
                How to Maintain a Consistent Blogging Schedule
            </h2>

            <p class="featured-desc">
                Learn strategies to keep your blog updated regularly without
                sacrificing quality. Discover simple productivity methods
                and planning techniques used by successful bloggers.
            </p>

            <a href="#" class="read-more">Read Now →</a>

        </div>

    </div>

</section>

<!-- ================= BLOG POSTS ================= -->
<section class="blogs">

    <div class="blog-grid">

        <!-- CARD 1 -->
        <div class="blog-card">

            <img src="assets/images/1.jpg" alt="">

            <div class="blog-content">

                <div class="blog-top">
                    <span class="category">Blogging Tips</span>
                    <span class="date">September 15, 2024</span>
                </div>

                <h3>Leveraging Social Media for Blog Promotion</h3>

                <p>
                    Explore effective strategies for promoting your blog through
                    social media channels.
                </p>

                <a href="#" class="read-more">Read Now →</a>

            </div>

        </div>


        <!-- CARD 2 -->
        <div class="blog-card">

            <img src="assets/images/2.jpg" alt="">

            <div class="blog-content">

                <div class="blog-top">
                    <span class="category">Content Strategy</span>
                    <span class="date">September 15, 2024</span>
                </div>

                <h3>Maximizing Engagement with Interactive Content</h3>

                <p>
                    Discover how interactive content can boost user engagement
                    and drive traffic.
                </p>

                <a href="#" class="read-more">Read Now →</a>

            </div>

        </div>


        <!-- CARD 3 -->
        <div class="blog-card">

            <img src="assets/images/3.jpg" alt="">

            <div class="blog-content">

                <div class="blog-top">
                    <span class="category">SEO</span>
                    <span class="date">September 15, 2024</span>
                </div>

                <h3>The Importance of SEO in Blog Writing</h3>

                <p>
                    Understand why SEO is vital for increasing your blog's
                    visibility and ranking.
                </p>

                <a href="#" class="read-more">Read Now →</a>

            </div>

        </div>

    </div>
    <div class="button">
        <button class="blog-btn">Browse All Blogs</button>
    </div>
    

</section>

<!-- Newsletter -->
<section class="newsletter">
  <div class="newsletter-box">

    <h2>Subscribe to our email<br>newsletter</h2>

    <form class="newsletter-form">
      <input type="email" placeholder="Enter Your Email">
      <button type="submit">Submit</button>
    </form>

  </div>
</section>


<!-- ================= BLOG CATEGORIES ================= -->
<section class="categories">
    <h2>Blog categories</h2>

    <div class="category-grid">

        <div class="category-card">
            <img src="assets/images/lifestyle.jpg" alt="">
            <span>Lifestyle</span>
        </div>

        <div class="category-card">
            <img src="assets/images/design.jpg" alt="">
            <span>Design</span>
        </div>

        <div class="category-card">
            <img src="assets/images/webflow.jpg" alt="">
            <span>Webflow</span>
        </div>

        <div class="category-card">
            <img src="assets/images/marketing.jpg" alt="">
            <span>Marketing</span>
        </div>

        <div class="category-card">
            <img src="assets/images/music.jpg" alt="">
            <span>Music</span>
        </div>

    </div>
</section>

<!-- You might also like -->
<section class="recommend">
  <h2>You might also like</h2>

  <div class="recommend-container">

    <div class="card">
      <img src="assets/images/l1.jpg" alt="">
      <h3>Skills that you can learn from business</h3>
      <p>By Larry Lawson</p>
    </div>

    <div class="card">
      <img src="assets/images/l2.jpg" alt="">
      <h3>Five unbelievable facts about money.</h3>
      <p>By Louis Crawford</p>
    </div>

    <div class="card">
      <img src="assets/images/l3.jpg" alt="">
      <h3>This is why this year will be the year</h3>
      <p>By Joan Wallace</p>
    </div>

    <div class="card">
      <img src="assets/images/l4.jpg" alt="">
      <h3>Ten questions you should answer truthfully.</h3>
      <p>By Larry Lawson</p>
    </div>

  </div>
</section>


<!-- ================= FOOTER ================= -->
<footer class="footer">
  <div class="footer-container">

    <!-- LEFT -->
    <div class="footer-left">
      <h2>Subscribe to our email newsletter</h2>
      <p>Subscribe to our email newsletter.</p>

      <div class="subscribe-box">
        <input type="email" placeholder="Enter your email">
        <button class="footer-btn">Subscribe</button>
      </div>
    </div>

    <!-- MIDDLE -->
    <div class="footer-links">
      <ul>
        <li><a href="#" class="read-more">Home</a></li>
        <li><a href="#" class="read-more">Home2</a></li>
        <li><a href="#" class="read-more">Contact</a></li>
        <li><a href="#" class="read-more">Blog</a></li>
        <li><a href="#" class="read-more">About</a></li>
      </ul>
    </div>

    <!-- RIGHT -->
    <div class="footer-links">
      <ul>
        <li><a href="#" class="read-more">Author</a></li>
        <li><a href="#" class="read-more">Privacy Policy</a></li>
        <li><a href="#" class="read-more">Style Guide</a></li>
        <li><a href="#" class="read-more">Instructions</a></li>
        <li><a href="#" class="read-more">Licenses</a></li>
        <li><a href="#" class="read-more">Changelog</a></li>
      </ul>
    </div>

  </div>

  <div class="footer-bottom">
    Designed by <span>Owolabi</span>. Powered by <span>Ahmid</span>
  </div>
</footer>



<script src="assets/js/bilog.js"></script>


</body>
</html>