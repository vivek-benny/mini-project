<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "login";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$messageType = '';

// Fetch all feedback with user and booking details
$feedback_query = "SELECT 
                    cf.feedback_id,
                    cf.rating,
                    cf.comments,
                    cf.submitted_at,
                    r.name as customer_name,
                    r.email as customer_email,
                    b.booking_id,
                    b.appointment_date,
                    b.status as booking_status
                  FROM customer_feedback cf
                  JOIN register r ON cf.user_id = r.user_id
                  LEFT JOIN bookings b ON cf.booking_id = b.booking_id
                  ORDER BY cf.submitted_at DESC";

$feedback_result = $conn->query($feedback_query);

// Calculate average rating
$avg_rating_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_feedback FROM customer_feedback";
$avg_rating_result = $conn->query($avg_rating_query);
$avg_rating_data = $avg_rating_result->fetch_assoc();
$average_rating = $avg_rating_data['avg_rating'] ? round($avg_rating_data['avg_rating'], 2) : 0;
$total_feedback = $avg_rating_data['total_feedback'];

// Calculate rating distribution
$rating_distribution = [];
for ($i = 1; $i <= 5; $i++) {
    $rating_count_query = "SELECT COUNT(*) as count FROM customer_feedback WHERE rating = $i";
    $rating_count_result = $conn->query($rating_count_query);
    $rating_count_data = $rating_count_result->fetch_assoc();
    $rating_distribution[$i] = $rating_count_data['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Feedback Management - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('images/admin.jpg') center/cover no-repeat fixed;
            min-height: 100vh;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: -1;
        }
        
        /* Sidebar styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            padding: 20px;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .sidebar.collapsed {
            transform: translateX(-280px);
        }
        
        .sidebar-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 30px;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin-bottom: 10px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .nav-link i {
            margin-right: 15px;
            font-size: 1.2rem;
            width: 20px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        /* Top Bar */
        .top-bar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .menu-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #333;
            cursor: pointer;
            padding: 10px;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        
        .menu-toggle:hover {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .top-bar h1 {
            color: #2c3e50;
            font-size: 1.8rem;
        }
        
        /* Content Cards */
        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .content-header h2 {
            color: #2c3e50;
            font-size: 1.8rem;
        }
        
        /* Stats Section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
            color: #667eea;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 1rem;
        }
        
        .rating-display {
            color: #ff6b35;
            font-size: 2.8rem;
        }
        
        /* Rating Distribution */
        .rating-distribution {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .rating-bar {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .rating-label {
            width: 30px;
            font-weight: 600;
        }
        
        .bar-container {
            flex: 1;
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff6b35, #f7931e);
            border-radius: 5px;
        }
        
        .rating-count {
            width: 40px;
            text-align: right;
            font-size: 0.9rem;
        }
        
        /* Feedback List */
        .feedback-list {
            margin-top: 30px;
        }
        
        .feedback-item {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }
        
        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .customer-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .customer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .customer-details h4 {
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .customer-details p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .feedback-rating {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .star {
            color: #ddd;
            font-size: 1.2rem;
        }
        
        .star.filled {
            color: #ff6b35;
        }
        
        .feedback-content {
            margin-top: 15px;
        }
        
        .feedback-text {
            color: #495057;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .booking-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            font-size: 0.9rem;
        }
        
        .booking-info p {
            margin: 5px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .no-feedback {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }
        
        .no-feedback i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #e9ecef;
        }
        
        /* Alert Styles */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-280px);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .feedback-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-cogs"></i> Admin Panel</h2>
            <p>Garage</p>
        </div>
        <nav>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="adminbookingview.php" class="nav-link">
                        <i class="fas fa-calendar-check"></i>
                        <span>Manage Bookings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_users.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_staff.php" class="nav-link">
                        <i class="fas fa-user-tie"></i>
                        <span>Manage Staff</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_services.php" class="nav-link">
                        <i class="fas fa-wrench"></i>
                        <span>Manage Services</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_mechanics.php" class="nav-link">
                        <i class="fas fa-user-cog"></i>
                        <span>Manage Mechanics</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_leaves.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Manage Leave</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_feedback.php" class="nav-link active">
                        <i class="fas fa-star"></i>
                        <span>Manage Feedback</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settingsadmin.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <!-- Top Bar -->
        <div class="top-bar">
            <div style="display: flex; align-items: center; gap: 20px;">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Manage Feedback</h1>
            </div>
        </div>

        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="alert <?php echo ($messageType === 'success') ? 'alert-success' : 'alert-error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Stats Section -->
        <div class="content-card">
            <div class="content-header">
                <h2><i class="fas fa-chart-bar"></i> Feedback Overview</h2>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Average Rating</div>
                    <div class="rating-display">
                        <?php 
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $average_rating) {
                                echo '<i class="fas fa-star"></i>';
                            } elseif ($i - 0.5 <= $average_rating) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                    </div>
                    <div class="stat-value"><?php echo $average_rating; ?>/5</div>
                    <div class="stat-label">Based on <?php echo $total_feedback; ?> reviews</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Total Feedback</div>
                    <div class="stat-value"><?php echo $total_feedback; ?></div>
                    <div class="stat-label">Customer Reviews</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-label">Satisfaction Rate</div>
                    <div class="stat-value">
                        <?php 
                        $satisfaction_rate = $total_feedback > 0 ? round(($rating_distribution[4] + $rating_distribution[5]) / $total_feedback * 100, 1) : 0;
                        echo $satisfaction_rate;
                        ?>%
                    </div>
                    <div class="stat-label">4 & 5 Star Reviews</div>
                </div>
            </div>
            
            <h3 style="margin-top: 30px; margin-bottom: 20px; color: #2c3e50;">Rating Distribution</h3>
            <div class="rating-distribution">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <div class="rating-bar">
                    <div class="rating-label"><?php echo $i; ?></div>
                    <div class="bar-container">
                        <div class="bar-fill" style="width: <?php echo $total_feedback > 0 ? ($rating_distribution[$i] / $total_feedback * 100) : 0; ?>%"></div>
                    </div>
                    <div class="rating-count"><?php echo $rating_distribution[$i]; ?></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Feedback List -->
        <div class="content-card">
            <div class="content-header">
                <h2><i class="fas fa-comments"></i> Customer Feedback</h2>
            </div>
            
            <?php if ($feedback_result && $feedback_result->num_rows > 0): ?>
                <div class="feedback-list">
                    <?php while ($feedback = $feedback_result->fetch_assoc()): ?>
                    <div class="feedback-item">
                        <div class="feedback-header">
                            <div class="customer-info">
                                <div class="customer-avatar">
                                    <?php echo strtoupper(substr($feedback['customer_name'], 0, 1)); ?>
                                </div>
                                <div class="customer-details">
                                    <h4><?php echo htmlspecialchars($feedback['customer_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($feedback['customer_email']); ?></p>
                                </div>
                            </div>
                            <div class="feedback-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $feedback['rating'] ? 'filled' : ''; ?>"></i>
                                <?php endfor; ?>
                                <span style="margin-left: 10px; font-weight: 600;"><?php echo $feedback['rating']; ?>/5</span>
                            </div>
                        </div>
                        
                        <div class="feedback-content">
                            <?php if (!empty($feedback['comments'])): ?>
                                <div class="feedback-text">
                                    <?php echo htmlspecialchars($feedback['comments']); ?>
                                </div>
                            <?php else: ?>
                                <div class="feedback-text" style="font-style: italic; color: #6c757d;">
                                    No additional comments provided.
                                </div>
                            <?php endif; ?>
                            
                            <div class="booking-info">
                                <p><i class="fas fa-calendar"></i> Submitted: <?php echo date('M d, Y g:i A', strtotime($feedback['submitted_at'])); ?></p>
                                <?php if ($feedback['booking_id']): ?>
                                    <p><i class="fas fa-receipt"></i> Booking ID: #<?php echo $feedback['booking_id']; ?></p>
                                    <p><i class="fas fa-info-circle"></i> Booking Status: <?php echo htmlspecialchars($feedback['booking_status']); ?></p>
                                    <?php if ($feedback['appointment_date'] && $feedback['appointment_date'] !== '0000-00-00'): ?>
                                        <p><i class="fas fa-calendar-check"></i> Appointment: <?php echo date('M d, Y', strtotime($feedback['appointment_date'])); ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p><i class="fas fa-info-circle"></i> No booking associated</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-feedback">
                    <i class="fas fa-comment-slash"></i>
                    <h3>No Feedback Found</h3>
                    <p>There are no customer feedback submissions yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 300);
            });
        }, 5000);
    </script>
</body>
</html>
<?php
$conn->close();
?>