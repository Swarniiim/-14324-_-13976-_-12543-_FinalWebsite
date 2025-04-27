<?php
session_start();
require_once "includes/db.php";

$game_id = $_GET['id'] ?? null;
if (!$game_id) {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM games WHERE id = ?");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$result = $stmt->get_result();
$game = $result->fetch_assoc();

if (!$game) {
    echo "Game not found.";
    exit;
}
$trailer_url = htmlspecialchars($game['trailer_url']);
// Check if favorited
$isFavorited = false;
if (isset($_SESSION['user_id']) && $_SESSION['is_admin'] == 0) {
    $user_id = $_SESSION['user_id'];
    $check = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND game_id = ?");
    $check->bind_param("ii", $user_id, $game['id']);
    $check->execute();
    $check->store_result();
    $isFavorited = $check->num_rows > 0;
    $check->close();
}

$userRating = 0;

if (isset($_SESSION['user_id']) && $_SESSION['is_admin'] == 0) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT rating FROM ratings WHERE user_id = ? AND game_id = ?");
    $stmt->bind_param("ii", $user_id, $game['id']);
    $stmt->execute();
    $stmt->bind_result($ratingValue);
    if ($stmt->fetch()) {
        $userRating = $ratingValue;
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($game['title']) ?> - Game Details</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">

<div class="container detail-container my-5">
  <div class="row g-4">
    <!-- Game Image -->
    <div class="col-md-6">
      <img src="img/<?= htmlspecialchars($game['image']) ?>" alt="<?= htmlspecialchars($game['title']) ?>" class="img-fluid rounded shadow">
    </div>

    <!-- Game Details -->
    <div class="col-md-6">
      <h2 class="fw-bold"><?= htmlspecialchars($game['title']) ?></h2>
      <p class="text-muted"><?= htmlspecialchars($game['platform']) ?></p>
      <p><strong>Genre:</strong> <?= htmlspecialchars($game['genre']) ?></p>
      <p><strong>Category:</strong> <?= ucfirst(htmlspecialchars($game['category'])) ?></p>
      <hr>
      <p><strong>Description:</strong> <?= htmlspecialchars($game['description']) ?></p>

      <!-- Favorites Button -->
      <?php if (isset($_SESSION['user_id']) && $_SESSION['is_admin'] == 0): ?>
        <button 
          id="favoriteBtn"
          class="btn favorite-toggle-btn <?= $isFavorited ? 'btn-danger' : 'btn-outline-danger' ?>"
          data-game-id="<?= $game['id'] ?>"
          data-favorited="<?= $isFavorited ? '1' : '0' ?>">
          <i class="bi <?= $isFavorited ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
          <span><?= $isFavorited ? 'Remove from Favorites' : 'Add to Favorites' ?></span>
        </button>
        <!-- Watch Trailer Button -->
        <button 
          id="watchTrailerBtn" 
          class="btn btn-outline-warning ms-3"
          data-bs-toggle="modal" 
          data-bs-target="#trailerModal">
          <i class="bi bi-play-circle"></i> Watch Trailer
        </button>
      <?php endif; ?>
    </div>
  </div>
  <!-- Modal for Trailer -->
  <div class="modal fade" id="trailerModal" tabindex="-1" aria-labelledby="trailerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="trailerModalLabel">Watch Trailer</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Embed YouTube Trailer -->
          <iframe id="trailerIframe" width="100%" height="400" src="" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
      </div>
    </div>
  </div>
 

  <!-- Review Section -->
  <?php if (isset($_SESSION['user_id']) && $_SESSION['is_admin'] == 0): ?>
  <hr class="my-4">
  <div class="review-box mt-3">
    <h5 class="mb-3">Leave a Review</h5>
    <form action="submit_review.php" method="POST">
      <input type="hidden" name="game_id" value="<?= $game['id'] ?>">

      <!-- Star Rating -->
      <label class="form-label">Your Rating:</label>
      <div class="star-rating-group d-flex gap-1 mb-3">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <label style="cursor: pointer;">
            <input type="radio" name="rating" value="<?= $i ?>" style="display: none;">
            <i class="bi bi-star-fill star-icon" data-value="<?= $i ?>"></i>
          </label>
        <?php endfor; ?>
      </div>

      <!-- Review -->
      <form id="reviewForm" method="POST">
  <input type="hidden" name="game_id" value="<?= $game['id'] ?>">
  <div class="mb-3">
    <label class="form-label">Your Review:</label>
    <textarea name="review" class="form-control" rows="3" placeholder="Write your thoughts..." required></textarea>
  </div>
  <button type="submit" class="btn btn-success">Submit Review</button>
</form>

    </form>
  </div>

  <?php endif; ?>

  <a href="index.php" class="btn btn-outline-secondary mt-4">← Back to Home</a>
</div>
<hr>
<h5>User Reviews</h5>

<?php
// Fetch the reviews from the database
$reviewStmt = $conn->prepare("
  SELECT r.id, r.review, r.created_at, u.name 
  FROM reviews r
  JOIN users u ON r.user_id = u.id 
  WHERE r.game_id = ?
  ORDER BY r.created_at DESC
");

$reviewStmt->bind_param("i", $game['id']);
$reviewStmt->execute();
$result = $reviewStmt->get_result();
?>

<?php if ($result->num_rows === 0): ?>
  <p class="text-muted">No reviews yet. Be the first to leave a review!</p>
<?php else: ?>
  <?php while ($row = $result->fetch_assoc()): ?>
    <div class="review-box mb-3 p-3 border rounded bg-light" id="review-<?= htmlspecialchars($row['id']) ?>">
      <div class="d-flex justify-content-between">
        <strong><?= htmlspecialchars($row['name']) ?></strong>
        <small class="text-muted"><?= date("M d, Y h:i A", strtotime($row['created_at'])) ?></small>
      </div>
      <p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($row['review'])) ?></p>
      
      <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
        <!-- Admin delete button -->
        <button class="btn btn-danger btn-sm mt-2" onclick="deleteReview(<?= htmlspecialchars($row['id']) ?>)">
          <i class="bi bi-trash"></i> Delete Review
        </button>
      <?php endif; ?>

    </div>
  <?php endwhile; ?>
<?php endif; ?>




<!-- Toast for Rating/Favorite Feedback -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
  <div id="favToast" class="toast align-items-center text-white bg-success border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="favToastText"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const userRating = <?= $userRating ?? 0 ?>;
const gameId = <?= json_encode($game['id']) ?>;

// Favorite toggle
const favoriteBtn = document.getElementById('favoriteBtn');
if (favoriteBtn) {
  favoriteBtn.addEventListener('click', () => {
    fetch('favorite_toggle_ajax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `game_id=${gameId}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.favorited) {
        favoriteBtn.classList.remove('btn-outline-danger');
        favoriteBtn.classList.add('btn-danger');
        favoriteBtn.innerHTML = '<i class="bi bi-heart-fill"></i> <span>Remove from Favorites</span>';
        showToast("❤️ Added to Favorites");
      } else {
        favoriteBtn.classList.remove('btn-danger');
        favoriteBtn.classList.add('btn-outline-danger');
        favoriteBtn.innerHTML = '<i class="bi bi-heart"></i> <span>Add to Favorites</span>';
        showToast("❌ Removed from Favorites");
      }
    });
  });
}

