<?php
// Start session
session_start();

// Database connection settings
$host = "localhost";
$dbUsername = "root"; // Change to your database username
$dbPassword = 12345678; // Change to your database password
$dbName = "artvista"; // Change to your database name

// Initialize message variables
$error_message = '';
$success_message = '';

// Connect to database
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbName", $dbUsername, $dbPassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    $error_message = "Connection failed: " . $e->getMessage();
    echo $error_message;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST['loginEmail']) ? trim($_POST['loginEmail']) : '';
    $password = isset($_POST['loginPassword']) ? $_POST['loginPassword'] : '';
    $remember = isset($_POST['rememberMe']) ? true : false;
    
    // Form validation
    if (empty($email)) {
        $error_message = "Email is required";
        echo $error_message;
    } elseif (empty($password)) {
        $error_message = "Password is required";
        echo $error_message;
    } else {
        try {
            // Check if user exists
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // User exists, verify password
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Check password (assuming password is hashed with password_hash())
                if (password_verify($password, $user['password'])) {
                    // Password is correct, set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['logged_in'] = true;
                    
                    // Set cookie if remember me is checked
                    if ($remember) {
                        setcookie("user_email", $email, time() + (86400 * 30), "/"); // 30 days
                    }
                    
                    // Redirect to home page
                    header("Location: home.html");
                    exit();
                } else {
                    $error_message = "Invalid password";
                    echo "Invalid password!";
                }
            } else {
                $error_message = "User not found. Please register first.";
                echo "User not found. Please register first.";
            }
        } catch(PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Check if there's a remember me cookie
if (!isset($_SESSION['logged_in']) && isset($_COOKIE['user_email'])) {
    $email = $_COOKIE['user_email'];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['logged_in'] = true;

            echo "Redirecting...";
            
            header("Location: home.html");
            exit();
        }
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}