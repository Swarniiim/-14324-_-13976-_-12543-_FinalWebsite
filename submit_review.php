<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] == 1) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$game_id = $_POST['game_id'] ?? null;
$review  = trim($_POST['review'] ?? '');

if (!$game_id || empty($review)) {
    $_SESSION['error'] = "Review cannot be empty.";
    header("Location: game_details.php?id=" . $game_id);
    exit;
}

$stmt = $conn->prepare("INSERT INTO reviews (user_id, game_id, review) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $user_id, $game_id, $review);

if ($stmt->execute()) {
    $_SESSION['success'] = "✅ Review submitted!";
} else {
    $_SESSION['error'] = "Something went wrong. Try again.";
}

header("Location: game_details.php?id=" . $game_id);
exit;
?>
