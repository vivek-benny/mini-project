<?php
// manage_mechanics.php - Fixed to prevent form resubmission on refresh
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "login";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get message from session and clear it immediately
$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['messageType'] ?? '';
unset($_SESSION['message'], $_SESSION['messageType']);

// Alter mechanics table to add new columns (run once)
$conn->query("ALTER TABLE mechanics ADD COLUMN IF NOT EXISTS joined_date DATE NULL");
$conn->query("ALTER TABLE mechanics ADD COLUMN IF NOT EXISTS address TEXT NULL");
$conn->query("ALTER TABLE mechanics ADD COLUMN IF NOT EXISTS phone_number VARCHAR(15) NULL");
$conn->query("ALTER TABLE mechanics ADD COLUMN IF NOT EXISTS email VARCHAR(100) NULL");

// Handle POST actions with Post/Redirect/Get pattern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_mechanic':
            // Form validation
            $errors = [];
            $name = trim($_POST['name']);
            $age = intval($_POST['age']);
            $profession = trim($_POST['profession']);
            $address = trim($_POST['address']);
            $phone_number = trim($_POST['phone_number']);
            $email = trim($_POST['email']);
            
            // Name validation - only alphabets and spaces
            if (empty($name)) {
                $errors[] = "Name is required";
            } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
                $errors[] = "Name should only contain alphabets and spaces";
            }
            
            // Age validation - between 18 and 100
            if ($age < 18 || $age > 100) {
                $errors[] = "Age must be between 18 and 100";
            }
            
            // Profession validation - only alphabets and spaces
            if (empty($profession)) {
                $errors[] = "Profession is required";
            } elseif (!preg_match("/^[a-zA-Z\s]+$/", $profession)) {
                $errors[] = "Profession should only contain alphabets and spaces";
            }
            
            // Phone number validation - exactly 10 digits
            if (empty($phone_number)) {
                $errors[] = "Phone number is required";
            } elseif (!preg_match("/^[0-9]{10}$/", $phone_number)) {
                $errors[] = "Phone number must be exactly 10 digits";
            }
            
            // Email validation
            if (empty($email)) {
                $errors[] = "Email is required";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Please enter a valid email address";
            }
            
            // If there are validation errors, redirect back with errors
            if (!empty($errors)) {
                $_SESSION['message'] = "Validation errors: " . implode(", ", $errors);
                $_SESSION['messageType'] = 'error';
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
            
            // Sanitize data
            $name = $conn->real_escape_string($name);
            $profession = $conn->real_escape_string($profession);
            $address = $conn->real_escape_string($address);
            $phone_number = $conn->real_escape_string($phone_number);
            $email = $conn->real_escape_string($email);
            
            $insert_mechanic_query = "INSERT INTO mechanics (name, age, profession, status, joined_date, address, phone_number, email) 
                                    VALUES ('$name', $age, '$profession', 'free', CURDATE(), '$address', '$phone_number', '$email')";
            
            if ($conn->query($insert_mechanic_query)) {
                $_SESSION['message'] = "Mechanic added successfully!";
                $_SESSION['messageType'] = 'success';
            } else {
                $_SESSION['message'] = "Error adding mechanic: " . $conn->error;
                $_SESSION['messageType'] = 'error';
            }
            
            // Redirect to prevent resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
            break;
            
        case 'update_mechanic':
            // Form validation
            $errors = [];
            $mechanic_id = intval($_POST['mechanic_id']);
            $name = trim($_POST['name']);
            $age = intval($_POST['age']);
            $profession = trim($_POST['profession']);
            $address = trim($_POST['address']);
            $phone_number = trim($_POST['phone_number']);
            $email = trim($_POST['email']);
            
            // Name validation - only alphabets and spaces
            if (empty($name)) {
                $errors[] = "Name is required";
            } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
                $errors[] = "Name should only contain alphabets and spaces";
            }
            
            // Age validation - between 18 and 100
            if ($age < 18 || $age > 100) {
                $errors[] = "Age must be between 18 and 100";
            }
            
            // Profession validation - only alphabets and spaces
            if (empty($profession)) {
                $errors[] = "Profession is required";
            } elseif (!preg_match("/^[a-zA-Z\s]+$/", $profession)) {
                $errors[] = "Profession should only contain alphabets and spaces";
            }
            
            // Phone number validation - exactly 10 digits
            if (empty($phone_number)) {
                $errors[] = "Phone number is required";
            } elseif (!preg_match("/^[0-9]{10}$/", $phone_number)) {
                $errors[] = "Phone number must be exactly 10 digits";
            }
            
            // Email validation
            if (empty($email)) {
                $errors[] = "Email is required";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Please enter a valid email address";
            }
            
            // If there are validation errors, redirect back with errors
            if (!empty($errors)) {
                $_SESSION['message'] = "Validation errors: " . implode(", ", $errors);
                $_SESSION['messageType'] = 'error';
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
            
            // Sanitize data
            $name = $conn->real_escape_string($name);
            $profession = $conn->real_escape_string($profession);
            $address = $conn->real_escape_string($address);
            $phone_number = $conn->real_escape_string($phone_number);
            $email = $conn->real_escape_string($email);
            
            $update_mechanic_query = "UPDATE mechanics SET 
                                    name = '$name',
                                    age = $age,
                                    profession = '$profession',
                                    address = '$address',
                                    phone_number = '$phone_number',
                                    email = '$email'
                                    WHERE mechanic_id = $mechanic_id";
            
            if ($conn->query($update_mechanic_query)) {
                $_SESSION['message'] = "Mechanic updated successfully!";
                $_SESSION['messageType'] = 'success';
            } else {
                $_SESSION['message'] = "Error updating mechanic: " . $conn->error;
                $_SESSION['messageType'] = 'error';
            }
            
            // Redirect to prevent resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
            break;
            
        case 'delete_mechanic':
            $mechanic_id = intval($_POST['mechanic_id']);
            $delete_mechanic_query = "DELETE FROM mechanics WHERE mechanic_id = $mechanic_id";
            
            if ($conn->query($delete_mechanic_query)) {
                $_SESSION['message'] = "Mechanic deleted successfully!";
                $_SESSION['messageType'] = 'success';
            } else {
                $_SESSION['message'] = "Error deleting mechanic: " . $conn->error;
                $_SESSION['messageType'] = 'error';
            }
            
            // Redirect to prevent resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
            break;
    }
}

