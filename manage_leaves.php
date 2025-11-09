<?php
// manage_leaves.php
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

// Handle leave operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $leave_id = $_POST['leave_id'];
                $status = $_POST['status'];
                $sql = "UPDATE leave_applications SET status = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $status, $leave_id);
                
                if ($stmt->execute()) {
                    $message = "Leave application " . $status . " successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error updating leave status: " . $conn->error;
                    $messageType = "error";
                }
                break;
                
            case 'delete_leave':
                $leave_id = $_POST['leave_id'];
                $sql = "DELETE FROM leave_applications WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $leave_id);
                
                if ($stmt->execute()) {
                    $message = "Leave application deleted successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error deleting leave application: " . $conn->error;
                    $messageType = "error";
                }
                break;
        }
    }
}

// Get leave statistics
$total_leaves_query = "SELECT COUNT(*) as total FROM leave_applications";
$total_leaves_result = $conn->query($total_leaves_query);
$total_leaves = $total_leaves_result->fetch_assoc()['total'];

// Get pending leaves
$pending_leaves_query = "SELECT COUNT(*) as count FROM leave_applications WHERE status IS NULL OR status = 'pending'";
$pending_leaves_result = $conn->query($pending_leaves_query);
$pending_leaves = $pending_leaves_result->fetch_assoc()['count'];

// Get approved leaves
$approved_leaves_query = "SELECT COUNT(*) as count FROM leave_applications WHERE status = 'approved'";
$approved_leaves_result = $conn->query($approved_leaves_query);
$approved_leaves = $approved_leaves_result->fetch_assoc()['count'];

// Get active leaves (currently on leave)
$active_leaves_query = "SELECT COUNT(*) as count FROM leave_applications WHERE CURDATE() BETWEEN for_when AND till_when AND status = 'approved'";
$active_leaves_result = $conn->query($active_leaves_query);
$active_leaves = $active_leaves_result->fetch_assoc()['count'];

// Fetch all leave applications with staff details
$leaves_query = "
    SELECT 
        la.*,
        s.staffname,
        s.email as staff_email,
        s.phone as staff_phone,
        DATEDIFF(la.till_when, la.for_when) + 1 as duration_days
    FROM leave_applications la
    LEFT JOIN staff s ON la.staff_id = s.staff_id
    ORDER BY la.created_at DESC
