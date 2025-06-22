<?php
session_start();
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $config = json_decode(file_get_contents('../config.json'), true);
    if ($_POST['password'] == $config['admin_password']) {
        $_SESSION['admin'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid password';
    }
}
if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="assets/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #212529;
        }
        .form-signin {
            max-width: 400px;
            width: 100%;
        }
    </style>
</head>
<body>
    <main class="form-signin text-center">
        <form method="POST">
            <h1 class="h3 mb-3 fw-normal">BLOKER_V8 Admin</h1>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="floatingPassword" name="password" placeholder="Password" required>
                <label for="floatingPassword">Password</label>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <button class="w-100 btn btn-lg btn-primary" type="submit">Sign in</button>
            <p class="mt-5 mb-3 text-body-secondary">&copy; <?= date('Y') ?></p>
        </form>
    </main>
</body>
</html>
