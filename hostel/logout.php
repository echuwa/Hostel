<?php
session_start();
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';

// Store logout message in session to display on index page
$_SESSION['logout_message'] = "Goodbye, $username! You have been successfully logged out.";

// Destroy the session
unset($_SESSION['id']);
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out | Hostel Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .logout-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            text-align: center;
        }
        .logout-card {
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: white;
            max-width: 500px;
            width: 90%;
        }
        .spinner {
            width: 3rem;
            height: 3rem;
            margin: 2rem auto;
        }
        .logout-message {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
        }
        .logout-icon {
            font-size: 4rem;
            color: #4a6baf;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-card">
            <div class="logout-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h2>Logging Out</h2>
            <div class="logout-message">
                Goodbye, <?php echo htmlspecialchars($username); ?>! You're being securely logged out...
            </div>
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted">Redirecting to homepage in <span id="countdown">5</span> seconds</p>
        </div>
    </div>

    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    
    <script>
        // Countdown timer
        let seconds = 5;
        const countdown = setInterval(() => {
            seconds--;
            document.getElementById('countdown').textContent = seconds;
            if (seconds <= 0) {
                clearInterval(countdown);
                window.location.href = 'index.php';
            }
        }, 1000);
    </script>
</body>
</html>