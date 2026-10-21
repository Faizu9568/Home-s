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

if (($_SESSION["user_role"] ?? "") !== "admin") {
    die("Access denied. Admin account required.");
}

/* =========================
   ADMIN ACTIONS
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $bookingId = (int)($_POST["booking_id"] ?? 0);
    $action = $_POST["action"] ?? "";

    if ($bookingId > 0) {

        if ($action === "accepted") {

            $stmt = $conn->prepare(
                "UPDATE bookings SET status = 'accepted' WHERE id = ?"
            );

            if ($stmt) {
                $stmt->bind_param("i", $bookingId);
                $stmt->execute();
                $stmt->close();
            }

        } elseif ($action === "rejected") {

            $stmt = $conn->prepare(
                "UPDATE bookings SET status = 'rejected' WHERE id = ?"
            );

            if ($stmt) {
                $stmt->bind_param("i", $bookingId);
                $stmt->execute();
                $stmt->close();
            }

        } elseif ($action === "completed") {

            $stmt = $conn->prepare(
                "UPDATE bookings SET status = 'completed' WHERE id = ?"
            );

            if ($stmt) {
                $stmt->bind_param("i", $bookingId);
                $stmt->execute();
                $stmt->close();
            }

        } elseif ($action === "cancelled") {

            $stmt = $conn->prepare(
                "UPDATE bookings SET status = 'cancelled' WHERE id = ?"
            );

            if ($stmt) {
                $stmt->bind_param("i", $bookingId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    header("Location: booking.php");
    exit();
}

/* =========================
   GET BOOKINGS
========================= */

