<?php
include 'header.php';
include 'Database.php';

// Create a new Brand instance
$brandObj = new Brand();
$brands = $brandObj->getAllBrands();

// Create a new Category instance
$categoryObj = new Category();
$categories = $categoryObj->getAllCategories();

// Get current filter values from query parameters
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';
$selectedBrand = isset($_GET['brand']) ? $_GET['brand'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';

// Fetch products based on filters (this is an example, adjust according to your query)
$productObj = new Product();
$products = $productObj->getProducts($selectedCategory, $selectedBrand, $sort);

?>
<main>
  <div class="products-section">
    <h2 class="products-section-heading heading">All Products</h2>
    <form method="get" action="shop.php" id="filterForm">
      <div class="filter-box">
        <div class="container ps-0">
          <div class="filter-box__top">
            <div class="filter-box__top-left">
              <div class="select-box--item">
                <select id="category" name="category" class="filter__dropdown-menu">
                  <option value="">Select Category</option>
                  <?php
                  foreach ($categories as $category) {
                    $selected = $category['category_id'] == $selectedCategory ? 'selected' : '';
                    echo '<option value="' . $category['category_id'] . '" ' . $selected . '>' . $category['category_name'] . '</option>';
                  }
                  ?>
                </select>
              </div>
              <div class="select-box--item">
                <select id="brands" name="brand" class="filter__dropdown-menu">
                  <option value="">Select Brand</option>
                  <?php
                  foreach ($brands as $brand) {
                    $selected = $brand['brand_id'] == $selectedBrand ? 'selected' : '';
                    echo '<option value="' . $brand['brand_id'] . '" ' . $selected . '>' . $brand['brand_name'] . '</option>';
                  }
                  ?>
                </select>
              </div>
            </div>
            <div class="filter-box__top-right">
              <div class="select-box--item">
                <select id="sort-by" name="sort" class="filter__dropdown-menu">
                  <option value="sort-by-latest" <?php echo $sort == 'sort-by-latest' ? 'selected' : ''; ?>>Latest</option>
                  <option value="sort-by-oldest" <?php echo $sort == 'sort-by-oldest' ? 'selected' : ''; ?>>Oldest</option>
                  <option value="sort-by-low" <?php echo $sort == 'sort-by-low' ? 'selected' : ''; ?>>Price: Low to High</option>
                  <option value="sort-by-high" <?php echo $sort == 'sort-by-high' ? 'selected' : ''; ?>>Price: High to Low</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="filter-box__bottom">
          <div class="container ps-0">
            <div class="filter-box__bottom-content">
              <div class="active__filters">
                <?php
                if ($selectedCategory || $selectedBrand) {
                  echo "<h5>Active Filters:</h5>";
                }
                ?>
                <div class="active__filters-item">
                  <?php if ($selectedCategory) : ?>
                    <button class="filter-item" onclick="removeFilter('category', '<?php echo $selectedCategory; ?>')">
                      Category: <?php echo $categories[array_search($selectedCategory, array_column($categories, 'category_id'))]['category_name']; ?>
                      <span class="icon">
                        <ion-icon name="close"></ion-icon>
                      </span>
                    </button>
                  <?php endif; ?>
                  <?php if ($selectedBrand) : ?>
                    <button class="filter-item" onclick="removeFilter('brand', '<?php echo $selectedBrand; ?>')">
                      Brand: <?php echo $brands[array_search($selectedBrand, array_column($brands, 'brand_id'))]['brand_name']; ?>
                      <span class="icon">
                        <ion-icon name="close"></ion-icon>
                      </span>
                    </button>
                  <?php endif; ?>
                </div>
              </div>
              <div class="filter__result">
                <p><span class="number"><?php echo count($products); ?></span> Results found.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
    <div class="products-cards">
      <?php if (count($products) != 0) : ?>
        <?php foreach ($products as $product) : ?>
          <div class="product-card">
            <img src="./images/products/<?php echo $product['shoe_image']; ?>" alt="<?php echo htmlspecialchars($product['shoe_name']); ?> Image" />
            <div class="product-details mt-2">
              <p class="text-secondary-emphasis text-start mb-0"><?php echo htmlspecialchars($product['shoe_name']); ?></p>
              <div class="d-flex align-items-center justify-content-between mt-4">
                <div class="text-start">
                  <h3 class="product-price-heading">
                    $<?php echo number_format($product['shoe_srp'], 2); ?> <del class="old-price text-black-50">&nbsp;$<?php echo number_format($product['shoe_mrp'], 2); ?></del>
                  </h3>
                  <ion-icon name="star" class="review-icon"></ion-icon>
                  <ion-icon name="star" class="review-icon"></ion-icon>
                  <ion-icon name="star" class="review-icon"></ion-icon>
                  <ion-icon name="star" class="review-icon"></ion-icon>
                  <ion-icon name="star-half" class="review-icon"></ion-icon>
                </div>
                <a href="product-details.php?shoe_id=<?php echo base64_encode($product['shoe_id']); ?>" class="btn"><ion-icon name="arrow-forward-outline"></ion-icon></a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else : ?>
        <div class="w-100 align-items-center d-flex justify-content-center">
          <img src="./images/no-product-found.png" class="w-50" alt="No Results Found" />
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php
include 'footer.php';
?>

<script>
  function updateFilters() {
    document.getElementById('filterForm').submit();
  }

  function removeFilter(type, value) {
    const form = document.getElementById('filterForm');
    const inputs = form.querySelectorAll('select[name]');

    // Remove filter value
    for (let input of inputs) {
      if (input.name === type) {
        input.value = '';
        break;
      }
    }

    // Submit the form
    form.submit();
  }

  document.addEventListener('DOMContentLoaded', function() {
    const filters = document.querySelectorAll('select[name]');
    filters.forEach(filter => {
      filter.addEventListener('change', updateFilters);
    });
  });
</script>