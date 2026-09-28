<?php

session_start();

include "../config/database.php";


$role = strtolower(
    trim($_GET["role"] ?? "")
);


/* =====================================================
   CUSTOMER LOGOUT
===================================================== */

if ($role === "customer") {

    unset($_SESSION["customer_id"]);
    unset($_SESSION["customer_name"]);
    unset($_SESSION["customer_email"]);
    unset($_SESSION["customer_phone"]);


    /*
     * If provider is still logged in,
     * restore provider as active user.
     */

    if (isset($_SESSION["provider_id"])) {

        $providerId =
            (int) $_SESSION["provider_id"];

        $stmt = $conn->prepare(
            "SELECT id, name, email, phone
             FROM users
             WHERE id = ?
             AND role = 'provider'
             LIMIT 1"
        );

        $stmt->bind_param(
            "i",
            $providerId
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $provider = $result->fetch_assoc();

            $_SESSION["user_id"] =
                (int) $provider["id"];

            $_SESSION["user_name"] =
                $provider["name"];

            $_SESSION["user_email"] =
                $provider["email"];

            $_SESSION["user_phone"] =
                $provider["phone"];

            $_SESSION["user_role"] =
                "provider";
        }

        $stmt->close();

        header("Location: ../provider/dashboard.php");
        exit();
    }


    /* No provider session */
    unset($_SESSION["user_id"]);
    unset($_SESSION["user_name"]);
    unset($_SESSION["user_email"]);
    unset($_SESSION["user_phone"]);
    unset($_SESSION["user_role"]);

    header("Location: ../html/login.html");
    exit();
}


/* =====================================================
   PROVIDER LOGOUT
===================================================== */

if ($role === "provider") {

    unset($_SESSION["provider_id"]);
    unset($_SESSION["provider_name"]);
    unset($_SESSION["provider_email"]);
    unset($_SESSION["provider_phone"]);


    /*
     * If customer is still logged in,
     * restore customer as active user.
     */

    if (isset($_SESSION["customer_id"])) {

        $customerId =
            (int) $_SESSION["customer_id"];

        $stmt = $conn->prepare(
            "SELECT id, name, email, phone
             FROM users
             WHERE id = ?
             AND role = 'customer'
             LIMIT 1"
        );

        $stmt->bind_param(
            "i",
            $customerId
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $customer = $result->fetch_assoc();

            $_SESSION["user_id"] =
                (int) $customer["id"];

            $_SESSION["user_name"] =
                $customer["name"];

            $_SESSION["user_email"] =
                $customer["email"];

            $_SESSION["user_phone"] =
                $customer["phone"];

            $_SESSION["user_role"] =
                "customer";
        }

        $stmt->close();

        header("Location: ../customer/dashboard.php");
        exit();
    }


    /* No customer session */
    unset($_SESSION["user_id"]);
    unset($_SESSION["user_name"]);
    unset($_SESSION["user_email"]);
    unset($_SESSION["user_phone"]);
    unset($_SESSION["user_role"]);

    header("Location: ../html/provider-login.html");
    exit();
}


/* =====================================================
   ADMIN / NORMAL LOGOUT
===================================================== */

$_SESSION = array();

if (ini_get("session.use_cookies")) {

    $params =
        session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

header("Location: ../html/login.html");
exit();

?>