// Star rating logic
const stars = document.querySelectorAll('.star-icon');
let selectedRating = userRating;

// Pre-highlight if user has already rated
highlightStars(selectedRating);

stars.forEach((star, index) => {
  const value = index + 1;

  star.addEventListener('mouseover', () => highlightStars(value));
  star.addEventListener('mouseout', () => highlightStars(selectedRating));

  star.addEventListener('click', () => {
    selectedRating = value;
    document.querySelector(`input[name="rating"][value="${value}"]`).checked = true;
    highlightStars(selectedRating);

    fetch('submit_rating.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `game_id=${gameId}&rating=${selectedRating}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        showToast("⭐ Rating submitted!");
      } else {
        showToast("❌ Error saving rating");
      }
    });
  });
});

function highlightStars(rating) {
  stars.forEach((s, i) => {
    s.classList.toggle('selected', i < rating);
  });
}

function showToast(message) {
  const toastEl = document.getElementById('favToast');
  document.getElementById('favToastText').textContent = message;
  const bsToast = new bootstrap.Toast(toastEl);
  bsToast.show();
}
</script>
<script>
function deleteReview(reviewId) {
    if (confirm("Are you sure you want to delete this review?")) {
        fetch('delete_review.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `review_id=${reviewId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Review deleted successfully!");
                // Reload the page to update the reviews
                location.reload();
            } else {
                alert("Failed to delete review. Please try again.");
            }
        })
        .catch(error => console.error("Error deleting review:", error));
    }
}
</script>
<script>
// Function to extract YouTube video ID from the URL
function extractYouTubeVideoId(url) {
  var videoId = '';
  var regex = /(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;
  var matches = url.match(regex);
  if (matches) {
    videoId = matches[1];
  }
  return videoId;
}


const trailerUrl = '<?= $trailer_url ?>';
const trailerBtn = document.getElementById('watchTrailerBtn');
const trailerIframe = document.getElementById('trailerIframe');

if (trailerBtn) {
  trailerBtn.addEventListener('click', function() {
    const videoId = extractYouTubeVideoId(trailerUrl);
    if (videoId) {
      trailerIframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
    }
  });
}
</script>


</body>
</html>
