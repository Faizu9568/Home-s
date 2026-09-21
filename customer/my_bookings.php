
<?php

session_start();
include "../config/database.php";

/* =========================
   CUSTOMER LOGIN CHECK
========================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../html/login.html");
    exit();
}

$customerId = (int) $_SESSION["user_id"];


/* =========================
   GET CUSTOMER BOOKINGS
========================= */

$getBookings = $conn->prepare(
    "SELECT
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

     ORDER BY bookings.id DESC"
);

if (!$getBookings) {
    die("Booking query failed: " . $conn->error);
}

$getBookings->bind_param("i", $customerId);
$getBookings->execute();

$bookings = $getBookings->get_result();

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
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #222;
        }

        nav {
            background: #153b6d;
            color: white;
            padding: 18px 8%;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav strong {
            font-size: 24px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
        }

        h1 {
            color: #153b6d;
        }

        .booking-card {
            background: white;
            padding: 25px;
            margin-top: 20px;
            border-radius: 12px;

            box-shadow:
                0 5px 20px rgba(0,0,0,0.08);
        }

        .service-name {
            font-size: 24px;
            font-weight: bold;
            color: #153b6d;
            margin-bottom: 20px;
        }

        .info {
            margin: 10px 0;
            line-height: 1.5;
        }

        .info strong {
            display: inline-block;
            width: 110px;
        }

        .provider-box {
            margin-top: 20px;
            padding: 18px;
            background: #f0f7ff;
            border-left: 5px solid #153b6d;
            border-radius: 8px;
        }

        .provider-box h3 {
            margin-top: 0;
            color: #153b6d;
        }

        .status {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
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

        .review-box {
            margin-top: 20px;
            padding: 20px;
            background: #fffaf0;
            border-left: 5px solid #ff9800;
            border-radius: 8px;
        }

        .review-box h3 {
            margin-top: 0;
            color: #b45309;
        }

        .review-button {
            display: inline-block;
            margin-top: 10px;
            background: #ff7a00;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        .review-button:hover {
            background: #e66d00;
        }

        .reviewed {
            margin-top: 10px;
            padding: 12px;
            background: #d1e7dd;
            color: #0f5132;
            border-radius: 6px;
            font-weight: bold;
        }

        .stars {
            color: #f59e0b;
            font-size: 22px;
            letter-spacing: 2px;
        }

        .empty {
            background: white;
            padding: 35px;
            text-align: center;
            border-radius: 12px;

            box-shadow:
                0 5px 20px rgba(0,0,0,0.08);
        }

        .button {
            display: inline-block;
            margin-top: 20px;
            background: #ff7a00;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

    </style>

</head>

<body>

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
            <a href="my_bookings.php" class="active">My Bookings</a>
        </li>

        <li>
            <a href="profile.php">Profile</a>
        </li>

    </ul>

    <div class="nav-buttons">

        <a href="../php/logout.php" class="logout-btn">
            Logout
        </a>

    </div>

</nav>


<div class="container">

    <h1>My Bookings</h1>


    <?php if ($bookings->num_rows > 0) { ?>

        <?php while ($booking = $bookings->fetch_assoc()) { ?>

            <div class="booking-card">

                <!-- SERVICE -->

                <div class="service-name">
<div style="color:#666; font-size:14px; margin-bottom:10px;">
    Booking ID: #<?php echo (int) $booking["id"]; ?>
</div>
                    <?php
                    echo htmlspecialchars(
                        $booking["service_name"]
                    );
                    ?>

                </div>


                <!-- DATE -->

                <div class="info">

                    <strong>Date:</strong>

                    <?php
                    echo htmlspecialchars(
                        $booking["booking_date"]
                    );
                    ?>

                </div>


                <!-- TIME -->

                <div class="info">

                    <strong>Time:</strong>

                    <?php
                    echo htmlspecialchars(
                        $booking["booking_time"]
                    );
                    ?>

                </div>


                <!-- ADDRESS -->

                <div class="info">

                    <strong>Address:</strong>

                    <?php
                    echo htmlspecialchars(
                        $booking["address"]
                    );
                    ?>

                </div>


                <!-- MESSAGE -->

                <div class="info">

                    <strong>Message:</strong>

                    <?php
                    echo htmlspecialchars(
                        $booking["message"] ?? ""
                    );
                    ?>

                </div>


                <!-- STATUS -->

                <span class="status <?php
                    echo htmlspecialchars(
                        $booking["status"]
                    );
                ?>">

                    <?php
                    echo ucfirst(
                        htmlspecialchars(
                            $booking["status"]
                        )
                    );
                    ?>

                </span>


                <!-- PROVIDER DETAILS -->

                <?php if (
                    $booking["status"] === "accepted" ||
                    $booking["status"] === "completed"
                ) { ?>

                    <div class="provider-box">

                        <h3>Provider Details</h3>

                        <div class="info">

                            <strong>Name:</strong>

                            <?php
                            echo htmlspecialchars(
                                $booking["provider_name"]
                                ?? "Not available"
                            );
                            ?>

                        </div>


                        <div class="info">

                            <strong>Phone:</strong>

                            <?php
                            echo htmlspecialchars(
                                $booking["provider_phone"]
                                ?? "Not available"
                            );
                            ?>

                        </div>

                    </div>

                <?php } ?>


                <!-- REVIEW SECTION -->

                <?php if (
                    $booking["status"] === "completed" &&
                    !empty($booking["provider_id"])
                ) { ?>

                    <div class="review-box">

                        <h3>Service Review</h3>


                        <?php if (
                            !empty($booking["review_id"])
                        ) { ?>

                            <div class="reviewed">

                                <div>
                                    You have already reviewed this booking.
                                </div>

                                <div class="stars">

                                    <?php
                                    echo str_repeat(
                                        "★",
                                        (int) $booking["review_rating"]
                                    );

                                    echo str_repeat(
                                        "☆",
                                        5 - (int) $booking["review_rating"]
                                    );
                                    ?>

                                </div>

                                <?php if (
                                    !empty($booking["review_comment"])
                                ) { ?>

                                    <div style="margin-top:8px;">

                                        <strong>Your Review:</strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $booking["review_comment"]
                                        );
                                        ?>

                                    </div>

                                <?php } ?>

                            </div>

                        <?php } else { ?>

                            <p>
                                Your service has been completed.
                                Please rate your provider.
                            </p>

                            <a
                                class="review-button"
                                href="review.php?id=<?php
                                    echo (int) $booking["id"];
                                ?>"
                            >
                                ⭐ Give Review
                            </a>

                        <?php } ?>

                    </div>

                <?php } ?>

            </div>

        <?php } ?>

    <?php } else { ?>

        <div class="empty">

            <h3>No Bookings Found</h3>

            <p>
                You have not booked any service yet.
            </p>

            <a
                class="button"
                href="../html/service.html"
            >
                Book a Service
            </a>

        </div>

    <?php } ?>

</div>

</body>

</html>

<?php

$getBookings->close();
$conn->close();

?>