<?php
session_start();

/* =========================
   CUSTOMER LOGIN CHECK
========================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../html/login.html");
    exit();
}

if (strtolower(trim($_SESSION["user_role"] ?? "")) !== "customer") {
    http_response_code(403);
    exit("Access denied. Customer account required.");
}

/* =========================
   GET CUSTOMER NAME
========================= */

$customerId = (int) $_SESSION["user_id"];

$rawName = $_SESSION["user_name"]
    ?? $_SESSION["name"]
    ?? $_SESSION["customer_name"]
    ?? "Customer";

/* Database se actual customer name lene ki koshish */
try {
    require_once __DIR__ . "/../config/database.php";

    if (isset($conn) && $conn instanceof mysqli) {

        $stmt = $conn->prepare(
            "SELECT name FROM users
             WHERE id = ? AND LOWER(role) = 'customer'
             LIMIT 1"
        );

        if ($stmt) {
            $stmt->bind_param("i", $customerId);
            $stmt->execute();

            $result = $stmt->get_result();
            $customer = $result->fetch_assoc();

            if (!empty($customer["name"])) {
                $rawName = $customer["name"];
                $_SESSION["user_name"] = $rawName;
            }

            $stmt->close();
        }
    }
} catch (Throwable $e) {
    // Database lookup fail ho to session wala naam use hoga.
}

$rawName = trim((string) $rawName);

if ($rawName === "") {
    $rawName = "Customer";
}

$name = htmlspecialchars($rawName, ENT_QUOTES, "UTF-8");

