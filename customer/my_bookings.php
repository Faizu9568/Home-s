<?php
session_start();
include "../config/database.php";

/* CUSTOMER LOGIN CHECK */
if (
    !isset($_SESSION["customer_id"]) &&
    isset($_SESSION["user_id"]) &&
    strtolower(trim($_SESSION["user_role"] ?? "")) === "customer"
) {
    $_SESSION["customer_id"] = (int) $_SESSION["user_id"];
}

if (!isset($_SESSION["customer_id"])) {
    header("Location: ../html/login.html");
    exit();
}

$customerId = (int) $_SESSION["customer_id"];

/* GET CUSTOMER BOOKINGS */
$sql = "
    SELECT
        bookings.id,
        bookings.booking_date,
        bookings.booking_time,
        bookings.address,
        bookings.message,
        bookings.status,
        services.name AS service_name,
        providers.id AS provider_id,
        provider_users.name AS provider_name,
        provider_users.phone AS provider_phone,
        reviews.id AS review_id,
        reviews.rating AS review_rating,
        reviews.comment AS review_comment
    FROM bookings
    JOIN services
        ON bookings.service_id = services.id
    LEFT JOIN providers
        ON bookings.provider_id = providers.id
    LEFT JOIN users AS provider_users
        ON providers.user_id = provider_users.id
    LEFT JOIN reviews
        ON reviews.booking_id = bookings.id
        AND reviews.customer_id = bookings.customer_id
    WHERE bookings.customer_id = ?
    ORDER BY bookings.id DESC
";

$getBookings = $conn->prepare($sql);

if (!$getBookings) {
    error_log("Booking query failed: " . $conn->error);
    exit("Unable to load bookings. Please try again later.");
}

$getBookings->bind_param("i", $customerId);
$getBookings->execute();

$bookings = $getBookings->get_result();

