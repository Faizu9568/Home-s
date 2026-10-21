<?php

session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../html/login.html");
    exit();
}

// Get submitted OTP
$submittedOtp = trim($_POST["otp"] ?? "");

if (!preg_match('/^\d{6}$/', $submittedOtp)) {
    die("Please enter a valid 6 digit OTP.");
}

// Check OTP session
if (
    !isset($_SESSION["test_otp"]) ||
    !isset($_SESSION["otp_phone"])
) {
    die("OTP session expired. Please login again.");
}

$sessionOtp = (string) $_SESSION["test_otp"];
$phone = (string) $_SESSION["otp_phone"];

if (!hash_equals($sessionOtp, $submittedOtp)) {
    die("Incorrect OTP. Please try again.");
}

$user = null;

// ========================================
// NEW CUSTOMER VERIFICATION
// ========================================

if (
    isset($_SESSION["new_customer"]) &&
    $_SESSION["new_customer"] === true
) {

    $newPhone = (string) ($_SESSION["new_customer_phone"] ?? "");
    $newName = trim((string) ($_SESSION["new_customer_name"] ?? ""));

    if ($newPhone !== $phone) {
        die("Mobile number verification failed. Please try again.");
    }

    if ($newName === "") {
        die("Your name is missing. Please login again and enter your name.");
    }

    if (mb_strlen($newName) > 100) {
        die("Name must not exceed 100 characters.");
    }

    // Check again to prevent duplicate accounts
    $check = $conn->prepare(
        "SELECT id, name, email, phone, role
         FROM users
         WHERE phone = ?
         LIMIT 1"
    );

    if (!$check) {
        die("Unable to verify account. Please try again.");
    }

    $check->bind_param("s", $phone);
    $check->execute();

    $result = $check->get_result();

    if ($result->num_rows > 0) {

        // Account was created in the meantime
        $existingUser = $result->fetch_assoc();

        if (strtolower(trim($existingUser["role"])) !== "customer") {
            $check->close();
            die("This mobile number belongs to another account type.");
        }

        $user = $existingUser;

    } else {

        // Create new customer
        $email = "customer_" . $phone . "_" .
                 bin2hex(random_bytes(4)) . "@homeserve.local";

        $randomPassword = bin2hex(random_bytes(32));
        $passwordHash = password_hash($randomPassword, PASSWORD_DEFAULT);
        $role = "customer";

        $insert = $conn->prepare(
            "INSERT INTO users (name, email, password, phone, role)
             VALUES (?, ?, ?, ?, ?)"
        );

        if (!$insert) {
            $check->close();
            die("Unable to create your account. Please try again.");
        }

        $insert->bind_param(
            "sssss",
            $newName,
            $email,
            $passwordHash,
            $phone,
            $role
        );

        if (!$insert->execute()) {
            $insert->close();
            $check->close();
            die("Account creation failed. Please try again.");
        }

        $newUserId = (int) $insert->insert_id;

        $insert->close();

        // Fetch the newly created customer
        $fetch = $conn->prepare(
            "SELECT id, name, email, phone, role
             FROM users
             WHERE id = ?
             LIMIT 1"
        );

        if (!$fetch) {
            $check->close();
            die("Account created, but unable to load your profile.");
        }

        $fetch->bind_param("i", $newUserId);
        $fetch->execute();

        $newResult = $fetch->get_result();
        $user = $newResult->fetch_assoc();

        $fetch->close();
    }

    $check->close();

    // Clear new customer details after successful verification
    unset(
        $_SESSION["new_customer"],
        $_SESSION["new_customer_phone"],
        $_SESSION["new_customer_name"]
    );

// ========================================
// EXISTING CUSTOMER VERIFICATION
// ========================================

} elseif (isset($_SESSION["otp_user_id"])) {

    $userId = (int) $_SESSION["otp_user_id"];

    $fetch = $conn->prepare(
        "SELECT id, name, email, phone, role, otp, otp_expiry
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    if (!$fetch) {
        die("Unable to verify your account. Please try again.");
    }

    $fetch->bind_param("i", $userId);
    $fetch->execute();

    $result = $fetch->get_result();

    if ($result->num_rows === 0) {
        $fetch->close();
        die("Account not found. Please login again.");
    }

    $existingUser = $result->fetch_assoc();
    $fetch->close();

    // Confirm phone matches
    if ((string) $existingUser["phone"] !== $phone) {
        die("Mobile number verification failed.");
    }

    // Confirm account role
    if (strtolower(trim($existingUser["role"])) !== "customer") {
        die("This account is not a customer account.");
    }

    // Confirm database OTP
    if (!hash_equals((string) $existingUser["otp"], $submittedOtp)) {
        die("Incorrect OTP. Please try again.");
    }

    // Confirm expiry
    $expiry = strtotime((string) $existingUser["otp_expiry"]);

    if ($expiry === false || $expiry < time()) {
        die("OTP has expired. Please login again.");
    }

    // Clear database OTP
    $clear = $conn->prepare(
        "UPDATE users SET otp = NULL, otp_expiry = NULL WHERE id = ?"
    );

    if ($clear) {
        $clear->bind_param("i", $userId);
        $clear->execute();
        $clear->close();
    }

    $user = $existingUser;

} else {
    die("Invalid login session. Please login again.");
}

// ========================================
// SAVE CUSTOMER SESSION
// ========================================

if (!$user || empty($user["id"])) {
    die("Unable to load your account. Please login again.");
}

// Regenerate session ID after authentication
session_regenerate_id(true);

$_SESSION["user_id"] = (int) $user["id"];
$_SESSION["user_name"] = (string) $user["name"];
$_SESSION["user_email"] = (string) $user["email"];
$_SESSION["user_phone"] = (string) $user["phone"];
$_SESSION["user_role"] = (string) $user["role"];

// Clear temporary OTP data
unset(
    $_SESSION["test_otp"],
    $_SESSION["otp_user_id"],
    $_SESSION["otp_phone"]
);

// Redirect to requested page
$redirect = $_SESSION["login_redirect"] ?? "dashboard";
unset($_SESSION["login_redirect"]);

if ($redirect === "booking") {
    header("Location: ../customer/booking.php");
    exit();
}

header("Location: ../customer/dashboard.php");
exit();
?>