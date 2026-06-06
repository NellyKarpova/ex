<?php
session_start();
require_once 'functions.php';

// Проверяем, что пользователь авторизован и админ
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещён']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешён']);
    exit;
}

if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Файл не получен или ошибка загрузки']);
    exit;
}

$uploadedFile = $_FILES['pdf_file'];
$originalName = pathinfo($uploadedFile['name'], PATHINFO_FILENAME);
$extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));

if ($extension !== 'pdf') {
    http_response_code(400);
    echo json_encode(['error' => 'Неверный тип файла. Ожидается PDF']);
    exit;
}

// Создаём папку reports, если её нет
$reportsDir = __DIR__ . '/reports';
if (!is_dir($reportsDir)) {
    mkdir($reportsDir, 0777, true);
}

$timestamp = date('Ymd_His');
$newFilename = $originalName . '_' . $timestamp . '.pdf';
$destination = $reportsDir . '/' . $newFilename;

// Перемещаем загруженный файл
if (move_uploaded_file($uploadedFile['tmp_name'], $destination)) {
    echo json_encode([
        'success' => true,
        'file' => 'reports/' . $newFilename,
        'message' => 'PDF сохранён на сервере'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Не удалось сохранить файл на сервере']);
}