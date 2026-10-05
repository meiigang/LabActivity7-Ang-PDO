<?php
    declare(strict_types=1);
    session_start();
    require __DIR__ . '/db.php';

    if (!empty($_SESSION['authenticated'])) {
        header('Location: index.php');
        exit;
    }

    $errors = [];
    $name = '';
    $email = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '' || strlen($name) > 100) {
            $errors[] = 'Name must be between 1 and 100 characters.';
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Enter a valid email address.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($errors === []) {
            $statement = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $statement->execute(['email' => $email]);

            if ($statement->fetch() !== false) {
                $errors[] = 'That email is already registered.';
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO users (name, email, password) VALUES (:name, :email, :password)'
                );
                $statement->execute([
                    'name' => $name,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                $_SESSION['flash'] = 'Registration successful. You can now log in.';
                header('Location: login.php');
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
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Register</h1>

    <?php foreach ($errors as $error): ?>
        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endforeach; ?>

    <form method="post">
        <label>
            Name
            <input type="text" name="name" maxlength="100" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required>
        </label>
        <br>
        <label>
            Email
            <input type="email" name="email" maxlength="255" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
        </label>
        <br>
        <label>
            Password
            <input type="password" name="password" minlength="8" required>
        </label>
        <br>
        <button type="submit">Register</button>
    </form>

    <p><a href="login.php">Log in</a></p>

</body>
</html>