<?php
require_once 'config.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function getUserByLogin($login) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function updateFailedAttempts($login, $isSuccess) {
    global $db;
    if ($isSuccess) {
        $stmt = $db->prepare("UPDATE users SET failed_attempts = 0 WHERE login = ?");
        $stmt->bind_param("s", $login);
        $stmt->execute();
    } else {
        $stmt = $db->prepare("UPDATE users SET failed_attempts = failed_attempts + 1 WHERE login = ?");
        $stmt->bind_param("s", $login);
        $stmt->execute();

        $stmt2 = $db->prepare("UPDATE users SET is_blocked = 1 WHERE login = ? AND failed_attempts >= 3");
        $stmt2->bind_param("s", $login);
        $stmt2->execute();
    }
}

function blockUserById($id) {
    global $db;
    $stmt = $db->prepare("UPDATE users SET is_blocked = 1, failed_attempts = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

function unblockUserById($id) {
    global $db;
    $stmt = $db->prepare("UPDATE users SET is_blocked = 0, failed_attempts = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

function addUser($login, $password, $role, $customer_id = null) {
    global $db;
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (login, password, role, customer_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $login, $hash, $role, $customer_id);
    return $stmt->execute();
}

function updateUser($id, $password, $role, $unblock = false, $customer_id = null) {
    global $db;
    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ?, role = ?, customer_id = ? WHERE id = ?");
        $stmt->bind_param("ssii", $hash, $role, $customer_id, $id);
        $stmt->execute();
    } else {
        $stmt = $db->prepare("UPDATE users SET role = ?, customer_id = ? WHERE id = ?");
        $stmt->bind_param("sii", $role, $customer_id, $id);
        $stmt->execute();
    }
    if ($unblock) unblockUserById($id);
}

function deleteUser($id) {
    global $db;
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

function getAllUsers() {
    global $db;
    $result = $db->query("SELECT id, login, role, customer_id, is_blocked, failed_attempts FROM users");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getAllCustomers() {
    global $db;
    $result = $db->query("SELECT id, name FROM Customers ORDER BY name");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getOrdersByCustomerId($customer_id) {
    global $db;
    $stmt = $db->prepare("
        SELECT orders.number_of_order, orders.date, 
               SUM(order_line.count * order_line.price_of_unit) AS total_sum
        FROM orders
        JOIN order_line ON orders.id = order_line.id_order
        WHERE orders.id_customer = ?
        GROUP BY orders.id, orders.number_of_order, orders.date
        ORDER BY orders.date DESC
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getAllOrders() {
    global $db;
    $result = $db->query("
        SELECT orders.number_of_order, orders.date, 
               SUM(order_line.count * order_line.price_of_unit) AS total_sum
        FROM orders
        JOIN order_line ON orders.id = order_line.id_order
        GROUP BY orders.id
        ORDER BY orders.date DESC
    ");
    return $result->fetch_all(MYSQLI_ASSOC);
}


// Добавить нового заказчика (таблица Customers)
function addCustomer($name, $inn, $addres, $phone) {
    global $db;
    $stmt = $db->prepare("INSERT INTO Customers (name, inn, addres, phone) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $inn, $addres, $phone);
    $stmt->execute();
    return $stmt->insert_id; // возвращает id созданной записи
}



/**
 * Возвращает для заказчика список его заказов с расчётом полной себестоимости
 * (учитывается количество продукции и стоимость всех материалов по нормам расхода)
 *
 * @param int $customer_id
 * @return array
 */

/**
 * Возвращает для заказчика список его заказов с расчётом полной себестоимости
 * (количество продукции * сумма расходов материалов по актуальным ценам на дату заказа)
 *
 * @param int $customer_id
 * @return array
 */
function getOrderCostByCustomerId($customer_id) {
    global $db;
    
    $sql = "
        SELECT 
            o.number_of_order,
            o.date,
            SUM(ol.count * COALESCE((
                SELECT SUM(s.count * p.price)
                FROM specifications s
                JOIN price p ON p.id_material = s.id_material
                WHERE s.id_goods = ol.id_goods
                  AND p.start_date <= o.date
                  AND (p.end_date IS NULL OR p.end_date >= o.date)
            ), 0)) AS total_cost
        FROM orders o
        JOIN order_line ol ON o.id = ol.id_order
        WHERE o.id_customer = ?
        GROUP BY o.id, o.number_of_order, o.date
        ORDER BY o.date DESC
    ";
    
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return ['error' => 'Ошибка подготовки запроса: ' . $db->error];
    }
    
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    return $orders;
}
?>