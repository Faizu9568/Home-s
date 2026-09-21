<?php

session_start();

include "../config/database.php";

/* Only POST request allowed */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../html/login.html");
    exit();
}


/* Get OTP */
$otp = trim($_POST["otp"] ?? "");


/* Validate OTP */
if ($otp === "" || !preg_match("/^[0-9]{6}$/", $otp)) {
    die("
        <h2>Invalid OTP</h2>
        <p>Please enter a valid 6 digit OTP.</p>
        <a href='../html/verify_otp.html'>Try Again</a>
    ");
}


/*
|--------------------------------------------------------------------------
| NEW CUSTOMER
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION["new_customer"]) &&
    $_SESSION["new_customer"] === true &&
    isset($_SESSION["new_customer_phone"]) &&
    isset($_SESSION["test_otp"])
) {

    $phone = $_SESSION["new_customer_phone"];
    $sessionOtp = $_SESSION["test_otp"];


    /* Check OTP */
    if ((string)$sessionOtp !== (string)$otp) {

        die("
            <h2>Invalid OTP</h2>
            <p>The OTP you entered is incorrect.</p>
            <a href='../html/verify_otp.html'>Try Again</a>
        ");
    }


    /*
     * Check if phone already exists.
     * This prevents duplicate customer accounts.
     */

    $check = $conn->prepare(
        "SELECT id, name, email, phone, role
         FROM users
         WHERE phone = ?
         LIMIT 1"
    );

    if (!$check) {
        die("Database error: " . $conn->error);
    }

    $check->bind_param("s", $phone);
    $check->execute();

    $result = $check->get_result();


    /*
     * If account already exists
     */

    if ($result->num_rows > 0) {

        $user = $result->fetch_assoc();

        $check->close();


        if (strtolower(trim($user["role"])) !== "customer") {
            die("This mobile number belongs to another account type.");
        }

    }


    /*
     * New customer account
     */

    else {

        $check->close();


        /*
         * Temporary name
         */
        $name = "Customer";


        /*
         * Temporary unique email.
         *
         * Email is required by database,
         * so we create an internal placeholder.
         */

        $email =
            "customer_" .
            $phone .
            "_" .
            bin2hex(random_bytes(3)) .
            "@homeserve.local";


        /*
         * Generate random password.
         *
         * Customer will login using OTP,
         * so they don't need to know this password.
         */

        $randomPassword =
            bin2hex(random_bytes(16));


        $hashedPassword =
            password_hash(
                $randomPassword,
                PASSWORD_DEFAULT
            );


        $role = "customer";


        /*
         * Insert customer
         */

        $stmt = $conn->prepare(
            "INSERT INTO users
            (name, email, password, phone, role)
            VALUES (?, ?, ?, ?, ?)"
        );


        if (!$stmt) {
            die("Database error: " . $conn->error);
        }


        $stmt->bind_param(
            "sssss",
            $name,
            $email,
            $hashedPassword,
            $phone,
            $role
        );


        if (!$stmt->execute()) {

            $error = $stmt->error;

            $stmt->close();

            die("
                <h2>Account Creation Failed</h2>
                <p>$error</p>
                <a href='../html/login.html'>Try Again</a>
            ");
        }


        $newUserId = $stmt->insert_id;

        $stmt->close();


        /*
         * Get newly created customer
         */

        $find = $conn->prepare(
            "SELECT id, name, email, phone, role
             FROM users
             WHERE id = ?
             LIMIT 1"
        );


        if (!$find) {
            die("Database error: " . $conn->error);
        }


        $find->bind_param(
            "i",
            $newUserId
        );

        $find->execute();

        $result = $find->get_result();

        $user = $result->fetch_assoc();

        $find->close();
    }

}


/*
|--------------------------------------------------------------------------
| EXISTING CUSTOMER
|--------------------------------------------------------------------------
*/

else {

    /*
     * Check OTP session
     */

    if (!isset($_SESSION["otp_user_id"])) {

        die("
            <h2>OTP Session Expired</h2>
            <p>Please login again.</p>
            <a href='../html/login.html'>Login Again</a>
        ");
    }


    $userId =
        (int) $_SESSION["otp_user_id"];


    /*
     * Get customer
     */

    $stmt = $conn->prepare(
        "SELECT id, name, email, phone, role, otp, otp_expiry
         FROM users
         WHERE id = ?
         LIMIT 1"
    );


    if (!$stmt) {
        die("Database error: " . $conn->error);
    }


    $stmt->bind_param(
        "i",
        $userId
    );

    $stmt->execute();

    $result = $stmt->get_result();


    if ($result->num_rows === 0) {

        $stmt->close();

        die("
            <h2>User Not Found</h2>
            <a href='../html/login.html'>Login Again</a>
        ");
    }


    $user = $result->fetch_assoc();

    $stmt->close();


    /*
     * Check OTP
     */

    if ((string)$user["otp"] !== (string)$otp) {

        die("
            <h2>Invalid OTP</h2>
            <p>The OTP you entered is incorrect.</p>
            <a href='../html/verify_otp.html'>Try Again</a>
        ");
    }


    /*
     * Check OTP expiry
     */

    if (
        empty($user["otp_expiry"]) ||
        strtotime($user["otp_expiry"]) < time()
    ) {

        die("
            <h2>OTP Expired</h2>
            <p>Please request a new OTP.</p>
            <a href='../html/login.html'>Login Again</a>
        ");
    }


    /*
     * Customer only
     */

    if (
        strtolower(trim($user["role"])) !== "customer"
    ) {

        die("
            <h2>Access Denied</h2>
            <p>Only customers can use OTP login.</p>
            <a href='../html/login.html'>Login Again</a>
        ");
    }


    /*
     * Clear OTP
     */

    $clear = $conn->prepare(
        "UPDATE users
         SET otp = NULL,
             otp_expiry = NULL
         WHERE id = ?"
    );


    if ($clear) {

        $clear->bind_param(
            "i",
            $userId
        );

        $clear->execute();

        $clear->close();
    }
}


/*
|--------------------------------------------------------------------------
| CREATE LOGIN SESSION
|--------------------------------------------------------------------------
*/

$_SESSION["user_id"] =
    (int) $user["id"];

$_SESSION["user_name"] =
    $user["name"];

$_SESSION["user_email"] =
    $user["email"];

$_SESSION["user_phone"] =
    $user["phone"];

$_SESSION["user_role"] =
    $user["role"];


/*
|--------------------------------------------------------------------------
| REMEMBER DESTINATION
|--------------------------------------------------------------------------
*/

$redirect =
    $_SESSION["login_redirect"] ?? "dashboard";


/*
|--------------------------------------------------------------------------
| CLEAR TEMPORARY OTP SESSION
|--------------------------------------------------------------------------
*/

unset($_SESSION["otp_user_id"]);
unset($_SESSION["otp_phone"]);
unset($_SESSION["test_otp"]);
unset($_SESSION["new_customer"]);
unset($_SESSION["new_customer_phone"]);
unset($_SESSION["login_redirect"]);


/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

if ($redirect === "booking") {

    header(
        "Location: ../html/booking.html"
    );

    exit();
}


/*
|--------------------------------------------------------------------------
| CUSTOMER DASHBOARD
|--------------------------------------------------------------------------
*/

header(
    "Location: ../customer/dashboard.php"
);

exit();

?>