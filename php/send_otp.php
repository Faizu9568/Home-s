<?php

session_start();
include "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../html/login.html");
    exit();
}

$phone = trim($_POST["phone"] ?? "");
$phone = preg_replace("/\D/", "", $phone);

if (strlen($phone) !== 10) {
    die("Please enter a valid 10 digit mobile number.");
}

$redirect = $_POST["redirect"] ?? "dashboard";

if ($redirect !== "booking") {
    $redirect = "dashboard";
}

/* Old OTP session clear */
unset($_SESSION["test_otp"]);
unset($_SESSION["otp_user_id"]);
unset($_SESSION["new_customer"]);
unset($_SESSION["new_customer_phone"]);

/* Check customer */
$stmt = $conn->prepare(
    "SELECT id, name, email, phone, role
     FROM users
     WHERE phone = ?
     LIMIT 1"
);

$stmt->bind_param("s", $phone);
$stmt->execute();

$result = $stmt->get_result();

$isNewUser = false;
$userId = 0;

if ($result->num_rows > 0) {

    $user = $result->fetch_assoc();

    if (strtolower(trim($user["role"])) !== "customer") {
        die("This mobile number belongs to another account type.");
    }

    $userId = (int)$user["id"];

} else {

    $isNewUser = true;

    $_SESSION["new_customer"] = true;
    $_SESSION["new_customer_phone"] = $phone;
}

/* Generate OTP */
$otp = (string) random_int(100000, 999999);

/* Save OTP in session */
$_SESSION["test_otp"] = $otp;
$_SESSION["otp_phone"] = $phone;
$_SESSION["login_redirect"] = $redirect;

/* Existing user */
if (!$isNewUser) {

    $expiry = date("Y-m-d H:i:s", time() + 300);

    $update = $conn->prepare(
        "UPDATE users
         SET otp = ?, otp_expiry = ?
         WHERE id = ?"
    );

    $update->bind_param("ssi", $otp, $expiry, $userId);
    $update->execute();
    $update->close();

    $_SESSION["otp_user_id"] = $userId;
}

$stmt->close();

/*
    CLEAN FLOW:
    OTP Generated page par rukne ke bajay
    direct Verify OTP page par jao.
    
    Test mode ke liye OTP URL me temporarily bhej rahe hain.
*/
header(
    "Location: ../html/verify_otp.html?redirect=" .
    urlencode($redirect) .
    "&test_otp=" .
    urlencode($otp)
);

exit();
?>