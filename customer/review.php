<?php

session_start();
include "../config/database.php";

/* =========================
   CUSTOMER LOGIN CHECK
========================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../html/login.html");
    exit();
}

$customerId = (int) $_SESSION["user_id"];


/* =========================
   GET BOOKING ID
========================= */

$bookingId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($bookingId <= 0) {
    die("Invalid booking ID.");
}


/* =========================
   GET COMPLETED BOOKING
========================= */

$query = $conn->prepare(
    "SELECT
        bookings.id,
        bookings.customer_id,
        bookings.provider_id,
        bookings.status,
        services.name AS service_name,
        users.name AS provider_name
     FROM bookings
     JOIN services
        ON bookings.service_id = services.id
     LEFT JOIN providers
        ON bookings.provider_id = providers.id
     LEFT JOIN users
        ON providers.user_id = users.id
     WHERE bookings.id = ?
       AND bookings.customer_id = ?
       AND bookings.status = 'completed'
     LIMIT 1"
);

if (!$query) {
    die("Booking query failed: " . $conn->error);
}

$query->bind_param("ii", $bookingId, $customerId);
$query->execute();

$result = $query->get_result();

if ($result->num_rows === 0) {
    die("Completed booking not found.");
}

$booking = $result->fetch_assoc();

$query->close();


$providerId = (int) $booking["provider_id"];


/* =========================
   CHECK EXISTING REVIEW
========================= */

$reviewCheck = $conn->prepare(
    "SELECT id, rating, comment
     FROM reviews
     WHERE booking_id = ?
       AND customer_id = ?
     LIMIT 1"
);

if (!$reviewCheck) {
    die("Review check failed: " . $conn->error);
}

$reviewCheck->bind_param("ii", $bookingId, $customerId);
$reviewCheck->execute();

$reviewResult = $reviewCheck->get_result();

$existingReview = null;

if ($reviewResult->num_rows > 0) {
    $existingReview = $reviewResult->fetch_assoc();
}

$reviewCheck->close();


/* =========================
   SUBMIT REVIEW
========================= */

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $rating = isset($_POST["rating"])
        ? (int) $_POST["rating"]
        : 0;

    $comment = trim($_POST["comment"] ?? "");


    /* Validate rating */

    if ($rating < 1 || $rating > 5) {

        $message = "Please select a rating between 1 and 5.";

    } elseif ($existingReview) {

        $message = "You have already reviewed this booking.";

    } else {

        $insert = $conn->prepare(
            "INSERT INTO reviews
                (booking_id, customer_id, provider_id, rating, comment)
             VALUES
                (?, ?, ?, ?, ?)"
        );

        if (!$insert) {
            die("Review insert failed: " . $conn->error);
        }

        $insert->bind_param(
            "iiiis",
            $bookingId,
            $customerId,
            $providerId,
            $rating,
            $comment
        );

        if ($insert->execute()) {

            $insert->close();
            $conn->close();

            header(
                "Location: my_bookings.php?review=success"
            );
            exit();

        } else {

            $message = "Review could not be submitted: " . $insert->error;

        }

        $insert->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Service Review | HomeServe</title>

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
    width: 90%;
    max-width: 700px;
    margin: 50px auto;
}

.review-card {
    background: white;
    padding: 35px;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

h1 {
    color: #153b6d;
    margin-top: 0;
}

.service {
    font-size: 22px;
    font-weight: bold;
    color: #153b6d;
    margin-bottom: 10px;
}

.provider {
    margin-bottom: 30px;
    color: #555;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 10px;
}

.stars {
    display: flex;
    gap: 8px;
    margin-bottom: 25px;
}

.stars input {
    display: none;
}

.stars label {
    font-size: 40px;
    color: #ccc;
    cursor: pointer;
}

.stars input:checked ~ label {
    color: #ccc;
}

.stars label:hover,
.stars label:hover ~ label {
    color: #ffb400;
}

textarea {
    width: 100%;
    min-height: 130px;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    resize: vertical;
    font-family: Arial, sans-serif;
    font-size: 15px;
}

.submit-btn {
    margin-top: 20px;
    background: #ff7a00;
    color: white;
    border: none;
    padding: 13px 25px;
    border-radius: 7px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
}

.submit-btn:hover {
    background: #e66d00;
}

.back-btn {
    display: inline-block;
    margin-top: 15px;
    color: #153b6d;
    text-decoration: none;
    font-weight: bold;
}

.message {
    background: #fff3cd;
    color: #856404;
    padding: 12px;
    border-radius: 7px;
    margin-bottom: 20px;
}

.already-reviewed {
    background: #d1e7dd;
    color: #0f5132;
    padding: 20px;
    border-radius: 8px;
}

</style>

</head>

<body>

<nav>

    <strong>HomeServe</strong>

    <div>

        <a href="my_bookings.php">
            My Bookings
        </a>

        <a href="../php/logout.php">
            Logout
        </a>

    </div>

</nav>


<div class="container">

    <div class="review-card">

        <h1>Service Review</h1>

        <div class="service">
            <?php echo htmlspecialchars($booking["service_name"]); ?>
        </div>

        <div class="provider">
            Provider:
            <strong>
                <?php echo htmlspecialchars($booking["provider_name"] ?? "Provider"); ?>
            </strong>
        </div>


        <?php if ($message !== "") { ?>

            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php } ?>


        <?php if ($existingReview) { ?>

            <div class="already-reviewed">

                <h3>Thank you for your review!</h3>

                <p>
                    Your Rating:
                    <?php echo str_repeat("⭐", (int)$existingReview["rating"]); ?>
                </p>

                <p>
                    <?php echo htmlspecialchars($existingReview["comment"] ?? ""); ?>
                </p>

            </div>

        <?php } else { ?>


            <p>
                Your service has been completed.
                Please rate your provider.
            </p>


            <form method="POST">

                <label>
                    Your Rating
                </label>

                <div class="stars">

                    <input
                        type="radio"
                        id="star5"
                        name="rating"
                        value="5"
                    >

                    <label for="star5">★</label>


                    <input
                        type="radio"
                        id="star4"
                        name="rating"
                        value="4"
                    >

                    <label for="star4">★</label>


                    <input
                        type="radio"
                        id="star3"
                        name="rating"
                        value="3"
                    >

                    <label for="star3">★</label>


                    <input
                        type="radio"
                        id="star2"
                        name="rating"
                        value="2"
                    >

                    <label for="star2">★</label>


                    <input
                        type="radio"
                        id="star1"
                        name="rating"
                        value="1"
                    >

                    <label for="star1">★</label>

                </div>


                <label for="comment">
                    Your Comment
                </label>

                <textarea
                    id="comment"
                    name="comment"
                    placeholder="Write your experience..."
                ></textarea>


                <button
                    type="submit"
                    class="submit-btn"
                >
                    Submit Review
                </button>

            </form>


        <?php } ?>


        <a
            class="back-btn"
            href="my_bookings.php"
        >
            ← Back to My Bookings
        </a>

    </div>

</div>

</body>

</html>

<?php

$conn->close();

?>