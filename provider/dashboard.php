<?php

session_start();
include "../config/database.php";


/* =========================
   PROVIDER LOGIN CHECK
========================= */

if (!isset($_SESSION["provider_id"])) {
    header("Location: ../html/provider_login.html");
    exit();
}

$providerUserId = (int) $_SESSION["provider_id"];


/* =========================
   VERIFY PROVIDER ACCOUNT
========================= */

$userQuery = $conn->prepare(
    "SELECT id, name, email, phone
     FROM users
     WHERE id = ?
       AND role = 'provider'
     LIMIT 1"
);

if (!$userQuery) {
    die("User query failed: " . $conn->error);
}

$userQuery->bind_param("i", $providerUserId);
$userQuery->execute();

$userResult = $userQuery->get_result();

if ($userResult->num_rows === 0) {
    unset($_SESSION["provider_id"]);
    header("Location: ../html/provider_login.html");
    exit();
}

$user = $userResult->fetch_assoc();

$providerName = $user["name"];

$userQuery->close();


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

$getProvider->bind_param("i", $providerUserId);
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
   PROVIDER STATUS
========================= */

if (
    strtolower(trim($provider["status"])) !== "active"
) {
    die("Your provider account is not active.");
}


/* =========================
   PENDING BOOKINGS
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
   ACCEPTED / COMPLETED
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
       AND bookings.status IN ('accepted', 'completed')

     ORDER BY bookings.id DESC"
);


if (!$acceptedQuery) {
    die("Accepted booking query failed: " . $conn->error);
}


$acceptedQuery->bind_param(
    "i",
    $providerId
);

$acceptedQuery->execute();

$acceptedBookings = $acceptedQuery->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Provider Dashboard | HomeServe</title>


<style>
/* =====================================
   HOMESERVE — PROVIDER DASHBOARD UI
===================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary: #173f73;
    --primary-dark: #102f57;
    --primary-light: #eaf2ff;
    --orange: #ff7900;
    --orange-dark: #e66d00;
    --green: #198754;
    --red: #dc3545;
    --text: #243247;
    --muted: #718096;
    --border: #e7edf5;
    --surface: #ffffff;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    color: var(--text);
    min-height: 100vh;

    background:
        radial-gradient(
            circle at 0% 0%,
            rgba(45, 104, 180, 0.09),
            transparent 28%
        ),
        radial-gradient(
            circle at 100% 30%,
            rgba(255, 121, 0, 0.07),
            transparent 25%
        ),
        #f4f7fc;

    line-height: 1.6;
}

/* =====================================
   NAVIGATION BAR
===================================== */

nav {
    position: sticky;
    top: 0;
    z-index: 1000;

    min-height: 76px;
    padding: 15px 6%;

    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;

    background: rgba(23, 63, 115, 0.97);
    color: white;

    box-shadow: 0 5px 25px rgba(16, 47, 87, 0.16);
    backdrop-filter: blur(12px);

    animation: navAppear 0.5s ease both;
}

nav strong {
    font-size: 23px;
    font-weight: 800;
    letter-spacing: -0.5px;
    white-space: nowrap;
}

nav a {
    position: relative;

    display: inline-block;
    margin-left: 18px;

    color: white;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;

    transition: color 0.25s ease;
}

nav a:not(:last-child):hover {
    color: #ffd4ad;
}

nav a:not(:last-child)::after {
    content: "";

    position: absolute;
    left: 0;
    bottom: -7px;

    width: 0;
    height: 2px;

    background: #ffb16b;
    transition: width 0.25s ease;
}

nav a:not(:last-child):hover::after {
    width: 100%;
}

/* Logout button */

nav a[href*="logout"] {
    padding: 10px 17px;
    margin-left: 18px;

    background: var(--orange);
    border-radius: 9px;

    box-shadow: 0 4px 12px rgba(255, 121, 0, 0.15);

    transition:
        transform 0.25s ease,
        background 0.25s ease,
        box-shadow 0.25s ease;
}

