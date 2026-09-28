<?php

session_start();

include "../config/database.php";


/* =========================
   REQUEST CHECK
========================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../html/login.html");
    exit();
}


/* =========================
   GET LOGIN DATA
========================= */

$email = trim($_POST["email"] ?? "");

$password = $_POST["password"] ?? "";

$loginRole = strtolower(
    trim($_POST["login_role"] ?? "")
);


/* =========================
   BASIC VALIDATION
========================= */

if ($email === "" || $password === "") {

    die("
        <h2>Login Failed</h2>
        <p>Please enter email and password.</p>
        <a href='../html/login.html'>Try Again</a>
    ");
}


/* =========================
   VALID LOGIN ROLE
========================= */

if (
    $loginRole !== "customer" &&
    $loginRole !== "provider" &&
    $loginRole !== "admin"
) {

    die("
        <h2>Login Failed</h2>
        <p>Please select a valid login type.</p>
        <a href='../html/login.html'>Go Back</a>
    ");
}


/* =========================
   FIND USER
========================= */

$stmt = $conn->prepare(
    "SELECT id, name, email, password, phone, role
     FROM users
     WHERE email = ?
     LIMIT 1"
);

if (!$stmt) {

    die("Database query failed: " . $conn->error);
}

$stmt->bind_param("s", $email);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows === 0) {

    $stmt->close();

    die("
        <h2>Login Failed</h2>
        <p>No account found with this email.</p>
        <a href='../html/login.html'>Try Again</a>
    ");
}


$user = $result->fetch_assoc();

$stmt->close();


/* =========================
   CHECK ROLE
========================= */

$userRole = strtolower(
    trim($user["role"])
);


if ($userRole !== $loginRole) {

    die("
        <h2>Login Failed</h2>
        <p>The selected login type does not match this account.</p>
        <a href='../html/login.html'>Try Again</a>
    ");
}


/* =========================
   CHECK PASSWORD
========================= */

if (!password_verify($password, $user["password"])) {

    die("
        <h2>Login Failed</h2>
        <p>Incorrect password.</p>
        <a href='../html/login.html'>Try Again</a>
    ");
}


/* =====================================================
   IMPORTANT:
   DO NOT DESTROY EXISTING SESSION
   CUSTOMER + PROVIDER CAN EXIST TOGETHER
===================================================== */


/* =========================
   CUSTOMER LOGIN
========================= */

if ($userRole === "customer") {

    $_SESSION["customer_id"] =
        (int) $user["id"];

    $_SESSION["customer_name"] =
        $user["name"];

    $_SESSION["customer_email"] =
        $user["email"];

    $_SESSION["customer_phone"] =
        $user["phone"];


    /* Keep old variables for existing customer pages */
    $_SESSION["user_id"] =
        (int) $user["id"];

    $_SESSION["user_name"] =
        $user["name"];

    $_SESSION["user_email"] =
        $user["email"];

    $_SESSION["user_phone"] =
        $user["phone"];

    $_SESSION["user_role"] =
        "customer";


    header("Location: ../customer/dashboard.php");
    exit();
}


/* =========================
   PROVIDER LOGIN
========================= */

if ($userRole === "provider") {

    $_SESSION["provider_id"] =
        (int) $user["id"];

    $_SESSION["provider_name"] =
        $user["name"];

    $_SESSION["provider_email"] =
        $user["email"];

    $_SESSION["provider_phone"] =
        $user["phone"];


    /* Keep old variables for existing provider pages */
    $_SESSION["user_id"] =
        (int) $user["id"];

    $_SESSION["user_name"] =
        $user["name"];

    $_SESSION["user_email"] =
        $user["email"];

    $_SESSION["user_phone"] =
        $user["phone"];

    $_SESSION["user_role"] =
        "provider";


    header("Location: ../provider/dashboard.php");
    exit();
}


/* =========================
   ADMIN LOGIN
========================= */

if ($userRole === "admin") {

    $_SESSION["admin_id"] =
        (int) $user["id"];

    $_SESSION["admin_name"] =
        $user["name"];

    $_SESSION["admin_email"] =
        $user["email"];


    $_SESSION["user_id"] =
        (int) $user["id"];

    $_SESSION["user_name"] =
        $user["name"];

    $_SESSION["user_email"] =
        $user["email"];

    $_SESSION["user_phone"] =
        $user["phone"];

    $_SESSION["user_role"] =
        "admin";


    header("Location: ../admin/dashboard.php");
    exit();
}


/* =========================
   SAFETY
========================= */

die("
    <h2>Login Failed</h2>
    <p>Invalid account role.</p>
    <a href='../html/login.html'>Try Again</a>
");

?>