/* SAFE OUTPUT */
function h($value): string {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

/* STATUS CLASS */
function statusClass($status): string {
    $status = strtolower(trim((string)$status));

    $allowed = [
        "pending",
        "accepted",
        "completed",
        "rejected"
    ];

    return in_array($status, $allowed, true)
        ? $status
        : "other";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Bookings | HomeServe</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --blue: #1d477d;
            --dark-blue: #15365f;
            --orange: #ff7900;
            --text: #263449;
            --muted: #68768a;
            --border: #e7edf5;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: var(--text);

            background:
                radial-gradient(
                    circle at 5% 10%,
                    rgba(29,71,125,.08),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 95% 35%,
                    rgba(255,121,0,.07),
                    transparent 28%
                ),
                #f3f6fb;

            min-height: 100vh;
        }

        a {
            text-decoration: none;
        }

        /* NAVBAR */

        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;

            padding: 18px 5%;

            background: var(--blue);
            color: white;

            box-shadow: 0 5px 20px rgba(18,43,75,.15);

            animation: slideDown .5s ease;
        }

        .logo a {
            color: white;
            font-size: 27px;
            font-weight: 800;
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 25px;

            list-style: none;
        }

        .nav-links a {
            position: relative;

            color: white;
            font-size: 14px;
            font-weight: bold;

            transition: color .25s;
        }

        .nav-links a:hover {
            color: #ffd1a5;
        }

        .nav-links a::after {
            content: "";

            position: absolute;
            left: 0;
            bottom: -7px;

            width: 0;
            height: 2px;

            background: #ffbd80;

            transition: width .25s;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .logout {
            display: inline-block;

            padding: 12px 18px;

            background: var(--orange);
            color: white;

            border-radius: 9px;
            font-size: 14px;
            font-weight: bold;

            transition: .25s;
        }

        .logout:hover {
            background: #e66d00;
            transform: translateY(-2px);
        }

        /* MAIN CONTAINER */

        .container {
            width: min(1120px, 100%);
            margin: auto;
            padding: 40px 22px;
        }

        /* PAGE HERO */

        .hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;

            padding: 35px;

            background: linear-gradient(
                120deg,
                #ffffff,
                #f7faff,
                #fff3e6
            );

            border: 1px solid white;
            border-radius: 22px;

            box-shadow: 0 12px 32px rgba(24,48,80,.08);

            animation: fadeUp .6s ease;
        }

        .hero-label {
            display: inline-block;

            padding: 8px 13px;

            background: #eaf2ff;
            color: var(--blue);

            border-radius: 30px;

            font-size: 12px;
            font-weight: bold;
            letter-spacing: .5px;
        }

        .hero h1 {
            margin: 17px 0 10px;

            color: var(--blue);
            font-size: clamp(28px, 4vw, 38px);
        }

        .hero p {
            max-width: 650px;

            color: var(--muted);
            font-size: 15px;
            line-height: 1.8;
        }

        .hero-icon {
            display: grid;
            place-items: center;

            width: 85px;
            height: 85px;

            flex-shrink: 0;

            border-radius: 22px;
            background: #fff0e2;

            font-size: 42px;

            animation: floating 3s ease-in-out infinite;
        }

        /* BUTTONS */

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            padding: 12px 18px;

            background: var(--orange);
            color: white;

            border: none;
            border-radius: 9px;

            font-size: 14px;
            font-weight: bold;

            cursor: pointer;

            transition:
                transform .25s,
                background .25s,
                box-shadow .25s;
        }

        .btn:hover {
            background: #e66d00;
            transform: translateY(-3px);

            box-shadow: 0 7px 16px rgba(255,121,0,.2);
        }

        /* SECTION TITLE */

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: end;
            flex-wrap: wrap;
            gap: 15px;

            margin: 35px 2px 20px;

            animation: fadeUp .6s .1s both;
        }

        .section-heading h2 {
            color: var(--blue);
            font-size: 25px;
        }

        .section-heading p {
            margin-top: 8px;

            color: var(--muted);
            font-size: 14px;
        }

        /* BOOKING CARDS */

        .booking-list {
            display: grid;
            gap: 22px;
        }

        .booking-card {
            position: relative;
            overflow: hidden;

            padding: 28px;

            background: white;

            border: 1px solid var(--border);
            border-radius: 18px;

            box-shadow: 0 7px 24px rgba(24,48,80,.06);

            animation: fadeUp .6s ease both;

            transition:
                transform .3s,
                box-shadow .3s,
                border-color .3s;
        }

        .booking-card::before {
            content: "";

            position: absolute;
            top: 0;
            left: 0;

            width: 4px;
            height: 100%;

            background: linear-gradient(
                var(--blue),
                var(--orange)
            );
        }

        .booking-card:hover {
            transform: translateY(-5px);

            border-color: #d5e2f2;

            box-shadow: 0 15px 32px rgba(24,48,80,.11);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 15px;

            padding-bottom: 20px;
            border-bottom: 1px solid #edf1f6;
        }

        .booking-id {
            margin-bottom: 8px;

            color: var(--muted);
            font-size: 12px;
            font-weight: bold;
        }

        .service-name {
            color: var(--blue);
            font-size: 25px;
            font-weight: 800;
        }

        /* STATUS BADGES */

        .status {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            padding: 9px 13px;

            border-radius: 30px;

            font-size: 12px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .status::before {
            content: "";

            width: 7px;
            height: 7px;

            border-radius: 50%;
            background: currentColor;
        }

        .pending {
            background: #fff3cd;
            color: #856404;
        }

        .accepted {
            background: #d1e7dd;
            color: #0f5132;
        }

        .completed {
            background: #cfe2ff;
            color: #084298;
        }

        .rejected {
            background: #f8d7da;
            color: #842029;
        }

        .other {
            background: #eef1f5;
            color: #526176;
        }

        /* BOOKING DETAILS */

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;

            padding: 22px 0;
        }

        .detail {
            padding: 15px;

            background: #fbfcff;

            border: 1px solid #edf1f6;
            border-radius: 12px;

            transition: border-color .25s;
        }

        .detail:hover {
            border-color: #cbdcf2;
        }

        .detail-label {
            margin-bottom: 8px;

            color: var(--muted);
            font-size: 11px;
            font-weight: bold;
            letter-spacing: .6px;
            text-transform: uppercase;
        }

        .detail-value {
            color: #2c3e55;
            font-size: 14px;
            line-height: 1.7;

            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        /* PROVIDER DETAILS */

        .provider-box {
            padding: 20px;

            background: #f3f8ff;

            border: 1px solid #dfeafa;
            border-radius: 14px;
        }

        .provider-box h3 {
            margin-bottom: 16px;
            color: var(--blue);
            font-size: 18px;
        }

        .provider-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }

        /* REVIEW SECTION */

        .review-box {
            margin-top: 20px;
            padding: 20px;

            background: #fffaf2;

            border: 1px solid #ffe2bc;
            border-radius: 14px;
        }

        .review-box h3 {
            margin-bottom: 12px;
            color: #a65300;
            font-size: 18px;
        }

        .review-box p {
            margin-bottom: 14px;

            color: #6b5b4b;
            font-size: 14px;
            line-height: 1.7;
        }

        .review-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            padding: 12px 18px;

            background: var(--orange);
            color: white;

            border-radius: 9px;

            font-size: 14px;
            font-weight: bold;

            transition: .25s;
        }

        .review-button:hover {
            background: #e66d00;
            transform: translateY(-2px);
            box-shadow: 0 7px 16px rgba(255,121,0,.2);
        }

        .reviewed {
            padding: 15px;

            background: #edfbf3;
            color: #17683d;

            border: 1px solid #bfe7d0;
            border-radius: 10px;

            font-size: 14px;
            line-height: 1.7;
        }

        .stars {
            margin-top: 7px;

            color: #e99a00;
            font-size: 24px;
            letter-spacing: 2px;
        }

        .review-comment {
            margin-top: 8px;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        /* EMPTY BOOKINGS */

        .empty {
            padding: 45px 22px;

            text-align: center;

            background: white;

            border: 1px solid var(--border);
            border-radius: 18px;

            box-shadow: 0 8px 25px rgba(24,48,80,.06);

            animation: fadeUp .6s ease;
        }

        .empty-icon {
            display: grid;
            place-items: center;

            width: 75px;
            height: 75px;

            margin: 0 auto 18px;

            background: #edf4ff;
            border-radius: 22px;

            font-size: 38px;
        }

        .empty h3 {
            margin-bottom: 10px;
            color: var(--blue);
            font-size: 24px;
        }

        .empty p {
            margin-bottom: 22px;

            color: var(--muted);
            line-height: 1.7;
        }

        /* FOOTER */

        footer {
            margin-top: 35px;
            padding: 28px 18px;

            background: var(--blue);
            color: #eaf1fb;

            text-align: center;
        }

        footer h3 {
            margin-bottom: 8px;
            color: white;
            font-size: 22px;
        }

        footer p {
            margin-top: 8px;
            font-size: 14px;
        }

        /* ANIMATIONS */

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes floating {
            0%, 100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        /* TABLET */

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
                gap: 16px;
            }
        }

        /* MOBILE */

        @media (max-width: 600px) {

            .navbar {
                padding: 17px 12px;
                gap: 18px;
            }

            .logo a {
                font-size: 25px;
            }

            .nav-links {
                gap: 13px;
            }

            .nav-links a {
                font-size: 12px;
            }

            .container {
                padding: 25px 13px;
            }

            .hero {
                padding: 25px 20px;
                border-radius: 17px;
            }

            .hero h1 {
                font-size: 28px;
            }

            .hero p {
                font-size: 14px;
            }

            .hero-icon {
                width: 55px;
                height: 55px;
                font-size: 28px;
                border-radius: 15px;
            }

            .section-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .booking-card {
                padding: 20px 16px;
            }

            .booking-header {
                flex-direction: column;
            }

            .service-name {
                font-size: 22px;
            }

            .details-grid,
            .provider-grid {
                grid-template-columns: 1fr;
                gap: 11px;
            }

            .detail {
                padding: 13px;
            }

            .logout {
                padding: 10px 14px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
            }
        }