nav a[href*="logout"]:hover {
    color: white;
    background: var(--orange-dark);
    transform: translateY(-2px);

    box-shadow: 0 7px 18px rgba(255, 121, 0, 0.28);
}

/* =====================================
   MAIN CONTAINER
===================================== */

.container {
    width: min(1200px, 92%);
    margin: 38px auto 60px;
}

/* =====================================
   WELCOME SECTION
===================================== */

.welcome {
    position: relative;
    overflow: hidden;

    padding: 35px 38px;
    margin-bottom: 38px;

    background:
        linear-gradient(
            120deg,
            #ffffff 0%,
            #f4f8ff 65%,
            #fff1e3 100%
        );

    border: 1px solid rgba(255, 255, 255, 0.9);
    border-radius: 22px;

    box-shadow: 0 12px 35px rgba(23, 63, 115, 0.07);

    animation: fadeUp 0.55s ease both;
}

.welcome::after {
    content: "🏡";

    position: absolute;
    right: 45px;
    top: 50%;

    font-size: 75px;
    opacity: 0.9;

    transform: translateY(-50%);
    animation: floatHome 3s ease-in-out infinite;
}

.welcome h1 {
    position: relative;
    z-index: 1;

    margin-bottom: 8px;
    padding-right: 95px;

    color: var(--primary);
    font-size: clamp(25px, 3vw, 35px);
    line-height: 1.3;
    letter-spacing: -0.7px;
}

.welcome p {
    position: relative;
    z-index: 1;

    max-width: 620px;
    padding-right: 75px;

    color: var(--muted);
    font-size: 15px;
}

/* =====================================
   SECTION HEADINGS
===================================== */

h2 {
    position: relative;

    margin: 34px 0 20px;
    padding-left: 15px;

    color: var(--primary);
    font-size: 25px;
    font-weight: 800;
    letter-spacing: -0.4px;

    animation: fadeUp 0.5s ease both;
}

h2::before {
    content: "";

    position: absolute;
    left: 0;
    top: 5px;
    bottom: 5px;

    width: 4px;
    border-radius: 5px;

    background: var(--orange);
}

/* =====================================
   BOOKING CARDS
===================================== */

.booking-card {
    position: relative;
    overflow: hidden;

    padding: 28px;
    margin-bottom: 23px;

    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;

    box-shadow: 0 6px 24px rgba(23, 63, 115, 0.055);

    animation: fadeUp 0.55s ease both;

    transition:
        transform 0.3s ease,
        box-shadow 0.3s ease,
        border-color 0.3s ease;
}

.booking-card::before {
    content: "";

    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;

    width: 4px;

    background: linear-gradient(
        180deg,
        var(--primary),
        var(--orange)
    );

    transform: scaleY(0.35);
    transform-origin: center;

    transition: transform 0.3s ease;
}

.booking-card:hover {
    transform: translateY(-5px);

    border-color: #cbdcf2;

    box-shadow: 0 17px 38px rgba(23, 63, 115, 0.12);
}

.booking-card:hover::before {
    transform: scaleY(1);
}

/* Service title */

.service-name {
    display: inline-block;

    margin-bottom: 17px;

    color: var(--primary);
    font-size: 23px;
    font-weight: 800;
    letter-spacing: -0.4px;

    transition: color 0.25s ease;
}

.booking-card:hover .service-name {
    color: var(--orange-dark);
}

/* =====================================
   BOOKING INFORMATION
===================================== */

.info {
    display: flex;
    align-items: flex-start;
    gap: 12px;

    margin: 12px 0;

    color: #536176;
    font-size: 14px;
    line-height: 1.7;

    overflow-wrap: anywhere;
}

.info strong {
    display: inline-block;
    flex: 0 0 125px;

    color: #293e59;
    font-weight: 700;
}

/* =====================================
   STATUS BADGES
===================================== */

.status {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    margin-top: 15px;
    padding: 8px 14px;

    border-radius: 30px;

    font-size: 12px;
    font-weight: 800;
    text-transform: capitalize;
    letter-spacing: 0.3px;
}

