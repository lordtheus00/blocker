<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out...</title>
    <link href="assets/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html, body {
            height: 100%;
            background-color: #212529;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .logout-container .logout-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            animation: spin 2s linear infinite;
        }
        .logout-container .logout-message {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .logout-container .redirect-message {
            color: #adb5bd;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <i class="fas fa-spinner logout-icon"></i>
        <div class="logout-message">Logging Out...</div>
        <div class="redirect-message">You will be redirected to the login page</div>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 2500);
    </script>
</body>
</html>
