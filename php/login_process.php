<?php

session_start();

include "../config/database.php";


if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../html/login.html");
    exit();
}


$email = trim($_POST["email"] ?? "");

$password = $_POST["password"] ?? "";

$loginRole = strtolower(trim($_POST["login_role"] ?? ""));


if ($email === "" || $password === "") {

    die("
        <h2>Login Failed</h2>
        <p>Please enter email and password.</p>
        <a href='../html/login.html'>Try Again</a>
    ");
}


/* Customer, Provider and Admin are allowed */

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


/* Find user */

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


/* Check selected role */

$userRole = strtolower(trim($user["role"]));


if ($userRole !== $loginRole) {

    die("
        <h2>Login Failed</h2>
        <p>The selected login type does not match this account.</p>
        <a href='../html/login.html'>Try Again</a>
    ");
}


/* Check password */

if (!password_verify($password, $user["password"])) {

    die("
        <h2>Login Failed</h2>
        <p>Incorrect password.</p>
        <a href='../html/login.html'>Try Again</a>
    ");
}


/* Create session */

$_SESSION["user_id"] = (int)$user["id"];

$_SESSION["user_name"] = $user["name"];

$_SESSION["user_email"] = $user["email"];

$_SESSION["user_phone"] = $user["phone"];

$_SESSION["user_role"] = $user["role"];


/* Customer */

if ($userRole === "customer") {

    header("Location: ../customer/dashboard.php");

    exit();
}


/* Provider */

if ($userRole === "provider") {

    header("Location: ../provider/dashboard.php");

    exit();
}


/* Admin */

if ($userRole === "admin") {

    header("Location: ../admin/dashboard.php");

    exit();
}


/* Safety */

session_destroy();

die("
    <h2>Login Failed</h2>
    <p>Invalid account role.</p>
    <a href='../html/login.html'>Try Again</a>
");


?>