.status::before {
    content: "";

    width: 7px;
    height: 7px;

    border-radius: 50%;
    background: currentColor;
}

.pending {
    color: #946200;
    background: #fff4cf;
    border: 1px solid #ffe6a0;
}

.accepted {
    color: #147445;
    background: #e1f7eb;
    border: 1px solid #c2ebd4;
}

.completed {
    color: #1556a0;
    background: #e6f0ff;
    border: 1px solid #cbdfff;
}

/* =====================================
   ACTION BUTTONS
===================================== */

.buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;

    margin-top: 23px;
    padding-top: 20px;

    border-top: 1px solid #edf1f6;
}

.btn {
    position: relative;
    overflow: hidden;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    min-height: 43px;
    padding: 12px 19px;
    margin: 0;

    border: 1px solid transparent;
    border-radius: 9px;

    color: white;
    text-decoration: none;
    text-align: center;

    font-family: inherit;
    font-size: 13px;
    font-weight: 800;

    cursor: pointer;

    transition:
        transform 0.22s ease,
        box-shadow 0.22s ease,
        background 0.22s ease;
}

.btn:hover {
    color: white;
    transform: translateY(-2px);
}

.btn:active {
    transform: scale(0.98);
}

/* Accept */

.accept {
    background: var(--green);
    box-shadow: 0 4px 12px rgba(25, 135, 84, 0.12);
}

.accept:hover {
    background: #146c43;
    box-shadow: 0 7px 17px rgba(25, 135, 84, 0.23);
}

/* Reject */

.reject {
    background: #fff;
    color: var(--red);
    border-color: #f0c8cd;
}

.reject:hover {
    background: #fff0f1;
    color: #b42332;
    box-shadow: 0 5px 14px rgba(220, 53, 69, 0.1);
}

/* =====================================
   EMPTY STATE
===================================== */

.empty {
    position: relative;
    overflow: hidden;

    padding: 40px 25px;
    margin: 20px 0 32px;

    background: white;
    border: 1px solid var(--border);
    border-radius: 18px;

    text-align: center;

    box-shadow: 0 8px 25px rgba(23, 63, 115, 0.055);

    animation: fadeUp 0.55s ease both;
}

.empty::before {
    content: "📭";

    display: grid;
    place-items: center;

    width: 72px;
    height: 72px;

    margin: 0 auto 17px;

    background: var(--primary-light);
    border-radius: 22px;

    font-size: 35px;
}

.empty h3 {
    margin-bottom: 8px;

    color: var(--primary);
    font-size: 21px;
}

.empty p {
    color: var(--muted);
    font-size: 14px;
}

/* =====================================
   ACCESSIBILITY
===================================== */

