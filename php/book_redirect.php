<?php

session_start();

/* Get selected service */
$service = trim($_GET["service"] ?? "");

/* If customer is already logged in */
if (
    isset($_SESSION["user_id"]) &&
    isset($_SESSION["user_role"]) &&
    strtolower(trim($_SESSION["user_role"])) === "customer"
) {
    $url = "../html/booking.html";

    if ($service !== "") {
        $url .= "?service=" . urlencode($service);
    }

    header("Location: " . $url);
    exit();
}

/* Remember selected service */
if ($service !== "") {
    $_SESSION["booking_service"] = $service;
}

/* User is not logged in → login first */
header("Location: ../html/login.html?redirect=booking");
exit();

?>