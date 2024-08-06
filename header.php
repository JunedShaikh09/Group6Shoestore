<?php
// Start the session
if (!isset($_SESSION)) {
    session_start();
}

// Decode user details from session if they exist
$userDetails = isset($_SESSION['user_details']) ? json_decode($_SESSION['user_details'], true) : null;


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/style.css" />
    <title>ShoeShack</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Lexend&display=swap" rel="stylesheet" />

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Glide JS Slider -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.css" />

</head>

<body>
    <!-- Header -->
    <header>
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <a class="navbar-brand header-logo" href="index.php">
                    <img src="images/logo.png" alt="ShoeShack Logo" />
                </a>
                <button class="navbar-toggler-custom" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
                    <ion-icon name="menu-outline" class="fs-5"></ion-icon>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="shop.php">Shop</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">Cart</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="contact.php">Contact Us</a>
                        </li>
                    </ul>
                    <ul class="navbar-nav navbar-icons">
                        <li class="nav-item">

                            <?php

                            // display first name and lastname from user details
                            if ($userDetails) {
                                echo '<p class="nav-sign-in-link text-decoration-none me-5 mb-0">' . $userDetails['firstname'] . ' ' . $userDetails['lastname'] . '</p>';
                            }

                            if (!$userDetails) {
                                echo '<a class="nav-sign-in-link text-decoration-none" href="sign-in.php">Sign In</a>';
                            } else {
                                echo '<a class="nav-sign-out-link text-decoration-none" href="logout.php">Sign Out</a>';
                            }
                            ?>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Menu</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="shop.php">Shop</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">Cart</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact Us</a>
                    </li>
                    <li class="nav-item">
                        <?php
                        if (!$userDetails) {
                            echo '<a class="nav-sign-in-link" href="sign-in.php">Sign In</a>';
                        } else {
                            echo '<a class="nav-sign-out-link" href="logout.php">Sign Out</a>';
                        }
                        ?>
                    </li>
                </ul>
            </div>
        </div>

    </header>