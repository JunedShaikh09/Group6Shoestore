<?php
include_once 'header.php';
include_once 'Database.php';

$isSignedIn = false;
$productObj = new ProductDetail();
$shoeDetails = [];

if (isset($_SESSION['user_details'])) {
  $isSignedIn = true;

  $userDetails = json_decode($_SESSION['user_details'], true);
  // Retrieve Cart Items
  $cartItems = (new Cart())->getCartItemsFromDB($userDetails['customer_id']);
} else {
  $isSignedIn = false;
  // Retrieve Cart Items
  $cartItems = (new Cart())->getCartItemsFromCookies();
}

foreach ($cartItems as $item) {
  $shoe_id = $item['shoe_id'];

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

if (isset($_POST['checkout'])) {
  if (isset($_SESSION['user_details'])) {
    header('Location: checkout.php');
  } else {
    header('Location: sign-in.php');
  }
}
?>
<!-- Main -->
<main>
  <section class="shopping-cart">
    <div class="container p-0">
      <?php if (count($shoeDetails) > 0) : ?>
        <div class="section__head">
          <h2 class="section--title-four">My Shopping Cart</h2>
        </div>
        <div class="shopping-cart__content">
          <div class="cart-table-container">
            <div class="cart-table">
              <table class="table">
                <thead>
                  <tr>
                    <th scope="col">Product</th>
                    <th scope="col">Price</th>
                    <th scope="col">Quantity</th>
                    <th scope="col">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($shoeDetails as $details) : ?>
                    <?php
                    $shoe = $details['shoe'];
                    $quantity = $details['quantity'];
                    $subtotal = 0;
                    if ($isSignedIn) {
                      $subtotal = floatval($shoe['shoe_srp']) * intval($quantity);
                    } else {
                      $subtotal = $details['subtotal'];
                    }
                    ?>
                    <tr>
                      <td data-label="Product" class="cart-table-item align-middle">
                        <a href="product-details.php?shoe_id=<?php echo base64_encode($shoe['shoe_id']); ?>" class="cart-table__product-item">
                          <img src="./images/products/<?php echo htmlspecialchars($shoe['shoe_image'] ?? 'default.png'); ?>" alt="<?php echo htmlspecialchars($shoe['shoe_name']); ?>" class="cart-table__product-item-img">
                          <h5 class="w-60">
                            <?php echo htmlspecialchars($shoe['shoe_name']); ?>
                            <br><br>
                            <b>Size: <?php echo htmlspecialchars($shoe['shoe_size']);
                                      echo htmlspecialchars($shoe['size_type']); ?></b>
                          </h5>

                        </a>
                      </td>
                      <td data-label="Price" class="cart-table-item order-date align-middle">
                        $<?php echo number_format(floatval($shoe['shoe_srp']), 2, '.', ''); ?>
                      </td>
                      <td data-label="Quantity" class="cart-table-item order-total align-middle">
                        <div class="counter-btn-wrapper">
                          <button type="button" id="counterBtnDec" class="counter-btn counter-btn-dec">-</button>
                          <input type="number" name="quantity" id="counterBtnCounter" class="counter-btn-counter bg-white" min="0" max="10" value="<?php echo $quantity; ?>" readonly>
                          <button type="button" id="counterBtnInc" class="counter-btn counter-btn-inc">+</button>
                        </div>
                      </td>
                      <td data-label="Subtotal" class="cart-table-item order-subtotal align-middle">
                        <div class="d-flex justify-content-between align-items-center">
                          <p class=" mb-0">$<?php echo number_format(floatval($subtotal), 2, '.', ''); ?></p>
                          <button type="button" class="delete-item">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                              <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="#CCCCCC" stroke-miterlimit="10" />
                              <path d="M16 8L8 16" stroke="#666666" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                              <path d="M16 16L8 8" stroke="#666666" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                          </button>
                        </div>
                      </td>
                      <input type="hidden" id="shoe_id" name="shoe_id" value="<?php echo $shoe['shoe_id']; ?>">
                      <input type="hidden" id="shoe_size_id" name="shoe_size_id" value="<?php echo $shoe['shoe_size_id']; ?>">
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="cart-table-action-btn">
              <a href="shop.php" class="btn-back-to-shop">Return to Shop</a>
            </div>
          </div>

          <div class="order-summary-container">
            <div class="bill-card">
              <div class="bill-card__content">
                <div class="bill-card__header">
                  <h2 class="bill-card__header-title">Order Summary</h2>
                </div>
                <div class="bill-card__body">
                  <div class="bill-card__memo">
                    <?php
                    $subtotal_sum = 0;
                    $tax_sum = 0;
                    $total_sum = 0;

                    foreach ($shoeDetails as $details) {
                      if ($isSignedIn) {
                        $subtotal_sum = $details['subtotal'];
                        $tax_sum = $details['tax'];
                        $total_sum = $details['total'];
                      } else {
                        $subtotal_sum += $details['subtotal'];
                        $tax_sum += $details['tax'];
                        $total_sum += $details['total'];
                      }
                    }
                    ?>
                    <div class="bill-card__memo-item subtotal">
                      <p>Subtotal:</p>
                      <span>$<?php echo number_format(floatval($subtotal_sum), 2, '.', ''); ?></span>
                    </div>
                    <div class="bill-card__memo-item taxes">
                      <p>TAX:</p>
                      <span>$<?php echo number_format(floatval($tax_sum), 2, '.', ''); ?></span>
                    </div>
                    <div class="bill-card__memo-item shipping">
                      <p>Shipping:</p>
                      <span>Free</span>
                    </div>
                    <div class="bill-card__memo-item total">
                      <p>Total:</p>
                      <span>$<?php echo number_format(floatval($total_sum), 2, '.', ''); ?></span>
                    </div>
                  </div>
                  <form method="post">
                    <button name="checkout" class="place-order-button" type="submit">
                      Checkout
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </div>

        </div>
      <?php else : ?>
        <div class="empty-cart">
          <div class="empty-cart__content d-flex flex-column justify-content-center align-items-center gap-3">
            <div class="empty-cart__image">
              <img src="./images/empty-cart.png" alt="Empty Cart">
            </div>
            <a href="shop.php" class="btn-back-to-shop">Return to Shop</a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php
include_once 'footer.php';
?>

<script>
  document.addEventListener('DOMContentLoaded', function() {

    const updateCart = async (shoeId, shoeSizeId, newQty) => {
      const response = await fetch('update_cart.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          'action': 'update',
          'shoe_id': shoeId,
          'shoe_size_id': shoeSizeId,
          'quantity': newQty
        })
      });

      const result = await response.json();
      if (!result.success) {
        alert('Failed to update cart');
      } else {
        window.location.reload();
      }
    };

    const deleteCartItem = async (shoeId, shoeSizeId) => {
      const response = await fetch('update_cart.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          'action': 'delete',
          'shoe_id': shoeId,
          'shoe_size_id': shoeSizeId
        })
      });

      const result = await response.json();

      if (!result.success) {
        alert('Failed to remove item');
      } else {
        window.location.reload();
      }
    };

    document.querySelectorAll('.counter-btn').forEach(button => {
      button.addEventListener('click', function() {
        const row = this.closest('tr');
        const shoeId = row.querySelector('[name="shoe_id"]').value;
        const shoeSizeId = row.querySelector('[name="shoe_size_id"]').value;
        const quantityInput = row.querySelector('.counter-btn-counter');
        let quantity = parseInt(quantityInput.value);

        if (this.classList.contains('counter-btn-inc')) {
          if (quantity < 20) {
            quantity++;
          } else {
            alert('Quantity cannot exceed 20.');
            return;
          }
        } else if (this.classList.contains('counter-btn-dec')) {
          if (quantity > 1) {
            quantity--;
          } else {
            alert('Quantity cannot go below 1.');
            return;
          }
        }

        quantityInput.value = quantity;
        updateCart(shoeId, shoeSizeId, quantity);
      });
    });

    document.querySelectorAll('.delete-item').forEach(button => {
      button.addEventListener('click', function() {
        const row = this.closest('tr');
        const shoeId = row.querySelector('[name="shoe_id"]').value;
        const shoeSizeId = row.querySelector('[name="shoe_size_id"]').value;
        deleteCartItem(shoeId, shoeSizeId);
      });
    });

  });
</script>