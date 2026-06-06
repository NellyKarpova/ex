<?php
$pageTitle = $pageTitle ?? 'Система управления';
$currentFile = basename($_SERVER['PHP_SELF']);
$useMainWrapper = !in_array($currentFile, ['transfer_true.php']); // для валидации не оборачиваем в main
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="style.css">
    <?php if ($currentFile === 'login.php'): ?>
        <script src="captcha.js" defer></script>
    <?php endif; ?>
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <div class="logo">Система заказов</div>
        <nav class="header-nav">
            <?php if (isset($_SESSION['login']) && $currentFile !== 'login.php' && $currentFile !== 'register.php'): ?>
                <a href="index.php">Главная</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin.php">Администрирование</a>
                <?php endif; ?>
                <a href="logout.php">Выйти</a>
                <span class="user-name"><?= htmlspecialchars($_SESSION['login']) ?></span>
            <?php endif; ?>
        </nav>
        <!-- Кнопка возврата только для страницы валидации -->
        <?php if ($currentFile === 'transfer_true.php'): ?>
            <div class="back-button-block">
                <a href="admin.php" class="back-button-blue">← Вернуться в админ-панель</a>
            </div>
        <?php endif; ?>
    </div>
</header>

<?php if ($useMainWrapper): ?>
<main class="site-main">
<?php endif; ?>