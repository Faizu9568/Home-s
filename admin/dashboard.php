<?php
session_start();

include "../config/database.php";

/* Admin authentication — keep existing security check */
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    strtolower(trim($_SESSION["user_role"])) !== "admin"
) {
    die("Access denied. Admin account required.");
}

/* Get dashboard statistics */
function getCount($conn, $table) {
    $allowedTables = ["users", "providers", "bookings", "services"];

    if (!in_array($table, $allowedTables, true)) {
        return 0;
    }

    $result = $conn->query("SELECT COUNT(*) AS total FROM `$table`");

    if ($result) {
        $row = $result->fetch_assoc();
        return (int) $row["total"];
    }

    return 0;
}

$totalUsers     = getCount($conn, "users");
$totalProviders = getCount($conn, "providers");
$totalBookings  = getCount($conn, "bookings");
$totalServices  = getCount($conn, "services");

$adminName = htmlspecialchars(
    $_SESSION["user_name"] ?? "Admin",
    ENT_QUOTES,
    "UTF-8"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | HomeServe</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #5b5ce2;
            --primary-light: #eeeeff;
            --sidebar: #171a2e;
            --background: #f5f6fb;
            --text: #20243a;
            --muted: #858ba3;
            --border: #eaecf3;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background: var(--background);
            color: var(--text);
        }

        a {
            text-decoration: none;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 255px;
            background: var(--sidebar);
            color: white;
            padding: 28px 17px;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 12px 35px;
        }

        .brand-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #7778ff, #5556d9);
            border-radius: 13px;
            font-size: 23px;
            font-weight: 800;
            box-shadow: 0 6px 18px #5b5ce244;
        }

        .brand h2 {
            font-size: 19px;
            letter-spacing: -0.7px;
        }

        .brand p {
            font-size: 11px;
            color: #9da3c2;
            margin-top: 4px;
        }

        .menu-label {
            color: #777e9f;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
            padding: 0 14px;
            margin-bottom: 12px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 14px;
            border-radius: 10px;
            color: #aeb4ce;
            font-size: 13px;
            font-weight: 500;
            transition: 0.25s ease;
        }

        .menu a:hover {
            background: #ffffff12;
            color: white;
            transform: translateX(3px);
        }

        .menu a.active {
            background: linear-gradient(100deg, #6263e8, #5152c9);
            color: white;
            box-shadow: 0 6px 18px #5556d933;
        }

        .menu-icon {
            width: 22px;
            text-align: center;
            font-size: 17px;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding-top: 30px;
        }

        .help-card {
            padding: 17px;
            background: #ffffff0c;
            border: 1px solid #ffffff13;
            border-radius: 13px;
            margin: 0 3px 18px;
        }

        .help-card strong {
            display: block;
            font-size: 12px;
            margin-bottom: 7px;
        }

        .help-card p {
            font-size: 11px;
            color: #a7aec7;
            line-height: 1.7;
        }

        .logout {
            display: block;
            padding: 13px 14px;
            color: #ffb4bc;
            border-radius: 9px;
            font-size: 13px;
            transition: 0.2s;
        }

        .logout:hover {
            background: #ff536515;
        }

        /* Main content */
        .main {
            flex: 1;
            min-width: 0;
            padding: 30px 35px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 35px;
        }

        .breadcrumb {
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 9px;
        }

        .page-title {
            font-size: 27px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .page-subtitle {
            color: var(--muted);
            font-size: 13px;
            margin-top: 8px;
            line-height: 1.6;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            border: 1px solid var(--border);
            padding: 10px 15px;
            border-radius: 13px;
            box-shadow: 0 3px 12px #20243a04;
        }

        .avatar {
            height: 41px;
            width: 41px;
            display: grid;
            place-items: center;
            background: var(--primary-light);
            color: var(--primary);
            font-size: 16px;
            font-weight: 800;
            border-radius: 12px;
        }

        .profile-name {
            font-size: 12px;
            font-weight: 700;
        }

        .profile-role {
            color: var(--muted);
            font-size: 10px;
            margin-top: 4px;
        }

        /* Welcome banner */
        .welcome-banner {
            position: relative;
            overflow: hidden;
            padding: 29px 31px;
            border-radius: 19px;
            color: white;
            background: linear-gradient(115deg, #5556d9, #7778f3);
            margin-bottom: 30px;
            box-shadow: 0 12px 28px #5b5ce225;
        }

        .welcome-banner::after {
            content: "";
            position: absolute;
            width: 210px;
            height: 210px;
            right: 65px;
            top: -125px;
            border: 35px solid #ffffff13;
            border-radius: 50%;
        }

        .welcome-banner::before {
            content: "";
            position: absolute;
            width: 130px;
            height: 130px;
            right: -20px;
            bottom: -90px;
            border: 25px solid #ffffff12;
            border-radius: 50%;
        }

        .welcome-banner h2 {
            font-size: 22px;
            margin-bottom: 9px;
            position: relative;
            z-index: 1;
        }

        .welcome-banner p {
            color: #e4e4ff;
            font-size: 13px;
            line-height: 1.7;
            max-width: 540px;
            position: relative;
            z-index: 1;
        }

        /* Statistics */
        .section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 17px;
        }

        .section-heading h2 {
            font-size: 17px;
            font-weight: 750;
        }

        .section-heading p {
            font-size: 11px;
            color: var(--muted);
            margin-top: 5px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .stat-card {
            position: relative;
            overflow: hidden;
            background: white;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 22px 20px;
            transition: transform 0.25s, box-shadow 0.25s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 30px #20243a0b;
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
        }

        .stat-label {
            color: #777e96;
            font-size: 12px;
            font-weight: 600;
        }

        .stat-icon {
            width: 43px;
            height: 43px;
            border-radius: 13px;
            display: grid;
            place-items: center;
            font-size: 20px;
        }

        .purple {
            background: #eeeeff;
        }

        .green {
            background: #e5f9ef;
        }

        .orange {
            background: #fff0df;
        }

        .blue {
            background: #e6f2ff;
        }

        .stat-number {
            font-size: clamp(25px, 2.5vw, 34px);
            font-weight: 800;
            letter-spacing: -1px;
            overflow-wrap: anywhere;
        }

        .stat-caption {
            color: #9298ac;
            font-size: 11px;
            margin-top: 8px;
        }

        /* Management section */
        .management-section {
            margin-top: 34px;
        }

        .management-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .management-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 22px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 15px;
            transition: 0.25s ease;
        }

        .management-card:hover {
            border-color: #c8c9ff;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px #20243a08;
        }

        .management-icon {
            width: 50px;
            height: 50px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border-radius: 14px;
            font-size: 22px;
        }

        .management-info {
            flex: 1;
            min-width: 0;
        }

        .management-info h3 {
            font-size: 13px;
            margin-bottom: 7px;
        }

        .management-info p {
            color: var(--muted);
            font-size: 11px;
            line-height: 1.6;
        }

        .arrow {
            font-size: 21px;
            color: #a0a5b8;
            transition: 0.2s;
        }

        .management-card:hover .arrow {
            color: var(--primary);
            transform: translateX(4px);
        }

        .footer {
            text-align: center;
            color: #9ba0b1;
            font-size: 11px;
            margin-top: 38px;
            padding: 15px 0;
        }

        /* Tablet */
        @media (max-width: 1150px) {
            .sidebar {
                width: 220px;
            }

            .main {
                padding: 25px 22px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        /* Mobile */
        @media (max-width: 700px) {
            .layout {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding: 17px;
            }

            .brand {
                padding: 0 5px 18px;
            }

            .menu-label,
            .sidebar-bottom {
                display: none;
            }

            .menu {
                flex-direction: row;
                overflow-x: auto;
                padding-bottom: 5px;
            }

            .menu a {
                flex-shrink: 0;
                padding: 11px 13px;
            }

            .menu a:hover {
                transform: none;
            }

            .main {
                padding: 23px 16px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
                margin-bottom: 25px;
            }

            .page-title {
                font-size: 24px;
            }

            .welcome-banner {
                padding: 23px 21px;
            }

            .welcome-banner h2 {
                font-size: 19px;
            }

            .stats-grid {
                gap: 12px;
            }

            .stat-card {
                padding: 17px 14px;
            }

            .stat-top {
                align-items: flex-start;
                flex-direction: column-reverse;
                gap: 12px;
                margin-bottom: 17px;
            }

            .management-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 380px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-top {
                flex-direction: row;
                align-items: center;
            }
        }
    </style>
</head>

<body>

<div class="layout">

    <!-- Sidebar -->
    <aside class="sidebar">

        <div class="brand">
            <div class="brand-icon">H</div>
            <div>
                <h2>HomeServe</h2>
                <p>ADMINISTRATION PANEL</p>
            </div>
        </div>

        <div class="menu-label">MAIN MENU</div>

        <nav class="menu">
            <a href="dashboard.php" class="active">
                <span class="menu-icon">▦</span>
                Dashboard
            </a>

            <a href="users.php">
                <span class="menu-icon">♙</span>
                Users
            </a>

            <a href="providers.php">
                <span class="menu-icon">♧</span>
                Providers
            </a>

            <a href="booking.php">
                <span class="menu-icon">▤</span>
                Bookings
            </a>

            <a href="servises.php">
                <span class="menu-icon">⚙</span>
                Services
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="help-card">
                <strong>HomeServe Admin</strong>
                <p>Manage users, service providers and bookings from one place.</p>
            </div>

            <a href="../php/logout.php" class="logout">
                ↪ &nbsp; Sign out
            </a>
        </div>

    </aside>

    <!-- Main dashboard -->
    <main class="main">

        <header class="topbar">
            <div>
                <div class="breadcrumb">Home / Dashboard</div>
                <h1 class="page-title">Admin Dashboard</h1>
                <p class="page-subtitle">
                    Monitor and manage your HomeServe platform.
                </p>
            </div>

            <div class="admin-profile">
                <div class="avatar">
                    <?php echo strtoupper(substr($adminName, 0, 1)); ?>
                </div>

                <div>
                    <div class="profile-name"><?php echo $adminName; ?></div>
                    <div class="profile-role">Administrator</div>
                </div>
            </div>
        </header>

        <section class="welcome-banner">
            <h2>Welcome back, <?php echo $adminName; ?>! 👋</h2>
            <p>
                Here's an overview of your HomeServe platform.
                Manage customers, providers, bookings and services
                from your admin workspace.
            </p>
        </section>

        <section>
            <div class="section-heading">
                <div>
                    <h2>Platform Overview</h2>
                    <p>Current totals from your database</p>
                </div>
            </div>

            <div class="stats-grid">

                <div class="stat-card">
                    <div class="stat-top">
                        <span class="stat-label">Total Users</span>
                        <div class="stat-icon purple">♙</div>
                    </div>

                    <div class="stat-number">
                        <?php echo number_format($totalUsers); ?>
                    </div>

                    <div class="stat-caption">Registered platform users</div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <span class="stat-label">Total Providers</span>
                        <div class="stat-icon green">♧</div>
                    </div>

                    <div class="stat-number">
                        <?php echo number_format($totalProviders); ?>
                    </div>

                    <div class="stat-caption">Service provider accounts</div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <span class="stat-label">Total Bookings</span>
                        <div class="stat-icon orange">▤</div>
                    </div>

                    <div class="stat-number">
                        <?php echo number_format($totalBookings); ?>
                    </div>

                    <div class="stat-caption">All recorded bookings</div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <span class="stat-label">Total Services</span>
                        <div class="stat-icon blue">⚙</div>
                    </div>

                    <div class="stat-number">
                        <?php echo number_format($totalServices); ?>
                    </div>

                    <div class="stat-caption">Available service records</div>
                </div>

            </div>
        </section>

        <section class="management-section">

            <div class="section-heading">
                <div>
                    <h2>Quick Management</h2>
                    <p>Choose a section to manage your platform</p>
                </div>
            </div>

            <div class="management-grid">

                <a href="users.php" class="management-card">
                    <div class="management-icon purple">♙</div>
                    <div class="management-info">
                        <h3>User Management</h3>
                        <p>View and manage registered users.</p>
                    </div>
                    <span class="arrow">›</span>
                </a>

                <a href="providers.php" class="management-card">
                    <div class="management-icon green">♧</div>
                    <div class="management-info">
                        <h3>Provider Management</h3>
                        <p>Review and manage service providers.</p>
                    </div>
                    <span class="arrow">›</span>
                </a>

                <a href="booking.php" class="management-card">
                    <div class="management-icon orange">▤</div>
                    <div class="management-info">
                        <h3>Booking Management</h3>
                        <p>Review and manage customer bookings.</p>
                    </div>
                    <span class="arrow">›</span>
                </a>

                <a href="servises.php" class="management-card">
                    <div class="management-icon blue">⚙</div>
                    <div class="management-info">
                        <h3>Service Management</h3>
                        <p>Manage your platform's service records.</p>
                    </div>
                    <span class="arrow">›</span>
                </a>

            </div>

        </section>

        <footer class="footer">
            © <?php echo date("Y"); ?> HomeServe · Admin Dashboard
        </footer>

    </main>

</div>

</body>
</html>