a:focus-visible {
    outline: 3px solid #ffbf80;
    outline-offset: 3px;
}

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(16px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes navAppear {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes floatHome {
    0%, 100% {
        transform: translateY(-50%);
    }

    50% {
        transform: translateY(calc(-50% - 7px));
    }
}

/* =====================================
   TABLET
===================================== */

@media (max-width: 850px) {
    nav {
        padding: 15px 4%;
        flex-wrap: wrap;
        justify-content: center;
    }

    nav strong {
        width: 100%;
        text-align: center;
    }

    nav a {
        margin: 5px 8px;
    }

    nav a[href*="logout"] {
        margin: 5px 8px;
    }

    .container {
        width: 94%;
    }
}

/* =====================================
   MOBILE
===================================== */

@media (max-width: 600px) {
    nav {
        gap: 8px;
        padding: 16px 10px;
    }

    nav strong {
        font-size: 21px;
    }

    nav a {
        font-size: 12px;
        margin: 5px 6px;
    }

    nav a[href*="logout"] {
        padding: 8px 11px;
    }

    .container {
        width: 94%;
        margin: 23px auto 40px;
    }

    .welcome {
        padding: 25px 20px;
        border-radius: 17px;
    }

    .welcome::after {
        right: 17px;
        top: 18px;
        font-size: 32px;
        transform: none;
        animation: none;
    }

    .welcome h1 {
        padding-right: 28px;
        font-size: 25px;
    }

    .welcome p {
        padding-right: 0;
        font-size: 13px;
    }

    h2 {
        margin-top: 28px;
        font-size: 21px;
    }

    .booking-card {
        padding: 22px 17px;
        border-radius: 15px;
    }

    .service-name {
        font-size: 21px;
    }

    .info {
        display: block;
        font-size: 13px;
    }

    .info strong {
        display: block;
        margin-bottom: 2px;
    }

    .buttons {
        flex-direction: column;
    }

    .btn {
        width: 100%;
    }

    .empty {
        padding: 30px 17px;
    }
}

/* Respect reduced-motion preferences */

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}

</style>

</head>


<body>


<nav>

<strong>HomeServe - Provider</strong>

<div>

<a href="dashboard.php">
    Dashboard
</a>

<a href="profile.php">
    Profile
</a>

<a href="../php/logout.php?role=provider">
    Logout
</a>

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

<h2>
New Service Bookings
</h2>


<?php if ($pendingBookings->num_rows > 0) { ?>


<?php while ($booking = $pendingBookings->fetch_assoc()) { ?>


<div class="booking-card">


<div class="service-name">

<?php
echo htmlspecialchars(
    $booking["service_name"]
);
?>

</div>


<div class="info">

<strong>Customer:</strong>

<?php
echo htmlspecialchars(
    $booking["customer_name"]
);
?>

</div>


<div class="info">

<strong>Date:</strong>

<?php
echo htmlspecialchars(
    $booking["booking_date"]
);
?>

</div>


<div class="info">

<strong>Time:</strong>

<?php
echo htmlspecialchars(
    $booking["booking_time"]
);
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
echo htmlspecialchars(
    $booking["address"]
);
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
    href="booking.php?action=accept&id=<?php
        echo (int) $booking["id"];
    ?>"
>
    Accept Booking
</a>


<a
    class="btn reject"
    href="booking.php?action=reject&id=<?php
        echo (int) $booking["id"];
    ?>"
    onclick="return confirm(
        'Are you sure you want to reject this booking?'
    );"
>
    Reject Booking
</a>


</div>


</div>


<?php } ?>


<?php } else { ?>


<div class="empty">

<h3>
No New Bookings
</h3>

<p>
There are currently no pending service bookings.
</p>

</div>


<?php } ?>


<!-- =========================
     ACCEPTED BOOKINGS
========================= -->

<h2>
My Accepted Bookings
</h2>


<?php if ($acceptedBookings->num_rows > 0) { ?>


<?php while ($booking = $acceptedBookings->fetch_assoc()) { ?>


<div class="booking-card">


<div class="service-name">

<?php
echo htmlspecialchars(
    $booking["service_name"]
);
?>

</div>


<div class="info">

<strong>Customer:</strong>

<?php
echo htmlspecialchars(
    $booking["customer_name"]
);
?>

</div>


<div class="info">

<strong>Date:</strong>

<?php
echo htmlspecialchars(
    $booking["booking_date"]
);
?>

</div>


<div class="info">

<strong>Time:</strong>

<?php
echo htmlspecialchars(
    $booking["booking_time"]
);
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
echo htmlspecialchars(
    $booking["address"]
);
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


<span class="status
<?php echo htmlspecialchars(
    $booking["status"]
); ?>
">

<?php
echo ucfirst(
    htmlspecialchars(
        $booking["status"]
    )
);
?>

</span>


<?php if ($booking["status"] === "accepted") { ?>


<div class="buttons">

<a
    class="btn accept"
    href="booking.php?action=complete&id=<?php
        echo (int) $booking["id"];
    ?>"
    onclick="return confirm(
        'Are you sure this booking is completed?'
    );"
>
    Complete Booking
</a>

</div>


<?php } ?>


</div>


<?php } ?>


<?php } else { ?>


<div class="empty">

<h3>
No Accepted Bookings
</h3>

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