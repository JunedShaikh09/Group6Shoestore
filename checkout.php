<?php
include_once 'header.php';
include_once 'Database.php';

if (isset($_SESSION['user_details'])) {
    $error = [];
    $userDetails = json_decode($_SESSION['user_details'], true);
    $cartItems = (new Cart())->getCartItemsFromDB($userDetails['customer_id']);

    // Check if cart items are empty and redirect if true
    if (empty($cartItems)) {
        header('Location: shop.php');
        exit();
    }

    $shoeDetails = [];
    foreach ($cartItems as $item) {
        $shoe_id = $item['shoe_id'];
        $productObj = new ProductDetail();
        $shoe = $productObj->getShoeBySizeId($shoe_id, $item['shoe_size_id']);
        if ($shoe) {
            $shoeDetails[] = [
                'shoe' => $shoe[0],
                'quantity' => $item['shoe_qty'],
                'subtotal' => $item['subtotal_bill'],
                'tax' => $item['shoe_tax'],
                'total' => $item['final_amt']
            ];
        }
    }

    $provincesObj = new PlaceOfSupply();
    $provinces = $provincesObj->getProvinces();

    if (isset($_POST['place_order'])) {
        // Validate fields
        if (empty($_POST['address'])) {
            $error[] = 'Address is required.';
        }

        if (empty($_POST['cities'])) {
            $error[] = 'City is required.';
        }

        if (empty($_POST['states'])) {
            $error[] = 'Province is required.';
        }

        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $error[] = 'Invalid email address.';
        }

        if (empty($_POST['postal_code'])) {
            $error[] = 'Postal code is required.';
        } elseif (!preg_match('/^[A-Za-z]\d[A-Za-z] \d[A-Za-z]\d$/', $_POST['postal_code'])) {
            $error[] = 'Invalid postal code format. It should be in the format A1A 1A1.';
        }

        if (empty($_POST['phone'])) {
            $error[] = 'Phone number is required.';
        } elseif (!preg_match('/^\d{10}$/', $_POST['phone'])) {
            $error[] = 'Invalid phone number. It should be 10 digits.';
        }

        if (empty($_POST['payment'])) {
            $error[] = 'Please select a payment method.';
        }

        // If no errors, proceed with order placement
        if (empty($error)) {
            $customer_id = $userDetails['customer_id'];
            $address = $_POST['address'];
            $country = $_POST['country'];
            $place_of_supply_id = $_POST['cities'];
            $postal_code = $_POST['postal_code'];
            $phone = $_POST['phone'];
            $payment_method = $_POST['payment'];

            $userAddressObj = new UserAddress();
            $userAddressObj->addUserAddress($customer_id, $address, $country, $place_of_supply_id, $postal_code, $phone);

            $invoice_number = uniqid("inv-");
            $payment_type = $payment_method;
            $order_status = 'Completed';

            $checkoutObj = new Checkout();
            $order_id = $checkoutObj->updateOrderMasterTbl($customer_id, $invoice_number, $payment_type, $order_status);

            // Generate the invoice
            header("Location: invoice.php?order_id=" . base64_encode($order_id));
            exit();
        }
    }
} else {
    header('Location: sign-in.php');
    exit();
}
?>
<!-- Main -->
<main>
    <section class="billing-section">
        <div class="container">
            <form method="post" action="checkout.php" class="billing-content">
                <div class="billing-form">
                    <div class="billing-card">
                        <div class="billing-card-header">
                            <h2>Billing Information</h2>
                        </div>
                        <div class="billing-card-body">
                            <div class="contact-form flex-column">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="fname" class="form-label">First Name</label>
                                        <input type="text" id="fname" class="form-control" placeholder="Your first name" value="<?php echo $userDetails['firstname']; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="lname" class="form-label">Last Name</label>
                                        <input type="text" id="lname" class="form-control" placeholder="Your last name" value="<?php echo $userDetails['lastname']; ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Street Address</label>
                                    <input type="text" id="address" name="address" class="form-control" placeholder="Your Address">
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-2">
                                        <label for="country" class="form-label">Country</label>
                                        <input type="text" id="country" name="country" class="form-control" placeholder="Your Country" value="Canada" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="states" class="form-label">Province</label>
                                        <select id="states" name="states" class="form-select">
                                            <option value="">Select Province</option>
                                            <?php
                                            foreach ($provinces as $province) {
                                                echo "<option value='$province[place_of_supply_id]'>$province[provinces]</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="cities" class="form-label">City</label>
                                        <select id="cities" name="cities" class="form-select" disabled>
                                            <option value="">Select City</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="postal-code" class="form-label">Postal Code</label>
                                        <input type="text" id="postal_code" name="postal_code" class="form-control" placeholder="Postal Code">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="text" id="email" name="email" class="form-control" placeholder="Email Address" value="<?php echo $userDetails['email']; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="text" id="phone" name="phone" class="form-control" placeholder="Phone number">
                                    </div>
                                </div>

                                <?php if (!empty($error)) : ?>
                                    <div class="alert alert-danger">
                                        <ul class="d-fleex justify-content-center align-items-center">
                                            <?php foreach ($error as $err) : ?>
                                                <li><?php echo htmlspecialchars($err); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="order-summary">
                    <div class="bill-card">
                        <div class="bill-card-header">
                            <h2>Order Summary</h2>
                        </div>
                        <div class="bill-card-body">
                            <div class="product-list">
                                <?php foreach ($shoeDetails as $details) : ?>
                                    <div class="product-item">
                                        <a href="cart.php" class="text-decoration-none text-dark ">
                                            <div class="product-info">
                                                <img src="./images/products/<?php echo htmlspecialchars($details['shoe']['shoe_image']); ?>" alt="<?php echo htmlspecialchars($details['shoe']['shoe_name']); ?> Image" />
                                                <h5 class="d-inline-block text-truncate" style="max-width: 180px;"><?php echo htmlspecialchars($details['shoe']['shoe_name']); ?></h5><span class="quantity">x<?php echo htmlspecialchars($details['quantity']); ?>
                                            </div>
                                        </a>
                                        <p class="product-price">$<?php echo htmlspecialchars($details['shoe']["shoe_srp"] * $details['quantity']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="order-totals">
                                <div class="total-item">
                                    <p>Subtotal:</p>
                                    <span>$<?php echo htmlspecialchars(number_format($details['subtotal'], 2)); ?></span>
                                </div>
                                <div class="total-item">
                                    <p>TAX:</p>
                                    <span>$<?php echo htmlspecialchars(number_format($details['tax'], 2)); ?></span>
                                </div>
                                <div class="total-item">
                                    <p>Shipping:</p>
                                    <span>Free</span>
                                </div>
                                <div class="total-item total">
                                    <p>Total:</p>
                                    <span>$<?php echo htmlspecialchars(number_format($details['total'], 2)); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bill-card">
                        <div class="bill-card-header">
                            <h2>Payment Method</h2>
                        </div>
                        <div class="bill-card-body">
                            <div class="payment-methods">
                                <div class="payment-method">
                                    <input type="radio" name="payment" id="debit" value="Debit" />
                                    <ion-icon name="card-outline"></ion-icon>
                                    <label for="debit" class="ms-1">Debit / Credit Card</label>
                                </div>
                                <div class="payment-method">
                                    <input type="radio" name="payment" id="paypal" value="Paypal" />
                                    <ion-icon name="logo-paypal"></ion-icon>
                                    <label for="paypal" class="ms-1">Paypal</label>
                                </div>
                            </div>
                            <button type="submit" name="place_order" class="place-order-btn" disabled>Place Order</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
</main>

<?php
include_once 'footer.php';
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymentMethods = document.querySelectorAll('input[name="payment"]');
        const placeOrderButton = document.querySelector('.place-order-btn');


        // // redirect to shop.php on click of place order button 
        // document.querySelector('.place-order-btn').addEventListener('click', function() {
        //     window.location.href = 'shop.php';
        // })

        // Function to check if any payment method is selected and enable/disable the button
        function checkPaymentSelection() {
            const isAnyPaymentMethodSelected = Array.from(paymentMethods).some(method => method.checked);
            placeOrderButton.disabled = !isAnyPaymentMethodSelected;
        }

        paymentMethods.forEach(method => {
            method.addEventListener('change', checkPaymentSelection);
        });

        checkPaymentSelection();

        const provinceSelect = document.querySelector('#states');
        const citySelect = document.getElementById('cities');
        const form = document.querySelector('form.contact-form');

        // Handle province change
        provinceSelect.addEventListener('change', function() {
            // Get the selected option element
            const selectedOption = this.selectedOptions[0];

            // Get the text content of the selected option
            const selectedProvinceText = selectedOption ? selectedOption.textContent : '';

            // Clear the cities select box
            citySelect.innerHTML = '<option value="">Select City</option>';
            citySelect.disabled = true;

            if (selectedProvinceText !== "Select Province") {
                // Fetch cities from the server based on the selected province
                fetch('get_cities.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            'province': selectedProvinceText,
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (Array.isArray(data)) {
                            // Populate the city select dropdown with the cities
                            data.forEach(city => {
                                const option = document.createElement('option');
                                option.value = city.place_of_supply_id;
                                option.textContent = city.cities;
                                citySelect.appendChild(option);
                            });
                            citySelect.disabled = false;
                        } else {
                            console.error('Unexpected response format:', data);
                        }
                    })
                    .catch(error => console.error('Error fetching cities:', error));
            }
        });


    });
</script>