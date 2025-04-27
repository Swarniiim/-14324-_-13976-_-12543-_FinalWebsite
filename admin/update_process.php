<?php
session_start();
require_once "../includes/db.php";

// Admin access check
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../index.php");
    exit;
}

// Collect form data
$id = $_POST['id'];
$title = trim($_POST['title']);
$genre = trim($_POST['genre']);
$platform = trim($_POST['platform']);
$category = trim($_POST['category']);
$description = trim($_POST['description']);
$trailer = trim($_POST['trailer']);

// Prepare and execute the update
$stmt = $conn->prepare("UPDATE games SET title=?, genre=?, platform=?, category=?, description=?, trailer_url=? WHERE id=?");
$stmt->bind_param("ssssssi", $title, $genre, $platform, $category, $description, $trailer, $id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Game updated successfully!";
    header("Location: update_game.php");
    exit;
} else {
    $_SESSION['error'] = "Failed to update game.";
    header("Location: update_game.php");
    exit;
}
?>
