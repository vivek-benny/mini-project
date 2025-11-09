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

// Fetch active services from services table
$result = $conn->query("SELECT service_id, service_name, image, description, price, estimated_time, duration_minutes, marketing_description FROM services WHERE status = 'active' ORDER BY service_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - AutoCare Pro</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Service-specific styles */
        body {
            background: #f7f7f7 url("images/services.jpg") no-repeat center center;
            background-size: cover;
            color: #333;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            // Optimized for performance - no fixed attachment to prevent scrolling lag
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .service-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #f0f0f0;
            position: relative;
            // Optimized for performance
        }

        .service-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }

        .service-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .service-content {
            padding: 25px;
        }

        .service-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #222;
            margin-bottom: 15px;
        }

        .service-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .service-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .service-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #ff4b2b;
        }

        .service-time {
            color: #888;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }

        .service-time i {
            margin-right: 5px;
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
            width: 100%;
            justify-content: center;
        }

        .btn:hover {
            background: #e63946;
            transition: background 0.1s ease;
        }

        .no-image {
            width: 100%;
            height: 200px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 2.5rem;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .services-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            }
            
            .service-content {
                padding: 20px;
            }
            
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
        }

        @media (max-width: 480px) {
            .services-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .service-content {
                padding: 18px;
            }
            
            .main-title {
                font-size: 1.8rem;
                padding: 10px 15px;
            }
            
            .container {
                padding: 0 15px 30px;
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

    <h1 class="main-title">Our Premium Services</h1>
    <p class="main-subtitle">Professional automotive care with cutting-edge technology and expert technicians</p>

    <div class="container">
        <div class="services-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($service = $result->fetch_assoc()): ?>
                    <div class="service-card">
                        <?php if (!empty($service['image']) && file_exists($service['image'])): ?>
                            <img src="<?php echo htmlspecialchars($service['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($service['service_name']); ?>" 
                                 class="service-image">
                        <?php else: ?>
                            <div class="no-image">
                                <i class="fas fa-wrench"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="service-content">
                            <h3 class="service-title">
                                <?php echo htmlspecialchars($service['service_name']); ?>
                            </h3>
                            
                            <p class="service-description">
                                <?php 
                                // Use marketing_description if available, otherwise use regular description
                                $description = !empty($service['marketing_description']) 
                                    ? $service['marketing_description'] 
                                    : $service['description'];
                                echo htmlspecialchars($description); 
                                ?>
                            </p>
                            
                            <div class="service-meta">
                                <?php if (!empty($service['price'])): ?>
                                    <div class="service-price">
                                        ₹<?php echo number_format($service['price'], 2); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="service-time">
                                    <i class="fas fa-clock"></i>
                                    <?php 
                                    // Use duration_minutes if available, otherwise use estimated_time
                                    $time = !empty($service['duration_minutes']) 
                                        ? $service['duration_minutes'] . ' mins'
                                        : ($service['estimated_time'] ?? 'Contact us');
                                    echo htmlspecialchars($time); 
                                    ?>
                                </div>
                            </div>
                            
                            <a href="service-details.php?service_id=<?php echo $service['service_id']; ?>" class="btn">
                                <i class="fas fa-info-circle"></i>
                                More About
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <i class="fas fa-tools" style="font-size: 3rem; margin-bottom: 20px; color: #ccc;"></i>
                    <h3 style="color: #fff; margin-bottom: 10px; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">No services available at the moment</h3>
                    <p style="color: #fff; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Please check back later for our premium automotive services.</p>
                </div>
            <?php endif; ?>
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
            
            // Optimize scrolling performance
            let ticking = false;
            
            function update() {
                ticking = false;
            }
            
            function requestTick() {
                if (!ticking) {
                    requestAnimationFrame(update);
                    ticking = true;
                }
            }
            
            window.addEventListener('scroll', requestTick, { passive: true });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>