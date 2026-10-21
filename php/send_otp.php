<?php

session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../html/login.html");
    exit();
}

// Get form data
$phone = trim($_POST["phone"] ?? "");
$phone = preg_replace("/\D/", "", $phone);

$name = trim($_POST["name"] ?? "");

// Validate phone
if (strlen($phone) !== 10) {
    die("Please enter a valid 10 digit mobile number.");
}

// Validate name if provided
if ($name !== "" && mb_strlen($name) > 100) {
    die("Name must not exceed 100 characters.");
}

// Redirect destination
$redirect = $_POST["redirect"] ?? "dashboard";

if ($redirect !== "booking") {
    $redirect = "dashboard";
}

// Clear old OTP session data
unset(
    $_SESSION["test_otp"],
    $_SESSION["otp_user_id"],
    $_SESSION["new_customer"],
    $_SESSION["new_customer_phone"],
    $_SESSION["new_customer_name"],
    $_SESSION["otp_phone"]
);

// Check whether customer already exists
$stmt = $conn->prepare(
    "SELECT id, name, email, phone, role
     FROM users
     WHERE phone = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Unable to process login. Please try again.");
}

$stmt->bind_param("s", $phone);
$stmt->execute();

$result = $stmt->get_result();

$isNewUser = false;
$userId = 0;

if ($result->num_rows > 0) {

    // Existing user
    $user = $result->fetch_assoc();

    if (strtolower(trim($user["role"])) !== "customer") {
        $stmt->close();
        die("This mobile number belongs to another account type.");
    }

    $userId = (int) $user["id"];

} else {

    // New customer
    if ($name === "") {
        $stmt->close();
        die("Please enter your name before continuing.");
    }

    $isNewUser = true;

    $_SESSION["new_customer"] = true;
    $_SESSION["new_customer_phone"] = $phone;
    $_SESSION["new_customer_name"] = $name;
}

$stmt->close();

// Generate test OTP
$otp = (string) random_int(100000, 999999);

// Save OTP in session
$_SESSION["test_otp"] = $otp;
$_SESSION["otp_phone"] = $phone;
$_SESSION["login_redirect"] = $redirect;

// Existing customer: save OTP in database
if (!$isNewUser) {

    $expiry = date("Y-m-d H:i:s", time() + 300);

    $update = $conn->prepare(
        "UPDATE users
         SET otp = ?, otp_expiry = ?
         WHERE id = ?"
    );

    if (!$update) {
        die("Unable to generate OTP. Please try again.");
    }

    $update->bind_param("ssi", $otp, $expiry, $userId);
    $update->execute();
    $update->close();

    $_SESSION["otp_user_id"] = $userId;
}

// Redirect to OTP verification page
header(
    "Location: ../html/verify_otp.html?redirect=" .
    urlencode($redirect) .
    "&test_otp=" .
    urlencode($otp)
);

exit();
?>