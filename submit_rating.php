<?php
session_start();
header('Content-Type: application/json');
require_once "includes/db.php";

// Make sure user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$game_id = $_POST['game_id'] ?? null;
$rating  = $_POST['rating'] ?? null;

// Basic validation
if (!$game_id || !$rating || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Insert or update the rating
$stmt = $conn->prepare("INSERT INTO ratings (user_id, game_id, rating) 
                        VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE rating = VALUES(rating)");
$stmt->bind_param("iii", $user_id, $game_id, $rating);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
$stmt->close();
?>
