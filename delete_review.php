<?php
session_start();
require_once "includes/db.php";

// Only allow admin to delete reviews
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get the review ID from the POST request
$review_id = $_POST['review_id'] ?? null;

if (!$review_id) {
    echo json_encode(['success' => false, 'message' => 'Review ID is missing']);
    exit;
}

// Delete the review from the database
$stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
$stmt->bind_param("i", $review_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete review']);
}

$stmt->close();
$conn->close();
?>
