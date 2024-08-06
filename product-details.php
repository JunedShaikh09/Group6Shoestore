<?php
include 'header.php';
include 'Database.php';

// Retrieve and decode the shoe ID
$encoded_id = $_GET['shoe_id'];
$shoe_id = base64_decode($encoded_id);

// Create a new Product instance
$productObj = new ProductDetail();
$shoe = $productObj->getShoeById($shoe_id);

// Get all shoes with the same name
$shoe_name = $shoe[0]['shoe_name'] ?? '';
$sameNameShoes = $productObj->getShoesByName($shoe_name);

// Handle cart action
if (isset($_POST['quantity'])) {

    // Get quantity and size
    $shoe_qty = intval($_POST['quantity']);
    $shoe_size_id = intval($_POST['shoe_size_id']);
    $subtotal_bill = floatval($shoe[0]['shoe_srp']) * $shoe_qty;
    $shoe_tax_rate = 0.13;
    $shoe_tax = $subtotal_bill * $shoe_tax_rate;
    $final_amt = $subtotal_bill + $shoe_tax;

    $final_amt_formatted = number_format($final_amt, 2, '.', '');

    // Add item to cart
    $cartObj = new Cart();

    if (isset($_SESSION['user_details'])) {
        $user_details = json_decode($_SESSION['user_details'], true);
        $cartObj->addToCartDB($user_details['customer_id'], $shoe_id, $shoe_size_id, $shoe_qty, $subtotal_bill, $shoe_tax, $final_amt_formatted);
    } else {
        $cartObj->addToCart($shoe_id, $shoe_size_id, $shoe_qty, $subtotal_bill, $shoe_tax, $final_amt_formatted);
    }

    // Redirect back to the same page
    header('Location: cart.php');
    exit();
}

?>