// Fetch all mechanics
$mechanics_query = "SELECT * FROM mechanics ORDER BY joined_date DESC, name";
$mechanics_result = $conn->query($mechanics_query);

// Get mechanics statistics
$total_mechanics = $conn->query("SELECT COUNT(*) as count FROM mechanics")->fetch_assoc()['count'];
$free_mechanics = $conn->query("SELECT COUNT(*) as count FROM mechanics WHERE status = 'free'")->fetch_assoc()['count'];
$assigned_mechanics = $conn->query("SELECT COUNT(*) as count FROM mechanics WHERE status = 'assigned'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Mechanics - Admin Dashboard</title>
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
        
        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #feca57, #ff9f43);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
        }
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 0.85rem;
        }
        
        /* Mechanics Grid */
        .mechanics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .mechanic-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .mechanic-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #28a745, #20c997);
        }
        
        .mechanic-card:hover {
            transform: translateY(-5px);
        }
        
        .mechanic-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .mechanic-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .mechanic-profession {
            background: #e9ecef;
            color: #495057;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .mechanic-details {
            margin-bottom: 20px;
        }
        
        .mechanic-detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .mechanic-detail-item i {
            color: #28a745;
            width: 16px;
        }
        
        .mechanic-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        /* Status Badge */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-free {
            background: #d4edda;
            color: #155724;
        }
        
        .status-assigned {
            background: #fff3cd;
            color: #856404;
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
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: white;
            margin: 2% auto;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        
        .close:hover {
            color: #000;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #28a745, #20c997);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #28a745;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-280px);
            }
            .main-content {
                margin-left: 0;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .mechanics-grid {
                grid-template-columns: 1fr;
            }
            .mechanic-actions {
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
                    <a href="manage_services.php" class="nav-link">
                        <i class="fas fa-wrench"></i>
                        <span>Manage Services</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_mechanics.php" class="nav-link active">
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
                <h1>Manage Mechanics</h1>
            </div>
        </div>

        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="alert <?php echo ($messageType === 'success') ? 'alert-success' : 'alert-error'; ?>">
                <i class="fas fa-<?php echo ($messageType === 'success') ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Mechanics Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_mechanics; ?></div>
                <div class="stat-label">Total Mechanics</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $free_mechanics; ?></div>
                <div class="stat-label">Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $assigned_mechanics; ?></div>
                <div class="stat-label">Assigned</div>
            </div>
        </div>

        <!-- Add New Mechanic Form -->
        <div class="content-card">
            <h2>Add New Mechanic</h2>
            <p style="color: #6c757d; margin-bottom: 20px;">
                <i class="fas fa-info-circle"></i> Status will be set to "Available" and joined date will be automatically set to today's date.
            </p>
            <form method="POST" action="" id="addMechanicForm">
                <input type="hidden" name="action" value="add_mechanic" />
                <div class="form-grid">
                    <div class="form-group">
                        <label for="mechanic_name">Full Name</label>
                        <input type="text" name="name" id="mechanic_name" required pattern="[a-zA-Z\s]+" title="Name should only contain letters and spaces" />
                    </div>
                    <div class="form-group">
                        <label for="mechanic_age">Age</label>
                        <input type="number" name="age" id="mechanic_age" min="18" max="100" required title="Age must be between 18 and 100" />
                    </div>
                    <div class="form-group">
                        <label for="mechanic_profession">Profession/Specialization</label>
                        <input type="text" name="profession" id="mechanic_profession" placeholder="e.g., Engine Specialist, Brake Expert" required pattern="[a-zA-Z\s]+" title="Profession should only contain letters and spaces" />
                    </div>
                    <div class="form-group">
                        <label for="mechanic_phone">Phone Number</label>
                        <input type="tel" name="phone_number" id="mechanic_phone" required pattern="[0-9]{10}" title="Phone number must be exactly 10 digits" maxlength="10" />
                    </div>
                    <div class="form-group">
                        <label for="mechanic_email">Email Address</label>
                        <input type="email" name="email" id="mechanic_email" required title="Please enter a valid email address" />
                    </div>
                    <div class="form-group">
                        <label for="mechanic_address">Address</label>
                        <textarea name="address" id="mechanic_address" rows="3" required></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Mechanic
                </button>
            </form>
        </div>

        <!-- Existing Mechanics List -->
        <div class="content-card">
            <h2>Existing Mechanics (<?php echo $total_mechanics; ?>)</h2>
            <?php if ($mechanics_result && $mechanics_result->num_rows > 0): ?>
                <div class="mechanics-grid">
                    <?php while ($mechanic = $mechanics_result->fetch_assoc()): ?>
                        <div class="mechanic-card">
                            <div class="mechanic-header">
                                <div>
                                    <div class="mechanic-name"><?php echo htmlspecialchars($mechanic['name']); ?></div>
                                    <div class="mechanic-profession"><?php echo htmlspecialchars($mechanic['profession']); ?></div>
                                </div>
                                <div>
                                    <span class="status-badge <?php echo ($mechanic['status'] === 'free') ? 'status-free' : 'status-assigned'; ?>">
                                        <?php echo htmlspecialchars(strtoupper($mechanic['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mechanic-details">
                                <div class="mechanic-detail-item">
                                    <i class="fas fa-birthday-cake"></i>
                                    <span><?php echo htmlspecialchars($mechanic['age']); ?> years old</span>
                                </div>
                                <div class="mechanic-detail-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Joined: <?php echo htmlspecialchars($mechanic['joined_date'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="mechanic-detail-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($mechanic['phone_number'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="mechanic-detail-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($mechanic['email'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="mechanic-detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars(substr($mechanic['address'] ?? 'N/A', 0, 50)) . (strlen($mechanic['address'] ?? '') > 50 ? '...' : ''); ?></span>
                                </div>
                            </div>
                            
                            <div class="mechanic-actions">
                                <button class="btn btn-warning btn-sm" onclick="editMechanic(<?php echo $mechanic['mechanic_id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this mechanic?')">
                                    <input type="hidden" name="action" value="delete_mechanic" />
                                    <input type="hidden" name="mechanic_id" value="<?php echo $mechanic['mechanic_id']; ?>" />
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>No mechanics found. Add a new mechanic above.</p>
            <?php endif; ?>
        </div>
    </main>

    <!-- Edit Mechanic Modal -->
    <div id="editMechanicModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeMechanicModal()">&times;</span>
            <h2>Edit Mechanic</h2>
            <p style="color: #6c757d; margin-bottom: 20px;">
                <i class="fas fa-info-circle"></i> Status and joined date cannot be modified here. Status is managed by staff assignments.
            </p>
            <form method="POST" action="" id="editMechanicForm">
                <input type="hidden" name="action" value="update_mechanic" />
                <input type="hidden" name="mechanic_id" id="edit_mechanic_id" />
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_mechanic_name">Full Name</label>
                        <input type="text" name="name" id="edit_mechanic_name" required pattern="[a-zA-Z\s]+" title="Name should only contain letters and spaces" />
                    </div>
                    <div class="form-group">
                        <label for="edit_mechanic_age">Age</label>
                        <input type="number" name="age" id="edit_mechanic_age" min="18" max="100" required title="Age must be between 18 and 100" />
                    </div>
                    <div class="form-group">
                        <label for="edit_mechanic_profession">Profession/Specialization</label>
                        <input type="text" name="profession" id="edit_mechanic_profession" required pattern="[a-zA-Z\s]+" title="Profession should only contain letters and spaces" />
                    </div>
                    <div class="form-group">
                        <label for="edit_mechanic_phone">Phone Number</label>
                        <input type="tel" name="phone_number" id="edit_mechanic_phone" required pattern="[0-9]{10}" title="Phone number must be exactly 10 digits" maxlength="10" />
                    </div>
                    <div class="form-group">
                        <label for="edit_mechanic_email">Email Address</label>
                        <input type="email" name="email" id="edit_mechanic_email" required title="Please enter a valid email address" />
                    </div>
                    <div class="form-group">
                        <label for="edit_mechanic_address">Address</label>
                        <textarea name="address" id="edit_mechanic_address" rows="3" required></textarea>
                    </div>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeMechanicModal()" style="margin-right: 10px; background: #6c757d; color: white;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Mechanic</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Get mechanics data for edit modal
        const mechanicsData = <?php echo json_encode($mechanics_result->fetch_all(MYSQLI_ASSOC)); ?>;

        // Prevent form resubmission on refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }

        // Form validation functions
        function validateName(name) {
            const nameRegex = /^[a-zA-Z\s]+$/;
            return nameRegex.test(name) && name.trim() !== '';
        }

        function validateAge(age) {
            const ageNum = parseInt(age);
            return !isNaN(ageNum) && ageNum >= 18 && ageNum <= 100;
        }

        function validateProfession(profession) {
            const professionRegex = /^[a-zA-Z\s]+$/;
            return professionRegex.test(profession) && profession.trim() !== '';
        }

        function validatePhoneNumber(phone) {
            const phoneRegex = /^[0-9]{10}$/;
            return phoneRegex.test(phone);
        }

        function validateEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function validateForm(form) {
            let isValid = true;
            
            // Validate name
            const nameInput = form.querySelector('input[name="name"]');
            if (nameInput && !validateName(nameInput.value)) {
                showError(nameInput, 'Name should only contain letters and spaces');
                isValid = false;
            }
            
            // Validate age
            const ageInput = form.querySelector('input[name="age"]');
            if (ageInput && !validateAge(ageInput.value)) {
                showError(ageInput, 'Age must be between 18 and 100');
                isValid = false;
            }
            
            // Validate profession
            const professionInput = form.querySelector('input[name="profession"]');
            if (professionInput && !validateProfession(professionInput.value)) {
                showError(professionInput, 'Profession should only contain letters and spaces');
                isValid = false;
            }
            
            // Validate phone number
            const phoneInput = form.querySelector('input[name="phone_number"]');
            if (phoneInput && !validatePhoneNumber(phoneInput.value)) {
                showError(phoneInput, 'Phone number must be exactly 10 digits');
                isValid = false;
            }
            
            // Validate email
            const emailInput = form.querySelector('input[name="email"]');
            if (emailInput && !validateEmail(emailInput.value)) {
                showError(emailInput, 'Please enter a valid email address');
                isValid = false;
            }
            
            return isValid;
        }

        function validateField(field) {
            const fieldName = field.name;
            const value = field.value;
            
            switch (fieldName) {
                case 'name':
                    if (!validateName(value)) {
                        showError(field, 'Name should only contain letters and spaces');
                        return false;
                    }
                    break;
                case 'age':
                    if (!validateAge(value)) {
                        showError(field, 'Age must be between 18 and 100');
                        return false;
                    }
                    break;
                case 'profession':
                    if (!validateProfession(value)) {
                        showError(field, 'Profession should only contain letters and spaces');
                        return false;
                    }
                    break;
                case 'phone_number':
                    if (!validatePhoneNumber(value)) {
                        showError(field, 'Phone number must be exactly 10 digits');
                        return false;
                    }
                    break;
                case 'email':
                    if (!validateEmail(value)) {
                        showError(field, 'Please enter a valid email address');
                        return false;
                    }
                    break;
            }
            
            clearFieldError(field);
            return true;
        }

        function showError(field, message) {
            clearFieldError(field);
            
            field.style.borderColor = '#dc3545';
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'field-error';
            errorDiv.style.color = '#dc3545';
            errorDiv.style.fontSize = '0.875rem';
            errorDiv.style.marginTop = '0.25rem';
            errorDiv.textContent = message;
            
            field.parentNode.appendChild(errorDiv);
        }

        function clearFieldError(field) {
            field.style.borderColor = '';
            
            const existingError = field.parentNode.querySelector('.field-error');
            if (existingError) {
                existingError.remove();
            }
        }

        // Edit mechanic functionality
        function editMechanic(mechanicId) {
            // Reset result pointer and find mechanic data
            <?php $mechanics_result->data_seek(0); ?>
            const mechanic = mechanicsData.find(m => m.mechanic_id == mechanicId);
            
            if (!mechanic) {
                alert('Mechanic data not found');
                return;
            }

            // Populate modal fields (excluding status and joined_date)
            document.getElementById('edit_mechanic_id').value = mechanic.mechanic_id;
            document.getElementById('edit_mechanic_name').value = mechanic.name;
            document.getElementById('edit_mechanic_age').value = mechanic.age;
            document.getElementById('edit_mechanic_profession').value = mechanic.profession;
            document.getElementById('edit_mechanic_phone').value = mechanic.phone_number || '';
            document.getElementById('edit_mechanic_email').value = mechanic.email || '';
            document.getElementById('edit_mechanic_address').value = mechanic.address || '';

            // Show modal
            document.getElementById('editMechanicModal').style.display = 'block';
        }

        // Close mechanic modal
        function closeMechanicModal() {
            document.getElementById('editMechanicModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const mechanicModal = document.getElementById('editMechanicModal');
            
            if (event.target == mechanicModal) {
                mechanicModal.style.display = 'none';
            }
        }

        // Document ready - initialize all functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listener to the main form
            const mainForm = document.querySelector('form[action=""][method="POST"] input[name="action"][value="add_mechanic"]').closest('form');
            if (mainForm) {
                mainForm.addEventListener('submit', function(e) {
                    if (!validateForm(this)) {
                        e.preventDefault();
                    }
                });
            }

            // Add event listener to the edit form
            const editForm = document.getElementById('editMechanicForm');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    if (!validateForm(this)) {
                        e.preventDefault();
                    }
                });
            }

            // Add real-time validation to form fields
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                const inputs = form.querySelectorAll('input, textarea');
                inputs.forEach(input => {
                    input.addEventListener('blur', function() {
                        validateField(this);
                    });
                    
                    input.addEventListener('input', function() {
                        clearFieldError(this);
                    });
                });
            });

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });

            // Responsive sidebar for mobile
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.add('collapsed');
                document.getElementById('mainContent').classList.add('expanded');
            }

            // Clear form after successful submission
            // Check if page was loaded after a redirect (POST success)
            if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_NAVIGATE) {
                // Clear form fields
                const form = document.querySelector('form[method="POST"]');
                if (form && window.location.search === '') {
                    form.reset();
                }
            }
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
