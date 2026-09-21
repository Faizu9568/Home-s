<?php

session_start();
include "../config/database.php";

/* Check provider login */
if (!isset($_SESSION["user_id"])) {
    header("Location: ../html/login.html");
    exit();
}

$userId = (int) $_SESSION["user_id"];


/* Get provider basic information */
$userQuery = $conn->prepare(
    "SELECT name, email, phone
     FROM users
     WHERE id = ? AND role = 'provider'"
);

$userQuery->bind_param("i", $userId);
$userQuery->execute();

$userResult = $userQuery->get_result();

if ($userResult->num_rows === 0) {
    die("Provider account not found.");
}

$user = $userResult->fetch_assoc();


/* Get all services */
$services = $conn->query(
    "SELECT id, name
     FROM services
     ORDER BY name ASC"
);

if (!$services) {
    die("Services query failed: " . $conn->error);
}


/* Save provider profile */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $serviceId = isset($_POST["service_id"])
        ? (int) $_POST["service_id"]
        : 0;

    $experience = trim($_POST["experience"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $about = trim($_POST["about"] ?? "");


    /* Validation */

    if ($serviceId <= 0) {

        $error = "Please select a service.";

    } elseif ($experience === "") {

        $error = "Please enter your experience.";

    } elseif ($location === "") {

        $error = "Please enter your service location.";

    } else {

        /* Check if provider profile already exists */

        $check = $conn->prepare(
            "SELECT id
             FROM providers
             WHERE user_id = ?"
        );

        $check->bind_param("i", $userId);
        $check->execute();

        $checkResult = $check->get_result();


        /* UPDATE existing profile */

        if ($checkResult->num_rows > 0) {

            $update = $conn->prepare(
                "UPDATE providers
                 SET service_id = ?,
                     experience = ?,
                     location = ?,
                     about = ?,
                     status = 'pending'
                 WHERE user_id = ?"
            );

            $update->bind_param(
                "isssi",
                $serviceId,
                $experience,
                $location,
                $about,
                $userId
            );


            if ($update->execute()) {

                header("Location: profile.php?saved=1");
                exit();

            } else {

                $error = "Provider profile could not be updated: "
                       . $update->error;
            }

        }


        /* INSERT new profile */

        else {

            $insert = $conn->prepare(
                "INSERT INTO providers
                 (user_id, service_id, experience, location, about, status)
                 VALUES (?, ?, ?, ?, ?, 'pending')"
            );


            if (!$insert) {

                $error = "Insert preparation failed: "
                       . $conn->error;

            } else {

                $insert->bind_param(
                    "iisss",
                    $userId,
                    $serviceId,
                    $experience,
                    $location,
                    $about
                );


                if ($insert->execute()) {

                    header("Location: profile.php?saved=1");
                    exit();

                } else {

                    $error = "Provider profile could not be created: "
                           . $insert->error;
                }
            }
        }
    }
}


/* Get existing provider profile */

$profile = null;

$profileQuery = $conn->prepare(
    "SELECT service_id, experience, location, about, status
     FROM providers
     WHERE user_id = ?"
);

$profileQuery->bind_param("i", $userId);
$profileQuery->execute();

$profileResult = $profileQuery->get_result();

if ($profileResult->num_rows > 0) {

    $profile = $profileResult->fetch_assoc();
}

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
            width: 80%;
            max-width: 900px;
            margin: 50px auto;
        }

        .box {
            background: white;
            padding: 40px;
            border-radius: 12px;

            box-shadow:
                0 5px 20px rgba(0,0,0,0.08);
        }

        h1 {
            color: #153b6d;
            margin-top: 0;
        }

        .user-info {
            background: #f5f7fb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .user-info p {
            margin: 8px 0;
        }

        label {
            display: block;
            margin-top: 20px;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 13px;

            border: 1px solid #d1d5db;
            border-radius: 7px;

            font-size: 16px;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        button {
            margin-top: 25px;

            background: #198754;
            color: white;

            border: none;

            padding: 14px 25px;

            border-radius: 7px;

            font-size: 16px;
            font-weight: bold;

            cursor: pointer;
        }

        button:hover {
            background: #157347;
        }

        .success {
            background: #d1e7dd;
            color: #0f5132;

            padding: 15px;

            border-radius: 7px;

            margin-bottom: 20px;
        }

        .error {
            background: #f8d7da;
            color: #842029;

            padding: 15px;

            border-radius: 7px;

            margin-bottom: 20px;
        }

        .status {
            display: inline-block;

            margin-top: 10px;

            padding: 8px 15px;

            border-radius: 20px;

            background: #fff3cd;
            color: #856404;

            font-weight: bold;
        }

    </style>

</head>

<body>

<nav>

    <strong>HomeServe - Provider</strong>

    <div>

        <a href="dashboard.php">Dashboard</a>

        <a href="profile.php">Profile</a>

        <a href="../php/logout.php">Logout</a>

    </div>

</nav>


<div class="container">

    <div class="box">

        <h1>Provider Profile</h1>

        <p>
            Complete your provider profile so customers can book your service.
        </p>


        <?php if (isset($_GET["saved"])) { ?>

            <div class="success">
                Provider profile saved successfully!
            </div>

        <?php } ?>


        <?php if (isset($error)) { ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php } ?>


        <div class="user-info">

            <p>
                <strong>Name:</strong>
                <?php echo htmlspecialchars($user["name"]); ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?php echo htmlspecialchars($user["email"]); ?>
            </p>

            <p>
                <strong>Phone:</strong>
                <?php echo htmlspecialchars($user["phone"]); ?>
            </p>

            <?php if ($profile) { ?>

                <p>

                    <strong>Profile Status:</strong>

                    <span class="status">

                        <?php
                        echo ucfirst(
                            htmlspecialchars($profile["status"])
                        );
                        ?>

                    </span>

                </p>

            <?php } ?>

        </div>


        <form method="POST" action="profile.php">


            <label for="service_id">
                Service You Provide
            </label>

            <select name="service_id"
                    id="service_id"
                    required>

                <option value="">
                    Select your service
                </option>


                <?php while ($service = $services->fetch_assoc()) { ?>

                    <option
                        value="<?php echo $service["id"]; ?>"

                        <?php
                        if (
                            $profile &&
                            $profile["service_id"] == $service["id"]
                        ) {
                            echo "selected";
                        }
                        ?>
                    >

                        <?php
                        echo htmlspecialchars(
                            $service["name"]
                        );
                        ?>

                    </option>

                <?php } ?>

            </select>


            <label for="experience">
                Experience
            </label>

            <input
                type="text"
                name="experience"
                id="experience"
                placeholder="Example: 3 years"

                value="<?php
                    echo $profile
                        ? htmlspecialchars($profile["experience"])
                        : "";
                ?>"

                required
            >


            <label for="location">
                Service Location
            </label>

            <input
                type="text"
                name="location"
                id="location"
                placeholder="Example: Okhla, Delhi"

                value="<?php
                    echo $profile
                        ? htmlspecialchars($profile["location"])
                        : "";
                ?>"

                required
            >


            <label for="about">
                About Your Service
            </label>

            <textarea
                name="about"
                id="about"
                placeholder="Tell customers about your service..."
            ><?php
                echo $profile
                    ? htmlspecialchars($profile["about"])
                    : "";
            ?></textarea>


            <button type="submit">
                Save Provider Profile
            </button>


        </form>

    </div>

</div>

</body>

</html>

<?php

$conn->close();

?>