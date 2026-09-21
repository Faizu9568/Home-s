<?php
session_start();

echo "<h1>Session Test</h1>";

echo "<p><strong>User ID:</strong> ";
echo $_SESSION["user_id"] ?? "NOT SET";
echo "</p>";

echo "<p><strong>Name:</strong> ";
echo htmlspecialchars($_SESSION["user_name"] ?? "NOT SET");
echo "</p>";

echo "<p><strong>Email:</strong> ";
echo htmlspecialchars($_SESSION["user_email"] ?? "NOT SET");
echo "</p>";

echo "<p><strong>Role:</strong> ";
echo htmlspecialchars($_SESSION["user_role"] ?? "NOT SET");
echo "</p>";
?>
