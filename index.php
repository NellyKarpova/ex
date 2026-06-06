<?php
require_once 'functions.php';

$successMessage = '';
if (isset($_SESSION['flash_success'])) {
    $successMessage = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['role'];
$login = $_SESSION['login'];
$customer_id = $_SESSION['customer_id'] ?? null;

$orders = [];
if ($role === 'user' && $customer_id) {
    $orders = getOrdersByCustomerId($customer_id);
} elseif ($role === 'admin') {
    $orders = getAllOrders();
}

$pageTitle = 'Главная';
include 'header.php';
?>

<!-- Основное содержимое страницы -->
<div class="main-content">
    <h1>Добро пожаловать, <?= htmlspecialchars($login) ?></h1>

    <?php if ($successMessage): ?>
    <div class="success"><?= htmlspecialchars($successMessage) ?></div>
<?php endif; ?>

    <?php if (isAdmin()): ?>
        <p><a href="admin.php">Управление пользователями</a> | <a href="cost.php">Расчёт себестоимости заказов</a></p>
    <?php endif; ?>

    <h2>Список заказов</h2>
    <?php if (empty($orders)): ?>
        <p>Нет доступных заказов.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>№ заказа</th><th>Дата</th><th>Сумма (руб.)</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['number_of_order']) ?></td>
                        <td><?= htmlspecialchars($order['date']) ?></td>
                        <td><?= number_format($order['total_sum'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; // отдельный файл подвала ?>