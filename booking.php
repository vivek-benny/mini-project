<?php
session_start();

// Only logged-in users can access
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "login");
if ($conn->connect_error) die("DB Connection Failed: " . $conn->connect_error);

// Create required tables if not exists (matching your actual SQL structure)
$conn->query("
CREATE TABLE IF NOT EXISTS leave_applications (
  id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  staff_id INT(11) NOT NULL,
  leave_reason TEXT NOT NULL,
  for_when DATE NOT NULL,
  till_when DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  status VARCHAR(40) DEFAULT NULL,
  UNIQUE KEY uniq_staff_dates (staff_id, for_when, till_when),
  CONSTRAINT leave_applications_ibfk_1 
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

$conn->query("
CREATE TABLE IF NOT EXISTS messages (
  id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT(11) NOT NULL,
  staff_id INT(11) DEFAULT NULL,
  message TEXT NOT NULL,
  response TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  responded_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");
$conn->query("
CREATE TABLE IF NOT EXISTS customer_feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    booking_id INT DEFAULT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comments TEXT NOT NULL,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES register(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_booking FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");


$conn->query("
CREATE TABLE IF NOT EXISTS services (
  service_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  service_name VARCHAR(100) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  price DECIMAL(10,2) DEFAULT NULL,
  estimated_time VARCHAR(50) DEFAULT NULL,
  category VARCHAR(100) DEFAULT NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  duration_minutes INT(11) DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  marketing_description TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

$conn->query("
CREATE TABLE IF NOT EXISTS service_details (
  id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  service_id INT(11) NOT NULL,
  why_choose TEXT NOT NULL,
  KEY fk_service_details_services (service_id),
  CONSTRAINT fk_service_details_services FOREIGN KEY (service_id) 
    REFERENCES services(service_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

$conn->query("
CREATE TABLE IF NOT EXISTS service_includes (
  id INT(11) NOT NULL AUTO_INCREMENT,
  service_id INT(11) NOT NULL,
  included_item VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  KEY service_id (service_id),
  CONSTRAINT service_includes_ibfk_1 FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

$conn->query("
CREATE TABLE IF NOT EXISTS vehicles (
    vehicle_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) DEFAULT NULL,
    vehicle_type VARCHAR(50) DEFAULT NULL,
    brand VARCHAR(50) DEFAULT NULL,
    model VARCHAR(50) DEFAULT NULL,
    registration_no VARCHAR(20) DEFAULT NULL,
    year INT(11) DEFAULT NULL,
    PRIMARY KEY (vehicle_id),
    KEY user_id (user_id),
    CONSTRAINT vehicles_ibfk_1 FOREIGN KEY (user_id) REFERENCES register(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$conn->query("
CREATE TABLE IF NOT EXISTS bookings (
  booking_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT(11) NOT NULL,
  vehicle_id INT(11) NOT NULL,
  booking_datetime DATETIME NOT NULL,
  status VARCHAR(50) DEFAULT 'Pending',
  mechanic VARCHAR(100) NOT NULL,
  mechanic_id INT(11) DEFAULT NULL,
  time_slot VARCHAR(100) NOT NULL,
  appointment_date DATE NOT NULL,
  prefereddate DATE DEFAULT NULL,
  KEY (user_id),
  KEY (vehicle_id),
  KEY (mechanic_id),
  CONSTRAINT bookings_ibfk_1 FOREIGN KEY (user_id) REFERENCES register(user_id) ON DELETE CASCADE,
  CONSTRAINT bookings_ibfk_2 FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

$conn->query("
CREATE TABLE IF NOT EXISTS mechanics (
  mechanic_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  age INT NOT NULL,
  profession VARCHAR(100) NOT NULL,
  status ENUM('free', 'assigned') DEFAULT 'free'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

$conn->query("
CREATE TABLE IF NOT EXISTS booking_services (
    id INT(11) NOT NULL AUTO_INCREMENT,
    booking_id INT(11) DEFAULT NULL,
    service_id INT(11) DEFAULT NULL,
    service_price DECIMAL(10,2) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY booking_id (booking_id),
    KEY service_id (service_id),
    CONSTRAINT booking_services_ibfk_2 FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Fetch logged-in user ID
$user_email = $_SESSION['email'];
$user_q = $conn->query("SELECT user_id FROM register WHERE email='$user_email'");
$user_data = $user_q->fetch_assoc();
$user_id = $user_data['user_id'];

// Handle Vehicle Registration with validation
$vehicle_msg = "";
if (isset($_POST['add_vehicle'])) {
    $vehicle_type = $_POST['vehicle_type'];
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $registration_no = strtoupper(trim($_POST['registration_no']));
    $year = intval($_POST['year']);
    
    $current_year = date('Y');
    $min_year = $current_year - 30; // 30 years old maximum
    
    $errors = [];
    
    // Validate year
    if ($year > $current_year) {
        $errors[] = "Vehicle year cannot be in the future.";
    }
    if ($year < $min_year) {
        $errors[] = "Vehicle cannot be older than 30 years.";
    }
    
    // Validate Kerala registration number format: KL XX Y YYYY
    if (!preg_match('/^KL\s(0[1-9]|1[0-4])\s[A-Z]{1,2}\s[0-9]{4}$/', $registration_no)) {
        $errors[] = "Invalid registration format. Use: KL XX Y YYYY (XX: 01-14, Y: 1-2 letters, YYYY: 0001-9999)";
    }
    
    if (empty($errors)) {
        $check_reg = $conn->query("SELECT * FROM vehicles WHERE registration_no='$registration_no'");
        if ($check_reg->num_rows > 0) {
            $vehicle_msg = "This registration number is already registered.";
        } else {
            $stmt = $conn->prepare("INSERT INTO vehicles (user_id, vehicle_type, brand, model, registration_no, year) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("issssi", $user_id, $vehicle_type, $brand, $model, $registration_no, $year);

            if ($stmt->execute()) {
                $vehicle_msg = "Vehicle registered successfully!";
            } else {
                $vehicle_msg = "Failed to register vehicle. Please try again.";
            }
        }
    } else {
        $vehicle_msg = implode(" ", $errors);
    }
}

// Fetch user's vehicles
$vehicles_res = $conn->query("SELECT * FROM vehicles WHERE user_id=$user_id");

// Fetch available services (only active ones)
$services_res = $conn->query("SELECT * FROM services WHERE status = 'active' ORDER BY service_name");

// Handle Booking with date validation
$booking_msg = "";
if (isset($_POST['book_services'])) {
    $selected_vehicle = $_POST['vehicle_id'];
    $selected_services = $_POST['services'] ?? [];
    $preferred_date = $_POST['preferred_date'];
    $booking_datetime = date("Y-m-d H:i:s");
    
    $errors = [];
    
    // Validate preferred date
    $today = date('Y-m-d');
    if ($preferred_date < $today) {
        $errors[] = "Preferred date cannot be in the past.";
    }
    
    if (!$selected_vehicle) {
        $errors[] = "Please select a vehicle.";
    }
    if (empty($selected_services)) {
        $errors[] = "Please select at least one service.";
    }
    if (empty($preferred_date)) {
        $errors[] = "Please select your preferred date.";
    }
    
    if (empty($errors)) {
        // Insert booking without default time slot
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, vehicle_id, booking_datetime, status, mechanic_id, staff_id, time_slot, appointment_date, prefereddate) VALUES (?,?,?,'Pending',NULL,NULL,'',?,?)");
        $stmt->bind_param("iisss", $user_id, $selected_vehicle, $booking_datetime, $preferred_date, $preferred_date);
        $stmt->execute();
        $booking_id = $stmt->insert_id;

        // Insert booking services
        $stmt2 = $conn->prepare("INSERT INTO booking_services (booking_id, service_id, service_price) VALUES (?,?,?)");
        $total_amount = 0;
        $service_names = [];
        
        foreach ($selected_services as $srv_id) {
            $srv_q = $conn->query("SELECT service_name, price FROM services WHERE service_id=$srv_id");
            $srv_data = $srv_q->fetch_assoc();
            $srv_price = $srv_data['price'];
            $service_names[] = $srv_data['service_name'];
            $total_amount += $srv_price;
            
            $stmt2->bind_param("iid", $booking_id, $srv_id, $srv_price);
            $stmt2->execute();
        }
        
        // Get vehicle details
        $veh_q = $conn->query("SELECT * FROM vehicles WHERE vehicle_id=$selected_vehicle");
        $veh_data = $veh_q->fetch_assoc();
        
        $booking_msg = "success|Booking Confirmed!\\n\\nBooking ID: #$booking_id\\nVehicle: {$veh_data['brand']} {$veh_data['model']} ({$veh_data['registration_no']})\\nServices: " . implode(', ', $service_names) . "\\nTotal Amount: ₹$total_amount\\nPreferred Date: $preferred_date\\n\\nOur staff will contact you soon to assign a mechanic and time slot.";
    } else {
        $booking_msg = "error|" . implode(" ", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vehicle Registration & Booking - GARAGE</title>
<link rel="stylesheet" href="style.css">
<style>
       * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: 
                linear-gradient(135deg, rgba(102, 126, 234, 0.8) 0%, rgba(118, 75, 162, 0.8) 100%),
                url('images/booking.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }

        .main-title {
            font-size: 2.5rem;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
            margin: 40px auto 15px;
            text-align: center;
            background: rgba(0, 0, 0, 0.6);
            display: block;
            padding: 15px 30px;
            border-radius: 12px;
            max-width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }

        .main-subtitle {
            font-size: 1.2rem;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
            margin: 0 auto 40px;
            text-align: center;
            max-width: 600px;
        }

        /* Container and Layout */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }

        /* Professional Cards */
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
            border-left: 5px solid #ff4b2b;
        }

        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            background: #ff4b2b;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
            color: white;
        }

        .card-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #222;
            margin: 0;
        }

        .card-subtitle {
            font-size: 1rem;
            color: #666;
            margin-top: 5px;
        }

        /* Form Styling */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #222;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            background: #fff;
            color: #333;
        }

        .form-control:focus {
            outline: none;
            border-color: #ff4b2b;
        }

        .form-control::placeholder {
            color: #888;
        }

        /* Enhanced Buttons */
        .btn {
            background: #ff4b2b;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            background: #e63946;
        }

        .btn-full {
            width: 100%;
            justify-content: center;
        }

        /* Enhanced Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
        }

        .alert.success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }

        .alert.error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        /* Vehicle Preview */
        .vehicle-preview {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            display: none;
            border: 1px solid #eee;
        }

        .vehicle-preview.active {
            display: block;
        }

        .preview-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #ff4b2b;
            margin-bottom: 15px;
        }

        .preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .preview-item {
            background: #fff;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            border: 1px solid #eee;
        }

        .preview-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .preview-value {
            font-size: 1rem;
            font-weight: 700;
            color: #222;
        }

        /* Service Cards Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .service-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .service-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-color: #ff4b2b;
        }

        .service-card.selected {
            border-color: #ff4b2b;
            background: rgba(255, 75, 43, 0.05);
        }

        .checkbox-wrapper {
            position: absolute;
            top: 15px;
            right: 15px;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #ff4b2b;
        }

        .service-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #222;
            margin-bottom: 10px;
        }

        .service-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ff4b2b;
            margin-bottom: 10px;
        }

        .service-time {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 15px;
            padding: 5px 10px;
            background: #f8f9fa;
            border-radius: 20px;
            display: inline-block;
        }

        .service-description {
            color: #666;
            line-height: 1.6;
            font-size: 0.95rem;
            margin-bottom: 15px;
        }

        .more-about-btn {
            background: #6c757d;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 20px;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .more-about-btn:hover {
            background: #5a6268;
        }

        /* Total Display */
        .total-display {
            background: #ff4b2b;
            color: white;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            margin: 25px 0;
        }

        .total-amount {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .total-services {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-title {
                font-size: 2rem;
                padding: 12px 20px;
                margin: 30px auto 10px;
            }

            .main-subtitle {
                font-size: 1rem;
                margin: 0 auto 30px;
            }

            .container {
                padding: 0 15px 30px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 20px;
            }

            .card-header {
                flex-direction: column;
                text-align: center;
            }

            .card-icon {
                margin-right: 0;
                margin-bottom: 10px;
            }

            .preview-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .main-title {
                font-size: 1.8rem;
                padding: 10px 15px;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .service-card {
                padding: 15px;
            }

            .total-amount {
                font-size: 2rem;
            }
        }
</style>
</head>
<body>

<nav>
  <div class="logo">GARAGE</div>
  <button class="menu-toggle" aria-label="Toggle menu">&#9776;</button>
  <ul>
    <li><a href="index.php">Home</a></li>
    <li><a href="service.php">Services</a></li>
    <li><a href="booking.php" class="active">Booking</a></li>
    <li><a href="contact_staff.php">Contact</a></li>
    <li><a href="profile.php">Profile</a></li>
  </ul>
</nav>

<h1 class="main-title">Vehicle Registration & Service Booking</h1>
<p class="main-subtitle">Add your vehicle for personalized service experience</p>

<div class="container">
  <!-- VEHICLE REGISTRATION -->
  <div class="card">
    <div class="card-header">
      <div class="card-icon">🚗</div>
      <div>
        <h2 class="card-title">Vehicle Registration</h2>
        <p class="card-subtitle">Add your vehicle for personalized service experience</p>
      </div>
    </div>

    <?php if($vehicle_msg): ?>
      <div class="alert <?= strpos($vehicle_msg,'successfully')!==false?'success':'error'?>"><?= $vehicle_msg ?></div>
    <?php endif; ?>

    <form method="post" id="vehicleForm">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Vehicle Type</label>
          <select name="vehicle_type" class="form-control" onchange="updatePreview()" required>
            <option value="">Select Vehicle Type</option>
            <option value="SUV">SUV</option>
            <option value="Sedan">Sedan</option>
            <option value="Hatchback">Hatchback</option>
            <option value="Truck">Truck</option>
            <option value="Motorcycle">Motorcycle</option>
            <option value="Coupe">Coupe</option>
            <option value="Convertible">Convertible</option>
            <option value="Minivan">Minivan</option>
            <option value="Crossover">Crossover</option>
            <option value="Pickup Truck">Pickup Truck</option>
            <option value="Estate/Wagon">Estate/Wagon</option>
            <option value="Sports Car">Sports Car</option>
            <option value="Luxury Car">Luxury Car</option>
            <option value="Electric Vehicle">Electric Vehicle</option>
            <option value="Hybrid">Hybrid</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Brand</label>
          <input name="brand" class="form-control" onchange="updatePreview()" placeholder="Enter vehicle brand" required>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Model</label>
          <input name="model" class="form-control" onchange="updatePreview()" placeholder="Enter vehicle model" required>
        </div>
        <div class="form-group">
          <label class="form-label">Registration No (Format: KL XX YYYY)</label>
          <input name="registration_no" class="form-control" onchange="updatePreview()" placeholder="KL 01 A 1234" pattern="^KL\s(0[1-9]|1[0-4])\s[A-Z]{1,2}\s[0-9]{4}$" maxlength="14" required>
          <small style="color: #666; font-size: 0.8rem;">Example: KL 01 A 1234 (Districts: 01-14, Series: 1-2 letters, Numbers: 0001-9999)</small>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Year</label>
          <input type="number" name="year" class="form-control" onchange="updatePreview()" placeholder="Enter manufacturing year" min="<?= date('Y')-30 ?>" max="<?= date('Y') ?>" required>
          <small style="color: #666; font-size: 0.8rem;">Vehicle should be between <?= date('Y')-30 ?> and <?= date('Y') ?></small>
        </div>
        <div class="form-group" style="display:flex;align-items:end;">
          <button class="btn btn-full" type="submit" name="add_vehicle">Register Vehicle</button>
        </div>
      </div>
    </form>

    <!-- PREVIEW -->
    <div class="vehicle-preview" id="vehiclePreview">
      <h3 class="preview-title">Vehicle Preview</h3>
      <div class="preview-grid">
        <div class="preview-item"><div class="preview-label">Type</div><div class="preview-value" id="previewType">-</div></div>
        <div class="preview-item"><div class="preview-label">Brand</div><div class="preview-value" id="previewBrand">-</div></div>
        <div class="preview-item"><div class="preview-label">Model</div><div class="preview-value" id="previewModel">-</div></div>
        <div class="preview-item"><div class="preview-label">Reg No</div><div class="preview-value" id="previewReg">-</div></div>
        <div class="preview-item"><div class="preview-label">Year</div><div class="preview-value" id="previewYear">-</div></div>
      </div>
    </div>
  </div>

  <!-- SERVICE BOOKING -->
  <div class="card">
    <div class="card-header">
      <div class="card-icon">🛠</div>
      <div>
        <h2 class="card-title">Book Services</h2>
        <p class="card-subtitle">Choose vehicle, select services & preferred date</p>
      </div>
    </div>

    <form method="post" id="bookingForm">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Choose Vehicle</label>
          <select name="vehicle_id" class="form-control" required>
            <option value="">-- Select Vehicle --</option>
            <?php 
            // Reset vehicles result
            $vehicles_res = $conn->query("SELECT * FROM vehicles WHERE user_id=$user_id");
            while($veh = $vehicles_res->fetch_assoc()): ?>
              <option value="<?= $veh['vehicle_id'] ?>"><?= $veh['vehicle_type'].' - '.$veh['brand'].' '.$veh['model'].' ('.$veh['registration_no'].')' ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Preferred Date</label>
          <input type="date" name="preferred_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
          <small style="color: #666; font-size: 0.8rem;">Select your preferred service date (must be today or later)</small>
        </div>
      </div>

      <div class="services-grid">
        <?php 
        // Reset services result
        $services_res = $conn->query("SELECT * FROM services WHERE status = 'active' ORDER BY service_name");
        while($srv = $services_res->fetch_assoc()): ?>
          <div class="service-card">
            <div class="checkbox-wrapper">
              <input type="checkbox" name="services[]" value="<?= $srv['service_id'] ?>" onchange="calculateTotal()">
            </div>
            <div class="service-name"><?= $srv['service_name'] ?></div>
            <div class="service-price">₹<?= $srv['price'] ?></div>
            <div class="service-time"><?= $srv['estimated_time'] ?? ($srv['duration_minutes'] ? $srv['duration_minutes'].' mins' : 'Contact us') ?></div>
            <div class="service-description"><?= $srv['description'] ?></div>
            <a href="service-details.php?service_id=<?= $srv['service_id'] ?>" class="more-about-btn" target="_blank">More About</a>
          </div>
        <?php endwhile; ?>
      </div>

      <div class="total-display" id="totalDisplay">
        <div class="total-amount">₹0.00</div>
        <div class="total-services">No services selected</div>
      </div>

      <button class="btn btn-full" type="submit" name="book_services">Confirm Booking</button>
    </form>
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

// Vehicle form validation
document.getElementById('vehicleForm').addEventListener('submit', function(e) {
    const regInput = document.querySelector('[name="registration_no"]');
    const yearInput = document.querySelector('[name="year"]');
    const regPattern = /^KL\s(0[1-9]|1[0-4])\s[A-Z]{1,2}\s\d{4}$/;
    const currentYear = new Date().getFullYear();
    const minYear = currentYear - 30;
    
    let hasErrors = false;
    
    // Registration validation
    if (regInput.value.trim() === '') {
        e.preventDefault();
        alert('GARAGE Service Alert:\n\nPlease enter the vehicle registration number.');
        regInput.focus();
        return false;
    }
    
    if (!regPattern.test(regInput.value.trim())) {
        e.preventDefault();
        hasErrors = true;
        alert('GARAGE Service Alert:\n\nInvalid registration format!\n\nCorrect format: KL XX Y YYYY\n- KL: Kerala state code (fixed)\n- XX: District code (01 to 14 only)\n- Y: Series code (1-2 letters A-Z)\n- YYYY: Four digits (0001-9999)\n\nExample: KL 01 A 1234');
        regInput.focus();
        return false;
    }
    
    // Year validation
    if (yearInput.value.trim() === '') {
        e.preventDefault();
        alert('GARAGE Service Alert:\n\nPlease enter the vehicle manufacturing year.');
        yearInput.focus();
        return false;
    }
    
    const year = parseInt(yearInput.value);
    if (isNaN(year) || year < 1900) {
        e.preventDefault();
        alert('GARAGE Service Alert:\n\nPlease enter a valid year.');
        yearInput.focus();
        return false;
    }
    
    if (year > currentYear) {
        e.preventDefault();
        alert('GARAGE Service Alert:\n\nVehicle year cannot be in the future!\n\nCurrent year: ' + currentYear + '\nPlease enter a year between ' + minYear + ' and ' + currentYear);
        yearInput.focus();
        return false;
    }
    
    if (year < minYear) {
        e.preventDefault();
        alert('GARAGE Service Alert:\n\nVehicle is too old for our service!\n\nWe service vehicles that are maximum 30 years old.\nMinimum year accepted: ' + minYear + '\nPlease enter a year between ' + minYear + ' and ' + currentYear);
        yearInput.focus();
        return false;
    }
    
    return true;
});

// Booking form validation and custom alert
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    const vehicleSelect = document.querySelector('[name="vehicle_id"]');
    const dateInput = document.querySelector('[name="preferred_date"]');
    const selectedServices = document.querySelectorAll('[name="services[]"]:checked');
    const today = new Date().toISOString().split('T')[0];
    
    // Vehicle validation
    if (!vehicleSelect.value) {
        e.preventDefault();
        alert('GARAGE Service Alert:\n\nPlease select a vehicle for the service.');
        vehicleSelect.focus();
        return false;
    }
    
    // Date validation
    if (!dateInput.value) {
        e.preventDefault();
        alert('GARAGE Service Alert:\n\nPlease select your preferred service date.');
        dateInput.focus();
        return false;
    }
    
    if (dateInput.value < today) {
        e.preventDefault();
        alert('GARAGE Service Alert:\n\nPreferred date cannot be in the past!\n\nPlease select today or a future date.');
        dateInput.focus();
        return false;
    }
    
    // Services validation
    if (selectedServices.length === 0) {
        e.preventDefault();
        alert('GARAGE Service Alert:\n\nPlease select at least one service for your booking.');
        return false;
    }
});

// Handle booking success message
<?php if ($booking_msg && strpos($booking_msg, 'success|') === 0): ?>
document.addEventListener('DOMContentLoaded', function() {
    const message = <?= json_encode(substr($booking_msg, 8)) ?>;
    alert('GARAGE Service Confirms:\n\n' + message);
});
<?php endif; ?>

// Auto-format registration number with progressive validation
document.querySelector('[name="registration_no"]').addEventListener('input', function(e) {
    const cursorPosition = e.target.selectionStart;
    let value = e.target.value.toUpperCase();
    let newValue = '';
    let newCursorPosition = cursorPosition;
    
    // Remove any characters that are not allowed
    value = value.replace(/[^A-Z0-9\s]/g, '');
    
    // Process character by character according to KL XX Y YYYY format
    for (let i = 0; i < value.length && newValue.length < 14; i++) { // Max length: "KL XX YZ YYYY" = 13 chars
        const char = value[i];
        const currentLength = newValue.length;
        
        // Position 0-1: Must be 'KL'
        if (currentLength === 0) {
            if (char === 'K') {
                newValue += char;
            } else if (char === 'L' && newValue === '') {
                newValue = 'KL'; // Auto-complete KL if user types L first
                newCursorPosition++;
            } else if (char === ' ') {
                newValue = 'KL '; // Auto-complete KL if user types space first
                newCursorPosition += 2;
            } else if (/[0-9]/.test(char)) {
                newValue = 'KL ' + char; // Auto-complete KL if user types number first
                newCursorPosition += 3;
            }
        } else if (currentLength === 1) {
            if (char === 'L') {
                newValue += char;
            } else if (char === ' ') {
                newValue += 'L '; // Auto-complete L if user types space
                newCursorPosition++;
            } else if (/[0-9]/.test(char)) {
                newValue += 'L ' + char; // Auto-complete L and add space if user types number
                newCursorPosition += 2;
            }
        }
        // Position 2: Must be space
        else if (currentLength === 2) {
            if (char === ' ') {
                newValue += char;
            } else if (/[0-9]/.test(char)) {
                newValue += ' ' + char; // Auto-add space if user types number
                newCursorPosition++;
            }
        }
        // Position 3-4: Must be numbers (district code 01-14)
        else if (currentLength >= 3 && currentLength <= 4) {
            if (/[0-9]/.test(char)) {
                // Validate district code as we build it
                const currentDistrict = newValue.substring(3) + char;
                if (currentLength === 3) {
                    // First digit: must be 0 or 1
                    if (char === '0' || char === '1') {
                        newValue += char;
                    }
                } else if (currentLength === 4) {
                    // Second digit: validate complete district code
                    const districtCode = parseInt(currentDistrict);
                    if (districtCode >= 1 && districtCode <= 14) {
                        newValue += char;
                    }
                }
            } else if (char === ' ' && currentLength === 4) {
                newValue += char;
            } else if (/[A-Z]/.test(char) && currentLength === 4) {
                newValue += ' ' + char; // Auto-add space if user types letter after district code
                newCursorPosition++;
            }
        }
        // Position 5: Must be space
        else if (currentLength === 5) {
            if (char === ' ') {
                newValue += char;
            } else if (/[A-Z]/.test(char)) {
                newValue += ' ' + char; // Auto-add space if user types letter
                newCursorPosition++;
            }
        }
        // Position 6-7: Must be letters (series code, 1-2 letters)
        else if (currentLength >= 6 && currentLength <= 7) {
            if (/[A-Z]/.test(char)) {
                newValue += char;
            } else if (char === ' ') {
                newValue += char;
            } else if (/[0-9]/.test(char)) {
                newValue += ' ' + char; // Auto-add space if user types number after series
                newCursorPosition++;
            }
        }
        // Handle space or number after series (flexible for 1 or 2 letter series)
        else if (currentLength === 8) {
            if (char === ' ') {
                newValue += char;
            } else if (/[0-9]/.test(char)) {
                // Check if position 7 is a letter (2-letter series) or space (1-letter series)
                if (/[A-Z]/.test(newValue.charAt(7))) {
                    // This is a 2-letter series, add space before number
                    newValue += ' ' + char;
                    newCursorPosition++;
                } else {
                    // This is continuation after 1-letter series + space
                    newValue += char;
                }
            }
        }
        // Handle final digits (registration number)
        else if (currentLength >= 9) {
            if (/[0-9]/.test(char)) {
                // Count existing digits in final section to ensure max 4 digits
                const parts = newValue.split(' ');
                const finalSection = parts[3] || '';
                if (finalSection.length < 4) {
                    newValue += char;
                }
            }
        }
    }
    
    e.target.value = newValue;
    
    // Restore cursor position, but not beyond the new value length
    const finalPosition = Math.min(newCursorPosition, newValue.length);
    setTimeout(() => {
        e.target.setSelectionRange(finalPosition, finalPosition);
    }, 0);
    
    updatePreview();
});

// Real-time year validation with improved feedback
document.querySelector('[name="year"]').addEventListener('input', function(e) {
    const currentYear = new Date().getFullYear();
    const minYear = currentYear - 30;
    const year = parseInt(e.target.value);
    
    // Remove any existing custom validity
    e.target.setCustomValidity('');
    
    if (e.target.value && !isNaN(year)) {
        if (year > currentYear) {
            e.target.setCustomValidity('Year cannot be in the future (current year: ' + currentYear + ')');
        } else if (year < minYear && year > 1900) {
            e.target.setCustomValidity('Vehicle cannot be older than 30 years (minimum year: ' + minYear + ')');
        }
    }
    
    updatePreview();
});

// Add real-time validation feedback for registration
document.querySelector('[name="registration_no"]').addEventListener('blur', function(e) {
    const regPattern = /^KL\s(0[1-9]|1[0-4])\s[A-Z]{1,2}\s\d{4}$/;
    const value = e.target.value.trim();
    
    if (value && !regPattern.test(value)) {
        e.target.setCustomValidity('Invalid format. Use: KL XX Y YYYY (XX: 01-14, Y: 1-2 letters, YYYY: 0001-9999)');
    } else {
        e.target.setCustomValidity('');
    }
});

// Clear custom validity on focus
document.querySelector('[name="registration_no"]').addEventListener('focus', function(e) {
    e.target.setCustomValidity('');
});

document.querySelector('[name="year"]').addEventListener('focus', function(e) {
    e.target.setCustomValidity('');
});

// Date input minimum date
document.querySelector('[name="preferred_date"]').min = new Date().toISOString().split('T')[0];

function updatePreview() {
    document.getElementById('previewType').innerText = document.querySelector('[name="vehicle_type"]').value || '-';
    document.getElementById('previewBrand').innerText = document.querySelector('[name="brand"]').value || '-';
    document.getElementById('previewModel').innerText = document.querySelector('[name="model"]').value || '-';
    document.getElementById('previewReg').innerText = document.querySelector('[name="registration_no"]').value || '-';
    document.getElementById('previewYear').innerText = document.querySelector('[name="year"]').value || '-';
    
    const hasAnyValue = document.querySelector('[name="vehicle_type"]').value || 
                       document.querySelector('[name="brand"]').value || 
                       document.querySelector('[name="model"]').value || 
                       document.querySelector('[name="registration_no"]').value || 
                       document.querySelector('[name="year"]').value;
    
    if (hasAnyValue) {
        document.getElementById('vehiclePreview').classList.add('active');
    }
}

function calculateTotal() {
    let total = 0, count = 0;
    const serviceCards = document.querySelectorAll('.service-card');
    
    serviceCards.forEach(card => {
        const checkbox = card.querySelector('input[type="checkbox"]');
        const isChecked = checkbox.checked;
        
        if (isChecked) {
            card.classList.add('selected');
            const priceText = card.querySelector('.service-price').innerText;
            const price = parseFloat(priceText.replace('₹', ''));
            total += price;
            count++;
        } else {
            card.classList.remove('selected');
        }
    });
    
    document.querySelector('.total-amount').innerText = '₹' + total.toFixed(2);
    document.querySelector('.total-services').innerText = count > 0 ? count + ' services selected' : 'No services selected';
}

// Initialize service card click handlers
document.querySelectorAll('.service-card').forEach(card => {
    card.addEventListener('click', function(e) {
        // Don't trigger if clicking on checkbox or more about button
        if (e.target.type === 'checkbox' || e.target.classList.contains('more-about-btn')) {
            return;
        }
        
        const checkbox = this.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
        calculateTotal();
    });
});
</script>

</body>
</html>