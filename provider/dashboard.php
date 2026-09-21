<?php

session_start();
include "../config/database.php";

/* =========================
   PROVIDER LOGIN CHECK
========================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../html/provider_login.html");
    exit();
}

if (
    !isset($_SESSION["user_role"]) ||
    strtolower(trim($_SESSION["user_role"])) !== "provider"
) {
    die("Access denied. Provider account required.");
}

$userId = (int) $_SESSION["user_id"];


/* =========================
   GET PROVIDER PROFILE
========================= */

$getProvider = $conn->prepare(
    "SELECT id, service_id, experience, location, about, status
     FROM providers
     WHERE user_id = ?
     LIMIT 1"
);

if (!$getProvider) {
    die("Provider query failed: " . $conn->error);
}

$getProvider->bind_param("i", $userId);
$getProvider->execute();

$providerResult = $getProvider->get_result();

if ($providerResult->num_rows === 0) {
    $getProvider->close();
    header("Location: profile.php");
    exit();
}

$provider = $providerResult->fetch_assoc();

$providerId = (int) $provider["id"];

$getProvider->close();


/* =========================
   PROVIDER STATUS CHECK
========================= */

if (strtolower(trim($provider["status"])) !== "active") {
    die("Your provider account is not active.");
}


/* =========================
   GET PROVIDER NAME
========================= */

$nameQuery = $conn->prepare(
    "SELECT name
     FROM users
     WHERE id = ?
     LIMIT 1"
);

if (!$nameQuery) {
    die("Name query failed: " . $conn->error);
}

$nameQuery->bind_param("i", $userId);
$nameQuery->execute();

$nameResult = $nameQuery->get_result();
$user = $nameResult->fetch_assoc();

$providerName = $user["name"] ?? "Provider";

$nameQuery->close();


/* =========================
   PENDING BOOKINGS
   ALL ACTIVE PROVIDERS
========================= */

$pendingQuery = $conn->prepare(
    "SELECT
        bookings.id,
        bookings.booking_date,
        bookings.booking_time,
        bookings.hours,
        bookings.total_amount,
        bookings.address,
        bookings.message,
        bookings.status,

        COALESCE(
            NULLIF(bookings.customer_name, ''),
            users.name
        ) AS customer_name,

        services.name AS service_name

     FROM bookings

     JOIN users
        ON bookings.customer_id = users.id

     JOIN services
        ON bookings.service_id = services.id

     WHERE bookings.status = 'pending'
       AND bookings.provider_id IS NULL

     ORDER BY bookings.id DESC"
);

if (!$pendingQuery) {
    die("Pending booking query failed: " . $conn->error);
}

$pendingQuery->execute();

$pendingBookings = $pendingQuery->get_result();


/* =========================
   ACCEPTED / COMPLETED BOOKINGS
========================= */

$acceptedQuery = $conn->prepare(
    "SELECT
        bookings.id,
        bookings.booking_date,
        bookings.booking_time,
        bookings.hours,
        bookings.total_amount,
        bookings.address,
        bookings.message,
        bookings.status,

        COALESCE(
            NULLIF(bookings.customer_name, ''),
            users.name
        ) AS customer_name,

        services.name AS service_name

     FROM bookings

     JOIN users
        ON bookings.customer_id = users.id

     JOIN services
        ON bookings.service_id = services.id

     WHERE bookings.provider_id = ?

       AND bookings.status IN (
           'accepted',
           'completed'
       )

     ORDER BY bookings.id DESC"
);

if (!$acceptedQuery) {
    die("Accepted booking query failed: " . $conn->error);
}

$acceptedQuery->bind_param("i", $providerId);

$acceptedQuery->execute();

$acceptedBookings = $acceptedQuery->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Provider Dashboard | HomeServe</title>

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
    width: 85%;
    max-width: 1200px;
    margin: 40px auto;
}

h1,
h2 {
    color: #153b6d;
}

