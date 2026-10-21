<?php

session_start();
include "../config/database.php";

/* =========================
   ADMIN LOGIN CHECK
========================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../html/login.html");
    exit();
}

if (
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    die("Access denied. Admin account required.");
}

/* =========================
   GET ALL USERS
========================= */

$query = "
    SELECT
        id,
        name,
        email,
        phone,
        role
    FROM users
    ORDER BY id DESC
";

$result = $conn->query($query);

if (!$result) {
    die("Users query failed: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Users | HomeServe</title>

    <style>

      /* ==========================================
   HOMESERVE - MODERN ADMIN USERS PAGE
   CSS-only UI upgrade
========================================== */

* {
    box-sizing: border-box;
}

:root {
    --primary: #4f46e5;
    --primary-dark: #3730a3;
    --text: #172033;
    --muted: #7b8498;
    --border: #e8ebf3;
    --surface: #ffffff;
    --background: #f5f7fc;
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: var(--background);
    color: var(--text);
    line-height: 1.6;
}

/* NAVIGATION */

nav {
    position: sticky;
    top: 0;
    z-index: 100;
    background: rgba(21, 39, 78, 0.97);
    backdrop-filter: blur(14px);
    color: white;
    padding: 18px 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    box-shadow: 0 4px 20px rgba(19, 35, 72, 0.12);
}

nav strong {
    font-size: 25px;
    font-weight: 800;
    letter-spacing: -0.6px;
}

nav a {
    color: #e5eaff;
    text-decoration: none;
    margin-left: 20px;
    font-size: 14px;
    font-weight: 600;
    transition: color 0.25s ease;
}

nav a:hover {
    color: #a5b4fc;
}

/* MAIN CONTAINER */

.container {
    width: 92%;
    max-width: 1400px;
    margin: 42px auto;
    animation: pageEnter 0.45s ease both;
}

/* PAGE HEADING */

h1 {
    color: var(--text);
    font-size: clamp(27px, 3vw, 35px);
    line-height: 1.25;
    letter-spacing: -1px;
    margin: 0 0 10px;
    font-weight: 800;
}

/* OPTIONAL PAGE DESCRIPTION */

.container > p {
    color: var(--muted);
    font-size: 14px;
}

/* TABLE CARD */

.table-box {
    position: relative;
    background: var(--surface);
    padding: 28px;
    margin-top: 25px;
    border: 1px solid rgba(226, 231, 241, 0.9);
    border-radius: 18px;
    box-shadow:
        0 8px 30px rgba(28, 43, 78, 0.045),
        0 1px 3px rgba(28, 43, 78, 0.025);
    overflow-x: auto;
    transition: box-shadow 0.3s ease;
}

.table-box:hover {
    box-shadow: 0 12px 38px rgba(28, 43, 78, 0.075);
}

/* TABLE */

table {
    width: 100%;
    min-width: 650px;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 12px;
    font-size: 14px;
}

/* TABLE HEADER */

th {
    background: #f3f5fb;
    color: #606b82;
    padding: 16px 18px;
    text-align: left;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    white-space: nowrap;
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
}

th:first-child {
    border-top-left-radius: 9px;
}

th:last-child {
    border-top-right-radius: 9px;
}

/* TABLE BODY */

td {
    padding: 17px 18px;
    color: #424c61;
    border-bottom: 1px solid #edf0f6;
    vertical-align: middle;
    transition: background 0.2s ease;
}

tbody tr {
    transition: background 0.2s ease;
}

tbody tr:hover td {
    background: #f7f8ff;
}

tbody tr:last-child td {
    border-bottom: none;
}

/* USER ROLE */

.role {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    text-transform: capitalize;
    padding: 5px 11px;
    border-radius: 7px;
    background: #eef2ff;
    color: #4338ca;
    white-space: nowrap;
}

/* LINKS INSIDE THE TABLE */

td a {
    color: var(--primary);
    font-weight: 700;
    text-decoration: none;
    transition: color 0.2s ease;
}

td a:hover {
    color: var(--primary-dark);
    text-decoration: underline;
}

/* BUTTONS - IF PRESENT */

button,
input[type="submit"] {
    font-family: inherit;
    font-weight: 700;
    cursor: pointer;
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease,
        background 0.2s ease;
}

button:hover,
input[type="submit"]:hover {
    transform: translateY(-1px);
}

button:active,
input[type="submit"]:active {
    transform: scale(0.98);
}

/* BACK BUTTON */

.back {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 22px;
    background: linear-gradient(135deg, #6366f1, #4338ca);
    color: white;
    padding: 11px 19px;
    text-decoration: none;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 700;
    box-shadow: 0 5px 14px rgba(79, 70, 229, 0.2);
    transition:
        transform 0.25s ease,
        box-shadow 0.25s ease;
}

.back:hover {
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 9px 20px rgba(79, 70, 229, 0.3);
}

/* EMPTY STATE */

.empty {
    text-align: center;
    padding: 45px 20px;
    color: var(--muted);
    font-size: 14px;
    background: #fafbff;
    border-radius: 12px;
}

/* KEYBOARD ACCESSIBILITY */

a:focus-visible,
button:focus-visible,
input:focus-visible {
    outline: 3px solid rgba(99, 102, 241, 0.35);
    outline-offset: 3px;
}

/* PAGE ANIMATION */

@keyframes pageEnter {
    from {
        opacity: 0;
        transform: translateY(8px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* RESPONSIVE DESIGN */

@media (max-width: 768px) {
    nav {
        padding: 16px 5%;
        flex-wrap: wrap;
    }

    nav strong {
        font-size: 22px;
    }

    nav a {
        margin-left: 0;
        margin-right: 14px;
    }

    .container {
        width: 94%;
        margin: 28px auto;
    }

    h1 {
        font-size: 27px;
    }

    .table-box {
        padding: 16px;
        border-radius: 13px;
    }

    th,
    td {
        padding: 13px 14px;
    }
}

@media (max-width: 480px) {
    nav {
        align-items: flex-start;
        flex-direction: column;
        gap: 10px;
    }

    .container {
        margin-top: 22px;
    }

    .table-box {
        padding: 11px;
    }

    table {
        font-size: 13px;
    }
}

/* REDUCE ANIMATION FOR USERS WHO PREFER IT */

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        scroll-behavior: auto !important;
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}

    </style>

</head>

<body>

<nav>

    <strong>HomeServe - Admin</strong>

    <div>

        <a href="dashboard.php">Dashboard</a>

        <a href="users.php">Users</a>

        <a href="providers.php">Providers</a>

        <a href="booking.php">Bookings</a>

        <a href="servises.php">Services</a>

        <a href="../php/logout.php">Logout</a>

    </div>

</nav>

<div class="container">

    <a class="back" href="dashboard.php">
        ← Back to Dashboard
    </a>

    <h1>Manage Users</h1>

    <div class="table-box">

        <?php if ($result->num_rows > 0) { ?>

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                    </tr>

                </thead>

                <tbody>

                    <?php while ($user = $result->fetch_assoc()) { ?>

                        <tr>

                            <td>
                                <?php echo (int)$user["id"]; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($user["name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($user["email"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($user["phone"]); ?>
                            </td>

                            <td class="role">
                                <?php echo htmlspecialchars($user["role"]); ?>
                            </td>

                        </tr>

                    <?php } ?>

                </tbody>

            </table>

        <?php } else { ?>

            <div class="empty">
                <h3>No Users Found</h3>
                <p>There are currently no users in the system.</p>
            </div>

        <?php } ?>

    </div>

</div>

</body>

</html>

<?php
$conn->close();
?>