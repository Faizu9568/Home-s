<?php
session_start();
include "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../html/login.html");
    exit();
}

$userId = $_SESSION["user_id"];


/* Get user information */

$userQuery = $conn->prepare(
    "SELECT name, email
     FROM users
     WHERE id = ?"
);

$userQuery->bind_param("i", $userId);
$userQuery->execute();

$userResult = $userQuery->get_result();
$user = $userResult->fetch_assoc();


/* Get provider information */

$providerQuery = $conn->prepare(
    "SELECT
        providers.id,
        providers.service_id,
        providers.experience,
        providers.location,
        providers.about,
        providers.status,
        services.name AS service_name

     FROM providers

     LEFT JOIN services
        ON providers.service_id = services.id

     WHERE providers.user_id = ?"
);

$providerQuery->bind_param("i", $userId);
$providerQuery->execute();

$providerResult = $providerQuery->get_result();

if ($providerResult->num_rows === 0) {

    die(
        "Provider profile not found. Please create your provider profile first."
    );

}

$provider = $providerResult->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Provider Profile | HomeServe</title>

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

        .container {
            width: 85%;
            max-width: 900px;
            margin: 50px auto;
        }

        h1 {
            color: #153b6d;
        }

        .profile-card {
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .row {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .row:last-child {
            border-bottom: none;
        }

        .label {
            font-weight: bold;
            color: #153b6d;
            display: block;
            margin-bottom: 6px;
        }

        .value {
            color: #444;
            line-height: 1.5;
        }

        .status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            background: #d1e7dd;
            color: #0f5132;
            font-weight: bold;
        }

        .back {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 20px;
            background: #153b6d;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

    </style>

</head>

<body>
<nav class="navbar">

    <div class="logo">
        <a href="dashboard.php">HomeServe</a>
    </div>

    <ul class="nav-links">

        <li>
            <a href="dashboard.php">Home</a>
        </li>

        <li>
            <a href="../html/service.html">Services</a>
        </li>

        <li>
            <a href="my_bookings.php">My Bookings</a>
        </li>

        <li>
            <a href="profile.php" class="active">Profile</a>
        </li>

    </ul>

    <div class="nav-buttons">

        <a href="../php/logout.php" class="logout-btn">
            Logout
        </a>

    </div>

</nav>


<div class="container">

    <h1>Provider Profile</h1>

    <div class="profile-card">


        <div class="row">

            <span class="label">
                Name
            </span>

            <div class="value">

                <?php
                echo htmlspecialchars(
                    $user["name"] ?? ""
                );
                ?>

            </div>

        </div>


        <div class="row">

            <span class="label">
                Email
            </span>

            <div class="value">

                <?php
                echo htmlspecialchars(
                    $user["email"] ?? ""
                );
                ?>

            </div>

        </div>


        <div class="row">

            <span class="label">
                Service
            </span>

            <div class="value">

                <?php
                echo htmlspecialchars(
                    $provider["service_name"] ?? "Not specified"
                );
                ?>

            </div>

        </div>


        <div class="row">

            <span class="label">
                Experience
            </span>

            <div class="value">

                <?php
                echo htmlspecialchars(
                    $provider["experience"] ?? "Not specified"
                );
                ?>

            </div>

        </div>


        <div class="row">

            <span class="label">
                Location
            </span>

            <div class="value">

                <?php
                echo htmlspecialchars(
                    $provider["location"] ?? "Not specified"
                );
                ?>

            </div>

        </div>


        <div class="row">

            <span class="label">
                About
            </span>

            <div class="value">

                <?php
                echo nl2br(
                    htmlspecialchars(
                        $provider["about"] ?? "No information available."
                    )
                );
                ?>

            </div>

        </div>


        <div class="row">

            <span class="label">
                Provider Status
            </span>

            <div class="value">

                <span class="status">

                    <?php
                    echo ucfirst(
                        htmlspecialchars(
                            $provider["status"] ?? "pending"
                        )
                    );
                    ?>

                </span>

            </div>

        </div>


        <a class="back" href="dashboard.php">
            ← Back to Dashboard
        </a>


    </div>

</div>

</body>
</html>

<?php
$conn->close();
?>