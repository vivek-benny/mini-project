<?php
session_start();

$conn = new mysqli("localhost", "root", "", "login");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ Create tables if not exist
$conn->query("CREATE TABLE IF NOT EXISTS register (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(30),
    email VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    phonenumber VARCHAR(10),
    profile_picture VARCHAR(255) DEFAULT NULL,`created_at` timestamp NOT NULL DEFAULT current_timestamp()
)");


$conn->query("CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30),
    email VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin') DEFAULT 'admin', profile_picture VARCHAR(255) DEFAULT NULL,`created_at` timestamp NOT NULL DEFAULT current_timestamp()

)");

$conn->query("CREATE TABLE IF NOT EXISTS staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    staffname VARCHAR(30),
    email VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    phone VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    role ENUM('staff') DEFAULT 'staff',profile_picture VARCHAR(255) DEFAULT NULL
)");

// Hash default passwords
$hashedAdminPassword = password_hash('admin123', PASSWORD_DEFAULT);
$hashedStaffPassword = password_hash('staff123', PASSWORD_DEFAULT);

// Email addresses for default accounts
$adminEmail = 'admin@example.com';
$staffEmail = 'staff@example.com';

// ✅ Insert default admin if not exists
$checkAdmin = $conn->query("SELECT * FROM admins WHERE email = '$adminEmail'");
if ($checkAdmin->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO admins (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", 'admin', $adminEmail, $hashedAdminPassword, 'admin');
    $stmt->execute();
    $stmt->close();
} else {
    // Check if existing admin password needs to be updated to hashed version
    $checkAdmin->data_seek(0); // Reset pointer to beginning
    $adminRow = $checkAdmin->fetch_assoc();
    if ($adminRow && $adminRow['password'] === 'admin123') {
        $updateStmt = $conn->prepare("UPDATE admins SET password = ? WHERE email = ?");
        $updateStmt->bind_param("ss", $hashedAdminPassword, $adminEmail);
        $updateStmt->execute();
        $updateStmt->close();
    }
}

// ✅ Insert default staff if not exists
$checkStaff = $conn->query("SELECT * FROM staff WHERE email = '$staffEmail'");
if ($checkStaff->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO staff (staffname, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", 'staff1', $staffEmail, $hashedStaffPassword, '2345678910', 'staff');
    $stmt->execute();
    $stmt->close();
} else {
    // Check if existing staff password needs to be updated to hashed version
    $checkStaff->data_seek(0); // Reset pointer to beginning
    $staffRow = $checkStaff->fetch_assoc();
    if ($staffRow && $staffRow['password'] === 'staff123') {
        $updateStmt = $conn->prepare("UPDATE staff SET password = ? WHERE email = ?");
        $updateStmt->bind_param("ss", $hashedStaffPassword, $staffEmail);
        $updateStmt->execute();
        $updateStmt->close();
    }
}

// ✅ Handle user sign-up
if (isset($_POST['sign-up'])) {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $phonenumber = trim($_POST["phonenumber"]);

    if (!empty($name) && !empty($email) && !empty($password) && !empty($phonenumber)) {
        // Hash the password before storing
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO register(name, email, password, phonenumber) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $hashedPassword, $phonenumber);

        if ($stmt->execute()) {
            echo "<script>alert('Registration successful! Please sign in.');</script>";
        } else {
            echo "<script>alert('Registration failed. Email may already be registered.');</script>";
        }
    } else {
        echo "<script>alert('All fields are required.');</script>";
    }
}

// ✅ Handle forgot password
if (isset($_POST['forgot-password'])) {
    $identifier = trim($_POST["identifier"]); // username or email
    $newPassword = trim($_POST["new-password"]);
    
    if (!empty($identifier) && !empty($newPassword)) {
        // Check if identifier is email or username
        $emailCheck = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        
        if ($emailCheck) {
            // Identifier is email
            $stmt = $conn->prepare("SELECT user_id FROM register WHERE email = ?");
            $stmt->bind_param("s", $identifier);
        } else {
            // Identifier is name
            $stmt = $conn->prepare("SELECT user_id FROM register WHERE name = ?");
            $stmt->bind_param("s", $identifier);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // User found, update password
            if (strlen($newPassword) >= 6) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                if ($emailCheck) {
                    $updateStmt = $conn->prepare("UPDATE register SET password = ? WHERE email = ?");
                    $updateStmt->bind_param("ss", $hashedPassword, $identifier);
                } else {
                    $updateStmt = $conn->prepare("UPDATE register SET password = ? WHERE name = ?");
                    $updateStmt->bind_param("ss", $hashedPassword, $identifier);
                }
                
                if ($updateStmt->execute()) {
                    echo "<script>alert('Password updated successfully! Please sign in with your new password.');</script>";
                } else {
                    echo "<script>alert('Failed to update password. Please try again.');</script>";
                }
                $updateStmt->close();
            } else {
                echo "<script>alert('Password must be at least 6 characters long.');</script>";
            }
        } else {
            echo "<script>alert('No user found with that username or email.');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('All fields are required.');</script>";
    }
}

// ✅ Handle sign-in for user, admin, or staff
// ✅ Handle sign-in for user, admin, or staff
if (isset($_POST['sign-in'])) {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $role = trim($_POST["role"]);

    if (!empty($email) && !empty($password) && !empty($role)) {
        
        if ($role === "user") {
            $stmt = $conn->prepare("SELECT user_id, name, password FROM register WHERE email = ?");
            $stmt->bind_param("s", $email);
        } elseif ($role === "admin") {
            $stmt = $conn->prepare("SELECT admin_id, username, password FROM admins WHERE email = ?");
            $stmt->bind_param("s", $email);
        } elseif ($role === "staff") {
            $stmt = $conn->prepare("SELECT staff_id, staffname, password FROM staff WHERE email = ?");
            $stmt->bind_param("s", $email);
        } else {
            echo "<script>alert('Invalid role selected!');</script>";
            exit();
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify the password (handle both hashed and plain text for backward compatibility)
            $passwordVerified = false;
            
            // Check if the stored password is already hashed (bcrypt hashes start with $2y$ or $2a$)
            if (strpos($user['password'], '$2y$') === 0 || strpos($user['password'], '$2a$') === 0) {
                // This is a hashed password, verify it properly
                if (password_verify($password, $user['password'])) {
                    $passwordVerified = true;
                }
            } else {
                // This is likely a plain text password, check directly
                if ($password === $user['password']) {
                    $passwordVerified = true;
                    // Hash the password for future use
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    // Update the database with the hashed password
                    if ($role === "user") {
                        $updateStmt = $conn->prepare("UPDATE register SET password = ? WHERE email = ?");
                        $updateStmt->bind_param("ss", $newHash, $email);
                        $updateStmt->execute();
                        $updateStmt->close();
                    } elseif ($role === "admin") {
                        $updateStmt = $conn->prepare("UPDATE admins SET password = ? WHERE email = ?");
                        $updateStmt->bind_param("ss", $newHash, $email);
                        $updateStmt->execute();
                        $updateStmt->close();
                    } elseif ($role === "staff") {
                        $updateStmt = $conn->prepare("UPDATE staff SET password = ? WHERE email = ?");
                        $updateStmt->bind_param("ss", $newHash, $email);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }
                }
            }
            
            if ($passwordVerified) {
                // ✅ Save login info to session
                $_SESSION['username'] = $user['name'] ?? $user['username'] ?? $user['staffname'];
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;

                // ✅ Redirect based on role
                if ($role === "user") {
                    header("Location: index.php");
                    exit();
                } elseif ($role === "admin") {
                    header("Location: admin.php");  // ✅ Admin redirects to admin.php
                    exit();
                } elseif ($role === "staff") {
                    header("Location: staff.php");
                    exit();
                }
            } else {
                echo "<script>alert('Invalid email or password!');</script>";
            }
        } else {
            echo "<script>alert('Invalid email or password!');</script>";
        }
    } else {
        echo "<script>alert('Please fill in all fields!');</script>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Garage Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    body {
      background: linear-gradient(to right,rgb(214, 194, 194),rgba(243, 107, 48, 0.84));
      display: flex; align-items: center; justify-content: center; flex-direction: column;
      min-height: 100vh;
    }
    .container {
      background-color: #fff;
      border-radius: 20px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      width: 768px; max-width: 100%; min-height: 480px;
      position: relative; overflow: hidden;
    }
    .container h1 { font-weight: 900; font-size: 2rem; color: #333; }
    .container span { font-size: 14px; color: #666; }
    .container button {
      background-color:rgb(243, 106, 48); color: #fff; font-size: 14px; padding: 12px 40px;
      border: none; border-radius: 30px; font-weight: 600;
      margin-top: 10px; cursor: pointer;
    }
    .container button:hover { background-color: #e04123; }
    .container form {
      background-color: #fff;
      display: flex; align-items: center; justify-content: center;
      flex-direction: column; padding: 0 40px; height: 100%;
    }
    .container input, select {
      background-color: #f1f1f1;
      border: 1px solid #ccc;
      padding: 12px 15px;
      margin: 8px 0;
      width: 100%;
      border-radius: 8px;
      outline: none;
      font-size: 14px;
    }
    small { font-size: 12px; margin-bottom: 5px; align-self: flex-start; }
    .hint { color: gray; }
    .error { color: red; }
    .form-container {
      position: absolute;
      top: 0;
      height: 100%;
      transition: all 0.6s ease-in-out;
    }
    .sign-in { left: 0; width: 50%; z-index: 2; }
    .sign-up { left: 0; width: 50%; opacity: 0; z-index: 1; }
    .container.active .sign-in { transform: translateX(100%); }
    .container.active .sign-up {
      transform: translateX(100%);
      opacity: 1;
      z-index: 5;
      animation: move 0.6s;
    }
    .toggle-container {
      position: absolute; top: 0; left: 50%; width: 50%; height: 100%;
      overflow: hidden; transition: all 0.6s ease-in-out;
      border-radius: 150px 0 0 100px; z-index: 1000;
    }
    .container.active .toggle-container {
      transform: translateX(-100%);
      border-radius: 0 150px 100px 0;
    }
    .toggle {
      background: linear-gradient(to right,rgb(243, 106, 48),rgba(245, 145, 102, 0.94));
      height: 100%; color: #fff; position: relative;
      left: -100%; width: 200%; transform: translateX(0);
      transition: all 0.6s ease-in-out;
    }
    .container.active .toggle { transform: translateX(50%); }
    .toggle-panel {
      position: absolute; width: 50%; height: 100%;
      display: flex; align-items: center; justify-content: center;
      flex-direction: column; padding: 0 30px; text-align: center; top: 0;
      transition: all 0.6s ease-in-out;
    }
    .toggle-left { transform: translateX(-200%); }
    .container.active .toggle-left { transform: translateX(0); }
    .toggle-right { right: 0; transform: translateX(0); }
    .container.active .toggle-right { transform: translateX(200%); }
    @keyframes move {
      0%, 49.99% { opacity: 0; z-index: 1; }
      50%, 100% { opacity: 1; z-index: 5; }
    }
    
    /* Forgot Password Modal */
    .modal {
      display: none;
      position: fixed;
      z-index: 10000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.4);
    }
    
    .modal-content {
      background-color: #fefefe;
      margin: 15% auto;
      padding: 20px;
      border: 1px solid #888;
      width: 80%;
      max-width: 500px;
      border-radius: 10px;
      box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
    }
    
    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }
    
    .close:hover,
    .close:focus {
      color: black;
      text-decoration: none;
      cursor: pointer;
    }
    
    .forgot-password-link {
      background: none;
      border: none;
      color: rgb(243, 106, 48);
      text-decoration: underline;
      cursor: pointer;
      font-size: 14px;
      margin: 10px 0;
    }
    
    .forgot-password-link:hover {
      color: #e04123;
    }
  </style>
</head>
<body>

<!-- Forgot Password Modal -->
<div id="forgotPasswordModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h2>Reset Password</h2>
    <form method="post" id="forgotPasswordForm">
      <input type="text" name="identifier" placeholder="Username or Email" required>
      <small>Enter your username or email to verify your account</small>
      
      <input type="password" id="new-password" name="new-password" placeholder="New Password" required>
      <small id="new-password-msg" class="hint">Password must be at least 6 characters long.</small>
      
      <input type="password" id="confirm-password" placeholder="Confirm New Password" required>
      <small id="confirm-password-msg" class="hint">Please confirm your new password</small>
      
      <button type="submit" name="forgot-password">Reset Password</button>
    </form>
  </div>
</div>

<div class="container" id="container">
  <!-- Sign Up -->
  <div class="form-container sign-up">
    <form method="post" onsubmit="return validateSignUp()">
      <h1>Create Account</h1>
      <span>or use your email for registration</span>

      <input type="text" name="name" id="signup-name" placeholder="Name" required>
      <small id="name-msg" class="hint">Only letters and spaces are allowed</small>

      <input type="email" id="signup-email" name="email" placeholder="Email" required>
      <small id="email-msg" class="hint">Enter a valid email address</small>

      <input type="password" id="signup-password" name="password" placeholder="Password" required>
      <small class="hint">Password must be at least 6 characters long.</small>
      <small id="password-msg"></small>

      <input type="number" id="signup-phone" name="phonenumber" placeholder="Phone Number" required>
      <small id="phone-msg" class="hint">Phone number must be exactly 10 digits</small>

      <button type="submit" name="sign-up">Sign Up</button>
    </form>
  </div>

  <!-- Sign In -->
  <div class="form-container sign-in">
    <form method="post">
      <h1>Sign In</h1>
      <span>or use your email and password</span>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <select name="role" required>
        <option value="">Select Role</option>
        <option value="user">User</option>
        <option value="admin">Admin</option>
        <option value="staff">Staff</option>
      </select>
      <button type="button" class="forgot-password-link" id="showForgotPassword" style="display: none;">Forgot Password?</button>
      <button type="submit" name="sign-in">Sign In</button>
    </form>
  </div>

  <!-- Toggle Panel -->
  <div class="toggle-container">
    <div class="toggle">
      <div class="toggle-panel toggle-left">
        <h1>Welcome Back!</h1>
        <p>To keep connected with us please login with your personal info</p>
        <button class="hidden" id="login">Sign In</button>
      </div>
      <div class="toggle-panel toggle-right">
        <h1>Hello, Friend!</h1>
        <p>Register with your personal details to use all of site features</p>
        <button class="hidden" id="register">Sign Up</button>
      </div>
    </div>
  </div>
</div>

<script>
const container = document.getElementById('container');
document.getElementById('register').addEventListener('click', () => container.classList.add("active"));
document.getElementById('login').addEventListener('click', () => container.classList.remove("active"));

// Forgot Password Modal
const modal = document.getElementById("forgotPasswordModal");
const btn = document.getElementById("showForgotPassword");
const span = document.getElementsByClassName("close")[0];

btn.onclick = function() {
  modal.style.display = "block";
}

span.onclick = function() {
  modal.style.display = "none";
}

window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
}

function validateEmail(email) {
  const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return pattern.test(email);
}

const nameField = document.getElementById("signup-name");
const phoneField = document.getElementById("signup-phone");
const emailField = document.getElementById("signup-email");
const passwordField = document.getElementById("signup-password");

const nameMsg = document.getElementById("name-msg");
const phoneMsg = document.getElementById("phone-msg");
const emailMsg = document.getElementById("email-msg");
const passwordMsg = document.getElementById("password-msg");

// Forgot password validation
const newPasswordField = document.getElementById("new-password");
const confirmPasswordField = document.getElementById("confirm-password");
const newPasswordMsg = document.getElementById("new-password-msg");
const confirmPasswordMsg = document.getElementById("confirm-password-msg");

newPasswordField.addEventListener("input", () => {
  if (newPasswordField.value.length < 6) {
    newPasswordField.style.border = "2px solid red";
    newPasswordMsg.textContent = "❌ Password must be at least 6 characters long.";
    newPasswordMsg.classList.add("error");
    newPasswordMsg.classList.remove("hint");
  } else {
    newPasswordField.style.border = "1px solid #ccc";
    newPasswordMsg.textContent = "Password must be at least 6 characters long.";
    newPasswordMsg.classList.add("hint");
    newPasswordMsg.classList.remove("error");
  }
});

confirmPasswordField.addEventListener("input", () => {
  if (confirmPasswordField.value !== newPasswordField.value) {
    confirmPasswordField.style.border = "2px solid red";
    confirmPasswordMsg.textContent = "❌ Passwords do not match.";
    confirmPasswordMsg.classList.add("error");
    confirmPasswordMsg.classList.remove("hint");
  } else {
    confirmPasswordField.style.border = "1px solid #ccc";
    confirmPasswordMsg.textContent = "Please confirm your new password";
    confirmPasswordMsg.classList.add("hint");
    confirmPasswordMsg.classList.remove("error");
  }
});

// Live validation
nameField.addEventListener("input", () => {
  const nameRegex = /^[A-Za-z\s]*$/;
  if (!nameRegex.test(nameField.value)) {
    nameField.style.border = "2px solid red";
    nameMsg.textContent = "❌ Only letters and spaces are allowed.";
    nameMsg.classList.add("error");
    nameMsg.classList.remove("hint");
  } else {
    nameField.style.border = "1px solid #ccc";
    nameMsg.textContent = "Only letters and spaces are allowed";
    nameMsg.classList.add("hint");
    nameMsg.classList.remove("error");
  }
});

phoneField.addEventListener("input", () => {
  const phoneRegex = /^\d{10}$/;
  if (!phoneRegex.test(phoneField.value)) {
    phoneField.style.border = "2px solid red";
    phoneMsg.textContent = "❌ Phone number must be exactly 10 digits.";
    phoneMsg.classList.add("error");
    phoneMsg.classList.remove("hint");
  } else {
    phoneField.style.border = "1px solid #ccc";
    phoneMsg.textContent = "Phone number must be exactly 10 digits";
    phoneMsg.classList.add("hint");
    phoneMsg.classList.remove("error");
  }
});

passwordField.addEventListener("input", () => {
  if (passwordField.value.length < 6) {
    passwordField.style.border = "2px solid red";
    passwordMsg.textContent = "❌ Password must be at least 6 characters long.";
    passwordMsg.classList.add("error");
    passwordMsg.classList.remove("hint");
  } else {
    passwordField.style.border = "1px solid #ccc";
    passwordMsg.textContent = "";
  }
});

emailField.addEventListener("input", () => {
  if (!validateEmail(emailField.value)) {
    emailField.style.border = "2px solid red";
    emailMsg.textContent = "❌ Enter a valid email address.";
    emailMsg.classList.add("error");
    emailMsg.classList.remove("hint");
  } else {
    emailField.style.border = "1px solid #ccc";
    emailMsg.textContent = "Enter a valid email address";
    emailMsg.classList.add("hint");
    emailMsg.classList.remove("error");
  }
});

// Final submit validation
function validateSignUp() {
  let valid = true;
  const nameRegex = /^[A-Za-z\s]+$/;
  if (!nameRegex.test(nameField.value.trim())) valid = false;
  if (!validateEmail(emailField.value.trim())) valid = false;
  if (!/^\d{10}$/.test(phoneField.value.trim())) valid = false;
  if (passwordField.value.trim().length < 6) valid = false;
  return valid;
}

// Show/hide forgot password button based on role selection
const roleSelect = document.querySelector('select[name="role"]');
const forgotPasswordBtn = document.getElementById("showForgotPassword");

roleSelect.addEventListener("change", function() {
  if (this.value === "user") {
    forgotPasswordBtn.style.display = "block";
  } else {
    forgotPasswordBtn.style.display = "none";
  }
});

// Forgot password form validation
document.getElementById("forgotPasswordForm").addEventListener("submit", function(e) {
  if (newPasswordField.value.length < 6) {
    alert("Password must be at least 6 characters long.");
    e.preventDefault();
    return false;
  }
  
  if (newPasswordField.value !== confirmPasswordField.value) {
    alert("Passwords do not match.");
    e.preventDefault();
    return false;
  }
});
</script>
</body>
</html>