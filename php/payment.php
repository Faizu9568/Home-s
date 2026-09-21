<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include "../config/database.php";

/* =========================
   CUSTOMER LOGIN CHECK
========================= */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    strtolower(trim($_SESSION["user_role"])) !== "customer"
) {
    header("Location: ../html/login.html");
    exit();
}

$customerId = (int) $_SESSION["user_id"];

/* =========================
   GET BOOKING ID
========================= */

$bookingId = isset($_GET["booking_id"])
    ? (int) $_GET["booking_id"]
    : (isset($_POST["booking_id"]) ? (int) $_POST["booking_id"] : 0);

if ($bookingId <= 0) {
    die("Invalid booking ID.");
}

/* =========================
   PAYMENT SUBMIT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $paymentMethod = $_POST["payment_method"] ?? "";

    $allowedMethods = ["UPI", "Card", "Cash"];

    if (!in_array($paymentMethod, $allowedMethods)) {
        die("Invalid payment method.");
    }

    /* Demo Payment ID */
    $paymentId = "HS" . date("YmdHis") . rand(100, 999);

    /* Update Payment Details */

    $update = $conn->prepare("
        UPDATE bookings
        SET
            payment_status = 'paid',
            payment_method = ?,
            payment_id = ?
        WHERE id = ?
          AND customer_id = ?
    ");

    if (!$update) {
        die("Payment update query failed: " . $conn->error);
    }

    $update->bind_param(
        "ssii",
        $paymentMethod,
        $paymentId,
        $bookingId,
        $customerId
    );

    if ($update->execute()) {

        $update->close();
        $conn->close();

        ?>

        <!DOCTYPE html>
        <html lang="en">

        <head>

            <meta charset="UTF-8">

            <meta
                name="viewport"
                content="width=device-width, initial-scale=1.0"
            >

            <title>Payment Successful | HomeServe</title>

            <style>

                * {
                    box-sizing: border-box;
                }

                body {
                    margin: 0;
                    font-family: Arial, sans-serif;
                    background: #f4f7fb;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                }

                .box {
                    background: white;
                    width: 90%;
                    max-width: 550px;
                    padding: 40px;
                    text-align: center;
                    border-radius: 18px;
                    box-shadow: 0 5px 25px rgba(0,0,0,0.12);
                }

                .success {
                    width: 80px;
                    height: 80px;
                    margin: 0 auto 20px;
                    border-radius: 50%;
                    background: #198754;
                    color: white;
                    font-size: 55px;
                    line-height: 80px;
                }

                h1 {
                    color: #198754;
                    margin-bottom: 10px;
                }

                .subtitle {
                    color: #666;
                    font-size: 16px;
                }

                .details {
                    background: #f8f9fa;
                    padding: 20px;
                    margin: 25px 0;
                    border-radius: 12px;
                    text-align: left;
                }

                .details p {
                    margin: 12px 0;
                }

                .paid {
                    color: #198754;
                    font-weight: bold;
                }

                .btn {
                    display: inline-block;
                    padding: 12px 22px;
                    background: #153b6d;
                    color: white;
                    text-decoration: none;
                    border-radius: 8px;
                    margin: 5px;
                    font-weight: bold;
                }

                .btn:hover {
                    background: #0f2d52;
                }

                .home {
                    background: #ff7a00;
                }

                .home:hover {
                    background: #e66d00;
                }

                .note {
                    font-size: 13px;
                    color: #777;
                    margin-top: 20px;
                }

            </style>

        </head>

        <body>

            <div class="box">

                <div class="success">
                    ✓
                </div>

                <h1>
                    Payment Successful!
                </h1>

                <p class="subtitle">
                    Your demo payment has been completed successfully.
                </p>

                <div class="details">

                    <p>
                        <strong>Booking ID:</strong>
                        #<?php echo $bookingId; ?>
                    </p>

                    <p>
                        <strong>Payment Method:</strong>
                        <?php echo htmlspecialchars($paymentMethod); ?>
                    </p>

                    <p>
                        <strong>Payment ID:</strong>
                        <?php echo htmlspecialchars($paymentId); ?>
                    </p>

                    <p>
                        <strong>Payment Status:</strong>
                        <span class="paid">PAID</span>
                    </p>

                </div>

                <p>
                    Your booking is now waiting for a service provider
                    to accept it.
                </p>

                <a
                    class="btn"
                    href="../customer/my_bookings.php"
                >
                    My Bookings
                </a>

                <a
                    class="btn home"
                    href="../html/index.html"
                >
                    Home
                </a>

                <p class="note">
                    Demo Payment Only — No real money has been charged.
                </p>

            </div>

        </body>

        </html>

        <?php

        exit();

    } else {

        $error = $update->error;

        $update->close();
        $conn->close();

        die(
            "Payment failed.<br><br>" .
            "Database Error: " .
            htmlspecialchars($error)
        );
    }
}

/* =========================
   GET BOOKING DETAILS
========================= */

$stmt = $conn->prepare("
    SELECT
        bookings.id,
        bookings.booking_date,
        bookings.booking_time,
        bookings.hours,
        bookings.total_amount,
        bookings.address,
        bookings.status,
        bookings.payment_status,
        services.name AS service_name
    FROM bookings
    INNER JOIN services
        ON bookings.service_id = services.id
    WHERE bookings.id = ?
      AND bookings.customer_id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Booking query failed: " . $conn->error);
}

$stmt->bind_param(
    "ii",
    $bookingId,
    $customerId
);

if (!$stmt->execute()) {
    die("Booking query execution failed: " . $stmt->error);
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $stmt->close();
    $conn->close();

    die("Booking not found.");
}

$booking = $result->fetch_assoc();

$stmt->close();
$conn->close();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Demo Payment | HomeServe</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
        }

        .container {
            width: 90%;
            max-width: 600px;
            margin: 50px auto;
        }

        .box {
            background: white;
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #153b6d;
            margin-bottom: 25px;
        }

        .booking {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }

        .booking p {
            margin: 12px 0;
        }

        .amount {
            font-size: 30px;
            font-weight: bold;
            color: #198754;
            text-align: center;
            margin: 25px;
        }

        h3 {
            color: #333;
            margin-top: 25px;
        }

        label {
            display: block;
            padding: 16px;
            border: 1px solid #ddd;
            border-radius: 10px;
            margin: 12px 0;
            cursor: pointer;
            transition: 0.2s;
        }

        label:hover {
            background: #f1f5fa;
            border-color: #153b6d;
        }

        input[type="radio"] {
            margin-right: 10px;
        }

        button {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 9px;
            background: #153b6d;
            color: white;
            font-size: 17px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 15px;
        }

        button:hover {
            background: #0f2d52;
        }

        .demo {
            text-align: center;
            color: #777;
            font-size: 13px;
            margin-top: 18px;
        }

    </style>

