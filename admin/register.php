<?php

require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../html/register.html");
    exit;
}

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$role = $_POST["role"] ?? "";
$password = $_POST["password"] ?? "";

/* Check required fields */

if (
    $name === "" ||
    $email === "" ||
    $phone === "" ||
    $role === "" ||
    $password === ""
) {
    die("Please fill all fields.");
}

/* Check account type */

if ($role !== "customer" && $role !== "provider") {
    die("Invalid account type.");
}

/* Check existing email */

$check = $conn->prepare(
    "SELECT id FROM users WHERE email = ?"
);

$check->bind_param("s", $email);
$check->execute();

$result = $check->get_result();

if ($result->num_rows > 0) {
    die("Email already registered. Please use another email.");
}

/* Secure password */

$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

/* Insert user */

$stmt = $conn->prepare(
    "INSERT INTO users
    (name, email, password, phone, role)
    VALUES (?, ?, ?, ?, ?)"
);

$stmt->bind_param(
    "sssss",
    $name,
    $email,
    $hashedPassword,
    $phone,
    $role
);

if ($stmt->execute()) {

    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Registration Successful</title>

        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f8fafc;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
            }

            .success-box {
                background: white;
                padding: 40px;
                border-radius: 15px;
                text-align: center;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            }

            h1 {
                color: #16a34a;
            }

            a {
                display: inline-block;
                margin-top: 20px;
                padding: 12px 22px;
                background: #2563eb;
                color: white;
                text-decoration: none;
                border-radius: 8px;
            }
        </style>
    </head>

    <body>

        <div class='success-box'>

            <h1>Registration Successful!</h1>

            <p>
                Your account has been created successfully.
            </p>

            <a href='../html/login.html'>
                Login Now
            </a>

        </div>

    </body>
    </html>
    ";

} else {

    echo "Registration failed. Please try again.";

}

$stmt->close();
$check->close();
$conn->close();

?>