<?php
session_start();

// ✅ Only logged-in users with 'user' role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "login");
if ($conn->connect_error) die("DB Connection Failed: " . $conn->connect_error);

if (!isset($_GET['booking_id'])) {
    echo "❌ Invalid booking ID.";
    exit();
}

$booking_id = intval($_GET['booking_id']);

// ✅ Fetch booking details
$booking_q = $conn->query("SELECT b.*, v.vehicle_type, v.brand, v.model, v.registration_no 
                           FROM bookings b
                           JOIN vehicles v ON b.vehicle_id = v.vehicle_id
                           WHERE b.booking_id = $booking_id");
if ($booking_q->num_rows === 0) {
    echo "❌ Booking not found.";
    exit();
}

$booking = $booking_q->fetch_assoc();

// ✅ Fetch selected services
$services_q = $conn->query("SELECT s.service_name, bs.service_price 
                            FROM booking_services bs
                            JOIN services s ON bs.service_id = s.service_id
                            WHERE bs.booking_id = $booking_id");

$services = [];
$total_price = 0;
while ($row = $services_q->fetch_assoc()) {
    $services[] = $row;
    $total_price += $row['service_price'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Booking Confirmation & Payment - AutoCare Garage</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      margin: 0;
      padding: 2rem;
      min-height: 100vh;
    }
    .ticket {
      max-width: 700px;
      margin: 0 auto;
      background: white;
      border: 3px dashed #ff6b35;
      border-radius: 20px;
      padding: 2.5rem;
      box-shadow: 0 15px 40px rgba(0,0,0,0.15);
      position: relative;
      overflow: hidden;
    }
    .ticket::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: linear-gradient(90deg, #ff6b35, #f7931e);
    }
    .ticket h2 {
      text-align: center;
      color: #ff6b35;
      margin-bottom: 2rem;
      font-size: 2rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }
    .garage-name {
      text-align: center;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      padding: 1rem;
      border-radius: 15px;
      margin-bottom: 2rem;
      font-size: 1.3rem;
      font-weight: bold;
    }
    .booking-info {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    .info-card {
      background: #f8f9fa;
      padding: 1.5rem;
      border-radius: 12px;
      border-left: 4px solid #ff6b35;
    }
    .info-label {
      font-weight: bold;
      color: #333;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .info-value {
      color: #555;
      font-size: 1.1rem;
    }
    .services-section {
      background: #fff;
      border: 2px solid #f0f0f0;
      border-radius: 15px;
      padding: 1.5rem;
      margin-bottom: 2rem;
    }
    .services-title {
      font-size: 1.3rem;
      font-weight: bold;
      color: #ff6b35;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .services-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .services-list li {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem;
      margin-bottom: 0.5rem;
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      border-radius: 10px;
      border-left: 4px solid #28a745;
    }
    .service-name {
      font-weight: 600;
      color: #333;
    }
    .service-price {
      font-weight: bold;
      color: #28a745;
      font-size: 1.1rem;
    }
    .total-section {
      background: linear-gradient(135deg, #ff6b35, #f7931e);
      color: white;
      padding: 1.5rem;
      border-radius: 15px;
      text-align: center;
      margin-bottom: 2rem;
      box-shadow: 0 8px 25px rgba(255, 107, 53, 0.3);
    }
    .total-label {
      font-size: 1.1rem;
      margin-bottom: 0.5rem;
    }
    .total-amount {
      font-size: 2.5rem;
      font-weight: bold;
    }
    .status {
      padding: 1rem;
      font-weight: bold;
      text-align: center;
      border-radius: 12px;
      margin-bottom: 2rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }
    .status.pending {
      background: #fff3cd;
      color: #856404;
      border: 2px solid #ffeaa7;
    }
    .status.assigned {
      background: #cce5ff;
      color: #004085;
      border: 2px solid #0066cc;
    }
    .status.completed {
      background: #d4edda;
      color: #155724;
      border: 2px solid #28a745;
    }
    .payment-section {
      text-align: center;
      margin-top: 2rem;
    }
    .confirm-payment-btn {
      background: linear-gradient(135deg, #28a745, #20c997);
      color: white;
      border: none;
      padding: 1.2rem 3rem;
      font-size: 1.2rem;
      font-weight: bold;
      border-radius: 50px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
      display: inline-flex;
      align-items: center;
      gap: 10px;
    }
    .confirm-payment-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 35px rgba(40, 167, 69, 0.4);
    }
    .confirm-payment-btn:active {
      transform: translateY(-1px);
    }
    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 1rem;
      text-decoration: none;
      color: #6c757d;
      background: #f8f9fa;
      padding: 0.8rem 2rem;
      border-radius: 50px;
      font-weight: 600;
      transition: all 0.3s ease;
      border: 2px solid #dee2e6;
    }
    .back-btn:hover {
      background: #e9ecef;
      color: #495057;
      transform: translateY(-2px);
    }
    
    @media (max-width: 768px) {
      .booking-info {
        grid-template-columns: 1fr;
      }
      .services-list li {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
      }
      .ticket {
        padding: 1.5rem;
        margin: 1rem;
      }
    }
  </style>
</head>
<body>

<div class="ticket">
  <h2>
    <i class="fas fa-clipboard-check"></i>
    Booking Confirmation & Payment
  </h2>

  <!-- Garage Name -->
  <div class="garage-name">
    <i class="fas fa-car-side"></i>
    AutoCare Pro Garage Services
  </div>

  <!-- Booking Information -->
  <div class="booking-info">
    <div class="info-card">
      <div class="info-label">
        <i class="fas fa-hashtag"></i>
        Booking ID
      </div>
      <div class="info-value">#<?= $booking['booking_id'] ?></div>
    </div>
    
    <div class="info-card">
      <div class="info-label">
        <i class="fas fa-calendar-alt"></i>
        Booking Date
      </div>
      <div class="info-value"><?= date('F j, Y', strtotime($booking['booking_datetime'])) ?></div>
    </div>
    
    <div class="info-card">
      <div class="info-label">
        <i class="fas fa-car"></i>
        Vehicle Details
      </div>
      <div class="info-value">
        <?= htmlspecialchars("{$booking['vehicle_type']} - {$booking['brand']} {$booking['model']}") ?><br>
        <small style="color: #6c757d;">Reg: <?= htmlspecialchars($booking['registration_no']) ?></small>
      </div>
    </div>
  </div>

  <!-- Services Booked -->
  <div class="services-section">
    <div class="services-title">
      <i class="fas fa-wrench"></i>
      Services Booked
    </div>
    <ul class="services-list">
      <?php foreach ($services as $srv): ?>
        <li>
          <span class="service-name">
            <i class="fas fa-check-circle" style="color: #28a745; margin-right: 5px;"></i>
            <?= htmlspecialchars($srv['service_name']) ?>
          </span>
          <span class="service-price">₹<?= number_format($srv['service_price'], 2) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- Total Cost -->
  <div class="total-section">
    <div class="total-label">Total Service Cost</div>
    <div class="total-amount">₹<?= number_format($total_price, 2) ?></div>
  </div>

  <!-- Booking Status -->
  <div class="status <?= strtolower($booking['status']) ?>">
    <i class="fas fa-info-circle"></i>
    Booking Status: <?= htmlspecialchars($booking['status']) ?>
  </div>

  <!-- Payment Confirmation Section -->
  <div class="payment-section">
    <button class="confirm-payment-btn" onclick="confirmPayment()">
      <i class="fas fa-credit-card"></i>
      Confirm Payment
    </button>
    
    <div>
      <a class="back-btn" href="booking.php">
        <i class="fas fa-arrow-left"></i>
        Back to Booking
      </a>
    </div>
  </div>
</div>

<script>
function confirmPayment() {
  // Show payment successful alert
  alert('Payment Successful! 🎉\n\nYour booking has been confirmed.\nBooking ID: #<?= $booking['booking_id'] ?>\nTotal Paid: ₹<?= number_format($total_price, 2) ?>');
  
  // Redirect back to booking page
  window.location.href = 'booking.php';
}

// Add some visual feedback
document.querySelector('.confirm-payment-btn').addEventListener('click', function() {
  this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Payment...';
  this.disabled = true;
  
  // Re-enable after redirect (in case redirect fails)
  setTimeout(() => {
    this.innerHTML = '<i class="fas fa-credit-card"></i> Confirm Payment';
    this.disabled = false;
  }, 2000);
});
</script>

</body>
</html>
