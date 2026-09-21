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

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #222;
        }

        nav {
            background: #153b6d;
            color: white;
            padding: 18px 8%;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav strong {
            font-size: 24px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
        }

        nav a:hover {
            text-decoration: underline;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
        }

        h1 {
            color: #153b6d;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            background: #ff7a00;
            color: white;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        .back:hover {
            background: #e66d00;
        }

        .table-box {
            background: white;
            padding: 25px;
            border-radius: 12px;

            box-shadow:
                0 5px 20px rgba(0,0,0,0.08);

            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background: #153b6d;
            color: white;
            padding: 14px;
            text-align: left;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #ddd;
        }

        tr:hover {
            background: #f8fafc;
        }

        .empty {
            text-align: center;
            padding: 35px;
            color: #666;
        }

        @media (max-width: 700px) {

            nav {
                flex-direction: column;
                gap: 15px;
            }

            nav a {
                margin-left: 8px;
                margin-right: 8px;
            }

            .container {
                width: 95%;
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