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
            max-width: 1600px;
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
            min-width: 1200px;
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
            vertical-align: top;
        }

        tr:hover {
            background: #f8fafc;
        }

        .status {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .pending {
            background: #fff3cd;
            color: #856404;
        }

        .active {
            background: #d1e7dd;
            color: #0f5132;
        }

        .approved {
            background: #d1e7dd;
            color: #0f5132;
        }

        .rejected {
            background: #f8d7da;
            color: #842029;
        }

        .inactive {
            background: #e2e3e5;
            color: #41464b;
        }

        .about {
            max-width: 250px;
            line-height: 1.5;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            border: none;
            padding: 9px 14px;
            border-radius: 6px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
        }

        .approve-btn {
            background: #198754;
        }

        .approve-btn:hover {
            background: #146c43;
        }

        .reject-btn {
            background: #dc3545;
        }

        .reject-btn:hover {
            background: #bb2d3b;
        }

        .active-text {
            color: #198754;
            font-weight: bold;
        }

        .rejected-text {
            color: #dc3545;
            font-weight: bold;
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