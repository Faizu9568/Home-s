<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../html/login.html");
    exit();
}

$name = htmlspecialchars($_SESSION["user_name"]);
$role = htmlspecialchars($_SESSION["user_role"]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | HomeServe</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 8%;
            background: #153b6d;
            color: white;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }

        .box {
            max-width: 750px;
            margin: 80px auto;
            padding: 45px;
            text-align: center;
            background: white;
            border-radius: 14px;
            box-shadow: 0 5px 22px rgba(0, 0, 0, 0.12);
        }

        h1 {
            color: #153b6d;
        }

        .role {
            display: inline-block;
            background: #e7f0ff;
            color: #153b6d;
            padding: 9px 16px;
            border-radius: 20px;
            font-weight: bold;
        }

        .button {
            display: inline-block;
            margin-top: 25px;
            padding: 13px 22px;
            background: #ff7a00;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <nav>
        <strong>HomeServe</strong>

        <div>
       <a href="../html/index.html">Home</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="box">
        <h1>Welcome, <?php echo $name; ?>!</h1>

        <p>You have logged in successfully.</p>
        <p class="role">Account type: <?php echo ucfirst($role); ?></p>

        <br>

        <?php if ($role === "customer") { ?>
            <a class="button" href="../html/service.html">Book a Service</a>
           <a class="button" href="../customer/my_bookings.php">My Bookings</a>
        <?php } elseif ($role === "provider") { ?>
            <p>Your Provider Dashboard will be added in the next step.</p>
        <?php } else { ?>
            <p>Your Admin Dashboard will be added later.</p>
        <?php } ?>
    </div>

</body>
</html>