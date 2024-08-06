<!-- Footer -->
<footer>
    <div class="footer-container">
        <div class="footer-logo">
            <a href="index.php"><img src="images/logo.png" alt="ShoeShack Logo" /></a>
            <p>Discover the latest trends in footwear at ShoeShack. Shop now for exclusive deals!</p>
        </div>
        <div class="footer-page-links mt-4">
            <nav>
                <div class="page-links-heading">
                    <h3>Page Links</h3>
                </div>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="shop.php">Shop</a></li>
                    <li><a href="cart.php">Shopping Cart</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </nav>
        </div>
        <div class="footer-shoes-categories mt-4">
            <div class="categories-links-heading">
                <h3>Shoes Categories</h3>
            </div>
            <ul class="footer-links">
                <li>
                    <a href="shop.php?category=1">Men</a>
                </li>
                <li>
                    <a href="shop.php?category=2">Women</a>
                </li>
                <li>
                    <a href="shop.php?category=3">Kids</a>
                </li>
            </ul>
        </div>
        <div class="footer-shoes-brands mt-4">
            <div class="brands-links-heading">
                <h3>Top Brands</h3>
            </div>
            <ul class="footer-links">
                <li>
                    <a href="shop.php?brand=1">Nike</a>
                </li>
                <li>
                    <a href="shop.php?brand=2">Under Armour</a>
                </li>
                <li>
                    <a href="shop.php?brand=4">Adidas</a>
                </li>
                <li>
                    <a href="shop.php?brand=6">Puma</a>
                </li>
            </ul>
        </div>
    </div>
    <div class="copyright">
        <p class="mb-0">
            Copyright &copy; 2024 ShoeShack. All rights reserved.
        </p>
    </div>
</footer>

<!-- Footer Scripts -->

<!-- Ionicons -->
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Swiper JS Slider -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<!-- Footer Scripts -->

</body>

</html>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const toggler = document.querySelector(".navbar-toggler-custom");
        const offcanvasNavbar = new bootstrap.Offcanvas(document.getElementById("offcanvasNavbar"));

        toggler.addEventListener("click", function() {
            offcanvasNavbar.show();
        });

        const swiper = new Swiper('.swiper', {
            direction: 'horizontal',
            loop: true,
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });

        var swiper2 = new Swiper('.new-products-swiper-container', {
            slidesPerView: 4,
            spaceBetween: 30,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            loop: true,
            breakpoints: {
                1024: {
                    slidesPerView: 4,
                },
                768: {
                    slidesPerView: 3,
                },
                480: {
                    slidesPerView: 1,
                },
            },
        });

        var swiper3 = new Swiper('.brands-swiper-container', {
            slidesPerView: 5,
            spaceBetween: 50,
            autoplay: {
                delay: 3000,
                disableOnInteraction: false,
            },
            loop: true,
            breakpoints: {
                1024: {
                    slidesPerView: 5,
                },
                768: {
                    slidesPerView: 3,
                },
                480: {
                    slidesPerView: 2,
                },
            },
        });

    });
</script>