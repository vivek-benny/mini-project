<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? 'user') !== 'user') {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "login");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get booking_id from URL parameter
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

// Verify that this booking belongs to the current user
if ($booking_id > 0) {
    $verify_stmt = $conn->prepare("
        SELECT b.booking_id, b.status, r.user_id 
        FROM bookings b 
        JOIN register r ON b.user_id = r.user_id 
        WHERE b.booking_id = ? AND r.email = ?
    ");
    $verify_stmt->bind_param("is", $booking_id, $_SESSION['email']);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Booking not found or doesn't belong to user
        header("Location: profile.php");
        exit();
    }
    
    $booking_data = $result->fetch_assoc();
    $user_id = $booking_data['user_id'];
} else {
    header("Location: profile.php");
    exit();
}

// Check if feedback already exists for this booking
$feedback_check = $conn->prepare("SELECT feedback_id FROM customer_feedback WHERE booking_id = ?");
$feedback_check->bind_param("i", $booking_id);
$feedback_check->execute();
$feedback_result = $feedback_check->get_result();

if ($feedback_result->num_rows > 0) {
    // Feedback already submitted
    header("Location: profile.php#bookings-tab");
    exit();
}

// Handle feedback submission
if (isset($_POST['submit_feedback'])) {
    $rating = intval($_POST['rating']);
    $comments = trim($_POST['comments']);
    
    // Validate rating
    if ($rating >= 1 && $rating <= 5) {
        // Insert feedback
        $insert_feedback = $conn->prepare("INSERT INTO customer_feedback (user_id, booking_id, rating, comments) VALUES (?, ?, ?, ?)");
        $insert_feedback->bind_param("iiis", $user_id, $booking_id, $rating, $comments);
        
        if ($insert_feedback->execute()) {
            // Redirect to profile with success message
            $_SESSION['feedback_success'] = "Thank you for your feedback!";
            header("Location: profile.php#bookings-tab");
            exit();
        } else {
            $error_message = "Error submitting feedback. Please try again.";
        }
    } else {
        $error_message = "Please select a rating between 1 and 5 stars.";
    }
}

// Handle "Later" option
if (isset($_POST['later'])) {
    // Just redirect back to profile
    header("Location: profile.php#bookings-tab");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Feedback - GARAGE</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.8) 0%, rgba(118, 75, 162, 0.8) 100%),
                        url('images/profile-bg.jpg') center/cover no-repeat fixed;
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #ff6b35, #f7931e);
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #666;
            font-size: 1.1rem;
        }

        .booking-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid #ff6b35;
        }

        .booking-info h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
        }

        .info-value {
            color: #2c3e50;
        }

        .feedback-form {
            margin-top: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .rating-stars {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .star {
            font-size: 2.5rem;
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .star:hover,
        .star.selected,
        .star.active {
            color: #ff6b35;
            transform: scale(1.1);
        }

        .rating-text {
            margin-top: 0.5rem;
            font-weight: 600;
            color: #ff6b35;
            font-size: 1.1rem;
            text-align: center;
        }

        textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
            min-height: 120px;
            transition: all 0.3s ease;
        }

        textarea:focus {
            outline: none;
            border-color: #ff6b35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-submit {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
        }

        .btn-later {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid #f5c6cb;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid #c3e6cb;
        }

        @media (max-width: 768px) {
            .container {
                margin: 20px auto;
                padding: 1.5rem;
            }
            
            .buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-star"></i> Service Feedback</h1>
            <p>We value your opinion! Please share your experience with our service.</p>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="booking-info">
            <h3><i class="fas fa-receipt"></i> Booking Details</h3>
            <div class="info-row">
                <span class="info-label">Booking ID:</span>
                <span class="info-value">#<?php echo htmlspecialchars($booking_id); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value"><?php echo htmlspecialchars($booking_data['status']); ?></span>
            </div>
        </div>
        
        <form method="POST" class="feedback-form">
            <div class="form-group">
                <label>How would you rate our service?</label>
                <div class="rating-stars">
                    <span class="star" data-rating="1">&#9733;</span>
                    <span class="star" data-rating="2">&#9733;</span>
                    <span class="star" data-rating="3">&#9733;</span>
                    <span class="star" data-rating="4">&#9733;</span>
                    <span class="star" data-rating="5">&#9733;</span>
                </div>
                <div class="rating-text" id="rating-text">Select a rating</div>
                <input type="hidden" name="rating" id="rating-input" value="0" required>
            </div>
            
            <div class="form-group">
                <label for="comments">Additional Comments (Optional)</label>
                <textarea name="comments" id="comments" placeholder="Please share any additional feedback about your experience..."></textarea>
            </div>
            
            <div class="buttons">
                <button type="submit" name="submit_feedback" class="btn btn-submit">
                    <i class="fas fa-paper-plane"></i> Submit Feedback
                </button>
                <button type="submit" name="later" class="btn btn-later">
                    <i class="fas fa-clock"></i> Later
                </button>
            </div>
        </form>
    </div>

    <script>
        // Star rating functionality
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star');
            const ratingInput = document.getElementById('rating-input');
            const ratingText = document.getElementById('rating-text');
            
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    ratingInput.value = rating;
                    
                    // Update star appearance
                    stars.forEach((s, index) => {
                        if (index < rating) {
                            s.classList.add('selected');
                        } else {
                            s.classList.remove('selected');
                        }
                    });
                    
                    // Update rating text
                    const ratingLabels = ['Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
                    ratingText.textContent = ratingLabels[rating - 1];
                });
                
                star.addEventListener('mouseover', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    
                    // Highlight stars up to hovered star
                    stars.forEach((s, index) => {
                        if (index < rating) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                });
            });
            
            // Remove hover effect when mouse leaves stars container
            document.querySelector('.rating-stars').addEventListener('mouseleave', function() {
                stars.forEach(star => {
                    star.classList.remove('active');
                });
                
                // Re-apply selected class to previously selected stars
                const currentRating = parseInt(ratingInput.value);
                if (currentRating > 0) {
                    stars.forEach((s, index) => {
                        if (index < currentRating) {
                            s.classList.add('selected');
                        } else {
                            s.classList.remove('selected');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>