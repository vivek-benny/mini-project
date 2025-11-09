<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Prevent caching of protected page
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 
?>
<!-- your HTML content follows -->


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Garage - Car Service</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    /* Existing style assumed to be in style.css */

    .testimonials-section {
      padding: 60px 20px;
      background: #fff;
      text-align: center;
      position: relative;
    }

    .testimonial-title {
      font-size: 2rem;
      color: #222;
      margin-bottom: 10px;
      font-weight: bold;
    }

    .testimonial-subtitle {
      color: #888;
      margin-bottom: 30px;
      font-size: 1rem;
    }

    .testimonials-container {
      position: relative;
      max-width: 400px;
      margin: 0 auto;
      overflow: hidden;
    }

    .testimonials-wrapper {
      display: flex;
      transition: transform 0.5s ease-in-out;
    }

    .testimonial-card {
      flex: 0 0 100%;
      background: #f9f9f9;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      text-align: left;
      margin: 0 10px;
    }

    .user-dp {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      margin-bottom: 20px;
      object-fit: cover;
      border: 3px solid #ff4b2b;
    }

    .testimonial-text {
      font-size: 1rem;
      line-height: 1.7;
      color: #333;
      margin-bottom: 20px;
      font-style: italic;
    }

    .testimonial-name {
      font-weight: bold;
      color: #ff4b2b;
      font-size: 1.1rem;
    }

    .review-source {
      font-size: 0.85rem;
      color: #888;
      margin-top: 5px;
    }

    .testimonial-nav {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-top: 30px;
      gap: 20px;
    }

    .nav-btn {
      background: #ff4b2b;
      color: white;
      border: none;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      cursor: pointer;
      font-size: 1.2rem;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .nav-btn:hover {
      background: #e63946;
      transform: scale(1.1);
    }

    .nav-btn:disabled {
      background: #ccc;
      cursor: not-allowed;
      transform: none;
    }

    .pagination-dots {
      display: flex;
      gap: 10px;
    }

    .dot {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: #ddd;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .dot.active {
      background: #ff4b2b;
      transform: scale(1.2);
    }

    .dot:hover {
      background: #ff6b4a;
    }

    /* Auto-play indicator */
    .auto-play-indicator {
      position: absolute;
      top: 20px;
      right: 20px;
      background: rgba(255, 75, 43, 0.1);
      color: #ff4b2b;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .auto-play-indicator:hover {
      background: rgba(255, 75, 43, 0.2);
    }

    .auto-play-indicator.paused {
      background: rgba(136, 136, 136, 0.1);
      color: #888;
    }

    .hero {
      background: url("images/background.jpg") no-repeat center center/cover;
      color: white;
      padding: 100px 20px;
      text-align: center;
    }

    .hero h1 {
      font-size: 2.5rem;
      font-weight: bold;
    }

    /* Plain content styling without container */
    .main-title {
      font-size: 2.5rem;
      color: #333;
      margin: 40px 20px 15px;
      text-align: center;
    }

    .main-subtitle {
      font-size: 1.2rem;
      color: #666;
      margin: 0 20px 20px;
      text-align: center;
    }

    .typewriter-text {
      font-size: 1rem;
      color: #555;
      margin: 0 20px 30px;
      max-width: 800px;
      text-align: center;
      margin-left: auto;
      margin-right: auto;
    }

    .typewriter-cursor {
      display: inline-block;
      width: 2px;
      height: 1.2em;
      background: #ff4b2b;
      margin-left: 2px;
      animation: blink 1s infinite;
    }

    @keyframes blink {
      0%, 50% { opacity: 1; }
      51%, 100% { opacity: 0; }
    }

    .btn {
      background: #ff4b2b;
      color: white;
      padding: 12px 25px;
      text-decoration: none;
      border-radius: 5px;
      font-size: 1rem;
      display: block;
      margin: 0 auto 30px;
      text-align: center;
      width: fit-content;
    }

    .btn:hover {
      background: #e63946;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      max-width: 800px;
      margin: 0 auto 40px;
      padding: 0 20px;
    }

    .features-grid .feature-item {
      padding: 20px;
      background: #ffffff;
      border-radius: 12px;
      text-align: center;
      opacity: 0;
      transform: translateY(30px) scale(0.9);
      transition: all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      border: 1px solid #f0f0f0;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      position: relative;
      overflow: hidden;
    }

    .features-grid .feature-item::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, #ff4b2b, #ff416c);
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .features-grid .feature-item:hover::before {
      opacity: 1;
    }

    .features-grid .feature-item.visible {
      opacity: 1;
      transform: translateY(0) scale(1);
      animation: professionalSlideIn 0.6s ease-out;
    }

    @keyframes professionalSlideIn {
      0% {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
      }
      60% {
        transform: translateY(-2px) scale(1.01);
      }
      100% {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .features-grid .feature-item .feature-image {
      width: 100%;
      height: 160px;
      object-fit: cover;
      border-radius: 8px;
      margin-bottom: 15px;
      transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      filter: brightness(0.95) contrast(1.05);
    }

    .features-grid .feature-item.visible .feature-image {
      transform: scale(1);
      filter: brightness(1) contrast(1);
    }

    .features-grid .feature-item:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
      border-color: #e0e0e0;
    }

    .features-grid .feature-item:hover .feature-image {
      transform: scale(1.03);
      filter: brightness(1.05) contrast(1.1);
    }

    .feature-text {
      font-size: 1rem;
      color: #333;
      font-weight: 500;
      letter-spacing: 0.3px;
      margin-top: 5px;
      line-height: 1.4;
    }

    /* Responsive design */
    @media (max-width: 768px) {
      .testimonials-container {
        max-width: 320px;
      }
      
      .testimonial-card {
        padding: 20px;
        margin: 0 5px;
      }
      
      .nav-btn {
        width: 35px;
        height: 35px;
        font-size: 1rem;
      }

      .main-title {
        font-size: 2rem;
      }
      
      .features-grid {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 15px;
        max-width: 100%;
      }
      
      .features-grid .feature-item {
        padding: 18px;
      }
      
      .features-grid .feature-item .feature-image {
        height: 140px;
      }
    }

    @media (max-width: 480px) {
      .features-grid {
        grid-template-columns: 1fr;
        gap: 12px;
      }
      
      .features-grid .feature-item {
        padding: 16px;
      }
      
      .features-grid .feature-item .feature-image {
        height: 120px;
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
    <li><a href="booking.php">Booking</a></li>  <!-- ✅ NEW BOOKING LINK -->
    <li><a href="contact_staff.php" >Contact</a></li>
    <li><a href="profile.php">Profile</a></li>
  </ul>
</nav>


  <header class="hero" role="banner">
    <h1>Welcome to Garage</h1>
  </header>

  <h1 class="main-title">Your Trusted Car Service Partner</h1>
  <p class="main-subtitle">Excellence in Every Service, Trust in Every Mile</p>
  
  <div class="typewriter-text" id="typewriterText">
    <span class="typewriter-cursor" id="cursor"></span>
  </div>
  
  <a href="service.php" class="btn">view  Services</a>
  
<div class="features-grid">
  <div class="feature-item">
    <img src="images/technicians.jpg" alt="Expert Technicians" class="feature-image" />
    <div class="feature-text">Expert Technicians</div>
  </div>
  <div class="feature-item">
    <img src="images/customer service.jpg" alt="Quick Service" class="feature-image" />
    <div class="feature-text">Quick Service</div>
  </div>
  <div class="feature-item">
    <img src="images/price.jpg" alt="Affordable Pricing" class="feature-image" />
    <div class="feature-text">Affordable Pricing</div>
  </div>
  <div class="feature-item">
    <img src="images/quarantee.jpg" alt="Quality Guarantee" class="feature-image" />
    <div class="feature-text">Quality Guarantee</div>
  </div>
</div>


  <section class="stats" aria-label="Company Statistics">
    <div class="stat"><h3>150+</h3><p>Registered Companies</p></div>
    <div class="stat"><h3>3000+</h3><p>Vehicles Serviced</p></div>
    <div class="stat"><h3>20+</h3><p>Expert Technicians</p></div>
    <div class="stat"><h3>10 Years</h3><p>Industry Experience</p></div>
  </section>

  <!-- TESTIMONIALS SECTION -->
  <section class="testimonials-section">
    <h2 class="testimonial-title">Customer Testimonials</h2>
    <p class="testimonial-subtitle">What Our Customers Have To Say</p>
    
    <div class="auto-play-indicator" id="autoPlayIndicator">
      ⏸️ Auto-play: ON
    </div>
    
    <div class="testimonials-container">
      <div class="testimonials-wrapper" id="testimonialsWrapper">
        <div class="testimonial-card">
          <img src="images/user1.jpg" alt="Atulya" class="user-dp" />
          <p class="testimonial-text">"It was a wonderful experience. Garage is a workshop of highly professional work force. They customized my car and delivered with complete ease. Highly recommended!"</p>
          <p class="testimonial-name">❝ Atul</p>
          <span class="review-source">Google Review</span>
        </div>
        <div class="testimonial-card">
          <img src="images/user2.jpg" alt="Rimi" class="user-dp" />
          <p class="testimonial-text">"Very professional and reasonably priced. Staff is excellent, and the facility is also top-notch! I would highly recommend trying it!"</p>
          <p class="testimonial-name">❝ Rimi</p>
          <span class="review-source">Google Review</span>
        </div>
        <div class="testimonial-card">
          <img src="images/user3.jpg" alt="Neha" class="user-dp" />
          <p class="testimonial-text">"Service was timely and efficient. My AC was fixed and I was updated with progress photos. Great experience!"</p>
          <p class="testimonial-name">❝ Neha</p>
          <span class="review-source">Google Review</span>
        </div>
      </div>
    </div>
    
    <div class="testimonial-nav">
      <button class="nav-btn" id="prevBtn" aria-label="Previous testimonial">‹</button>
      <div class="pagination-dots">
        <span class="dot active" data-slide="0"></span>
        <span class="dot" data-slide="1"></span>
        <span class="dot" data-slide="2"></span>
      </div>
      <button class="nav-btn" id="nextBtn" aria-label="Next testimonial">›</button>
    </div>
  </section>

  <footer>
    <div class="footer-container">
      <div class="contact-info">
        <h2>Contact Us</h2>
        <p>123 Garage St., Auto City, AC 12345</p>
        <p>Email: support@garage.com</p>
        <p>Phone: +1 (555) 123-4567</p>
      </div>
      <div class="social-media">
        <h2>Follow Us</h2>
        <p>
          <a href="#">Facebook</a> |
          <a href="#">Twitter</a> |
          <a href="#">Instagram</a>
        </p>
      </div>
    </div>
    <p class="footer-bottom">&copy; 2025 Garage. All rights reserved.</p>
  </footer>

  <script>
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('nav ul');
    menuToggle.addEventListener('click', () => {
      navLinks.classList.toggle('active');
    });

    function isInViewport(el) {
      const rect = el.getBoundingClientRect();
      return (
        rect.top <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.bottom >= 0
      );
    }

    // Stats animation
    const stats = document.querySelectorAll('section.stats .stat');
    function checkStatsVisibility() {
      stats.forEach(stat => {
        if (isInViewport(stat)) {
          stat.classList.add('visible');
        } else {
          stat.classList.remove('visible');
        }
      });
    }

    // Features animation with debugging
    const featureItems = document.querySelectorAll('.feature-item');
    console.log('Found feature items:', featureItems.length);
    
    function checkFeaturesVisibility() {
      featureItems.forEach((item, index) => {
        const rect = item.getBoundingClientRect();
        const isVisible = rect.top <= window.innerHeight && rect.bottom >= 0;
        
        console.log(`Feature ${index}: visible=${isVisible}, top=${rect.top}`);
        
        if (isVisible && !item.classList.contains('visible')) {
          console.log(`Animating feature ${index}`);
          // Add a small delay for each item to create a staggered effect
          setTimeout(() => {
            item.classList.add('visible');
            item.style.opacity = '1';
            item.style.transform = 'translateY(0) scale(1)';
          }, index * 150);
        } else if (!isVisible) {
          item.classList.remove('visible');
          item.style.opacity = '0';
          item.style.transform = 'translateY(30px) scale(0.9)';
        }
      });
    }
    
    // Force initial state
    featureItems.forEach(item => {
      item.style.opacity = '0';
      item.style.transform = 'translateY(30px) scale(0.9)';
      item.style.transition = 'all 0.6s ease';
    });

    // Combined scroll event listener
    function checkAllVisibility() {
      checkStatsVisibility();
      checkFeaturesVisibility();
    }

    window.addEventListener('scroll', checkAllVisibility);
    window.addEventListener('load', checkAllVisibility);

    // Testimonials Slider
    class TestimonialSlider {
      constructor() {
        this.wrapper = document.getElementById('testimonialsWrapper');
        this.prevBtn = document.getElementById('prevBtn');
        this.nextBtn = document.getElementById('nextBtn');
        this.dots = document.querySelectorAll('.dot');
        this.autoPlayIndicator = document.getElementById('autoPlayIndicator');
        
        this.currentSlide = 0;
        this.totalSlides = 3;
        this.isAutoPlay = true;
        this.autoPlayInterval = null;
        
        this.init();
      }
      
      init() {
        this.updateSlider();
        this.bindEvents();
        this.startAutoPlay();
      }
      
      bindEvents() {
        this.prevBtn.addEventListener('click', () => this.prevSlide());
        this.nextBtn.addEventListener('click', () => this.nextSlide());
        
        this.dots.forEach((dot, index) => {
          dot.addEventListener('click', () => this.goToSlide(index));
        });
        
        this.autoPlayIndicator.addEventListener('click', () => this.toggleAutoPlay());
        
        // Pause auto-play on hover
        const testimonialSection = document.querySelector('.testimonials-section');
        testimonialSection.addEventListener('mouseenter', () => {
          if (this.isAutoPlay) this.pauseAutoPlay();
        });
        
        testimonialSection.addEventListener('mouseleave', () => {
          if (this.isAutoPlay) this.startAutoPlay();
        });
      }
      
      updateSlider() {
        const translateX = -this.currentSlide * 100;
        this.wrapper.style.transform = `translateX(${translateX}%)`;
        
        // Update dots
        this.dots.forEach((dot, index) => {
          dot.classList.toggle('active', index === this.currentSlide);
        });
        
        // Update navigation buttons
        this.prevBtn.disabled = this.currentSlide === 0;
        this.nextBtn.disabled = this.currentSlide === this.totalSlides - 1;
      }
      
      nextSlide() {
        if (this.currentSlide < this.totalSlides - 1) {
          this.currentSlide++;
          this.updateSlider();
        } else {
          // Loop back to first slide
          this.currentSlide = 0;
          this.updateSlider();
        }
      }
      
      prevSlide() {
        if (this.currentSlide > 0) {
          this.currentSlide--;
          this.updateSlider();
        } else {
          // Loop to last slide
          this.currentSlide = this.totalSlides - 1;
          this.updateSlider();
        }
      }
      
      goToSlide(index) {
        this.currentSlide = index;
        this.updateSlider();
      }
      
      startAutoPlay() {
        if (!this.isAutoPlay) return;
        
        this.autoPlayInterval = setInterval(() => {
          this.nextSlide();
        }, 1000); // Change slide every 4 seconds
      }
      
      pauseAutoPlay() {
        if (this.autoPlayInterval) {
          clearInterval(this.autoPlayInterval);
          this.autoPlayInterval = null;
        }
      }
      
      toggleAutoPlay() {
        this.isAutoPlay = !this.isAutoPlay;
        
        if (this.isAutoPlay) {
          this.autoPlayIndicator.textContent = '⏸️ Auto-play: ON';
          this.autoPlayIndicator.classList.remove('paused');
          this.startAutoPlay();
        } else {
          this.autoPlayIndicator.textContent = '▶️ Auto-play: OFF';
          this.autoPlayIndicator.classList.add('paused');
          this.pauseAutoPlay();
        }
      }
    }
    
    // Initialize the slider when DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
      new TestimonialSlider();
      new TypewriterEffect();
    });

    // Typewriter Effect Class
    class TypewriterEffect {
      constructor() {
        this.textElement = document.getElementById('typewriterText');
        this.cursor = document.getElementById('cursor');
        this.texts = [
          "Welcome to Garage, your trusted car service partner. At Garage, we provide comprehensive and reliable car maintenance and repair services to keep your vehicle running smoothly and safely.",
          "Whether you need routine oil changes, brake repairs, engine diagnostics, or full-service inspections, our team of experienced technicians is here to deliver top-quality care tailored to your car's needs.",
          "We pride ourselves on transparency, quality workmanship, and customer satisfaction. Your safety and peace of mind are our top priorities."
        ];
        this.currentTextIndex = 0;
        this.currentCharIndex = 0;
        this.isDeleting = false;
        this.typeSpeed = 30; // Very fast typing
        this.deleteSpeed = 15;
        this.pauseBetweenTexts = 2000;
        
        this.init();
      }
      
      init() {
        this.type();
      }
      
      type() {
        const currentText = this.texts[this.currentTextIndex];
        
        if (this.isDeleting) {
          // Remove characters
          this.textElement.textContent = currentText.substring(0, this.currentCharIndex - 1);
          this.currentCharIndex--;
          
          if (this.currentCharIndex === 0) {
            this.isDeleting = false;
            this.currentTextIndex = (this.currentTextIndex + 1) % this.texts.length;
            setTimeout(() => this.type(), 500);
            return;
          }
        } else {
          // Add characters
          this.textElement.textContent = currentText.substring(0, this.currentCharIndex + 1);
          this.currentCharIndex++;
          
          if (this.currentCharIndex === currentText.length) {
            // Wait before starting to delete
            setTimeout(() => {
              this.isDeleting = true;
              this.type();
            }, this.pauseBetweenTexts);
            return;
          }
        }
        
        // Continue typing/deleting
        const speed = this.isDeleting ? this.deleteSpeed : this.typeSpeed;
        setTimeout(() => this.type(), speed);
      }
    }
  </script>
 <script>
  // Prevent back button from showing cached protected page
  window.addEventListener('pageshow', function (event) {
    if (event.persisted || window.performance.getEntriesByType("navigation")[0].type === "back_forward") {
      window.location.reload();
    }
  });
</script>


</body>
</html>