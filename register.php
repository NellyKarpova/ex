<?php
require_once 'functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $inn      = trim($_POST['inn'] ?? '');
    $addres   = trim($_POST['addres'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($name) || empty($addres) || empty($phone) || empty($login) || empty($password)) {
        $error = 'Все поля, кроме ИНН, обязательны.';
    } elseif ($password !== $password_confirm) {
        $error = 'Пароли не совпадают.';
    } elseif (strlen($password) < 4) {
        $error = 'Пароль должен содержать минимум 4 символа.';
    } elseif (getUserByLogin($login)) {
        $error = 'Логин уже занят.';
    } else {
        $customer_id = addCustomer($name, $inn, $addres, $phone);
        if (!$customer_id) {
            $error = 'Ошибка при создании заказчика.';
        } else {
            $result = addUser($login, $password, 'user', $customer_id);
            if ($result) {
                $success = 'Регистрация успешна! Теперь вы можете войти.';
                $_POST = [];
            } else {
                $error = 'Ошибка при создании учётной записи.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrapper" style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1rem;">
    <div class="form-compact">
        <h1>Регистрация</h1>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
            <p class="text-center"><a href="login.php">Перейти к входу</a></p>
        <?php else: ?>
            <form method="post">
                <label>ФИО или организация *</label>
                <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>

                <label>ИНН (необязательно)</label>
                <input type="text" name="inn" value="<?= htmlspecialchars($_POST['inn'] ?? '') ?>">

                <label>Адрес *</label>
                <input type="text" name="addres" value="<?= htmlspecialchars($_POST['addres'] ?? '') ?>" required>

                <label>Телефон *</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>

                <label>Логин *</label>
                <input type="text" name="login" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" required>

                <label>Пароль * (мин. 4 символа)</label>
                <input type="password" name="password" required>

                <label>Повторите пароль *</label>
                <input type="password" name="password_confirm" required>

                <button type="submit">Зарегистрироваться</button>
            </form>
            <p class="text-center">Уже есть аккаунт? <a href="login.php">Войти</a></p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>