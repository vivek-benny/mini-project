<?php
$conn = new mysqli("localhost", "root", "", "login");
$id = intval($_GET['id'] ?? 0);
$s = $conn->query("SELECT * FROM services WHERE id=$id")->fetch_assoc();
if (!$s) { echo "Service not found."; exit; }
?>
<!DOCTYPE html>
<html><head><title><?= htmlspecialchars($s['title']) ?></title>
<style>
  body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; padding: 40px; }
  .container { max-width: 700px; margin: auto; background: white; border-radius: 20px; padding: 30px; box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
  .container img { width: 100%; border-radius: 12px; margin-bottom: 20px; }
  .container h1 { color: #ff4b2b; }
  .container p { font-size: 1rem; color: #444; }
  .container .price { font-weight: bold; font-size: 1.2rem; margin-top: 20px; }
</style>
</head><body>
<div class="container">
  <img src="<?= htmlspecialchars($s['image']) ?>" alt="Service Image">
  <h1><?= htmlspecialchars($s['title']) ?></h1>
  <p><?= nl2br(htmlspecialchars($s['description'])) ?></p>
  <p class="price">Price: ₹<?= number_format($s['price'],2) ?></p>
  <a class="book-btn" href="#" style="display:inline-block;margin-top:20px;background:#ff4b2b;color:white;padding:10px 20px;border-radius:25px;text-decoration:none;">Book Now</a>
</div>
</body></html>
