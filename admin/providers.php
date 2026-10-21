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
    strtolower(trim($_SESSION["user_role"])) !== "admin"
) {
    die("Access denied. Admin account required.");
}


/* =========================
   APPROVE / REJECT PROVIDER
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $providerId = (int) ($_POST["provider_id"] ?? 0);
    $action = trim($_POST["action"] ?? "");

    if ($providerId <= 0) {
        die("Invalid provider ID.");
    }

    /* =========================
       APPROVE PROVIDER
    ========================= */

    if ($action === "approve") {

        $update = $conn->prepare(
            "UPDATE providers
             SET status = 'active'
             WHERE id = ?"
        );

        if (!$update) {
            die("Approve query failed: " . $conn->error);
        }

        $update->bind_param("i", $providerId);

        if (!$update->execute()) {
            die("Provider could not be approved: " . $update->error);
        }

        $update->close();

        header("Location: providers.php");
        exit();
    }


    /* =========================
       REJECT PROVIDER
    ========================= */

    if ($action === "reject") {

        $update = $conn->prepare(
            "UPDATE providers
             SET status = 'rejected'
             WHERE id = ?"
        );

        if (!$update) {
            die("Reject query failed: " . $conn->error);
        }

        $update->bind_param("i", $providerId);

        if (!$update->execute()) {
            die("Provider could not be rejected: " . $update->error);
        }

        $update->close();

        header("Location: providers.php");
        exit();
    }
}


/* =========================
   GET ALL PROVIDERS
========================= */

$query = "
    SELECT
        providers.id,
        providers.user_id,
        providers.experience,
        providers.location,
        providers.about,
        providers.status,

        users.name AS provider_name,
        users.email AS provider_email,
        users.phone AS provider_phone,

        services.name AS service_name

    FROM providers

    LEFT JOIN users
        ON providers.user_id = users.id

    LEFT JOIN services
        ON providers.service_id = services.id

    ORDER BY providers.id DESC
";

$result = $conn->query($query);

if (!$result) {
    die("Providers query failed: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Providers | HomeServe</title>

    <style>
<style>
/* ==========================================
   HOMESERVE - MODERN ADMIN PROVIDERS PAGE
========================================== */

:root {
    --primary: #4f46e5;
    --primary-dark: #3730a3;
    --navy: #111827;
    --background: #f5f7fc;
    --surface: #ffffff;
    --text: #1e293b;
    --muted: #64748b;
    --border: #e2e8f0;
    --green: #15803d;
    --red: #dc2626;
    --orange: #ea580c;
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
    animation: pageEnter 0.45s ease;
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
    letter-spacing: -0.6px;
    white-space: nowrap;
}

nav div {
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
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
    color: #ffffff;
    transform: translateY(-1px);
}

/* MAIN CONTENT */

.container {
    width: 92%;
    max-width: 1700px;
    margin: 35px auto 60px;
}

.back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 22px;
    padding: 10px 16px;
    background: #ffffff;
    color: var(--primary);
    border: 1px solid #e0e7ff;
    text-decoration: none;
    border-radius: 10px;
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
    margin: 0 0 25px;
    color: var(--navy);
    font-size: clamp(25px, 3vw, 34px);
    font-weight: 800;
    letter-spacing: -0.8px;
}

h1::after {
    content: "";
    display: block;
    width: 58px;
    height: 4px;
    margin-top: 10px;
    background: var(--primary);
    border-radius: 10px;
}

/* PROVIDER TABLE CONTAINER */

.table-box {
    background: var(--surface);
    padding: 24px;
    border: 1px solid #edf0f7;
    border-radius: 18px;
    box-shadow: var(--shadow);
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* TABLE */

table {
    width: 100%;
    min-width: 1250px;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 0;
    font-size: 14px;
}

thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #f1f5f9;
    color: #475569;
    padding: 16px 14px;
    text-align: left;
    white-space: nowrap;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.7px;
    text-transform: uppercase;
    border-bottom: 1px solid var(--border);
}

thead th:first-child {
    border-radius: 9px 0 0 0;
}

thead th:last-child {
    border-radius: 0 9px 0 0;
}

tbody td {
    padding: 17px 14px;
    color: #475569;
    border-bottom: 1px solid #edf0f5;
    vertical-align: middle;
    overflow-wrap: anywhere;
    transition: background 0.2s ease;
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

/* PROVIDER ID */

tbody td:first-child {
    color: var(--primary);
    font-weight: 800;
}

/* STATUS BADGES */

.status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 11px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 800;
    text-transform: capitalize;
    white-space: nowrap;
    border: 1px solid transparent;
}

.status::before {
    content: "";
    width: 7px;
    height: 7px;
    display: inline-block;
    border-radius: 50%;
    background: currentColor;
}

.pending {
    background: #fffbeb;
    color: #b45309;
    border-color: #fde68a;
}

.active,
.approved {
    background: #ecfdf5;
    color: #047857;
    border-color: #a7f3d0;
}

.rejected {
    background: #fef2f2;
    color: #b91c1c;
    border-color: #fecaca;
}

.inactive {
    background: #f1f5f9;
    color: #475569;
    border-color: #cbd5e1;
}

/* ABOUT TEXT */

.about {
    max-width: 260px;
    min-width: 180px;
    line-height: 1.7;
    color: var(--muted);
    overflow-wrap: anywhere;
}

/* ACTION BUTTONS */

.actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    min-width: 180px;
}

