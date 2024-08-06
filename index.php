<?php
include_once 'header.php';
include 'Database.php';

// Create a new Brand instance
$brandObj = new Brand();
$brands = $brandObj->getAllBrands();

// Create a new Product instance
$productObj = new Product();
$limit = 8;
$latestProducts = $productObj->getLatestProducts($limit);

?>

<main>
    <section class="banner-section">
        <div class="container">
            <div class="banner swiper">
                <div class="swiper-wrapper">
                    <div class="banner swiper-slide">
                        <a href="shop.php?category=1">
                            <img src="./images/banner/banner_1.png" alt="Banner Image" />
                        </a>
                    </div>
                    <div class="swiper-slide">
                        <a href="shop.php?category=2">
                            <img src="./images/banner/banner_2.png" alt="Banner Image" />
                        </a>
                    </div>
                    <div class="swiper-slide">
                        <a href="shop.php">
                            <img src="./images/banner/banner_3.png" alt="Banner Image" />
                        </a>
                    </div>
                </div>

                <!-- <div class="swiper-pagination"></div> -->
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </div>
    </section>

    <section class="new-products-section">
        <div class="products-section">
            <div class="section-top w-100 d-flex align-items-center justify-content-between">
                <h2 class="products-section-heading heading">New Arrivals: Fresh Styles Just In!</h2>
                <a href="shop.php" class="btn pe-0 border-0">View All
                    <span class="align-middle">
                        <ion-icon name="arrow-forward-outline"></ion-icon>
                    </span>
                </a>
            </div>
            <div class="container ps-0">
                <div class="swiper new-products-swiper-container">
                    <div class="swiper-wrapper gap-3">
                        <?php foreach ($latestProducts as $product) : ?>
                            <div class="latest-products product-card swiper-slide">
                                <!-- <div class=""> -->
                                <img src="./images/products/<?= htmlspecialchars($product['shoe_image']) ?>" alt="<?= htmlspecialchars($product['shoe_name']) ?> Image" class="img-fluid" />
                                <div class="product-details mt-2">
                                    <p class="text-secondary-emphasis text-start mb-0"><?= htmlspecialchars($product['shoe_name']) ?></p>
                                    <div class="d-flex align-items-center justify-content-between mt-4">
                                        <div class="text-start">
                                            <h3 class="product-price-heading">
                                                $<?= number_format($product['shoe_srp'], 2); ?>
                                                <del class="old-price text-black-50">&nbsp;$<?= number_format($product['shoe_mrp'], 2); ?></del>
                                            </h3>
                                            <ion-icon name="star" class="review-icon"></ion-icon>
                                            <ion-icon name="star" class="review-icon"></ion-icon>
                                            <ion-icon name="star" class="review-icon"></ion-icon>
                                            <ion-icon name="star" class="review-icon"></ion-icon>
                                            <ion-icon name="star-half" class="review-icon"></ion-icon>
                                        </div>
                                        <a href="product-details.php?shoe_id=<?= base64_encode($product['shoe_id']); ?>" class="btn"><ion-icon name="arrow-forward-outline"></ion-icon></a>
                                    </div>
                                </div>
                                <!-- </div> -->
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <section class="shop-by-brands-section">
        <div class="section-top w-100 d-flex align-items-center justify-content-between">
            <h2 class="products-section-heading heading">Shop By Brands</h2>
        </div>
        <div class="container ps-0">
            <div class="swiper brands-swiper-container">
                <div class="swiper-wrapper">
                    <?php foreach ($brands as $brand) { ?>
                        <div class="swiper-slide">
                            <a href="shop.php?brand=<?php echo $brand['brand_id']; ?>">
                                <div class="brand-card">
                                    <img src="./images/brands/<?php echo $brand['brand_image']; ?>" alt="<?php echo $brand['brand_name']; ?> Image">
                                </div>
                            </a>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>

</main>

<?php
include_once 'footer.php';
?>