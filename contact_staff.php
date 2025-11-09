<?php
session_start();
$conn = new mysqli("localhost", "root", "", "login");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Protect route to logged-in users
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$user_email = $_SESSION['email'];
$user = $conn->query("SELECT user_id, name FROM register WHERE email = '$user_email'")->fetch_assoc();
$user_id = $user['user_id'];
$username = $user['name'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showAlert('Message sent successfully!', 'success');
            });
        </script>";
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showAlert('Please enter a message.', 'error');
            });
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Support - Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Contact-specific styles */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.6) 0%, rgba(118, 75, 162, 0.6) 100%),
                        url('images/customer service.jpg') center/cover no-repeat;
            background-attachment: fixed;
            color: #333;
            display: flex;
            flex-direction: column;
        }

        .main-title {
            font-size: 2rem;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
            margin: 20px auto 10px;
            text-align: center;
            background: rgba(0, 0, 0, 0.6);
            display: block;
            padding: 10px 20px;
            border-radius: 12px;
            max-width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }

        .main-subtitle {
            font-size: 1.1rem;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
            margin: 0 auto 20px;
            text-align: center;
            max-width: 600px;
        }

        .container {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            padding: 0 20px 20px;
        }

        .contact-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            overflow: hidden;
        }

        .contact-content-wrapper {
            flex: 1;
            overflow-y: auto;
            padding-right: 10px;
        }

        .contact-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .contact-header h2 {
            color: #222;
            font-size: 1.5rem;
            margin-bottom: 8px;
        }

        .contact-header p {
            color: #666;
            font-size: 0.95rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #222;
            font-size: 0.95rem;
        }

        .form-group textarea {
            width: 100%;
            min-height: 120px;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            font-family: inherit;
            resize: none;
            transition: border-color 0.3s ease;
            background: #fff;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #ff4b2b;
        }

        .form-group textarea::placeholder {
            color: #888;
        }

        .btn {
            background: #ff4b2b;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn:hover {
            background: #e63946;
        }

        /* Alert Styles */
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 18px;
            border-radius: 6px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            opacity: 0;
            transform: translateX(100px);
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .alert.show {
            opacity: 1;
            transform: translateX(0);
        }

        .alert.success {
            background: #28a745;
        }

        .alert.error {
            background: #dc3545;
        }

        /* Support Info */
        .support-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            border-left: 4px solid #ff4b2b;
        }

        .support-info h3 {
            color: #222;
            margin-bottom: 8px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .support-info p {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                background-attachment: scroll;
            }

            .main-title {
                font-size: 1.8rem;
                padding: 8px 15px;
                margin: 15px auto 5px;
            }

            .main-subtitle {
                font-size: 1rem;
                margin: 0 auto 15px;
            }

            .contact-container {
                padding: 15px;
            }

            .contact-header h2 {
                font-size: 1.3rem;
            }

            .form-group textarea {
                min-height: 100px;
                padding: 8px 10px;
            }
        }

        @media (max-width: 480px) {
            .main-title {
                font-size: 1.6rem;
                padding: 8px 12px;
            }

            .main-subtitle {
                font-size: 0.9rem;
            }

            .contact-container {
                padding: 12px;
            }

            .contact-content-wrapper {
                padding-right: 5px;
            }

            .support-info {
                padding: 12px;
            }

            .support-info p {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav>
        <div class="logo">GARAGE</div>
        <button class="menu-toggle" aria-label="Toggle menu">&#9776;</button>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="service.php">Services</a></li>
            <li><a href="booking.php">Booking</a></li>
            <li><a href="contact_staff.php" class="active">Contact</a></li>
            <li><a href="profile.php">Profile</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <h1 class="main-title"><i class="fas fa-headset"></i> Send Feedback</h1>
    <p class="main-subtitle">We're here to help you with any questions or concerns</p>

    <div class="container">
        <div class="contact-container">
            <div class="contact-content-wrapper">
                <div class="contact-header">
                    <h2>Tell your Experience</h2>
                    <p>Our support team will get back to you within 24 hours</p>
                </div>

                <form method="post" id="contactForm">
                    <div class="form-group">
                        <label for="message">
                            <i class="fas fa-comment-dots"></i> Your Message *
                        </label>
                        <textarea 
                            name="message" 
                            id="message"
                            required 
                            placeholder="Please describe your question, issue, or feedback in detail. The more information you provide, the better we can assist you."
                        ></textarea>
                    </div>

                    <button type="submit" class="btn">
                        <i class="fas fa-paper-plane"></i>
                        Send Message
                    </button>
                </form>

                <div class="support-info">
                    <h3><i class="fas fa-info-circle"></i> Need Immediate Help?</h3>
                    <p>For urgent matters, you can also reach us at <strong>support@company.com</strong> or call our support hotline at <strong>+1 (555) 123-4567</strong>. Our team is available Monday through Friday, 9 AM to 6 PM EST.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const navLinks = document.querySelector('nav ul');
            
            if (menuToggle && navLinks) {
                menuToggle.addEventListener('click', function() {
                    navLinks.classList.toggle('active');
                });
            }
        });

        function showAlert(message, type) {
            const alert = document.createElement('div');
            alert.className = `alert ${type}`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;
            document.body.appendChild(alert);

            setTimeout(() => {
                alert.classList.add('show');
            }, 100);

            setTimeout(() => {
                alert.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(alert);
                }, 300);
            }, 4000);
        }

        // Form validation
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            const message = document.getElementById('message').value.trim();
            if (message.length < 10) {
                e.preventDefault();
                showAlert('Please enter a more detailed message (at least 10 characters).', 'error');
            }
        });

        // Auto-resize textarea
        document.getElementById('message').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    </script>
</body>
</html>