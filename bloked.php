<?php
session_start();


if (!isset($_SESSION['blocked_login'])) {
    header('Location: login.php');
    exit;
}

$login = htmlspecialchars($_SESSION['blocked_login']);

unset($_SESSION['blocked_login']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вы заблокированы. Обратитесь к администратору.</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper" style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1rem;">
    <div class="form-compact">
        <h1>Доступ ограничен</h1>
        <div class="error">
            Ваша учётная запись <strong><?= $login ?></strong> заблокирована из-за превышения количества неудачных попыток входа.
        </div>
        <p>Обратитесь к администратору для разблокировки.</p>
        <p class="text-center">
            <a href="login.php" class="button">Вернуться к форме авторизации</a>
        </p>
    </div>
</div>
</body>
</html>