<main>
    <section class="products section">
        <div class="container">
            <div class="row" style="margin-top: 32px">
                <div class="col-lg-6">
                    <div class="gallery-view">
                        <div class="gallery-main-image products__gallery-img--lg">
                            <img class="product-main-image" src="./images/products/<?php echo htmlspecialchars($shoe[0]['shoe_image'] ?? 'default.png'); ?>" alt="<?php echo htmlspecialchars($shoe[0]['shoe_name'] ?? ''); ?> Image" />
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <!-- Products information -->
                    <form name="add_to_cart" action="product-details.php?shoe_id=<?php echo $encoded_id; ?>" method="post">

                        <div class=" products__content">
                            <div class="products__content-title">
                                <h2 class="font-title--md"><?php echo htmlspecialchars($shoe[0]['shoe_name'] ?? ''); ?></h2>
                                <?php
                                if ($shoe[0]['shoe_stock'] == 0) {
                                    echo '<span class="label stock-out">Out of Stock</span>';
                                } else {
                                    echo '<span class="label stock-in">In Stock</span>';
                                }
                                ?>
                            </div>
                            <div class="products__content-info">
                                <ul class="ratings">
                                    <li><ion-icon name="star"></ion-icon></li>
                                    <li><ion-icon name="star"></ion-icon></li>
                                    <li><ion-icon name="star"></ion-icon></li>
                                    <li><ion-icon name="star"></ion-icon></li>
                                    <li><ion-icon name="star-half"></ion-icon></li>
                                </ul>
                            </div>

                            <div class="products__content-price">
                                <h2 class="font-body--xxxl-500">
                                    <del class="font-body--xxl-400">$<?php echo htmlspecialchars($shoe[0]['shoe_mrp'] ?? '0.00'); ?></del> $<?php echo htmlspecialchars($shoe[0]['shoe_srp'] ?? '0.00'); ?>
                                </h2>
                            </div>
                        </div>
                        <!-- brand  -->
                        <div class="products__content">
                            <div class="products__content-brand">
                                <div class="brand-name">
                                    <h4 class="font-body--md-400">Brand:</h4>
                                    <div class="brand-logo-card">
                                        <img src="./images/brands/<?php echo htmlspecialchars($shoe[0]['brand_image'] ?? 'default.png'); ?>" alt="brand-img" />
                                    </div>
                                </div>
                            </div>
                            <p class="products__content-brand-info font-body--md-400">
                                <?php echo $shoe[0]['shoe_description'] ?? ''; ?>
                            </p>
                            <div class="products__content-colors">
                                <h4 class="font-body--md-400">Colors:</h4>
                                <div class="color-options">
                                    <?php foreach ($sameNameShoes as $color) : ?>
                                        <a href="product-details.php?shoe_id=<?php echo base64_encode($color['shoe_id']); ?>" class="color-option <?php if ($shoe_id == $color['shoe_id']) echo htmlspecialchars('active') ?>">
                                            <img src="./images/products/<?php echo htmlspecialchars($color['shoe_image'] ?? 'default.png'); ?>" alt="color-img" />
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="products__content-sizes">
                                <h4 class="font-body--md-400">Sizes:</h4>
                                <div class="size-options">
                                    <select name="shoe_size_id" class="size-options-select font-body--md-400">
                                        <?php foreach ($shoe as $size) :
                                        ?>
                                            <option value="<?php echo htmlspecialchars($size['shoe_size_id']); ?>" <?php echo intval($size['shoe_size_stock']) == 0 ? 'disabled' : ''; ?>>
                                                <?php echo htmlspecialchars($size['shoe_size']); ?> (<?php echo htmlspecialchars($size['size_type']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!-- Action button -->
                        <div class="products__content">
                            <div class="products__content-action">
                                <div class="counter-btn-wrapper">
                                    <button type="button" id="counterBtnDec" class="counter-btn counter-btn-dec">-</button>
                                    <input type="number" name="quantity" id="counterBtnCounter" class="counter-btn-counter bg-white" min="0" max="10" value="1">
                                    <button type="button" id="counterBtnInc" class="counter-btn counter-btn-inc">+</button>
                                </div>
                                <!-- add to cart  -->
                                <button type="submit" class="add-to-cart-button button--md products__content-action-item">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                        <!-- Tags  -->
                        <div class="products__content">
                            <h5 class="products__content-category font-body--md-500">
                                Category: <span><a href="shop.php?category=<?php echo $shoe[0]['category_id'] ?? ''; ?>" class="text-decoration-none"><?php echo htmlspecialchars($shoe[0]['category_name'] ?? ''); ?></a></span>
                            </h5>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
include 'footer.php';
?>

<script>
    const counterBtnDec = document.getElementById('counterBtnDec');
    const counterBtnInc = document.getElementById('counterBtnInc');
    const counterBtnCounter = document.getElementById('counterBtnCounter');

    // Update button states based on the current quantity
    function updateButtonStates() {
        const quantity = parseInt(counterBtnCounter.value);

        // Disable decrement button if quantity is 1
        if (quantity == 1) {
            counterBtnDec.setAttribute('disabled', 'disabled');
        } else {
            counterBtnDec.removeAttribute('disabled');
        }

        // Disable increment button if quantity is 20
        if (quantity >= 20) {
            counterBtnInc.setAttribute('disabled', 'disabled');
        } else {
            counterBtnInc.removeAttribute('disabled');
        }
    }

    // Initialize button states
    updateButtonStates();

    // Decrement button event listener
    counterBtnDec.addEventListener('click', () => {
        let currentValue = parseInt(counterBtnCounter.value);
        if (currentValue > 0) {
            counterBtnCounter.value = currentValue - 1;
            updateButtonStates(); // Update button states after change
        }
    });

    // Increment button event listener
    counterBtnInc.addEventListener('click', () => {
        let currentValue = parseInt(counterBtnCounter.value);
        if (currentValue < 20) {
            counterBtnCounter.value = currentValue + 1;
            updateButtonStates(); // Update button states after change
        }
    });
</script>