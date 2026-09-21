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

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #222;
        }

        .navbar {
            background: #1d477d;
            color: white;
            padding: 18px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 17px;
        }

        .container {
            width: 92%;
            max-width: 1600px;
            margin: 45px auto;
        }

        .back-btn {
            display: inline-block;
            background: #ff7a00;
            color: white;
            padding: 14px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            margin-bottom: 35px;
        }

        h1 {
            color: #1d477d;
            font-size: 42px;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #666;
            font-size: 20px;
            margin-bottom: 30px;
        }

        .table-box {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        th {
            background: #1d477d;
            color: white;
            padding: 16px;
            text-align: left;
            font-size: 15px;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        tr:nth-child(even) {
            background: #f8f9fb;
        }

        .status {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .pending {
            background: #fff0c2;
            color: #8a6500;
        }

        .accepted {
            background: #d8f3e5;
            color: #12633d;
        }

        .rejected {
            background: #ffd9dd;
            color: #a51e2a;
        }

        .completed {
            background: #dbeafe;
            color: #174ea6;
        }

        .cancelled {
            background: #e5e7eb;
            color: #555;
        }

        .action-form {
            display: inline-block;
            margin: 3px;
        }

        button {
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .accept {
            background: #159957;
        }

        .reject {
            background: #dc3545;
        }

        .complete {
            background: #2563eb;
        }

        .cancel {
            background: #6b7280;
        }

        .empty {
            text-align: center;
            padding: 50px;
            color: #777;
            font-size: 20px;
        }

        .small {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

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