/* REVIEW POPUP */

.review-button {
    border: none;
    cursor: pointer;
    font-family: inherit;
}

.review-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 18px;

    background: rgba(12, 25, 45, 0.62);
    backdrop-filter: blur(5px);

    opacity: 0;
    visibility: hidden;
    pointer-events: none;

    transition: opacity 0.25s ease, visibility 0.25s ease;
}

.review-modal.active {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}

.review-modal-card {
    width: 100%;
    max-width: 430px;
    max-height: 90vh;
    overflow-y: auto;

    padding: 28px;

    background: #fff;
    border-radius: 20px;

    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.25);

    transform: translateY(18px) scale(0.97);
    transition: transform 0.25s ease;
}

.review-modal.active .review-modal-card {
    transform: translateY(0) scale(1);
}

.review-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 15px;

    margin-bottom: 22px;
}

.review-modal-header h2 {
    color: var(--blue);
    font-size: 23px;
}

.review-modal-header p {
    margin-top: 7px;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.5;
}

.review-close {
    width: 35px;
    height: 35px;
    flex-shrink: 0;

    border: none;
    border-radius: 50%;

    background: #f0f3f8;
    color: #334155;

    font-size: 23px;
    cursor: pointer;
}

.review-close:hover {
    background: #ffe9e9;
    color: #c62828;
}

