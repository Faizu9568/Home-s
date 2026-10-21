<?php
session_start();
require_once "../config/database.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../html/login.html");
    exit();
}

$userId = (int) $_SESSION["user_id"];

// Fetch logged-in customer
$stmt = $conn->prepare(
    "SELECT id, name, email, phone, role
     FROM users
     WHERE id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || $user["role"] !== "customer") {
    http_response_code(403);
    exit("Access denied. Customer account required.");
}

$message = "";
$messageType = "";

// Update profile
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");

    if ($name === "" || strlen($name) > 100) {
        $message = "Please enter a valid name (maximum 100 characters).";
        $messageType = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";

    } else {

        // Check whether another account already uses this email
        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ? AND id != ?"
        );

        $check->bind_param("si", $email, $userId);
        $check->execute();
        $emailResult = $check->get_result();
        $emailExists = $emailResult->num_rows > 0;
        $check->close();

        if ($emailExists) {
            $message = "This email is already registered with another account.";
            $messageType = "error";

        } else {

            $update = $conn->prepare(
                "UPDATE users
                 SET name = ?, email = ?
                 WHERE id = ? AND role = 'customer'"
            );

            $update->bind_param("ssi", $name, $email, $userId);

            if ($update->execute()) {

                $_SESSION["user_name"] = $name;
                $_SESSION["user_email"] = $email;

                $message = "Profile updated successfully!";
                $messageType = "success";

            } else {
                $message = "Unable to update profile. Please try again.";
                $messageType = "error";
            }

            $update->close();

            // Refresh details from database
            $stmt = $conn->prepare(
                "SELECT id, name, email, phone, role
                 FROM users
                 WHERE id = ?"
            );

            $stmt->bind_param("i", $userId);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
        }
    }
}

function e($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile | HomeServe</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fc;
            color: #263247;
        }

        nav {
            background: #153b6d;
            padding: 18px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .logo {
            color: white;
            font-size: 25px;
            font-weight: bold;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-links a,
        .logout {
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        .nav-links a:hover {
            text-decoration: underline;
        }

        .logout {
            background: #eaf1ff;
            color: #153b6d;
            padding: 10px 15px;
            border-radius: 7px;
            font-weight: bold;
        }

        .container {
            width: 90%;
            max-width: 850px;
            margin: 40px auto;
        }

        .heading {
            margin-bottom: 25px;
        }

        .heading h1 {
            margin-bottom: 8px;
            color: #153b6d;
        }

        .heading p {
            color: #697586;
            margin-top: 0;
        }

        .profile-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 5px 25px rgba(20, 50, 90, 0.08);
        }

        .profile-top {
            display: flex;
            align-items: center;
            gap: 18px;
            padding-bottom: 25px;
            margin-bottom: 15px;
            border-bottom: 1px solid #e8edf4;
        }

        .avatar {
            width: 75px;
            height: 75px;
            flex-shrink: 0;
            border-radius: 50%;
            background: #e4edff;
            color: #153b6d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            font-weight: bold;
        }

        .profile-top h2 {
            margin: 0 0 7px;
            overflow-wrap: anywhere;
        }

        .role {
            display: inline-block;
            background: #e4f7ec;
            color: #187344;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .field {
            margin: 22px 0;
        }

        .field label {
            display: block;
            margin-bottom: 9px;
            font-weight: bold;
            color: #344a67;
        }

        .field input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #d8e0eb;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
        }

        .field input:focus {
            border-color: #2563a6;
            box-shadow: 0 0 0 3px rgba(37, 99, 166, 0.1);
        }

        .field input[readonly] {
            background: #f2f5f9;
            color: #64748b;
        }

        .help {
            font-size: 12px;
            color: #738095;
            margin-top: 7px;
        }

        .message {
            padding: 13px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success {
            background: #e4f7ec;
            color: #17683e;
        }

        .error {
            background: #fff0f0;
            color: #a32929;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 28px;
        }

        .btn {
            display: inline-block;
            border: none;
            border-radius: 8px;
            padding: 13px 19px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-primary {
            background: #153b6d;
            color: white;
        }

        .btn-secondary {
            background: #edf2f8;
            color: #153b6d;
        }

        .btn:hover {
            opacity: 0.9;
        }

        footer {
            text-align: center;
            padding: 25px 15px;
            color: #778397;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            nav {
                padding: 18px 5%;
            }

            .container {
                margin: 25px auto;
            }

            .profile-card {
                padding: 20px;
            }

            .nav-links {
                gap: 12px;
            }

            .profile-top h2 {
                font-size: 19px;
            }
        }
    </style>
</head>

<body>

<nav>
    <a class="logo" href="dashboard.php">HomeServe</a>

    <ul class="nav-links">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="../html/service.html">Services</a></li>
        <li><a href="my_bookings.php">My Bookings</a></li>
        <li><a href="profile.php">My Profile</a></li>
    </ul>

    <a class="logout" href="../php/logout.php">Logout</a>
</nav>

<main class="container">

    <div class="heading">
        <h1>My Profile</h1>
        <p>View and manage your HomeServe account details.</p>
    </div>

    <section class="profile-card">

        <div class="profile-top">
            <div class="avatar">
                <?php
                echo e(
                    strtoupper(
                        substr(trim($user["name"] ?? "C"), 0, 1)
                    )
                );
                ?>
            </div>

            <div>
                <h2><?php echo e($user["name"]); ?></h2>
                <span class="role">Customer Account</span>
            </div>
        </div>

        <?php if ($message !== ""): ?>
            <div class="message <?php echo e($messageType); ?>">
                <?php echo e($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="profile.php">

            <div class="field">
                <label for="customer_id">Customer ID</label>
                <input
                    id="customer_id"
                    type="text"
                    value="<?php echo e($user["id"]); ?>"
                    readonly
                >
            </div>

            <div class="field">
                <label for="name">Full Name</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    maxlength="100"
                    value="<?php echo e($user["name"]); ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="email">Email Address</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    maxlength="254"
                    value="<?php echo e($user["email"]); ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="phone">Registered Phone Number</label>
                <input
                    id="phone"
                    type="text"
                    value="<?php echo e($user["phone"]); ?>"
                    readonly
                >
                <p class="help">
                    Your registered phone number cannot be changed here.
                </p>
            </div>

            <div class="field">
                <label for="role">Account Type</label>
                <input
                    id="role"
                    type="text"
                    value="Customer"
                    readonly
                >
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary">
                    Save Changes
                </button>

                <a href="dashboard.php" class="btn btn-secondary">
                    Back to Dashboard
                </a>

                <a href="my_bookings.php" class="btn btn-secondary">
                    My Bookings
                </a>
            </div>

        </form>

    </section>

</main>

<footer>
    &copy; <?php echo date("Y"); ?> HomeServe. All rights reserved.
</footer>

</body>
</html>

<?php
$conn->close();
?>