$sql = "
    SELECT
        b.id,
        b.booking_date,
        b.booking_time,
        b.address,
        b.hours,
        b.total_amount,
        b.message,
        b.status,
        b.created_at,

        u.name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone,

        p.id AS provider_id,
        pu.name AS provider_name,
        pu.email AS provider_email,

        s.name AS service_name,
        s.price AS service_price

    FROM bookings b

    LEFT JOIN users u
        ON b.customer_id = u.id

    LEFT JOIN providers p
        ON b.provider_id = p.id

    LEFT JOIN users pu
        ON p.user_id = pu.id

    LEFT JOIN services s
        ON b.service_id = s.id

    ORDER BY b.id DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("Booking database error: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Bookings | HomeServe Admin</title>

    <style>

<style>
/* =====================================
   HOMESERVE - PREMIUM ADMIN BOOKINGS
===================================== */

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
    --blue: #2563eb;
    --gray: #64748b;
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

.navbar {
    background: linear-gradient(120deg, #111827, #312e81);
    color: #ffffff;
    padding: 17px 5%;
    min-height: 76px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    box-shadow: 0 5px 20px rgba(15, 23, 42, 0.15);
}

.logo {
    font-size: 23px;
    font-weight: 800;
    letter-spacing: -0.5px;
    white-space: nowrap;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
}

.nav-links a {
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

.nav-links a:hover {
    background: rgba(255, 255, 255, 0.13);
    color: #ffffff;
    transform: translateY(-1px);
}

/* PAGE CONTENT */

.container {
    width: 92%;
    max-width: 1750px;
    margin: 35px auto 60px;
}

.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #ffffff;
    color: var(--primary);
    padding: 10px 16px;
    border: 1px solid #e0e7ff;
    border-radius: 10px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 22px;
    transition: all 0.25s ease;
}

.back-btn:hover {
    background: #eef2ff;
    border-color: #c7d2fe;
    transform: translateX(-3px);
}

h1 {
    color: var(--navy);
    font-size: clamp(27px, 3vw, 36px);
    font-weight: 800;
    letter-spacing: -0.8px;
    margin: 0 0 8px;
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

.subtitle {
    color: var(--muted);
    font-size: 15px;
    margin: 15px 0 25px;
}

/* TABLE CONTAINER */

.table-box {
    background: var(--surface);
    padding: 22px;
    border: 1px solid #edf0f7;
    border-radius: 18px;
    box-shadow: var(--shadow);
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* BOOKINGS TABLE */

table {
    width: 100%;
    min-width: 1500px;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 13px;
}

thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #f1f5f9;
    color: #475569;
    padding: 15px 13px;
    text-align: left;
    white-space: nowrap;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.55px;
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
    padding: 16px 13px;
    color: #475569;
    border-bottom: 1px solid #edf0f5;
    vertical-align: middle;
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

/* BOOKING ID */

tbody td:first-child {
    color: var(--primary);
    font-weight: 800;
    white-space: nowrap;
}

/* CUSTOMER / PROVIDER DETAILS */

td strong {
    color: #1e293b;
    font-weight: 750;
}

.small {
    color: var(--muted);
    font-size: 12px;
    margin-top: 4px;
    line-height: 1.5;
    overflow-wrap: anywhere;
}

/* STATUS BADGES */

.status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 11px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 800;
    text-transform: capitalize;
    white-space: nowrap;
    border: 1px solid transparent;
}

.status::before {
    content: "";
    display: inline-block;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: currentColor;
}

.pending {
    background: #fffbeb;
    color: #b45309;
    border-color: #fde68a;
}

.accepted {
    background: #ecfdf5;
    color: #047857;
    border-color: #a7f3d0;
}

.rejected {
    background: #fef2f2;
    color: #b91c1c;
    border-color: #fecaca;
}

.completed {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}

.cancelled {
    background: #f1f5f9;
    color: #475569;
    border-color: #cbd5e1;
}

/* ACTION FORMS */

.action-form {
    display: inline-block;
    vertical-align: middle;
    margin: 3px;
}

.action-form button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    padding: 9px 13px;
    border-radius: 9px;
    color: #ffffff;
    font-family: inherit;
    font-size: 12px;
    font-weight: 750;
    white-space: nowrap;
    cursor: pointer;
    box-shadow: 0 3px 8px rgba(15, 23, 42, 0.08);
    transition: transform 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
}

.action-form button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(15, 23, 42, 0.15);
}

.action-form button:active {
    transform: scale(0.97);
}

.accept {
    background: #15803d;
}

.accept:hover {
    background: #166534;
}

.reject {
    background: #dc2626;
}

.reject:hover {
    background: #b91c1c;
}

.complete {
    background: #2563eb;
}

.complete:hover {
    background: #1d4ed8;
}

.cancel {
    background: #64748b;
}

.cancel:hover {
    background: #475569;
}

.action-form button:focus-visible,
.nav-links a:focus-visible,
.back-btn:focus-visible {
    outline: 3px solid #a5b4fc;
    outline-offset: 3px;
}

/* NO BOOKINGS */

.empty {
    text-align: center;
    padding: 65px 20px;
    color: var(--muted);
    font-size: 15px;
}

.empty::before {
    content: "📋";
    display: block;
    font-size: 42px;
    margin-bottom: 12px;
}

/* TABLE SCROLLBAR */

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

/* RESPONSIVE */

@media (max-width: 1000px) {
    .navbar {
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
    .navbar {
        align-items: flex-start;
        flex-direction: column;
        gap: 12px;
    }

    .logo {
        font-size: 21px;
    }

    .nav-links {
        width: 100%;
        gap: 5px;
    }

    .nav-links a {
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

    .subtitle {
        font-size: 14px;
    }

    .table-box {
        padding: 10px;
        border-radius: 13px;
    }

    table {
        min-width: 1500px;
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

<nav class="navbar">

    <div class="logo">
        HomeServe - Admin
    </div>

    <div class="nav-links">

        <a href="dashboard.php">Dashboard</a>

        <a href="users.php">Users</a>

        <a href="providers.php">Providers</a>

        <a href="booking.php">Bookings</a>

        <a href="servises.php">Services</a>

        <a href="../php/logout.php">Logout</a>

    </div>

</nav>


<div class="container">

    <a href="dashboard.php" class="back-btn">
        ← Back to Dashboard
    </a>

    <h1>Manage Bookings</h1>

    <p class="subtitle">
        View and manage all HomeServe customer bookings.
    </p>


    <div class="table-box">

        <?php if ($result->num_rows === 0): ?>

            <div class="empty">
                No bookings found.
            </div>

        <?php else: ?>

            <table>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Customer</th>

                        <th>Service</th>

                        <th>Provider</th>

                        <th>Date</th>

                        <th>Time</th>

                        <th>Address</th>

                        <th>Message</th>

                        <th>Rate / Hour</th>
<th>Hours</th>
<th>Total Amount</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                <?php while ($booking = $result->fetch_assoc()): ?>

                    <tr>

                        <td>
                            #<?php echo (int)$booking["id"]; ?>
                        </td>


                        <td>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $booking["customer_name"] ?? "Unknown"
                                );
                                ?>
                            </strong>

                            <div class="small">
                                <?php
                                echo htmlspecialchars(
                                    $booking["customer_email"] ?? ""
                                );
                                ?>
                            </div>

                            <div class="small">
                                <?php
                                echo htmlspecialchars(
                                    $booking["customer_phone"] ?? ""
                                );
                                ?>
                            </div>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $booking["service_name"] ?? "Not assigned"
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $booking["provider_name"] ?? "Not assigned"
                            );
                            ?>

                            <?php if (!empty($booking["provider_email"])): ?>

                                <div class="small">
                                    <?php
                                    echo htmlspecialchars(
                                        $booking["provider_email"]
                                    );
                                    ?>
                                </div>

                            <?php endif; ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $booking["booking_date"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $booking["booking_time"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $booking["address"]
                                )
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $booking["message"] ?? ""
                                )
                            );
                            ?>

                        </td>


                      <td>
    ₹<?php
    echo number_format(
        (float)($booking["service_price"] ?? 0),
        2
    ); 
    ?>/hour
</td>

<td>
    <?php
    echo (int)($booking["hours"] ?? 1);
    ?> hour(s)
</td>

<td>
    <strong>
        ₹<?php
        echo number_format(
            (float)($booking["total_amount"] ?? 0),
            2
        );
        ?>
    </strong>
</td>


                        <td>

                            <?php

                            $status = $booking["status"] ?? "pending";

                            ?>

                            <span class="status <?php echo htmlspecialchars($status); ?>">

                                <?php
                                echo htmlspecialchars($status);
                                ?>

                            </span>

                        </td>


                        <td>

                            <?php if ($status === "pending"): ?>

                                <form method="POST" class="action-form">

                                    <input
                                        type="hidden"
                                        name="booking_id"
                                        value="<?php echo (int)$booking["id"]; ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="accepted"
                                    >

                                    <button
                                        type="submit"
                                        class="accept"
                                    >
                                        Accept
                                    </button>

                                </form>


                                <form method="POST" class="action-form">

                                    <input
                                        type="hidden"
                                        name="booking_id"
                                        value="<?php echo (int)$booking["id"]; ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="rejected"
                                    >

                                    <button
                                        type="submit"
                                        class="reject"
                                    >
                                        Reject
                                    </button>

                                </form>


                            <?php elseif ($status === "accepted"): ?>

                                <form method="POST" class="action-form">

                                    <input
                                        type="hidden"
                                        name="booking_id"
                                        value="<?php echo (int)$booking["id"]; ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="completed"
                                    >

                                    <button
                                        type="submit"
                                        class="complete"
                                    >
                                        Complete
                                    </button>

                                </form>


                                <form method="POST" class="action-form">

                                    <input
                                        type="hidden"
                                        name="booking_id"
                                        value="<?php echo (int)$booking["id"]; ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="cancelled"
                                    >

                                    <button
                                        type="submit"
                                        class="cancel"
                                    >
                                        Cancel
                                    </button>

                                </form>


                            <?php else: ?>

                                <span style="color:#777;">
                                    No action required
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </div>

</div>

</body>

</html>

<?php
$conn->close();
?>