$firstName = htmlspecialchars(
    explode(" ", $rawName)[0],
    ENT_QUOTES,
    "UTF-8"
);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customer Dashboard | HomeServe</title>

    <style>

        :root {
            --blue: #1d477d;
            --dark-blue: #15365f;
            --orange: #ff7900;
            --orange-dark: #e66d00;
            --text: #263449;
            --muted: #68768a;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: var(--text);
            background:
                radial-gradient(
                    circle at 5% 10%,
                    rgba(29, 71, 125, 0.09),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 95% 35%,
                    rgba(255, 121, 0, 0.08),
                    transparent 30%
                ),
                #f3f6fb;

            min-height: 100vh;
        }

        a {
            text-decoration: none;
        }

        /* =========================
           NAVBAR
        ========================= */

        .navbar {
            background: var(--blue);
            color: white;

            padding: 18px 4%;

            display: flex;
            justify-content: space-between;
            align-items: center;

            gap: 20px;

            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);

            position: sticky;
            top: 0;
            z-index: 1000;

            animation: slideDown 0.6s ease;
        }

        .logo a {
            color: white;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 30px;
            list-style: none;
        }

        .nav-links a {
            color: white;
            font-size: 15px;
            font-weight: 700;
            position: relative;
            transition: color 0.3s;
        }

        .nav-links a::after {
            content: "";
            position: absolute;
            width: 0;
            height: 2px;
            left: 0;
            bottom: -7px;
            background: #ffbd80;
            transition: width 0.3s;
        }

        .nav-links a:hover {
            color: #ffd3a8;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .logout-btn {
            background: var(--orange);
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: bold;
            display: inline-block;
            transition: 0.3s;
        }

        .logout-btn:hover {
            background: var(--orange-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 121, 0, 0.3);
        }

        /* =========================
           MAIN CONTAINER
        ========================= */

        .dashboard {
            max-width: 1150px;
            margin: auto;
            padding: 45px 22px 20px;
        }

        /* =========================
           WELCOME SECTION
        ========================= */

        .welcome {
            position: relative;
            overflow: hidden;

            background: linear-gradient(
                120deg,
                #ffffff,
                #f8fbff,
                #fff4e9
            );

            border-radius: 22px;
            padding: 40px;

            box-shadow: 0 12px 35px rgba(24, 48, 80, 0.09);

            animation: fadeUp 0.8s ease both;
        }

        .welcome::after {
            content: "🏡";

            position: absolute;
            right: 55px;
            top: 50%;

            transform: translateY(-50%);

            font-size: 90px;
            opacity: 0.13;

            animation: floating 4s ease-in-out infinite;
        }

        .welcome-label {
            display: inline-block;

            background: #e8f1ff;
            color: var(--blue);

            padding: 8px 13px;
            border-radius: 30px;

            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .welcome h1 {
            color: var(--blue);

            font-size: clamp(28px, 4vw, 42px);

            margin: 18px 0 12px;

            max-width: 80%;
        }

        .welcome p {
            color: #5e6979;

            font-size: 16px;
            line-height: 1.8;

            max-width: 650px;
        }

        .welcome-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 23px;
        }

        /* =========================
           BUTTONS
        ========================= */

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            gap: 8px;

            background: var(--orange);
            color: white;

            border: none;
            border-radius: 10px;

            padding: 13px 19px;

            font-size: 14px;
            font-weight: bold;

            cursor: pointer;

            transition:
                transform 0.25s ease,
                background 0.25s ease,
                box-shadow 0.25s ease;
        }

        .btn:hover {
            background: var(--orange-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 18px rgba(255, 121, 0, 0.25);
        }

        .btn-secondary {
            color: var(--blue);
            background: #eaf1fb;
        }

        .btn-secondary:hover {
            color: white;
            background: var(--blue);
        }

        /* =========================
           SECTION HEADING
        ========================= */

        .section-heading {
            margin: 38px 2px 20px;
            animation: fadeUp 0.8s 0.15s both;
        }

        .section-heading h2 {
            color: var(--blue);
            font-size: 27px;
            margin-bottom: 8px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 15px;
        }

        /* =========================
           DASHBOARD CARDS
        ========================= */

        .cards {
            display: grid;

            grid-template-columns: repeat(3, minmax(0, 1fr));

            gap: 22px;
        }

        .card {
            position: relative;
            overflow: hidden;

            display: flex;
            flex-direction: column;
            align-items: flex-start;

            background: white;

            border: 1px solid #e9eef5;
            border-radius: 19px;

            padding: 28px;

            min-height: 270px;

            box-shadow: 0 8px 25px rgba(24, 48, 80, 0.06);

            transition:
                transform 0.3s ease,
                box-shadow 0.3s ease,
                border-color 0.3s ease;

            animation: fadeUp 0.7s ease both;
        }

        .card:nth-child(1) {
            animation-delay: 0.15s;
        }

        .card:nth-child(2) {
            animation-delay: 0.3s;
        }

        .card:nth-child(3) {
            animation-delay: 0.45s;
        }

        .card::before {
            content: "";

            position: absolute;
            top: 0;
            left: 0;

            width: 100%;
            height: 4px;

            background: linear-gradient(
                90deg,
                var(--blue),
                var(--orange)
            );

            transform: scaleX(0);
            transform-origin: left;

            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-8px);

            border-color: #d4e1f2;

            box-shadow: 0 17px 35px rgba(24, 48, 80, 0.13);
        }

        .card:hover::before {
            transform: scaleX(1);
        }

        .card-icon {
            display: flex;
            align-items: center;
            justify-content: center;

            width: 58px;
            height: 58px;

            border-radius: 16px;

            background: #eaf2ff;

            font-size: 29px;

            margin-bottom: 20px;

            transition: transform 0.3s ease;
        }

        .card:nth-child(2) .card-icon {
            background: #fff0e2;
        }

        .card:nth-child(3) .card-icon {
            background: #e6f8ef;
        }

        .card:hover .card-icon {
            transform: rotate(-6deg) scale(1.08);
        }

        .card h3 {
            color: var(--blue);
            font-size: 22px;
            margin-bottom: 12px;
        }

        .card p {
            color: var(--muted);
            font-size: 15px;
            line-height: 1.8;
            margin-bottom: 22px;
            flex-grow: 1;
        }

        /* =========================
           TRUST SECTION
        ========================= */

        .trust-section {
            display: flex;
            flex-wrap: wrap;

            justify-content: center;
            gap: 18px 30px;

            margin: 28px 0 10px;
            padding: 22px;

            border: 1px solid #e5ebf4;
            border-radius: 16px;

            background: rgba(255, 255, 255, 0.8);

            animation: fadeUp 0.8s 0.5s both;
        }

        .trust-item {
            color: #536176;
            font-size: 14px;
        }

        .trust-item strong {
            color: var(--blue);
        }

        /* =========================
           FOOTER
        ========================= */

        footer {
            margin-top: 45px;
            padding: 30px 20px;

            text-align: center;

            color: white;
            background: var(--blue);
        }

        footer h3 {
            font-size: 22px;
            margin-bottom: 10px;
        }

        footer p {
            margin: 9px 0;
            line-height: 1.6;
        }

        /* =========================
           ANIMATIONS
        ========================= */

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(22px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes floating {
            0%, 100% {
                margin-top: 0;
            }

            50% {
                margin-top: -10px;
            }
        }

        /* =========================
           TABLET
        ========================= */

        @media (max-width: 850px) {

            .navbar {
                flex-wrap: wrap;
                justify-content: center;
            }

            .logo {
                width: 100%;
                text-align: center;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 18px;
            }

            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .welcome::after {
                right: 25px;
                opacity: 0.08;
            }
        }

        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 560px) {

            .navbar {
                padding: 17px 12px;
                gap: 17px;
            }

            .logo a {
                font-size: 25px;
            }

            .nav-links {
                gap: 13px;
            }

            .nav-links a {
                font-size: 13px;
            }

            .dashboard {
                padding: 25px 14px 15px;
            }

            .welcome {
                padding: 26px 21px;
                border-radius: 18px;
            }

            .welcome h1 {
                font-size: 29px;
                max-width: 100%;
            }

            .welcome p {
                font-size: 15px;
            }

            .welcome::after {
                display: none;
            }

            .welcome-actions {
                flex-direction: column;
            }

            .welcome-actions .btn {
                width: 100%;
            }

            .cards {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .card {
                min-height: 0;
                padding: 24px;
            }

            .section-heading h2 {
                font-size: 24px;
            }

            .trust-section {
                justify-content: flex-start;
                gap: 14px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

    </style>

</head>

<body>

    <!-- NAVBAR -->

    <nav class="navbar">

        <div class="logo">
            <a href="dashboard.php">HomeServe</a>
        </div>

        <ul class="nav-links">

            <li>
                <a href="dashboard.php">Home</a>
            </li>

            <li>
                <a href="../html/service.html">Services</a>
            </li>

            <li>
                <a href="my_bookings.php">My Bookings</a>
            </li>

            <li>
                <a href="profile.php">Profile</a>
            </li>

        </ul>

        <div>
            <a href="../php/logout.php" class="logout-btn">
                Logout ↗
            </a>
        </div>

    </nav>


    <!-- MAIN DASHBOARD -->

    <main class="dashboard">

        <!-- WELCOME SECTION -->

        <section class="welcome">

            <span class="welcome-label">
                🏡 YOUR HOMESERVE ACCOUNT
            </span>

            <h1>
                Welcome, <?php echo $name; ?>! 👋
            </h1>

            <p>
                Your home, your comfort!

                Book trusted home-service professionals,
                manage your bookings and enjoy reliable
                services at your doorstep.
            </p>

            <div class="welcome-actions">

                <a
                    href="../html/service.html"
                    class="btn"
                >
                    + Book a Service
                </a>

                <a
                    href="my_bookings.php"
                    class="btn btn-secondary"
                >
                    View My Bookings →
                </a>

            </div>

        </section>


        <!-- SECTION TITLE -->

        <section class="section-heading">

            <h2>Your Dashboard</h2>

            <p>
                What would you like to do today,
                <?php echo $firstName; ?>?
            </p>

        </section>


        <!-- DASHBOARD CARDS -->

        <section class="cards">


            <!-- BOOKINGS CARD -->

            <article class="card">

                <div class="card-icon">
                    📅
                </div>

                <h3>My Bookings</h3>

                <p>
                    View your current and previous
                    home-service bookings and check
                    their latest status.
                </p>

                <a
                    href="my_bookings.php"
                    class="btn"
                >
                    View Bookings →
                </a>

            </article>


            <!-- SERVICES CARD -->

            <article class="card">

                <div class="card-icon">
                    🛠️
                </div>

                <h3>Book a Service</h3>

                <p>
                    Find trusted professionals for
                    plumbing, electrical work,
                    cleaning, AC repair and more.
                </p>

                <a
                    href="../html/service.html"
                    class="btn"
                >
                    Explore Services →
                </a>

            </article>


            <!-- PROFILE CARD -->

            <article class="card">

                <div class="card-icon">
                    👤
                </div>

                <h3>My Profile</h3>

                <p>
                    View your customer account
                    details and manage your
                    HomeServe profile.
                </p>

                <a
                    href="profile.php"
                    class="btn"
                >
                    View Profile →
                </a>

            </article>


        </section>


        <!-- TRUST SECTION -->

        <section class="trust-section">

            <div class="trust-item">
                🛡️ <strong>Trusted Professionals</strong>
            </div>

            <div class="trust-item">
                ⚡ <strong>Easy Booking</strong>
            </div>

            <div class="trust-item">
                🏠 <strong>Service at Your Doorstep</strong>
            </div>

        </section>

    </main>


    <!-- FOOTER -->

    <footer>

        <h3>HomeServe</h3>

        <p>
            Your trusted home service provider.
        </p>

        <p>
            © <?php echo date("Y"); ?>
            HomeServe. All Rights Reserved.
        </p>

    </footer>

</body>

</html>