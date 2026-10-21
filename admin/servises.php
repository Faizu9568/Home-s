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
   GET SERVICES
========================= */

$query = "SELECT * FROM services ORDER BY id ASC";

$result = $conn->query($query);

if (!$result) {
    die("Services query failed: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Services | HomeServe</title>

    <style>

       <style>
:root {
    --primary: #4f46e5;
    --primary-dark: #3730a3;
    --navy: #111827;
    --background: #f5f7fc;
    --surface: #ffffff;
    --text: #1e293b;
    --muted: #64748b;
    --border: #e2e8f0;
    --shadow: 0 8px 30px rgba(15, 23, 42, 0.07);
}

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI",
                 Roboto, Arial, sans-serif;
    background: var(--background);
    color: var(--text);
    line-height: 1.6;
    animation: pageEnter 0.4s ease;
}

/* NAVIGATION */

nav {
    background: linear-gradient(120deg, #111827, #312e81);
    color: white;
    padding: 17px 5%;
    min-height: 76px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    box-shadow: 0 5px 20px rgba(15, 23, 42, 0.15);
}

nav strong {
    font-size: 23px;
    font-weight: 800;
    letter-spacing: -0.5px;
    white-space: nowrap;
}

nav div {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 7px;
}

nav a {
    color: #e2e8f0;
    text-decoration: none;
    padding: 9px 13px;
    border-radius: 9px;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.25s ease,
                color 0.25s ease,
                transform 0.25s ease;
}

nav a:hover {
    background: rgba(255, 255, 255, 0.13);
    color: white;
    transform: translateY(-1px);
}

/* MAIN CONTENT */

.container {
    width: 92%;
    max-width: 1400px;
    margin: 35px auto 60px;
}

.back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 22px;
    background: white;
    color: var(--primary);
    padding: 10px 16px;
    border: 1px solid #e0e7ff;
    border-radius: 10px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 700;
    transition: all 0.25s ease;
}

.back:hover {
    background: #eef2ff;
    border-color: #c7d2fe;
    transform: translateX(-3px);
}

h1 {
    color: var(--navy);
    font-size: clamp(28px, 3vw, 36px);
    font-weight: 800;
    letter-spacing: -0.8px;
    margin: 0 0 25px;
}

h1::after {
    content: "";
    display: block;
    width: 58px;
    height: 4px;
    background: var(--primary);
    border-radius: 10px;
    margin-top: 10px;
}

/* SERVICES TABLE CARD */

.table-box {
    background: var(--surface);
    padding: 24px;
    border: 1px solid #edf0f7;
    border-radius: 18px;
    box-shadow: var(--shadow);
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

table {
    width: 100%;
    min-width: 650px;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 0;
    font-size: 14px;
}

/* TABLE HEADER */

thead th {
    background: #f1f5f9;
    color: #475569;
    padding: 16px;
    text-align: left;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
}

thead th:first-child {
    border-radius: 9px 0 0 0;
}

thead th:last-child {
    border-radius: 0 9px 0 0;
}

/* TABLE ROWS */

tbody td {
    padding: 18px 16px;
    color: #475569;
    line-height: 1.7;
    border-bottom: 1px solid #edf0f5;
    vertical-align: top;
    overflow-wrap: anywhere;
}

tbody tr {
    transition: background 0.2s ease;
}

tbody tr:hover td {
    background: #f8faff;
}

tbody tr:last-child td {
    border-bottom: none;
}

/* SERVICE ID */

tbody td:first-child {
    width: 80px;
    color: var(--primary);
    font-weight: 800;
    white-space: nowrap;
}

/* SERVICE NAME */

tbody td:nth-child(2) {
    min-width: 200px;
    color: #1e293b;
    font-weight: 750;
}

/* DESCRIPTION */

tbody td:nth-child(3) {
    min-width: 300px;
    color: var(--muted);
}

/* EMPTY STATE */

.empty {
    text-align: center;
    padding: 60px 20px;
    color: var(--muted);
}

.empty::before {
    content: "🛠️";
    display: block;
    font-size: 45px;
    margin-bottom: 12px;
}

.empty h3 {
    color: var(--navy);
    font-size: 21px;
    margin: 0 0 8px;
}

.empty p {
    font-size: 14px;
    margin: 0;
}

/* SCROLLBAR */

.table-box::-webkit-scrollbar {
    height: 9px;
}

.table-box::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.table-box::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.table-box::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* ANIMATION */

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

@media (max-width: 850px) {
    nav {
        padding: 17px 4%;
    }

    .container {
        width: 94%;
        margin-top: 25px;
    }

    .table-box {
        padding: 15px;
    }
}

@media (max-width: 600px) {
    nav {
        align-items: flex-start;
        flex-direction: column;
        gap: 12px;
    }

    nav strong {
        font-size: 21px;
    }

    nav div {
        width: 100%;
        gap: 5px;
    }

    nav a {
        padding: 8px 10px;
        font-size: 12px;
    }

    .container {
        width: 95%;
        margin: 22px auto 35px;
    }

    h1 {
        font-size: 27px;
    }

    .table-box {
        padding: 10px;
        border-radius: 13px;
    }

    table {
        min-width: 650px;
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}
</style>
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

    <h1>Manage Services</h1>

    <div class="table-box">

        <?php if ($result->num_rows > 0) { ?>

            <table>

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Service Name</th>
                        <th>Description</th>

                    </tr>

                </thead>

                <tbody>

                    <?php while ($service = $result->fetch_assoc()) { ?>

                        <tr>

                            <td>
                                <?php
                                echo (int) $service["id"];
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $service["name"] ?? "Not available"
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $service["description"] ?? "No description"
                                );
                                ?>
                            </td>

                        </tr>

                    <?php } ?>

                </tbody>

            </table>

        <?php } else { ?>

            <div class="empty">

                <h3>No Services Found</h3>

                <p>
                    There are currently no services in the system.
                </p>

            </div>

        <?php } ?>

    </div>

</div>

</body>

</html>

<?php

$conn->close();

?>