<?php
require_once 'functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $captchaOk = $_POST['captchaOk'] ?? '';

    if (empty($login) || empty($password)) {
        $error = 'Логин и пароль обязательны.';
    } elseif ($captchaOk !== '1') {
        $error = 'Соберите пазл правильно перед входом.';
    } else {
        $user = getUserByLogin($login);
        if (!$user || !password_verify($password, $user['password'])) {
            updateFailedAttempts($login, false);
            $error = 'Неверный логин или пароль.';
        } elseif ($user['is_blocked']) {
    $_SESSION['blocked_login'] = $login;
    header('Location: blocked.php');
    exit;
        } else {
            updateFailedAttempts($login, true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['login'] = $user['login'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['customer_id'] = $user['customer_id'];
            header('Location: index.php');
            exit;
        }
    }

}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="stylesheet" href="style.css">
    <script src="captcha.js" defer></script>
</head>
<body>
<div class="wrapper" style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1rem;">
    <div class="form-compact">
        <h1>Вход</h1>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <label>Логин</label>
            <input type="text" name="login" required autofocus>

            <label>Пароль</label>
            <input type="password" name="password" required>

            <div class="captcha-controls">
                <div id="captchaContainer" class="captcha-container"></div>
                <button type="button" id="checkCaptchaBtn" class="small">Проверить пазл</button>
                <button type="button" id="resetCaptchaBtn" class="small">Сбросить</button>
                <div id="captchaError" class="error"></div>
                <input type="hidden" name="captchaOk" id="captchaOk" value="">
            </div>

            <button type="submit" id="loginBtn" disabled>Войти</button>
        </form>

        <div class="test-accounts">
    <hr>
    <h3>Тестовые пользователи</h3>
    <table class="test-table">
        <tr><th>Логин</th><th>Пароль</th><th>Роль</th></tr>
        <tr><td>admin</td><td>admin</td><td>admin</td></tr>
        <tr><td>ivan</td><td>passivan</td><td>user</td></tr>
        <tr><td>petrova</td><td>passpetrova</td><td>user</td></tr>
        <tr><td>sidorov</td><td>passsidorov</td><td>user</td></tr>
        <tr><td>blok</td><td>testblok</td><td>user</td></tr>
    </table>
    <p class="note">* Пользователь <strong>blok</strong> заблокирован (3 неудачных(ая/ые) попытки).</p>
</div>
        <p class="text-center mt-1">Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
    </div>
</div>
</body>
</html>