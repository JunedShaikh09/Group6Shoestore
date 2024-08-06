<?php
// Database.php
class Database
{
    private $connection;
    private $host = 'localhost';
    private $user = 'root';
    private $password = '';
    private $dbname = 'shoeshack';

    public function __construct()
    {
        $this->connect();
    }

    private function connect()
    {
        $this->connection = new mysqli($this->host, $this->user, $this->password, $this->dbname);

        if ($this->connection->connect_error) {
            die('Database connection failed: ' . $this->connection->connect_error);
        }
        $this->connection->set_charset('utf8mb4');
    }

    public function query($sql, $params = [])
    {
        $stmt = $this->connection->prepare($sql);
        if ($params) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    }

    public function close()
    {
        $this->connection->close();
    }
}

class User
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function sanitize($input)
    {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    public function userExists($email)
    {
        $result = $this->db->query('SELECT COUNT(*) FROM manage_customer_tbl WHERE customer_email = ?', [$email]);
        return $result->fetch_row()[0] > 0;
    }

    public function registerUser($firstname, $lastname, $email, $password)
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $this->db->query('INSERT INTO manage_customer_tbl (customer_firstname, customer_lastname, customer_email, customer_password) VALUES (?, ?, ?, ?)', [
            $firstname, $lastname, $email, $hashedPassword
        ]);
    }

    public function authenticateUser($email, $password)
    {
        $result = $this->db->query('SELECT * FROM manage_customer_tbl WHERE customer_email = ?', [$email]);
        $user = $result->fetch_assoc();

        // echo "<pre>";
        // print_r($user);
        // echo "</pre>";
        // exit();

        if ($user && password_verify($password, $user['customer_password'])) {
            $_SESSION['user_details'] = json_encode([
                'customer_id' => $user['customer_id'],
                'firstname' => $user['customer_firstname'],
                'lastname' => $user['customer_lastname'],
                'email' => $user['customer_email']
            ]);

            // Transfer Cookies data to DB
            $this->transferCartToDatabase($user['customer_id']);

            return true;
        } else {
            return false;
        }
    }

    // Get User and User's Address Details
    public function getUserDetails($customer_id)
    {
        $result = $this->db->query('SELECT c.*, a.* FROM manage_customer_tbl c JOIN manage_customer_address_tbl a ON c.customer_id = a.customer_id WHERE c.customer_id = ?', [$customer_id]);
        return $result->fetch_assoc();
    }

    private function transferCartToDatabase($customer_id)
    {
        // Retrieve cart items from cookies
        $cartItems = (new Cart())->getCartItemsFromCookies();

        if (empty($cartItems)) {
            return;
        }

        $subtotal_bill = array_sum(array_column($cartItems, 'subtotal_bill'));
        $shoe_tax = array_sum(array_column($cartItems, 'shoe_tax'));
        $invoice_number = "";
        $shipping_charges = 0;
        $discount_id = null;
        $discount_amt = 0;
        $payment_type = "";
        $order_status = "Pending";
        $final_amt = array_sum(array_column($cartItems, 'final_amt'));

        // Insert order details
        $this->db->query('INSERT INTO order_master_tbl (customer_id, invoice_number, subtotal_bill, shipping_charges, shoe_tax, discount_id, discount_amt, final_amt, payment_type,order_status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $customer_id, $invoice_number, $subtotal_bill, $shipping_charges, $shoe_tax, $discount_id, $discount_amt, $final_amt, $payment_type, $order_status
        ]);

        // Get the last inserted order_id from order_master_tbl using select 
        $result = $this->db->query('SELECT order_id FROM order_master_tbl WHERE customer_id = ? AND order_status = ?', [$customer_id, $order_status]);
        $order_id = $result->fetch_assoc();
        $order_id = $order_id['order_id'];

        echo "<pre>";
        print_r($order_id);
        echo "</pre>";

        // Insert cart items
        foreach ($cartItems as $item) {
            $this->db->query('INSERT INTO cart_master_tbl (order_id, shoe_id, shoe_size_id, shoe_qty) VALUES (?, ?, ?, ?)', [
                $order_id, $item['shoe_id'], $item['shoe_size_id'], $item['shoe_qty']
            ]);
        }

        // Clear cart cookies
        setcookie('cart', '', time() - 3600, '/');
    }
}

