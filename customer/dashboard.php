<?php

session_start();

/* ================= LOGIN CHECK ================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../html/login.html");
    exit();
}

/* ================= CUSTOMER ROLE CHECK ================= */

if (strtolower(trim($_SESSION["user_role"] ?? "")) !== "customer") {
    die("Access denied.");
}

/* ================= CUSTOMER NAME ================= */

$name = htmlspecialchars($_SESSION["user_name"] ?? "Customer");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customer Dashboard | HomeServe</title>

    <link rel="stylesheet" href="../css/dashboard.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #222;
        }


        /* ================= NAVBAR ================= */

        .navbar {
            background: #1d477d;
            color: white;

            padding: 18px 50px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);

            position: sticky;
            top: 0;
            z-index: 1000;
        }


        /* ================= LOGO ================= */

        .logo a {
            color: white;
            text-decoration: none;

            font-size: 26px;
            font-weight: bold;
        }


        /* ================= NAV LINKS ================= */

        .nav-links {
            display: flex;
            align-items: center;

            gap: 28px;

            list-style: none;

            margin: 0;
            padding: 0;
        }

        .nav-links li {
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            color: white;

            text-decoration: none;

            font-weight: bold;

            font-size: 15px;

            padding: 8px 4px;

            transition: 0.3s;
        }

        .nav-links a:hover {
            color: #ff8a00;
        }


        /* ================= LOGOUT ================= */

        .nav-buttons {
            display: flex;
            align-items: center;
        }

        .logout-btn {
            display: inline-block;

            padding: 10px 20px;

            background: #ff7a00;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-weight: bold;

            transition: 0.3s;
        }

        .logout-btn:hover {
            background: #e66d00;

            transform: translateY(-1px);
        }


        /* ================= DASHBOARD ================= */

        .dashboard {
            max-width: 1100px;

            margin: 50px auto;

            padding: 20px;
        }


        /* ================= WELCOME ================= */

        .welcome {
            background: white;

            padding: 35px;

            border-radius: 15px;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .welcome h1 {
            margin-top: 0;

            margin-bottom: 12px;

            color: #1d477d;

            font-size: 34px;
        }

        .welcome p {
            margin-bottom: 0;

            font-size: 17px;

            color: #555;
        }


        /* ================= CARDS ================= */

        .cards {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 25px;

            margin-top: 30px;
        }

        .card {
            background: white;

            padding: 30px;

            border-radius: 15px;

            text-align: center;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.08);

            transition: 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);

            box-shadow:
                0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .card h2 {
            color: #1d477d;

            margin-top: 0;
        }

        .card p {
            color: #555;

            line-height: 1.6;
        }

        .card a {
            display: inline-block;

            margin-top: 15px;

            padding: 12px 22px;

            background: #ff7a00;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-weight: bold;

            transition: 0.3s;
        }

        .card a:hover {
            background: #e66d00;
        }


        /* ================= FOOTER ================= */

        footer {
            text-align: center;

            padding: 30px;

            margin-top: 50px;

            background: #1d477d;

            color: white;
        }

        footer h3 {
            margin-top: 0;
        }


        /* ================= MOBILE ================= */

        @media (max-width: 800px) {

            .navbar {
                padding: 15px 20px;

                flex-direction: column;

                gap: 15px;
            }

            .nav-links {
                flex-wrap: wrap;

                justify-content: center;

                gap: 15px;
            }

            .dashboard {
                margin: 25px auto;

                padding: 15px;
            }

            .cards {
                grid-template-columns: 1fr;
            }

            .welcome h1 {
                font-size: 28px;
            }

        }

    </style>

</head>


<body>


    <!-- ================= NAVBAR ================= -->

    <nav class="navbar">


        <!-- LOGO -->

        <div class="logo">

            <a href="dashboard.php">
                HomeServe
            </a>

        </div>


        <!-- NAVIGATION -->

        <ul class="nav-links">

            <li>
                <a href="dashboard.php">
                    Home
                </a>
            </li>

            <li>
                <a href="../html/service.html">
                    Services
                </a>
            </li>

            <li>
                <a href="my_bookings.php">
                    My Bookings
                </a>
            </li>

            <li>
                <a href="profile.php">
                    Profile
                </a>
            </li>

        </ul>


        <!-- LOGOUT -->

        <div class="nav-buttons">

            <a
                href="../php/logout.php"
                class="logout-btn"
            >
                Logout
            </a>

        </div>


    </nav>



    <!-- ================= DASHBOARD ================= -->

    <main class="dashboard">


        <!-- WELCOME -->

        <div class="welcome">

            <h1>
                Welcome, <?php echo $name; ?>! 👋
            </h1>

            <p>
                Manage your home service bookings from your dashboard.
            </p>

        </div>



        <!-- ================= CARDS ================= -->

        <div class="cards">


            <!-- MY BOOKINGS -->

            <div class="card">

                <h2>
                    📅 My Bookings
                </h2>

                <p>
                    View your current and previous
                    home service bookings.
                </p>

                <a href="my_bookings.php">
                    View Bookings
                </a>

            </div>



            <!-- BOOK SERVICE -->

            <div class="card">

                <h2>
                    🛠️ Book a Service
                </h2>

                <p>
                    Find and book a professional
                    home service.
                </p>

                <a href="../html/service.html">
                    Book Service
                </a>

            </div>



            <!-- PROFILE -->

            <div class="card">

                <h2>
                    👤 My Profile
                </h2>

                <p>
                    View and manage your
                    account information.
                </p>

                <a href="profile.php">
                    View Profile
                </a>

            </div>


        </div>


    </main>



    <!-- ================= FOOTER ================= -->

    <footer>

        <h3>
            HomeServe
        </h3>

        <p>
            Your trusted home service provider.
        </p>

        <p>
            © 2026 HomeServe. All Rights Reserved.
        </p>

    </footer>


</body>

</html>