";
$leaves_result = $conn->query($leaves_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Leave Applications - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 5px;
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
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-item {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-5px);
        }
        
        .stat-item.total {
            background: linear-gradient(135deg, #48c9b0, #1dd1a1);
        }
        
        .stat-item.pending {
            background: linear-gradient(135deg, #feca57, #ff9f43);
        }
        
        .stat-item.approved {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .stat-item.active {
            background: linear-gradient(135deg, #a8e6cf, #7fcdcd);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* Button Styles */
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #48c9b0, #1dd1a1);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
        }
        
        .btn-info {
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            color: white;
        }
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 0.85rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        /* Leave Card */
        .leave-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            border-left: 4px solid #667eea;
        }
        
        .leave-card:hover {
            transform: translateY(-5px);
        }
        
        .leave-card.pending {
            border-left-color: #ffc107;
        }
        
        .leave-card.approved {
            border-left-color: #28a745;
        }
        
        .leave-card.rejected {
            border-left-color: #dc3545;
        }
        
        .leave-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .staff-info {
            flex: 1;
        }
        
        .staff-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .staff-email {
            color: #667eea;
            font-size: 0.9rem;
        }
        
        .leave-status {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .leave-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .detail-item i {
            color: #667eea;
            width: 16px;
        }
        
        .leave-reason {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        
        .leave-reason h4 {
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 1rem;
        }
        
        .leave-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        /* Alert Styles */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
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
            
            .main-content {
                margin-left: 0;
            }
            
            .content-header {
                flex-direction: column;
                gap: 20px;
                align-items: stretch;
            }
            
            .leave-actions {
                justify-content: center;
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
                    <a href="admin.php" class="nav-link">
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
                    <a href="manage_leaves.php" class="nav-link active">
                        <i class="fas fa-calendar-times"></i>
                        <span>Manage Leave</span>
                    </a>
                </li>
                 <li class="nav-item">
                    <a href="manage_feedback.php" class="nav-link">
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
                <h1>Manage Leave Applications</h1>
            </div>
        </div>

        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="alert <?php echo ($messageType === 'success') ? 'alert-success' : 'alert-error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="content-card">
            <div class="content-header">
                <h2><i class="fas fa-chart-line"></i> Leave Statistics</h2>
            </div>
            <div class="stats-grid">
                <div class="stat-item total">
                    <span class="stat-number"><?php echo $total_leaves; ?></span>
                    <span class="stat-label">Total Applications</span>
                </div>
                <div class="stat-item pending">
                    <span class="stat-number"><?php echo $pending_leaves; ?></span>
                    <span class="stat-label">Pending Approval</span>
                </div>
                <div class="stat-item approved">
                    <span class="stat-number"><?php echo $approved_leaves; ?></span>
                    <span class="stat-label">Approved</span>
                </div>
                <div class="stat-item active">
                    <span class="stat-number"><?php echo $active_leaves; ?></span>
                    <span class="stat-label">Currently On Leave</span>
                </div>
            </div>
        </div>

        <!-- Leave Applications List -->
        <div class="content-card">
            <div class="content-header">
                <h2><i class="fas fa-calendar-times"></i> All Leave Applications</h2>
            </div>

            <?php if ($leaves_result && $leaves_result->num_rows > 0): ?>
                <?php while ($leave = $leaves_result->fetch_assoc()): 
                    $approval_status = $leave['status'] ?: 'pending';
                ?>
                    <div class="leave-card <?php echo $approval_status; ?>">
                        <div class="leave-header">
                            <div class="staff-info">
                                <div class="staff-name"><?php echo htmlspecialchars($leave['staffname'] ?? 'Unknown Staff'); ?></div>
                                <div class="staff-email"><?php echo htmlspecialchars($leave['staff_email'] ?? 'No email'); ?></div>
                            </div>
                            <div>
                                <div class="leave-status status-<?php echo $approval_status; ?>">
                                    <?php 
                                    $status_text = ucfirst($approval_status);
                                    if ($approval_status === 'pending') $status_text = 'Pending Approval';
                                    echo $status_text; 
                                    ?>
                                </div>
                                <div style="text-align: center; color: #667eea; font-weight: 600;">
                                    <?php echo $leave['duration_days']; ?> day(s)
                                </div>
                            </div>
                        </div>

                        <div class="leave-details">
                            <div class="detail-item">
                                <i class="fas fa-calendar-alt"></i>
                                From: <?php echo date('M d, Y', strtotime($leave['for_when'])); ?>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-calendar-check"></i>
                                To: <?php echo date('M d, Y', strtotime($leave['till_when'])); ?>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-clock"></i>
                                Applied: <?php echo date('M d, Y H:i', strtotime($leave['created_at'])); ?>
                            </div>
                            <?php if ($leave['staff_phone']): ?>
                            <div class="detail-item">
                                <i class="fas fa-phone"></i>
                                <?php echo htmlspecialchars($leave['staff_phone']); ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="leave-reason">
                            <h4><i class="fas fa-comment-dots"></i> Leave Reason</h4>
                            <p><?php echo nl2br(htmlspecialchars($leave['leave_reason'])); ?></p>
                        </div>

                        <div class="leave-actions">
                            <?php if ($approval_status === 'pending'): ?>
                                <form method="POST" action="manage_leaves.php" style="display:inline-block;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to approve this leave application?')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" action="manage_leaves.php" style="display:inline-block;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject this leave application?')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="POST" action="manage_leaves.php" style="display:inline-block;">
                                <input type="hidden" name="action" value="delete_leave">
                                <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this leave application?')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info" style="text-align: center; padding: 40px; background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;">
                    <i class="fas fa-info-circle" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                    <h3>No Leave Applications Found</h3>
                    <p>There are no leave applications in the system yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }
    </script>
</body>
</html>