.welcome {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.booking-card {
    background: white;
    padding: 25px;
    margin-bottom: 20px;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.service-name {
    font-size: 24px;
    font-weight: bold;
    color: #153b6d;
    margin-bottom: 18px;
}

.info {
    margin: 10px 0;
    line-height: 1.5;
}

.info strong {
    display: inline-block;
    width: 120px;
}

.status {
    display: inline-block;
    margin-top: 12px;
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

.buttons {
    margin-top: 20px;
}

.btn {
    display: inline-block;
    padding: 11px 20px;
    border-radius: 6px;
    color: white;
    text-decoration: none;
    font-weight: bold;
    margin-right: 10px;
    cursor: pointer;
}

.accept {
    background: #198754;
}

.accept:hover {
    background: #157347;
}

.reject {
    background: #dc3545;
}

.reject:hover {
    background: #bb2d3b;
}

.empty {
    background: white;
    padding: 35px;
    text-align: center;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    color: #666;
}

</style>

</head>

<body>

<nav>

<strong>HomeServe - Provider</strong>

<div>

<a href="dashboard.php">Dashboard</a>

<a href="profile.php">Profile</a>

<a href="../php/logout.php">Logout</a>

</div>

</nav>


<div class="container">


<!-- =========================
     WELCOME
========================= -->

<div class="welcome">

<h1>
Welcome,
<?php echo htmlspecialchars($providerName); ?>!
</h1>

<p>
Manage customer service bookings from your provider dashboard.
</p>

</div>


<!-- =========================
     NEW BOOKINGS
========================= -->

<h2>New Service Bookings</h2>


<?php if ($pendingBookings->num_rows > 0) { ?>


<?php while ($booking = $pendingBookings->fetch_assoc()) { ?>


<div class="booking-card">


<div class="service-name">

<?php
echo htmlspecialchars($booking["service_name"]);
?>

</div>


<div class="info">

<strong>Customer:</strong>

<?php
echo htmlspecialchars(
    $booking["customer_name"] ?? "Customer"
);
?>

</div>


<div class="info">

<strong>Date:</strong>

<?php
echo htmlspecialchars($booking["booking_date"]);
?>

</div>


<div class="info">

<strong>Time:</strong>

<?php
echo htmlspecialchars($booking["booking_time"]);
?>

</div>


<div class="info">

<strong>Hours:</strong>

<?php
echo (int) $booking["hours"];
?>

hour(s)

</div>


<div class="info">

<strong>Total Amount:</strong>

₹<?php
echo number_format(
    (float) $booking["total_amount"],
    2
);
?>

</div>


<div class="info">

<strong>Address:</strong>

<?php
echo htmlspecialchars($booking["address"]);
?>

</div>


<div class="info">

<strong>Message:</strong>

<?php
echo htmlspecialchars(
    $booking["message"] ?? ""
);
?>

</div>


<span class="status pending">

Pending

</span>


<div class="buttons">


<a
    class="btn accept"
    href="booking.php?action=accept&id=<?php echo (int) $booking["id"]; ?>"
>
    Accept Booking
</a>


<a
    class="btn reject"
    href="booking.php?action=reject&id=<?php echo (int) $booking["id"]; ?>"
    onclick="return confirm('Are you sure you want to reject this booking?');"
>
    Reject Booking
</a>


</div>


</div>


<?php } ?>


<?php } else { ?>


<div class="empty">

<h3>No New Bookings</h3>

<p>
There are currently no pending service bookings.
</p>

</div>


<?php } ?>


<!-- =========================
     MY BOOKINGS
========================= -->

<h2>My Accepted Bookings</h2>


<?php if ($acceptedBookings->num_rows > 0) { ?>


<?php while ($booking = $acceptedBookings->fetch_assoc()) { ?>


<div class="booking-card">


<div class="service-name">

<?php
echo htmlspecialchars($booking["service_name"]);
?>

</div>


<div class="info">

<strong>Customer:</strong>

<?php
echo htmlspecialchars(
    $booking["customer_name"] ?? "Customer"
);
?>

</div>


<div class="info">

<strong>Date:</strong>

<?php
echo htmlspecialchars($booking["booking_date"]);
?>

</div>


<div class="info">

<strong>Time:</strong>

<?php
echo htmlspecialchars($booking["booking_time"]);
?>

</div>


<div class="info">

<strong>Hours:</strong>

<?php
echo (int) $booking["hours"];
?>

hour(s)

</div>


<div class="info">

<strong>Total Amount:</strong>

₹<?php
echo number_format(
    (float) $booking["total_amount"],
    2
);
?>

</div>


<div class="info">

<strong>Address:</strong>

<?php
echo htmlspecialchars($booking["address"]);
?>

</div>


<div class="info">

<strong>Message:</strong>

<?php
echo htmlspecialchars(
    $booking["message"] ?? ""
);
?>

</div>


<span
    class="status <?php echo htmlspecialchars($booking["status"]); ?>"
>

<?php
echo ucfirst(
    htmlspecialchars($booking["status"])
);
?>

</span>


<?php if ($booking["status"] === "accepted") { ?>


<div class="buttons">


<a
    class="btn accept"
    href="booking.php?action=complete&id=<?php echo (int) $booking["id"]; ?>"
    onclick="return confirm('Are you sure this booking is completed?');"
>
    Complete Booking
</a>


</div>


<?php } ?>


</div>


<?php } ?>


<?php } else { ?>


<div class="empty">

<h3>No Accepted Bookings</h3>

<p>
You have not accepted any booking yet.
</p>

</div>


<?php } ?>


</div>


</body>

</html>


<?php

$pendingQuery->close();

$acceptedQuery->close();

$conn->close();

?>