

<?php
// Start session
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Prevent caching of this page
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login page
header("Location: login.php");
exit();
?>

<!-- 
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Logout Confirmation</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: linear-gradient(to right, #f1e4e4, #f3a75a);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .logout-box {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      text-align: center;
    }
    .logout-box h2 {
      margin-bottom: 20px;
    }
    .logout-box button {
      padding: 10px 20px;
      margin: 10px;
      font-weight: bold;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      transition: 0.3s ease;
    }
    .yes {
      background-color: #f3642b;
      color: white;
    }
    .yes:hover {
      background-color: #d74d1d;
    }
    .no {
      background-color: #ddd;
    }
    .no:hover {
      background-color: #bbb;
    }
  </style>
</head>
<body>
  <div class="logout-box">
    <h2>Are you sure you want to logout?</h2>
    <button class="yes" onclick="confirmLogout()">Yes</button>
    <button class="no" onclick="cancelLogout()">No</button>
  </div>

  <script>
    function confirmLogout() {
      window.location.href = 'logout.php?logout=yes';
    }

    function cancelLogout() {
      window.location.href = 'index.php';
    }
  </script>
</body>
</html> -->
