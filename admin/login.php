
<?php

session_start();

if (
    isset($_SESSION["user_id"]) &&
    isset($_SESSION["user_role"]) &&
    strtolower(trim($_SESSION["user_role"])) === "admin"
) {
    header("Location: dashboard.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login | HomeServe</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6fb;
        }

        .navbar {
            background: #174477;
            color: white;
            padding: 18px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 25px;
            font-weight: bold;
        }

        .home-link {
            color: white;
            text-decoration: none;
            font-size: 15px;
        }

        .login-section {
            min-height: calc(100vh - 160px);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .login-box {
            width: 100%;
            max-width: 430px;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.10);
        }

        h1 {
            text-align: center;
            color: #174477;
            margin-bottom: 10px;
        }

        .description {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
            color: #333;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #ddd;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 15px;
        }

        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 7px;
            background: #174477;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #0f3158;
        }

        .links {
            text-align: center;
            margin-top: 25px;
        }

        .links a {
            color: #174477;
            text-decoration: none;
            font-weight: bold;
        }

        footer {
            text-align: center;
            background: #174477;
            color: white;
            padding: 18px;
        }

        footer p {
            margin: 5px;
        }

    </style>

</head>


<body>


    <nav class="navbar">

        <div class="logo">
            HomeServe
        </div>

        <a
            href="../html/home.html"
            class="home-link"
        >
            ← Back to Home
        </a>

    </nav>



    <section class="login-section">

        <div class="login-box">

            <h1>
                Admin Login 🛠️
            </h1>

            <p class="description">
                Login to manage the HomeServe platform.
            </p>


            <form
                action="../php/login_process.php"
                method="POST"
            >

                <input
                    type="hidden"
                    name="login_role"
                    value="admin"
                >


                <label for="email">
                    Admin Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter admin email"
                    required
                >


                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter admin password"
                    required
                >


                <button type="submit">
                    Admin Login
                </button>

            </form>


            <div class="links">

                <p>
                    Are you a provider?
                    <a href="../html/provider-login.html">
                        Provider Login
                    </a>
                </p>

                <p>
                    Are you a customer?
                    <a href="../html/login.html">
                        Customer Login
                    </a>
                </p>

            </div>

        </div>

    </section>



    <footer>

        <strong>HomeServe</strong>

        <p>
            Admin Management Portal
        </p>

    </footer>


</body>

</html>
```