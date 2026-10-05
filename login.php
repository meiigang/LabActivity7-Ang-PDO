<?php
    declare(strict_types=1);
    session_start();
    require __DIR__ . '/db.php';

    if (!empty($_SESSION['authenticated'])) {
        header('Location: index.php');
        exit;
    }

    $error = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    $email = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $error = 'Enter a valid email address.';
        } elseif ($password === '') {
            $error = 'Enter your password.';
        } else {
            $statement = $pdo->prepare('SELECT id, name, email, password FROM users WHERE email = :email');
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();

            if ($user === false) {
                $error = 'That email is not registered.';
            } elseif (!password_verify($password, (string) $user['password'])) {
                $error = 'Incorrect password.';
            } else {
                session_regenerate_id(true);
                $_SESSION['authenticated'] = true;
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                header('Location: index.php');
                exit;
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Login</h1>

    <p id="email-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>

    <form method="post" id="login-form">
        <label>
            Email
            <input type="email" name="email" id="email" maxlength="255" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
        </label>
        <br>
        <label>
            Password
            <input type="password" name="password" minlength="8" required>
        </label>
        <br>
        <button type="submit">Log in</button>
    </form>

    <p><a href="register.php">Register</a></p>

</body>
</html>