.review-modal label {
    display: block;
    margin: 18px 0 9px;

    color: #263449;
    font-size: 14px;
    font-weight: bold;
}

.review-stars {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 7px;
}

.review-stars input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.review-stars label {
    margin: 0;

    color: #d4d8e0;
    font-size: 37px;
    line-height: 1;

    cursor: pointer;
    transition: color 0.15s, transform 0.15s;
}

.review-stars label:hover {
    transform: scale(1.12);
}

.review-stars label:hover,
.review-stars label:hover ~ label,
.review-stars input:checked ~ label {
    color: #ffb400;
}

#reviewComment {
    width: 100%;
    min-height: 105px;
    padding: 13px;

    border: 1px solid #dce3ed;
    border-radius: 11px;

    font: inherit;
    font-size: 14px;
    resize: vertical;
    outline: none;
}

#reviewComment:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px rgba(29, 71, 125, 0.1);
}

.review-submit {
    width: 100%;
    margin-top: 20px;
    padding: 14px;

    border: none;
    border-radius: 10px;

    background: var(--orange);
    color: white;

    font-size: 15px;
    font-weight: bold;

    cursor: pointer;
    transition: background 0.2s, transform 0.2s;
}

.review-submit:hover:not(:disabled) {
    background: #e66d00;
    transform: translateY(-1px);
}

.review-submit:disabled {
    opacity: 0.7;
    cursor: wait;
}

.review-feedback {
    display: none;
    margin-top: 14px;
    padding: 11px;

    border-radius: 9px;
    font-size: 13px;
    line-height: 1.5;
}

.review-feedback.show {
    display: block;
}

.review-feedback.error {
    background: #fff0f0;
    color: #b42318;
}

.review-feedback.success {
    background: #eaf8ef;
    color: #17683d;
}

body.modal-open {
    overflow: hidden;
}

