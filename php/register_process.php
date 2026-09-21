<?php

session_start();
include "../config/database.php";


function showMessage($title, $message, $color) {
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <title>$title | HomeServe</title>

        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f5f7fb;
                text-align: center;
                padding-top: 100px;
            }

            .box {
                background: white;
                max-width: 500px;
                margin: auto;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 4px 20px #ddd;
            }

            h1 {
                color: $color;
            }

            a {
                display: inline-block;
                margin-top: 20px;
                background: #153b6d;
                color: white;
                padding: 12px 20px;
                text-decoration: none;
                border-radius: 6px;
            }
        </style>
    </head>

    <body>

        <div class='box'>

            <h1>$title</h1>

            <p>$message</p>

            <a href='../html/login.html'>Go to Login</a>

        </div>

    </body>
    </html>
    ";

    exit();
}


/* Only POST request allowed */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../html/register.html");

    exit();
}


/* Get form data */

$name = trim($_POST["full_name"] ?? "");

$email = trim($_POST["email"] ?? "");

$phone = trim($_POST["phone"] ?? "");

$password = $_POST["password"] ?? "";

$confirmPassword = $_POST["confirm_password"] ?? "";

$role = trim($_POST["role"] ?? "");

$serviceId = (int)($_POST["service_id"] ?? 0);


/* Required fields */

if (
    $name === "" ||
    $email === "" ||
    $phone === "" ||
    $password === "" ||
    $confirmPassword === "" ||
    $role === ""
) {

    showMessage(
        "Registration Error",
        "Please fill all required fields.",
        "red"
    );
}


/* Email validation */

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    showMessage(
        "Registration Error",
        "Please enter a valid email address.",
        "red"
    );
}


/* Password validation */

if (strlen($password) < 6) {

    showMessage(
        "Registration Error",
        "Password must contain at least 6 characters.",
        "red"
    );
}


if ($password !== $confirmPassword) {

    showMessage(
        "Registration Error",
        "Passwords do not match.",
        "red"
    );
}


/* Role validation */

if ($role !== "customer" && $role !== "provider") {

    showMessage(
        "Registration Error",
        "Please select Customer or Service Provider.",
        "red"
    );
}


/* Phone validation */

if (!preg_match("/^[0-9]{10}$/", $phone)) {

    showMessage(
        "Registration Error",
        "Please enter a valid 10-digit mobile number.",
        "red"
    );
}


/* Provider must select service */

if ($role === "provider" && $serviceId <= 0) {

    showMessage(
        "Registration Error",
        "Please select a service.",
        "red"
    );
}


/* Customer doesn't need service */

if ($role === "customer") {

    $serviceId = 0;
}


$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);


try {

    $conn->begin_transaction();


    /* Check email */

    $check = $conn->prepare(
        "SELECT id FROM users WHERE email = ?"
    );

    if (!$check) {

        throw new Exception(
            "Email check failed: " . $conn->error
        );
    }


    $check->bind_param(
        "s",
        $email
    );

    $check->execute();

    $check->store_result();


    if ($check->num_rows > 0) {

        $check->close();

        $conn->rollback();

        showMessage(
            "Registration Error",
            "This email is already registered. Please use another email.",
            "red"
        );
    }


    $check->close();


    /* Insert user */

    $insert = $conn->prepare(
        "INSERT INTO users
        (name, email, password, phone, role)
        VALUES (?, ?, ?, ?, ?)"
    );


    if (!$insert) {

        throw new Exception(
            "User insert query failed: " . $conn->error
        );
    }


    $insert->bind_param(
        "sssss",
        $name,
        $email,
        $hashedPassword,
        $phone,
        $role
    );


    if (!$insert->execute()) {

        throw new Exception(
            "User insert failed: " . $insert->error
        );
    }


    $userId = $insert->insert_id;

    $insert->close();


    /* Provider registration */

    if ($role === "provider") {


        /* Check service exists */

        $serviceCheck = $conn->prepare(
            "SELECT id FROM services WHERE id = ?"
        );


        if (!$serviceCheck) {

            throw new Exception(
                "Service check failed: " . $conn->error
            );
        }


        $serviceCheck->bind_param(
            "i",
            $serviceId
        );


        $serviceCheck->execute();

        $serviceCheck->store_result();


        if ($serviceCheck->num_rows === 0) {

            $serviceCheck->close();

            throw new Exception(
                "Selected service does not exist."
            );
        }


        $serviceCheck->close();


        /* Insert provider with service */

        $provider = $conn->prepare(
            "INSERT INTO providers
            (user_id, service_id)
            VALUES (?, ?)"
        );


        if (!$provider) {

            throw new Exception(
                "Provider query failed: " . $conn->error
            );
        }


        $provider->bind_param(
            "ii",
            $userId,
            $serviceId
        );


        if (!$provider->execute()) {

            throw new Exception(
                "Provider insert failed: " . $provider->error
            );
        }


        $provider->close();
    }


    /* Save everything */

    $conn->commit();


    showMessage(
        "Account Created Successfully!",
        "Your HomeServe account has been created successfully.",
        "green"
    );


} catch (Exception $e) {

    $conn->rollback();

    showMessage(
        "Registration Error",
        $e->getMessage(),
        "red"
    );
}


$conn->close();

?>