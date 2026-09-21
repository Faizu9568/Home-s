
<?php

session_start();

include "../config/database.php";


/* Check admin login */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    strtolower(trim($_SESSION["user_role"])) !== "admin"
) {
    die("Access denied. Admin account required.");
}


/* Get dashboard statistics */

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM users"
);

$totalUsers = $result
    ? $result->fetch_assoc()["total"]
    : 0;


$result = $conn->query(
    "SELECT COUNT(*) AS total FROM providers"
);

$totalProviders = $result
    ? $result->fetch_assoc()["total"]
    : 0;


$result = $conn->query(
    "SELECT COUNT(*) AS total FROM bookings"
);

$totalBookings = $result
    ? $result->fetch_assoc()["total"]
    : 0;


$result = $conn->query(
    "SELECT COUNT(*) AS total FROM services"
);

$totalServices = $result
    ? $result->fetch_assoc()["total"]
    : 0;

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | HomeServe</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
        }

        nav {
            background: #153b6d;
            color: white;
            padding: 18px 5%;
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
            margin-left: 15px;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
        }

        h1 {
            color: #153b6d;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,.08);
        }

        .card h3 {
            color: #555;
        }

        .number {
            font-size: 40px;
            font-weight: bold;
            color: #153b6d;
        }

        .management {
            background: white;
            margin-top: 35px;
            padding: 30px;
            border-radius: 12px;
        }

        .management-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .management-grid a {
            background: #153b6d;
            color: white;
            padding: 18px;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
        }
    </style>
</head>

<body>

<nav>
    <strong>HomeServe Admin</strong>

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

    <h1>Admin Dashboard</h1>

    <p>Welcome, <?php echo htmlspecialchars($_SESSION["user_name"]); ?>.</p>

    <div class="cards">

        <div class="card">
            <h3>Total Users</h3>
            <div class="number"><?php echo $totalUsers; ?></div>
        </div>

        <div class="card">
            <h3>Total Providers</h3>
            <div class="number"><?php echo $totalProviders; ?></div>
        </div>

        <div class="card">
            <h3>Total Bookings</h3>
            <div class="number"><?php echo $totalBookings; ?></div>
        </div>

        <div class="card">
            <h3>Total Services</h3>
            <div class="number"><?php echo $totalServices; ?></div>
        </div>

    </div>

    <div class="management">

        <h2>Management</h2>

        <div class="management-grid">

            <a href="users.php">Users</a>
            <a href="providers.php">Providers</a>
            <a href="booking.php">Bookings</a>
            <a href="servises.php">Services</a>

        </div>

    </div>

</div>

</body>
</html>