</head>

<body>

<div class="container">

    <div class="box">

        <h1>
            HomeServe Payment
        </h1>

        <div class="booking">

            <p>
                <strong>Booking ID:</strong>
                #<?php echo $booking["id"]; ?>
            </p>

            <p>
                <strong>Service:</strong>
                <?php echo htmlspecialchars($booking["service_name"]); ?>
            </p>

            <p>
                <strong>Date:</strong>
                <?php echo htmlspecialchars($booking["booking_date"]); ?>
            </p>

            <p>
                <strong>Time:</strong>
                <?php echo htmlspecialchars($booking["booking_time"]); ?>
            </p>

            <p>
                <strong>Hours:</strong>
                <?php echo htmlspecialchars($booking["hours"]); ?>
            </p>

            <p>
                <strong>Booking Status:</strong>
                <?php echo htmlspecialchars($booking["status"]); ?>
            </p>

        </div>

        <div class="amount">

            ₹<?php echo number_format(
                $booking["total_amount"],
                2
            ); ?>

        </div>

        <form method="POST">

            <input
                type="hidden"
                name="booking_id"
                value="<?php echo $booking["id"]; ?>"
            >

            <h3>
                Select Payment Method
            </h3>

            <label>

                <input
                    type="radio"
                    name="payment_method"
                    value="UPI"
                    required
                >

                📱 UPI

            </label>

            <label>

                <input
                    type="radio"
                    name="payment_method"
                    value="Card"
                >

                💳 Card

            </label>

            <label>

                <input
                    type="radio"
                    name="payment_method"
                    value="Cash"
                >

                💵 Cash on Service

            </label>

            <button type="submit">

                Pay ₹<?php echo number_format(
                    $booking["total_amount"],
                    2
                ); ?>

            </button>

        </form>

        <p class="demo">
            Demo Payment Only — No real money will be charged.
        </p>

    </div>

</div>

</body>

</html>