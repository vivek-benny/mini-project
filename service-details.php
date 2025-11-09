<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "login";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Get service ID from URL
$serviceId = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

if ($serviceId <= 0) {
    echo "<script>alert('Please select a valid service.'); window.location.href='service.php';</script>";
    exit;
}

// Fetch main service info from services table
$serviceQuery = "SELECT * FROM services WHERE service_id = $serviceId";
$service = $conn->query($serviceQuery)->fetch_assoc();

if (!$service) {
    echo "<script>alert('Service not found.'); window.location.href='service.php';</script>";
    exit;
}

// Fetch why_choose from service_details table
$serviceDetailQuery = "SELECT why_choose FROM service_details WHERE service_id = $serviceId";
$serviceDetail = $conn->query($serviceDetailQuery)->fetch_assoc();

// Fetch included items from service_includes table
$includesQuery = "SELECT included_item FROM service_includes WHERE service_id = $serviceId ORDER BY id";
$includesResult = $conn->query($includesQuery);
$includedItems = [];
while ($row = $includesResult->fetch_assoc()) {
    $includedItems[] = $row['included_item'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($service['service_name']); ?> - AutoCare Pro</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Service Details Specific Styles */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body {
            background: #f7f7f7 url("images/services2.jpg") no-repeat center center;
            background-size: cover;
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

        .container {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            padding: 0 20px 20px;
        }

        .service-detail-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            overflow: hidden;
        }

        .service-content-wrapper {
            flex: 1;
            overflow-y: auto;
            padding-right: 10px;
        }

        .service-header {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 30px;
            margin-bottom: 30px;
        }

        .service-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .no-image {
            width: 100%;
            height: 200px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 3rem;
            border-radius: 12px;
        }

        .service-info h1 {
            font-size: 1.8rem;
            color: #222;
            margin-bottom: 15px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        .service-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .meta-item i {
            color: #ff4b2b;
            font-size: 1rem;
        }

        .price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ff4b2b;
        }

        .description {
            font-size: 0.95rem;
            color: #666;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-top: 25px;
        }

        .detail-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #ff4b2b;
        }

        .detail-section h3 {
            color: #222;
            margin-bottom: 15px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
            text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.1);
        }

        .detail-section h3 i {
            color: #ff4b2b;
        }

        .includes-list {
            list-style: none;
            padding: 0;
            max-height: 200px;
            overflow-y: auto;
        }

        .includes-list li {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .includes-list li:last-child {
            border-bottom: none;
        }

        .includes-list li i {
            color: #28a745;
            font-size: 0.8rem;
        }

        .why-choose {
            color: #666;
            line-height: 1.6;
            font-size: 0.95rem;
            max-height: 200px;
            overflow-y: auto;
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            background: #e63946;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .service-header {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .details-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .service-info h1 {
                font-size: 1.6rem;
            }

            .service-meta {
                flex-direction: column;
                gap: 10px;
            }

            .main-title {
                font-size: 1.8rem;
                padding: 8px 15px;
                margin: 15px auto 5px;
            }

            .service-detail-container {
                padding: 15px;
            }

            .detail-section {
                padding: 15px;
            }

            .includes-list, .why-choose {
                max-height: 150px;
            }
        }

        @media (max-width: 480px) {
            .main-title {
                font-size: 1.6rem;
                padding: 8px 12px;
            }

            .service-info h1 {
                font-size: 1.4rem;
            }

            .service-detail-container {
                padding: 12px;
            }

            .service-content-wrapper {
                padding-right: 5px;
            }

            .details-grid {
                gap: 15px;
            }

            .detail-section {
                padding: 12px;
            }

            .includes-list, .why-choose {
                max-height: 120px;
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
            <li><a href="service.php" class="active">Services</a></li>
            <li><a href="booking.php">Booking</a></li>
            <li><a href="contact_staff.php">Contact</a></li>
            <li><a href="profile.php">Profile</a></li>
        </ul>
    </nav>

    <h1 class="main-title"><?php echo htmlspecialchars($service['service_name']); ?></h1>

    <div class="container">
        <div class="service-detail-container">
            <div class="service-content-wrapper">
                <div class="service-header">
                    <div class="service-info">
                        <div class="service-meta">
                            <?php if (!empty($service['price'])): ?>
                                <div class="meta-item">
                                    <i class="fas fa-tag"></i>
                                    <span class="price">₹<?php echo number_format($service['price'], 2); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="meta-item">
                                <i class="fas fa-clock"></i>
                                <span>
                                    <?php 
                                    $time = !empty($service['duration_minutes']) 
                                        ? $service['duration_minutes'] . ' mins'
                                        : ($service['estimated_time'] ?? 'Contact us');
                                    echo htmlspecialchars($time); 
                                    ?>
                                </span>
                            </div>
                            
                            <div class="meta-item">
                                <i class="fas fa-check-circle"></i>
                                <span><?php echo ucfirst($service['status']); ?></span>
                            </div>
                        </div>
                        
                        <div class="description">
                            <?php 
                            $description = !empty($service['marketing_description']) 
                                ? $service['marketing_description'] 
                                : $service['description'];
                            echo nl2br(htmlspecialchars($description)); 
                            ?>
                        </div>
                    </div>
                    
                    <div>
                        <?php if (!empty($service['image']) && file_exists($service['image'])): ?>
                            <img src="<?php echo htmlspecialchars($service['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($service['service_name']); ?>" 
                                 class="service-image">
                        <?php else: ?>
                            <div class="no-image">
                                <i class="fas fa-wrench"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="details-grid">
                    <div class="detail-section">
                        <h3>
                            <i class="fas fa-list-check"></i>
                            What's Included
                        </h3>
                        <?php if (!empty($includedItems)): ?>
                            <ul class="includes-list">
                                <?php foreach ($includedItems as $item): ?>
                                    <li>
                                        <i class="fas fa-check"></i>
                                        <?php echo htmlspecialchars($item); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="why-choose">No specific inclusions listed for this service.</p>
                        <?php endif; ?>
                    </div>

                    <div class="detail-section">
                        <h3>
                            <i class="fas fa-star"></i>
                            Why Choose This Service
                        </h3>
                        <p class="why-choose">
                            <?php 
                            echo $serviceDetail 
                                ? nl2br(htmlspecialchars($serviceDetail['why_choose']))
                                : "This service provides professional automotive care with quality guaranteed.";
                            ?>
                        </p>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="booking.php?service_id=<?php echo $serviceId; ?>" class="btn">
                        <i class="fas fa-calendar-check"></i>
                        Book This Service
                    </a>
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
    </script>
</body>
</html>

<?php
$conn->close();
?>