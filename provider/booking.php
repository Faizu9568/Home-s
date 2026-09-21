<?php

session_start();

include "../config/database.php";


/* =========================
   LOGIN CHECK
========================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../html/provider_login.html");
    exit();
}

$userId = (int) $_SESSION["user_id"];


/* =========================
   VERIFY USER FROM DATABASE
========================= */

$userQuery = $conn->prepare(
    "SELECT id, name, email, role
     FROM users
     WHERE id = ?
     LIMIT 1"
);

if (!$userQuery) {
    die("User query failed: " . $conn->error);
}

$userQuery->bind_param("i", $userId);
$userQuery->execute();

$userResult = $userQuery->get_result();

if ($userResult->num_rows === 0) {
    $userQuery->close();
    session_destroy();

    header("Location: ../html/provider_login.html");
    exit();
}

$user = $userResult->fetch_assoc();

$userQuery->close();


/* =========================
   PROVIDER ROLE CHECK
========================= */

$userRole = strtolower(trim($user["role"] ?? ""));

if ($userRole !== "provider") {
    die("Access denied. Provider account required.");
}


/* =========================
   GET PROVIDER PROFILE
========================= */

$getProvider = $conn->prepare(
    "SELECT id, status
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

    die("Provider profile not found.");
}

$provider = $providerResult->fetch_assoc();

$providerId = (int) $provider["id"];

$getProvider->close();


/* =========================
   PROVIDER STATUS CHECK
========================= */

$providerStatus = strtolower(
    trim($provider["status"] ?? "")
);

if ($providerStatus !== "active") {

    die("Your provider account is not active.");
}


/* =========================
   GET ACTION
========================= */

$action = strtolower(
    trim($_GET["action"] ?? "")
);

$bookingId = (int) ($_GET["id"] ?? 0);

if ($bookingId <= 0) {

    die("Invalid booking ID.");
}


/* ==================================================
   ACCEPT BOOKING
================================================== */

if ($action === "accept") {

    $checkBooking = $conn->prepare(
        "SELECT
            id,
            service_id,
            provider_id,
            status
         FROM bookings
         WHERE id = ?
         LIMIT 1"
    );

    if (!$checkBooking) {
        die("Booking check failed: " . $conn->error);
    }

    $checkBooking->bind_param(
        "i",
        $bookingId
    );

    $checkBooking->execute();

    $bookingResult = $checkBooking->get_result();

    if ($bookingResult->num_rows === 0) {

        $checkBooking->close();

        die("Booking not found.");
    }

    $booking = $bookingResult->fetch_assoc();

    $checkBooking->close();


    if ($booking["status"] !== "pending") {

        die(
            "Booking cannot be accepted because its current status is: " .
            htmlspecialchars($booking["status"])
        );
    }


    if ($booking["provider_id"] !== null) {

        die(
            "Booking is already assigned to another provider."
        );
    }


    $acceptQuery = $conn->prepare(
        "UPDATE bookings
         SET provider_id = ?,
             status = 'accepted'
         WHERE id = ?
           AND status = 'pending'
           AND provider_id IS NULL"
    );

    if (!$acceptQuery) {
        die("Accept query failed: " . $conn->error);
    }

    $acceptQuery->bind_param(
        "ii",
        $providerId,
        $bookingId
    );


    if (!$acceptQuery->execute()) {

        $error = $acceptQuery->error;

        $acceptQuery->close();

        die(
            "Database error while accepting booking:<br><br>" .
            htmlspecialchars($error)
        );
    }


    if ($acceptQuery->affected_rows === 1) {

        $acceptQuery->close();

        header("Location: dashboard.php");

        exit();

    } else {

        $acceptQuery->close();

        die(
            "Booking could not be accepted.<br><br>" .
            "The booking may have already been accepted by another provider."
        );
    }
}


/* ==================================================
   REJECT BOOKING
================================================== */

if ($action === "reject") {

    /*
       Rejection is not global because
       all active providers can see pending bookings.
    */

    header("Location: dashboard.php");

    exit();
}


/* ==================================================
   COMPLETE BOOKING
================================================== */

if ($action === "complete") {

    $completeQuery = $conn->prepare(
        "UPDATE bookings
         SET status = 'completed'
         WHERE id = ?
           AND provider_id = ?
           AND status = 'accepted'"
    );

    if (!$completeQuery) {
        die(
            "Complete query failed: " .
            $conn->error
        );
    }


    $completeQuery->bind_param(
        "ii",
        $bookingId,
        $providerId
    );


    if (!$completeQuery->execute()) {

        $error = $completeQuery->error;

        $completeQuery->close();

        die(
            "Database error while completing booking:<br><br>" .
            htmlspecialchars($error)
        );
    }


    if ($completeQuery->affected_rows === 1) {

        $completeQuery->close();

        header("Location: dashboard.php");

        exit();

    } else {

        $completeQuery->close();

        die(
            "Booking could not be completed.<br><br>" .
            "The booking may not belong to this provider " .
            "or it is not currently accepted."
        );
    }
}


/* =========================
   INVALID ACTION
========================= */

die("Invalid booking action.");

?>