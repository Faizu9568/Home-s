<?php

session_start();

// Saare session variables remove
$_SESSION = array();

// Session cookie bhi remove
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

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

// Session completely destroy
session_destroy();

// Login page par bhejo
header("Location: ../html/login.html");
exit();

?>