.actions form {
    margin: 0;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border: none;
    padding: 10px 14px;
    border-radius: 9px;
    color: white;
    font-weight: 700;
    cursor: pointer;
    font-size: 12px;
    font-family: inherit;
    white-space: nowrap;
    box-shadow: 0 3px 8px rgba(15, 23, 42, 0.08);
    transition: transform 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(15, 23, 42, 0.15);
}

.btn:active {
    transform: scale(0.97);
}

.btn:focus-visible,
nav a:focus-visible,
.back:focus-visible {
    outline: 3px solid #a5b4fc;
    outline-offset: 3px;
}

.approve-btn {
    background: #15803d;
}

.approve-btn:hover {
    background: #166534;
}

.reject-btn {
    background: #dc2626;
}

.reject-btn:hover {
    background: #b91c1c;
}

/* ACTION RESULT TEXT */

.active-text {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #15803d;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    padding: 7px 11px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 800;
    white-space: nowrap;
}

.rejected-text {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #b91c1c;
    background: #fef2f2;
    border: 1px solid #fecaca;
    padding: 7px 11px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 800;
    white-space: nowrap;
}

/* EMPTY STATE */

.empty {
    padding: 65px 20px;
    text-align: center;
    color: var(--muted);
}

.empty h3 {
    margin: 0 0 10px;
    color: var(--navy);
    font-size: 22px;
}

.empty p {
    margin: 0;
    font-size: 14px;
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

@media (max-width: 900px) {
    nav {
        padding: 17px 4%;
    }

    nav strong {
        font-size: 21px;
    }

    .container {
        width: 94%;
        margin-top: 25px;
    }

    .table-box {
        padding: 15px;
        border-radius: 14px;
    }
}

@media (max-width: 600px) {
    nav {
        align-items: flex-start;
        flex-direction: column;
        gap: 12px;
    }

    nav div {
        width: 100%;
        gap: 5px;
    }

    nav a {
        margin: 0;
        padding: 8px 10px;
        font-size: 12px;
    }

    .container {
        width: 95%;
        margin: 22px auto 35px;
    }

    h1 {
        font-size: 26px;
    }

    .table-box {
        padding: 10px;
    }

    table {
        min-width: 1150px;
    }

    .back {
        font-size: 13px;
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
git
    </style>

</head>

<body>


<nav>

    <strong>HomeServe - Admin</strong>

    <div>

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="users.php">
            Users
        </a>

        <a href="providers.php">
            Providers
        </a>

        <a href="booking.php">
            Bookings
        </a>

        <a href="services.php">
            Services
        </a>

        <a href="../php/logout.php">
            Logout
        </a>

    </div>

</nav>


<div class="container">


    <a class="back" href="dashboard.php">
        ← Back to Dashboard
    </a>


    <h1>
        Manage Providers
    </h1>


    <div class="table-box">


        <?php if ($result->num_rows > 0) { ?>


            <table>


                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Name</th>

                        <th>Email</th>

                        <th>Phone</th>

                        <th>Service</th>

                        <th>Experience</th>

                        <th>Location</th>

                        <th>About</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>


                    <?php while ($provider = $result->fetch_assoc()) { ?>


                        <tr>


                            <td>

                                <?php
                                echo (int) $provider["id"];
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $provider["provider_name"]
                                    ?? "Not available"
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $provider["provider_email"]
                                    ?? "Not available"
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $provider["provider_phone"]
                                    ?? "Not available"
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $provider["service_name"]
                                    ?? "Not assigned"
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $provider["experience"]
                                    ?? "Not available"
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $provider["location"]
                                    ?? "Not available"
                                );
                                ?>

                            </td>


                            <td class="about">

                                <?php
                                echo htmlspecialchars(
                                    $provider["about"]
                                    ?? "No information"
                                );
                                ?>

                            </td>


                            <td>


                                <?php

                                $status = strtolower(
                                    trim(
                                        $provider["status"]
                                        ?? "pending"
                                    )
                                );

                                ?>


                                <span class="status <?php echo htmlspecialchars($status); ?>">

                                    <?php
                                    echo ucfirst(
                                        htmlspecialchars($status)
                                    );
                                    ?>

                                </span>


                            </td>


                            <td>


                                <?php if ($status === "pending") { ?>


                                    <div class="actions">


                                        <!-- APPROVE -->

                                        <form
                                            method="POST"
                                            action="providers.php"
                                            onsubmit="return confirm('Are you sure you want to approve this provider?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="provider_id"
                                                value="<?php echo (int) $provider["id"]; ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="approve"
                                            >

                                            <button
                                                type="submit"
                                                class="btn approve-btn"
                                            >
                                                Approve
                                            </button>

                                        </form>


                                        <!-- REJECT -->

                                        <form
                                            method="POST"
                                            action="providers.php"
                                            onsubmit="return confirm('Are you sure you want to reject this provider?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="provider_id"
                                                value="<?php echo (int) $provider["id"]; ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="reject"
                                            >

                                            <button
                                                type="submit"
                                                class="btn reject-btn"
                                            >
                                                Reject
                                            </button>

                                        </form>


                                    </div>


                                <?php } elseif ($status === "active") { ?>


                                    <span class="active-text">
                                        ✓ Approved
                                    </span>


                                <?php } elseif ($status === "rejected") { ?>


                                    <span class="rejected-text">
                                        ✕ Rejected
                                    </span>


                                <?php } else { ?>


                                    <span>
                                        No Action
                                    </span>


                                <?php } ?>


                            </td>


                        </tr>


                    <?php } ?>


                </tbody>


            </table>


        <?php } else { ?>


            <div class="empty">

                <h3>
                    No Providers Found
                </h3>

                <p>
                    There are currently no provider profiles
                    in the system.
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