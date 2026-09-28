<?php

session_start();

include "../config/database.php";


/* =========================
   PROVIDER LOGIN CHECK
========================= */

if (!isset($_SESSION["provider_id"])) {

    header(
        "Location: ../html/provider_login.html"
    );

    exit();
}


$providerUserId =
    (int) $_SESSION["provider_id"];


/* =========================
   VERIFY PROVIDER
========================= */

$userQuery = $conn->prepare(
    "SELECT id
     FROM users
     WHERE id = ?
       AND role = 'provider'
     LIMIT 1"
);


if (!$userQuery) {
    die("User query failed: " . $conn->error);
}


$userQuery->bind_param(
    "i",
    $providerUserId
);

$userQuery->execute();

$userResult =
    $userQuery->get_result();


if ($userResult->num_rows === 0) {

    die("Provider account not found.");
}


$userQuery->close();


/* =========================
   GET PROVIDER ID
========================= */

$providerQuery = $conn->prepare(
    "SELECT id, status
     FROM providers
     WHERE user_id = ?
     LIMIT 1"
);


if (!$providerQuery) {
    die("Provider query failed: " . $conn->error);
}


$providerQuery->bind_param(
    "i",
    $providerUserId
);

$providerQuery->execute();

$providerResult =
    $providerQuery->get_result();


if ($providerResult->num_rows === 0) {

    die(
        "Provider profile not found. Please complete your profile first."
    );
}


$provider = $providerResult->fetch_assoc();

$providerId =
    (int) $provider["id"];


$providerQuery->close();


/* =========================
   PROVIDER ACTIVE CHECK
========================= */

if (
    strtolower(
        trim($provider["status"])
    ) !== "active"
) {

    die(
        "Your provider account is not active."
    );
}


/* =========================
   GET ACTION
========================= */

$action =
    strtolower(
        trim($_GET["action"] ?? "")
    );


$bookingId =
    isset($_GET["id"])
        ? (int) $_GET["id"]
        : 0;


if ($bookingId <= 0) {

    die("Invalid booking ID.");
}


/* =====================================================
   ACCEPT BOOKING
===================================================== */

if ($action === "accept") {


    $update = $conn->prepare(
        "UPDATE bookings
         SET provider_id = ?,
             status = 'accepted'
         WHERE id = ?
           AND status = 'pending'
           AND provider_id IS NULL"
    );


    if (!$update) {

        die(
            "Accept query failed: "
            . $conn->error
        );
    }


    $update->bind_param(
        "ii",
        $providerId,
        $bookingId
    );


    if (!$update->execute()) {

        die(
            "Could not accept booking: "
            . $update->error
        );
    }


    $update->close();


    header(
        "Location: dashboard.php"
    );

    exit();
}


/* =====================================================
   REJECT BOOKING
===================================================== */

if ($action === "reject") {

    /*
     * Your current system does not have
     * a separate rejected status.
     *
     * Therefore we simply return to dashboard.
     */

    header(
        "Location: dashboard.php"
    );

    exit();
}


/* =====================================================
   COMPLETE BOOKING
===================================================== */

if ($action === "complete") {


    $update = $conn->prepare(
        "UPDATE bookings
         SET status = 'completed'
         WHERE id = ?
           AND provider_id = ?
           AND status = 'accepted'"
    );


    if (!$update) {

        die(
            "Complete query failed: "
            . $conn->error
        );
    }


    $update->bind_param(
        "ii",
        $bookingId,
        $providerId
    );


    if (!$update->execute()) {

        die(
            "Could not complete booking: "
            . $update->error
        );
    }


    $update->close();


    header(
        "Location: dashboard.php"
    );

    exit();
}


/* =========================
   INVALID ACTION
========================= */

die("Invalid booking action.");

?>