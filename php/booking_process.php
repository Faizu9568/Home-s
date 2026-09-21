<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include "../config/database.php";

/* =========================
   CUSTOMER LOGIN CHECK
========================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../html/login.html");
    exit();
}

if (
    !isset($_SESSION["user_role"]) ||
    strtolower(trim($_SESSION["user_role"])) !== "customer"
) {
    die("Access denied. Customer account required.");
}

/* =========================
   POST REQUEST CHECK
========================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../html/booking.html");
    exit();
}

/* =========================
   GET FORM DATA
========================= */

$customerId = (int) $_SESSION["user_id"];

$customerName = trim($_POST["customer_name"] ?? "");

$serviceId = (int) ($_POST["service_id"] ?? 0);

$address = trim($_POST["address"] ?? "");

$bookingDate = trim($_POST["booking_date"] ?? "");

$bookingTime = trim($_POST["booking_time"] ?? "");

$hours = (int) ($_POST["hours"] ?? 1);

$message = trim($_POST["message"] ?? "");

/* =========================
   VALIDATION
========================= */

if ($customerName === "") {
    die("Please enter customer name.");
}

if ($serviceId <= 0) {
    die("Please select a service.");
}

if ($address === "") {
    die("Please enter service address.");
}

if ($bookingDate === "") {
    die("Please select booking date.");
}

if ($bookingTime === "") {
    die("Please select booking time.");
}

if ($hours < 1 || $hours > 12) {
    die("Please select valid number of hours.");
}

/* =========================
   GET SERVICE DETAILS
========================= */

$getService = $conn->prepare(
    "SELECT id, name, price
     FROM services
     WHERE id = ?
     LIMIT 1"
);

if (!$getService) {
    die("Service query failed: " . $conn->error);
}

$getService->bind_param("i", $serviceId);

if (!$getService->execute()) {
    die("Service query execution failed: " . $getService->error);
}

$result = $getService->get_result();

if ($result->num_rows === 0) {
    $getService->close();
    die("Service not found. Please go back and try again.");
}

$service = $result->fetch_assoc();

$getService->close();

/* =========================
   CALCULATE AMOUNT
========================= */

$pricePerHour = (float) $service["price"];

$totalAmount = $pricePerHour * $hours;

/* =========================
   CREATE BOOKING
========================= */

$addBooking = $conn->prepare(
    "INSERT INTO bookings
    (
        customer_id,
        customer_name,
        provider_id,
        service_id,
        booking_date,
        booking_time,
        hours,
        total_amount,
        address,
        message,
        status
    )
    VALUES
    (
        ?,
        ?,
        NULL,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        'pending'
    )"
);

if (!$addBooking) {
    die("Booking query failed: " . $conn->error);
}

/* =========================
   BIND DATA
========================= */

$addBooking->bind_param(
    "isissidss",
    $customerId,
    $customerName,
    $serviceId,
    $bookingDate,
    $bookingTime,
    $hours,
    $totalAmount,
    $address,
    $message
);

/* =========================
   SAVE BOOKING
========================= */

if (!$addBooking->execute()) {

    $error = $addBooking->error;

    $addBooking->close();
    $conn->close();

    die(
        "Booking could not be saved.<br><br>" .
        "Database Error: " .
        htmlspecialchars($error)
    );
}

/* =========================
   GET BOOKING ID
========================= */

$bookingId = $conn->insert_id;

$addBooking->close();
$conn->close();

/* =========================
   GO TO PAYMENT
========================= */

header("Location: payment.php?booking_id=" . $bookingId);
exit();

?>