class Brand
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getAllBrands()
    {
        $result = $this->db->query('SELECT * FROM brand_master_tbl');
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

class Category
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getAllCategories()
    {
        $result = $this->db->query('SELECT * FROM category_master_tbl');
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

class Product
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getLatestProducts($limit)
    {
        $sql = '
            SELECT s.*, c.*
            FROM shoes_master_tbl s
            JOIN shoes_color_tbl c ON s.shoe_id = c.shoe_id
            ORDER BY s.shoe_date DESC
            LIMIT ?';

        $params = [$limit];
        $result = $this->db->query($sql, $params);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getProducts($category = '', $brand = '', $sort = '')
    {
        $sql = '
            SELECT s.*, c.*
            FROM shoes_master_tbl s
            JOIN shoes_color_tbl c ON s.shoe_id = c.shoe_id
            WHERE 1=1';
        $params = [];

        if ($category) {
            $sql .= ' AND s.category_id = ?';
            $params[] = $category;
        }

        if ($brand) {
            $sql .= ' AND s.brand_id = ?';
            $params[] = $brand;
        }

        switch ($sort) {
            case 'sort-by-latest':
                $sql .= ' ORDER BY s.shoe_date DESC';
                break;
            case 'sort-by-oldest':
                $sql .= ' ORDER BY s.shoe_date ASC';
                break;
            case 'sort-by-low':
                $sql .= ' ORDER BY c.shoe_srp ASC';
                break;
            case 'sort-by-high':
                $sql .= ' ORDER BY c.shoe_srp DESC';
                break;
            default:
                $sql .= ' ORDER BY s.shoe_date DESC';
                break;
        }

        $result = $this->db->query($sql, $params);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

class ProductDetail
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getShoeById($shoe_id)
    {
        $sql = 'SELECT s.*, c.*, cm.color AS color_name, c.shoe_image AS color_image, cat.category_name, b.brand_image,
        sz.shoe_size_id, sz.shoe_size, sz.shoe_size_stock, sm.size_type
        FROM shoes_master_tbl s
        LEFT JOIN shoes_color_tbl c ON s.shoe_id = c.shoe_id
        LEFT JOIN color_master_tbl cm ON c.color_id = cm.color_id
        LEFT JOIN category_master_tbl cat ON s.category_id = cat.category_id
        LEFT JOIN brand_master_tbl b ON s.brand_id = b.brand_id
        LEFT JOIN shoes_size_tbl sz ON s.shoe_id = sz.shoe_id
        LEFT JOIN size_master_tbl sm ON sz.size_type_id = sm.size_type_id
        WHERE s.shoe_id = ?';

        $result = $this->db->query($sql, [$shoe_id]);

        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function getShoeBySizeId($shoe_id, $shoe_size_id)
    {
        $sql = 'SELECT s.*, c.*, cm.color AS color_name, c.shoe_image AS color_image, cat.category_name, b.brand_image,
            sz.shoe_size_id, sz.shoe_size, sz.shoe_size_stock, sm.size_type
            FROM shoes_master_tbl s
            LEFT JOIN shoes_color_tbl c ON s.shoe_id = c.shoe_id
            LEFT JOIN color_master_tbl cm ON c.color_id = cm.color_id
            LEFT JOIN category_master_tbl cat ON s.category_id = cat.category_id
            LEFT JOIN brand_master_tbl b ON s.brand_id = b.brand_id
            LEFT JOIN shoes_size_tbl sz ON s.shoe_id = sz.shoe_id AND sz.shoe_size_id = ?
            LEFT JOIN size_master_tbl sm ON sz.size_type_id = sm.size_type_id
            WHERE s.shoe_id = ?';

        $result = $this->db->query($sql, [$shoe_size_id, $shoe_id]);

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getShoesByName($shoe_name)
    {
        $sql = 'SELECT s.shoe_id, s.shoe_name, s.shoe_image, c.shoe_image, cm.color
            FROM shoes_master_tbl s
            LEFT JOIN shoes_color_tbl c ON s.shoe_id = c.shoe_id
            LEFT JOIN color_master_tbl cm ON c.color_id = cm.color_id
            WHERE s.shoe_name = ?';
        $result = $this->db->query($sql, [$shoe_name]);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

class Cart
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // Add item to cart
    public function addToCart($shoe_id, $shoe_size_id, $shoe_qty, $subtotal_bill, $shoe_tax, $final_amt)
    {
        if (isset($_SESSION['user_details'])) {
            $user_details = json_decode($_SESSION['user_details'], true);
            $this->addToCartDB($user_details['customer_id'], $shoe_id, $shoe_size_id, $shoe_qty, $subtotal_bill, $shoe_tax, $final_amt);
        } else {
            // User is not logged in
            $this->addToCookies($shoe_id, $shoe_size_id, $shoe_qty, $subtotal_bill, $shoe_tax, $final_amt);
        }
    }

    public function addToCartDB($customer_id, $shoe_id, $shoe_size_id, $shoe_qty, $subtotal_bill, $shoe_tax, $final_amt)
    {
        // Check if there's an existing pending order for this customer
        $sql = 'SELECT order_id FROM order_master_tbl WHERE customer_id = ? AND order_status = "Pending"';
        $result = $this->db->query($sql, [$customer_id]);
        $order = $result->fetch_assoc();

        if (!$order) {
            // Create a new order if one doesn't exist
            $sql = 'INSERT INTO order_master_tbl (customer_id, subtotal_bill, shoe_tax, final_amt, order_status) VALUES (?, ?, ?, ?, "Pending")';
            $this->db->query($sql, [$customer_id, $subtotal_bill, $shoe_tax, $final_amt]);

            $result = $this->db->query('SELECT order_id FROM order_master_tbl WHERE customer_id = ? AND order_status = ?', [$customer_id, "Pending"]);
            $order_id = $result->fetch_assoc();

            $sql = 'INSERT INTO cart_master_tbl (order_id, shoe_id, shoe_size_id, shoe_qty) VALUES (?, ?, ?, ?)';
            $this->db->query($sql, [$order_id['order_id'], $shoe_id, $shoe_size_id, $shoe_qty]);
        } else {
            $order_id = $order['order_id'];

            // Update the existing order
            $sql = 'UPDATE order_master_tbl SET subtotal_bill = subtotal_bill + ?, shoe_tax = shoe_tax + ?, final_amt = final_amt + ? WHERE order_id = ?';
            $this->db->query($sql, [$subtotal_bill, $shoe_tax, $final_amt, $order_id]);

            // Check that shoe_size_id exists
            $sql = 'SELECT * FROM cart_master_tbl WHERE order_id = ? AND shoe_id = ? AND shoe_size_id = ?';
            $result = $this->db->query($sql, [$order_id, $shoe_id, $shoe_size_id]);
            $cart = $result->fetch_assoc();

            if (!$cart) {
                $sql = 'INSERT INTO cart_master_tbl (order_id, shoe_id, shoe_size_id, shoe_qty) VALUES (?, ?, ?, ?)';
                $this->db->query($sql, [$order_id, $shoe_id, $shoe_size_id, $shoe_qty]);
            } else {
                $sql = 'UPDATE cart_master_tbl SET shoe_qty = shoe_qty + ? WHERE order_id = ? AND shoe_id = ? AND shoe_size_id = ?';
                $this->db->query($sql, [$shoe_qty, $order_id, $shoe_id, $shoe_size_id]);
            }
        }
    }

    // Update cart item
    public function updateCartItem($customerId, $shoeId, $shoeSizeId, $newQty)
    {
        // Get the current order and item details
        $sql = 'SELECT om.order_id, cm.shoe_qty, sc.shoe_srp 
                FROM order_master_tbl om 
                JOIN cart_master_tbl cm ON om.order_id = cm.order_id 
                JOIN shoes_master_tbl s ON cm.shoe_id = s.shoe_id
                JOIN shoes_color_tbl sc ON cm.shoe_id = sc.shoe_id
                WHERE om.customer_id = ? AND cm.shoe_id = ? AND cm.shoe_size_id = ? AND om.order_status = "Pending"';

        $result = $this->db->query($sql, [$customerId, $shoeId, $shoeSizeId]);
        $item = $result->fetch_assoc();

        if ($item) {
            $qtyDiff = $newQty - $item['shoe_qty'];
            $subtotalDiff = $qtyDiff * $item['shoe_srp'];
            $taxDiff = $subtotalDiff * 0.13;
            $totalDiff = $subtotalDiff + $taxDiff;

            // Update the cart item
            $sql = 'UPDATE cart_master_tbl SET shoe_qty = ? WHERE order_id = ? AND shoe_id = ? AND shoe_size_id = ?';
            $this->db->query($sql, [$newQty, $item['order_id'], $shoeId, $shoeSizeId]);

            // Update the order totals
            $sql = 'UPDATE order_master_tbl 
                    SET subtotal_bill = subtotal_bill + ?, 
                        shoe_tax = shoe_tax + ?, 
                        final_amt = final_amt + ? 
                    WHERE order_id = ?';
            $this->db->query($sql, [$subtotalDiff, $taxDiff, $totalDiff, $item['order_id']]);

            return true;
        }

        return false;
    }

    public function removeFromCart($customerId, $shoeId, $shoeSizeId)
    {
        // Get the current order and item details
        $sql = 'SELECT om.order_id, cm.shoe_qty, sc.shoe_srp 
                FROM order_master_tbl om 
                JOIN cart_master_tbl cm ON om.order_id = cm.order_id 
                JOIN shoes_master_tbl s ON cm.shoe_id = s.shoe_id
                JOIN shoes_color_tbl sc ON cm.shoe_id = sc.shoe_id
                WHERE om.customer_id = ? AND cm.shoe_id = ? AND cm.shoe_size_id = ? AND om.order_status = "Pending"';
        $result = $this->db->query($sql, [$customerId, $shoeId, $shoeSizeId]);
        $item = $result->fetch_assoc();

        if ($item) {
            $subtotal = $item['shoe_qty'] * $item['shoe_srp'];
            $tax = $subtotal * 0.13; // Assuming 13% tax rate
            $total = $subtotal + $tax;

            // Remove the item from the cart
            $sql = 'DELETE FROM cart_master_tbl WHERE order_id = ? AND shoe_id = ? AND shoe_size_id = ?';
            $this->db->query($sql, [$item['order_id'], $shoeId, $shoeSizeId]);

            // Update the order totals
            $sql = 'UPDATE order_master_tbl 
                    SET subtotal_bill = subtotal_bill - ?, 
                        shoe_tax = shoe_tax - ?, 
                        final_amt = final_amt - ? 
                    WHERE order_id = ?';
            $this->db->query($sql, [$subtotal, $tax, $total, $item['order_id']]);

            // Check if the cart is now empty
            $sql = 'SELECT COUNT(*) as count FROM cart_master_tbl WHERE order_id = ?';
            $result = $this->db->query($sql, [$item['order_id']]);
            $count = $result->fetch_assoc()['count'];

            if ($count == 0) {
                // If the cart is empty, remove the order
                $sql = 'DELETE FROM order_master_tbl WHERE order_id = ?';
                $this->db->query($sql, [$item['order_id']]);
            }

            return true;
        }

        return false;
    }

    private function addToCookies($shoe_id, $shoe_size_id, $shoe_qty, $subtotal_bill, $shoe_tax, $final_amt)
    {
        $cart = isset($_COOKIE['cart']) ? json_decode($_COOKIE['cart'], true) : [];
        $itemFound = false;

        // Update the existing item or add a new one
        foreach ($cart as &$item) {
            if ($item['shoe_id'] == $shoe_id && $item['shoe_size_id'] == $shoe_size_id) {
                // Update the existing item
                $item['shoe_qty'] = $item['shoe_qty'] + $shoe_qty;
                $item['subtotal_bill'] = $item['subtotal_bill'] + $subtotal_bill;
                $shoe_tax_rate = 0.13;
                $item['shoe_tax'] = $item['subtotal_bill'] * $shoe_tax_rate;
                $item['final_amt'] = $item['subtotal_bill'] + $item['shoe_tax'];
                $item['final_amt'] = number_format($item['final_amt'], 2, '.', '');
                $itemFound = true;
                break;
            }
        }

        if (!$itemFound) {
            // Add a new item if not found
            $cart[] = [
                'shoe_id' => $shoe_id,
                'shoe_size_id' => $shoe_size_id,
                'shoe_qty' => $shoe_qty,
                'subtotal_bill' => $subtotal_bill,
                'shoe_tax' => $shoe_tax,
                'final_amt' => $final_amt
            ];
        }

        // Update the cookie with the new cart data
        setcookie('cart', json_encode($cart), time() + 86400, '/'); // Cookie expires in 1 day
    }

    // Get cart items from cookies
    public function getCartItemsFromCookies()
    {
        return isset($_COOKIE['cart']) ? json_decode($_COOKIE['cart'], true) : [];
    }

    public function getCartItemsFromDB($customer_id)
    {

        // Check is there any data in order_master_tbl of customer_id whose status is pending
        $sql = 'SELECT COUNT(*) FROM order_master_tbl WHERE customer_id = ? AND order_status = ?';
        $result = $this->db->query($sql, [$customer_id, 'Pending']);
        $result->fetch_all(MYSQLI_ASSOC);

        if ($result->num_rows > 0) {

            // Get data from order_master_tbl & cart_master_tbl of customer_id whose status is pending
            $sql = 'SELECT o.*, c.* FROM order_master_tbl o JOIN cart_master_tbl c ON o.order_id = c.order_id WHERE o.customer_id = ? AND o.order_status = ?';
            $result = $this->db->query($sql, [$customer_id, 'Pending']);
            return $result->fetch_all(MYSQLI_ASSOC);

            // echo "<pre>";
            // print_r($cart);
            // echo "</pre>";
        } else {
            return false;
        }
    }

    // Remove item from cookies
    public function removeFromCookies($shoe_id, $shoe_size_id)
    {
        $cart = isset($_COOKIE['cart']) ? json_decode($_COOKIE['cart'], true) : [];
        $new_cart = array_filter($cart, function ($item) use ($shoe_id, $shoe_size_id) {
            return !($item['shoe_id'] == $shoe_id && $item['shoe_size_id'] == $shoe_size_id);
        });

        setcookie('cart', json_encode($new_cart), time() + 86400, '/'); // Cookie expires in 1 day
    }
}

class PlaceOfSupply
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getProvinces()
    {
        $sql = 'SELECT place_of_supply_id, provinces FROM place_of_supply_tbl
                GROUP BY provinces';
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function getCitiesByProvince($province)
    {
        $sql = 'SELECT place_of_supply_id, cities FROM place_of_supply_tbl WHERE provinces = ?';
        $result = $this->db->query($sql, [$province]);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

class UserAddress
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function addUserAddress($customer_id, $address, $country, $place_of_supply_id, $postal_code, $phone)
    {
        // Check is there any data in manage_customer_address_tbl of customer_id
        $sql = 'SELECT COUNT(*) as count FROM manage_customer_address_tbl WHERE customer_id = ?';
        $result = $this->db->query($sql, [$customer_id]);
        $count = $result->fetch_assoc()['count'];

        // Get province and city from place_of_supply_id
        $sql = 'SELECT provinces, cities FROM place_of_supply_tbl WHERE place_of_supply_id = ?';
        $result = $this->db->query($sql, [$place_of_supply_id]);
        $result = $result->fetch_all(MYSQLI_ASSOC);
        $province = $result[0]['provinces'];
        $city = $result[0]['cities'];

        // Update phone in manage_customer_tbl of customer_id
        $sql = 'UPDATE manage_customer_tbl SET customer_mobile = ? WHERE customer_id = ?';
        $this->db->query($sql, [$phone, $customer_id]);

        if ($count > 0) {
            // Update data in manage_customer_address_tbl of customer_id
            $sql = 'UPDATE manage_customer_address_tbl SET address = ?, country = ?, province = ?, city = ?, postal_code = ? WHERE customer_id = ?';
            $this->db->query($sql, [$address, $country, $province, $city, $postal_code, $customer_id]);
        } else {
            // Insert data into manage_customer_address_tbl
            $sql = 'INSERT INTO manage_customer_address_tbl (customer_id, address, country, province, city, postal_code) VALUES (?, ?, ?, ?, ?, ?)';
            $this->db->query($sql, [$customer_id, $address, $country, $province, $city, $postal_code]);
        }
    }
}


class Checkout
{

    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function updateOrderMasterTbl($customer_id, $invoice_number, $payment_type, $order_status)
    {
        // Get order_id from order_master_tbl of customer_id whose status is pending
        $sql = 'SELECT order_id FROM order_master_tbl WHERE customer_id = ? AND order_status = ?';
        $result = $this->db->query($sql, [$customer_id, 'Pending']);
        $order_id = $result->fetch_assoc()['order_id'];

        // Update invoice_number, payment_type, order_status in order_master_tbl
        $sql = 'UPDATE order_master_tbl SET invoice_number = ?, payment_type = ?, order_status = ? WHERE order_id = ?';
        $this->db->query($sql, [$invoice_number, $payment_type, $order_status, $order_id]);

        return $order_id;
    }

    // Get order details from order_master_tbl of order_id 
    public function getOrderDetails($order_id)
    {
        $sql = 'SELECT om.*, cm.* FROM order_master_tbl om JOIN cart_master_tbl cm ON om.order_id = cm.order_id WHERE om.order_id = ? AND om.order_status = ?';
        $result = $this->db->query($sql, [$order_id, 'Completed']);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

class ContactUs
{

    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function addContactUs($firstname, $lastname, $email, $mobile, $message)
    {
        $sql = 'INSERT INTO contact_us_master_tbl (customer_fistname,customer_lastname,customer_email,customer_mobile,message) VALUES (?, ?, ?, ?, ?)';
        $this->db->query($sql, [$firstname, $lastname, $email, $mobile, $message]);
    }
}
