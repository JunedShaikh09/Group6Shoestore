<?php
include 'Database.php';

$user = new User();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = $user->sanitize($_POST['firstname']);
    $lastname = $user->sanitize($_POST['lastname']);
    $email = $user->sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // Check for required fields
    if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{6,}$/', $password)) {
        $error = 'Password must be at least 6 characters long, and include at least one uppercase letter, one lowercase letter, and one number.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        if (!$user->userExists($email)) {
            $user->registerUser($firstname, $lastname, $email, $password);
            header('Location: sign-in.php');
            exit();
        } else {
            $error = 'Email is already registered.';
        }
    }
}
?>


<?php include 'header.php'; ?>

<main>
    <section class="create-account section section--xl">
        <div class="container">
            <div class="form-wrapper">
                <h6 class="font-title--sm">Create Account</h6>
                <form action="" method="post">
                    <div class="form-input">
                        <input type="text" name="firstname" placeholder="First Name" />
                    </div>
                    <div class="form-input">
                        <input type="text" name="lastname" placeholder="Last Name" />
                    </div>
                    <div class="form-input">
                        <input type="text" name="email" placeholder="Email" />
                    </div>
                    <div class="form-input">
                        <input type="password" name="password" placeholder="Password" id="password" />
                        <button type="button" class="icon icon-eye" onclick="showPassword('password',this)">
                            <ion-icon name="eye"></ion-icon>
                        </button>
                    </div>
                    <div class="form-input">
                        <input type="password" name="confirmPassword" placeholder="Confirm Password" id="confirmPassword" />
                        <button type="button" class="icon icon-eye" onclick="showPassword('confirmPassword',this)">
                            <ion-icon name="eye-off"></ion-icon>
                        </button>
                    </div>
                    <div class="form-button">
                        <button class="button button--md w-100" type="submit">Create Account</button>
                    </div>
                    <?php if (isset($error)) : ?>
                        <p class="error"><?php echo $error; ?></p>
                    <?php endif; ?>
                    <div class="form-register">
                        Already have an account? <a href="sign-in.php">Login</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>