@media (max-width: 480px) {
    .review-modal-card {
        padding: 23px 19px;
        border-radius: 17px;
    }

    .review-modal-header h2 {
        font-size: 21px;
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
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="../html/service.html">Services</a></li>
        <li><a href="my_bookings.php">My Bookings</a></li>
        <li><a href="profile.php">Profile</a></li>
    </ul>

    <a class="logout" href="../php/logout.php">
        Logout ↗
    </a>

</nav>


<main class="container">

    <!-- HERO -->

    <section class="hero">

        <div>
            <span class="hero-label">
                YOUR HOMESERVE ACCOUNT
            </span>

            <h1>My Bookings 📅</h1>

            <p>
                Track your home-service bookings,
                check your provider details and share
                feedback after your service is completed.
            </p>
        </div>

        <div class="hero-icon">
            🏡
        </div>

    </section>


    <!-- HEADING -->

    <div class="section-heading">

        <div>
            <h2>Your Service History</h2>

            <p>
                Your latest bookings appear first.
            </p>
        </div>

        <a class="btn" href="../html/service.html">
            + Book a Service
        </a>

    </div>


    <!-- BOOKINGS -->

    <?php if ($bookings->num_rows > 0): ?>

        <section class="booking-list">

            <?php
            $index = 0;

            while ($booking = $bookings->fetch_assoc()):

                $index++;

                $status = strtolower(
                    trim((string)($booking["status"] ?? ""))
                );

                $statusCss = statusClass($status);

                $rating = max(
                    0,
                    min(5, (int)($booking["review_rating"] ?? 0))
                );
            ?>

                <article
                    class="booking-card"
                    style="animation-delay: <?php echo min($index * 0.07, 0.35); ?>s"
                >

                    <div class="booking-header">

                        <div>

                            <div class="booking-id">
                                BOOKING ID #<?php
                                    echo (int)$booking["id"];
                                ?>
                            </div>

                            <div class="service-name">
                                <?php
                                    echo h($booking["service_name"]);
                                ?>
                            </div>

                        </div>

                        <span class="status <?php echo h($statusCss); ?>">
                            <?php
                                echo h(
                                    $status !== ""
                                    ? ucfirst($status)
                                    : "Unknown"
                                );
                            ?>
                        </span>

                    </div>


                    <!-- DETAILS -->

                    <div class="details-grid">

                        <div class="detail">

                            <div class="detail-label">
                                📅 Booking Date
                            </div>

                            <div class="detail-value">
                                <?php echo h($booking["booking_date"]); ?>
                            </div>

                        </div>

                        <div class="detail">

                            <div class="detail-label">
                                🕒 Booking Time
                            </div>

                            <div class="detail-value">
                                <?php echo h($booking["booking_time"]); ?>
                            </div>

                        </div>

                        <div class="detail">

                            <div class="detail-label">
                                📍 Service Address
                            </div>

                            <div class="detail-value">
                                <?php echo h($booking["address"]); ?>
                            </div>

                        </div>

                        <div class="detail">

                            <div class="detail-label">
                                📝 Your Message
                            </div>

                            <div class="detail-value">
                                <?php
                                    echo h(
                                        ($booking["message"] ?? "") !== ""
                                        ? $booking["message"]
                                        : "No additional message"
                                    );
                                ?>
                            </div>

                        </div>

                    </div>


                    <!-- PROVIDER DETAILS -->

                    <?php if (
                        in_array(
                            $status,
                            ["accepted", "completed"],
                            true
                        )
                    ): ?>

                        <div class="provider-box">

                            <h3>👨‍🔧 Provider Details</h3>

                            <div class="provider-grid">

                                <div>

                                    <div class="detail-label">
                                        Provider Name
                                    </div>

                                    <div class="detail-value">
                                        <?php
                                            echo h(
                                                $booking["provider_name"]
                                                ?: "Not available"
                                            );
                                        ?>
                                    </div>

                                </div>

                                <div>

                                    <div class="detail-label">
                                        Provider Phone
                                    </div>

                                    <div class="detail-value">
                                        <?php
                                            echo h(
                                                $booking["provider_phone"]
                                                ?: "Not available"
                                            );
                                        ?>
                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- REVIEW -->

                    <?php if (
                        $status === "completed" &&
                        !empty($booking["provider_id"])
                    ): ?>

                        <div class="review-box">

                            <h3>⭐ Service Review</h3>

                            <?php if (!empty($booking["review_id"])): ?>

                                <div class="reviewed">

                                    <strong>
                                        ✓ You have already reviewed this booking.
                                    </strong>

                                    <div class="stars">

                                        <?php
                                            echo str_repeat("★", $rating);

                                            echo str_repeat(
                                                "☆",
                                                5 - $rating
                                            );
                                        ?>

                                    </div>

                                    <?php if (
                                        !empty($booking["review_comment"])
                                    ): ?>

                                        <div class="review-comment">

                                            <strong>Your Review:</strong>

                                            <?php
                                                echo h(
                                                    $booking["review_comment"]
                                                );
                                            ?>

                                        </div>

                                    <?php endif; ?>

                                </div>

                            <?php else: ?>

                                <p>
                                    Your service has been completed!
                                    Rate your provider and share your experience.
                                </p>

                                <a
                                    class="review-button"
                                    href="review.php?id=<?php
                                        echo (int)$booking["id"];
                                    ?>"
                                >
                                    ⭐ Give Review
                                </a>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </article>

            <?php endwhile; ?>

        </section>

    <?php else: ?>

        <!-- EMPTY STATE -->

        <section class="empty">

            <div class="empty-icon">
                📭
            </div>

            <h3>No Bookings Yet</h3>

            <p>
                You haven't booked a service yet.
                Explore HomeServe and book a trusted
                professional for your home.
            </p>

            <a class="btn" href="../html/service.html">
                Explore Services →
            </a>

        </section>

    <?php endif; ?>

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
<!-- REVIEW MODAL -->

<div
    class="review-modal"
    id="reviewModal"
    aria-hidden="true"
>
    <section
        class="review-modal-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="reviewModalTitle"
    >

        <div class="review-modal-header">
            <div>
                <h2 id="reviewModalTitle">Rate Your Service ⭐</h2>

                <p id="reviewServiceName">Your completed service</p>

                <p id="reviewProviderName">Provider</p>
            </div>

            <button
                type="button"
                class="review-close"
                id="closeReviewModal"
                aria-label="Close review popup"
            >&times;</button>
        </div>

        <form id="reviewModalForm">

            <input
                type="hidden"
                id="reviewBookingId"
                name="booking_id"
            >

            <label>How was your experience?</label>

            <div class="review-stars">

                <input type="radio" id="modalStar5"
                    name="rating" value="5">
                <label for="modalStar5" title="5 stars">★</label>

                <input type="radio" id="modalStar4"
                    name="rating" value="4">
                <label for="modalStar4" title="4 stars">★</label>

                <input type="radio" id="modalStar3"
                    name="rating" value="3">
                <label for="modalStar3" title="3 stars">★</label>

                <input type="radio" id="modalStar2"
                    name="rating" value="2">
                <label for="modalStar2" title="2 stars">★</label>

                <input type="radio" id="modalStar1"
                    name="rating" value="1">
                <label for="modalStar1" title="1 star">★</label>

            </div>

            <label for="reviewComment">Write your review</label>

            <textarea
                id="reviewComment"
                name="comment"
                maxlength="1000"
                placeholder="Tell us about your experience..."
            ></textarea>

            <div
                class="review-feedback"
                id="reviewFeedback"
                role="status"
                aria-live="polite"
            ></div>

            <button
                type="submit"
                class="review-submit"
                id="reviewSubmitBtn"
            >
                Submit Review
            </button>

        </form>

    </section>
</div>
</footer>

<script>
(() => {
    const modal = document.getElementById("reviewModal");
    const form = document.getElementById("reviewModalForm");
    const closeBtn = document.getElementById("closeReviewModal");
    const bookingIdInput = document.getElementById("reviewBookingId");
    const serviceName = document.getElementById("reviewServiceName");
    const providerName = document.getElementById("reviewProviderName");
    const feedback = document.getElementById("reviewFeedback");
    const submitBtn = document.getElementById("reviewSubmitBtn");

    let previousFocus = null;

    function openModal(button) {
        previousFocus = button;

        form.reset();
        feedback.textContent = "";
        feedback.className = "review-feedback";
        submitBtn.disabled = false;
        submitBtn.textContent = "Submit Review";

        bookingIdInput.value = button.dataset.bookingId;
        serviceName.textContent = button.dataset.service || "Home service";
        providerName.textContent =
            "Provider: " + (button.dataset.provider || "Provider");

        modal.classList.add("active");
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("modal-open");

        document.getElementById("modalStar5").focus();
    }

    function closeModal() {
        modal.classList.remove("active");
        modal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("modal-open");

        if (previousFocus) {
            previousFocus.focus();
        }
    }

    document.querySelectorAll(".open-review-modal").forEach(button => {
        button.addEventListener("click", () => openModal(button));
    });

    closeBtn.addEventListener("click", closeModal);

    modal.addEventListener("click", event => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener("keydown", event => {
        if (
            event.key === "Escape" &&
            modal.classList.contains("active")
        ) {
            closeModal();
        }
    });

    form.addEventListener("submit", async event => {
        event.preventDefault();

        if (!form.querySelector('input[name="rating"]:checked')) {
            feedback.textContent = "Please select a star rating.";
            feedback.className = "review-feedback show error";
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = "Submitting...";
        feedback.textContent = "";
        feedback.className = "review-feedback";

        try {
            const response = await fetch("submit_review.php", {
                method: "POST",
                body: new FormData(form),
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || "Review could not be submitted."
                );
            }

            feedback.textContent = result.message;
            feedback.className = "review-feedback show success";

            submitBtn.textContent = "Submitted ✓";

            setTimeout(() => {
                closeModal();
                window.location.reload();
            }, 700);

        } catch (error) {
            feedback.textContent =
                error.message || "Something went wrong. Please try again.";

            feedback.className = "review-feedback show error";
            submitBtn.disabled = false;
            submitBtn.textContent = "Try Again";
        }
    });
})();
</script>
</body>
</html>

<?php
$getBookings->close();
$conn->close();
?>