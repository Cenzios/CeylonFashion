<?php
// ------------------------------
// components/reviews-handler.php
// Handles POST requests for reviews and questions
// ------------------------------

function handle_reviews_post($mysqli, $product_id, $currentUser, $isLoggedIn) {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
  }

  // Check CSRF token (function should be defined in main file)
  if (!function_exists('csrf_verify')) {
    die('CSRF verification function not found');
  }

  $ok = isset($_POST['csrf_token']) && csrf_verify($_POST['csrf_token']);

  if (!$ok) {
    http_response_code(403);
    die("Invalid CSRF token.");
  }

  if (!$isLoggedIn) {
    if (function_exists('redirect_self')) {
      redirect_self(['msg'=>'login_required']);
    } else {
      header("Location: " . $_SERVER['PHP_SELF'] . '?msg=login_required');
      exit;
    }
  }

  // Handle review submission
  if (isset($_POST['submit_review'])) {
    $rating  = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    if ($rating >= 1 && $rating <= 5 && $comment !== '') {
      $stmt = $mysqli->prepare("INSERT INTO product_reviews (product_id, username, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
      if ($stmt === false) { 
        die('MySQL prepare failed: ' . $mysqli->error); 
      }
      $stmt->bind_param("isis", $product_id, $currentUser, $rating, $comment);
      if ($stmt->execute()) {
        $stmt->close();
        if (function_exists('redirect_self')) {
          redirect_self(['msg'=>'review_added']);
        } else {
          $params = array_merge($_GET, ['msg'=>'review_added']);
          header("Location: " . $_SERVER['PHP_SELF'] . '?' . http_build_query($params));
          exit;
        }
      } else {
        die('Failed to insert review: ' . $stmt->error);
      }
    } else {
      if (function_exists('redirect_self')) {
        redirect_self(['msg'=>'invalid_review']);
      } else {
        $params = array_merge($_GET, ['msg'=>'invalid_review']);
        header("Location: " . $_SERVER['PHP_SELF'] . '?' . http_build_query($params));
        exit;
      }
    }
  }

  // Handle question submission
  if (isset($_POST['submit_question'])) {
    $question = isset($_POST['question']) ? trim($_POST['question']) : '';
    if ($question !== '') {
      $stmt = $mysqli->prepare("INSERT INTO product_questions (product_id, username, question, created_at) VALUES (?, ?, ?, NOW())");
      $stmt->bind_param("iss", $product_id, $currentUser, $question);
      $stmt->execute();
      $stmt->close();
      if (function_exists('redirect_self')) {
        redirect_self(['msg'=>'question_added']);
      } else {
        $params = array_merge($_GET, ['msg'=>'question_added']);
        header("Location: " . $_SERVER['PHP_SELF'] . '?' . http_build_query($params));
        exit;
      }
    } else {
      if (function_exists('redirect_self')) {
        redirect_self(['msg'=>'invalid_question']);
      } else {
        $params = array_merge($_GET, ['msg'=>'invalid_question']);
        header("Location: " . $_SERVER['PHP_SELF'] . '?' . http_build_query($params));
        exit;
      }
    }
  }
}

// Fetch reviews with pagination
function fetch_reviews($mysqli, $product_id, $perPage, $offset) {
  $stmt = $mysqli->prepare("
    SELECT username, rating, comment, created_at
    FROM product_reviews
    WHERE product_id = ?
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
  ");
  $stmt->bind_param("iii", $product_id, $perPage, $offset);
  $stmt->execute();
  $result = $stmt->get_result();
  $reviews = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  return $reviews;
}

// Fetch questions with pagination
function fetch_questions($mysqli, $product_id, $perPage, $offset) {
  $stmt = $mysqli->prepare("
    SELECT username, question, created_at
    FROM product_questions
    WHERE product_id = ?
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
  ");
  $stmt->bind_param("iii", $product_id, $perPage, $offset);
  $stmt->execute();
  $result = $stmt->get_result();
  $questions = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  return $questions;
}

// Get review statistics
function get_review_stats($mysqli, $product_id) {
  $avg = 0.0; 
  $totalReviews = 0;
  $stmt = $mysqli->prepare("SELECT COALESCE(AVG(rating),0), COUNT(*) FROM product_reviews WHERE product_id = ?");
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $stmt->bind_result($avg, $totalReviews);
  $stmt->fetch();
  $stmt->close();
  return [
    'average' => round((float)$avg, 1),
    'total' => $totalReviews
  ];
}

// Get total counts for pagination
function get_reviews_count($mysqli, $product_id) {
  $count = 0;
  $stmt = $mysqli->prepare("SELECT COUNT(*) FROM product_reviews WHERE product_id = ?");
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $stmt->bind_result($count);
  $stmt->fetch();
  $stmt->close();
  return $count;
}

function get_questions_count($mysqli, $product_id) {
  $count = 0;
  $stmt = $mysqli->prepare("SELECT COUNT(*) FROM product_questions WHERE product_id = ?");
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $stmt->bind_result($count);
  $stmt->fetch();
  $stmt->close();
  return $count;
}