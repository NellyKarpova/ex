<?php
define('SIMULATOR_URL', 'http://localhost:4444/TransferSimulator/');
define('LOG_FILE', 'validation_log.json');

$result = null;
$generatedValue = null;
$type = null;
$isValid = false;
$errorMessage = "";
$simulatorError = false;

$testTypes = [
    'fullName'     => ['label' => 'Проверка ФИО'],
    'snils'        => ['label' => 'Проверка СНИЛС'],
    'inn'          => ['label' => 'Проверка ИНН'],
    'mobilePhone'  => ['label' => 'Проверка Телефона'],
    'identityCard' => ['label' => 'Проверка Паспорта'],
    'email'        => ['label' => 'Проверка Email']
];

// Функция сохранения результата в JSON-лог
function logValidationResult($type, $receivedValue, $isValid, $errorMessage = '', $simulatorError = false) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => $type,
        'received_value' => $receivedValue,
        'is_valid' => $isValid,
        'error_message' => $errorMessage,
        'simulator_error' => $simulatorError
    ];

    $existing = [];
    if (file_exists(LOG_FILE)) {
        $json = file_get_contents(LOG_FILE);
        $existing = json_decode($json, true) ?? [];
    }
    $existing[] = $logEntry;
    // Ограничим историю 50 записями (опционально)
    if (count($existing) > 50) array_shift($existing);
    file_put_contents(LOG_FILE, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Функция получения последних записей для вывода
function getLastLogs($limit = 10) {
    if (!file_exists(LOG_FILE)) return [];
    $json = file_get_contents(LOG_FILE);
    $logs = json_decode($json, true) ?? [];
    return array_slice(array_reverse($logs), 0, $limit);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';

    if (array_key_exists($type, $testTypes)) {
        // Формируем URL без параметров – симулятор сам генерирует данные
        $url = SIMULATOR_URL . $type;

        $context = stream_context_create(['http' => ['timeout' => 5]]);
        $response = @file_get_contents($url, false, $context);

        if ($response !== false) {
            $data = json_decode($response, true);
            $generatedValue = $data['value'] ?? 'Неизвестно';
            $simulatorError = false;

            // Валидация полученного значения
            switch ($type) {
                case 'fullName':
                    $isValid = preg_match('/^[А-Яа-яЁё\s-]+$/u', $generatedValue);
                    $errorMessage = "ФИО содержит запрещенные символы (цифры, знаки '=', '+', '(' и т.д.).";
                    break;
                case 'snils':
                    $isValid = preg_match('/^\d{3}-\d{3}-\d{3}\s\d{2}$/', $generatedValue);
                    $errorMessage = "Формат СНИЛС нарушен или содержит недопустимые знаки на конце.";
                    break;
                case 'inn':
                    $isValid = preg_match('/^(\d{10}|\d{12})$/', $generatedValue);
                    $errorMessage = "ИНН должен содержать только 10 или 12 цифр без пробелов и спецсимволов.";
                    break;
                case 'mobilePhone':
                    $isValid = preg_match('/^(\+7|8)\d{10}$/', $generatedValue);
                    $errorMessage = "Неверный формат мобильного телефона. Ожидается +7XXXXXXXXXX без лишних знаков.";
                    break;
                case 'identityCard':
                    $isValid = preg_match('/^\d{4}\s\d{6}$/', $generatedValue);
                    $errorMessage = "Формат паспорта нарушен. Должно быть: 4 цифры серии, пробел, 6 цифр номера.";
                    break;
                case 'email':
                    $isValid = filter_var($generatedValue, FILTER_VALIDATE_EMAIL) !== false;
                    $errorMessage = "Строка не является валидным email-адресом.";
                    break;
            }

            // Сохраняем в JSON-лог
            logValidationResult($type, $generatedValue, $isValid, $isValid ? '' : $errorMessage, false);
            $result = true;
        } else {
            // Ошибка подключения к симулятору
            $simulatorError = true;
            logValidationResult($type, '', false, 'Симулятор недоступен', true);
            $result = false;
        }
    }
}

$lastLogs = getLastLogs();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Комплексный Валидатор Данных + JSON-логирование</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card bg-secondary text-white shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h3 class="mb-0">Валидатор данных (с сохранением в JSON)</h3>
                </div>
                <div class="card-body p-4">
                    
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-bold fs-5">Выберите тип данных для проверки:</label>
                            <select name="type" class="form-select form-select-lg bg-dark text-white border-secondary">
                                <?php foreach ($testTypes as $key => $values): ?>
                                    <option value="<?= $key ?>" <?= $type === $key ? 'selected' : '' ?>>
                                        <?= $values['label'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">Запросить данные и проверить</button>
                    </form>

                    <hr class="my-4 border-secondary">

                    <?php if ($result === true): ?>
                        <div class="card bg-dark text-white mb-4 border-secondary">
                            <div class="card-body text-center">
                                <span class="text-muted d-block small mb-1">Сгенерированное значение от TransferSimulator:</span>
                                <span class="fs-4 text-warning fw-mono bg-black px-3 py-1 rounded d-inline-block">
                                    <?= htmlspecialchars($generatedValue) ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($isValid): ?>
                            <div class="alert alert-success d-flex align-items-center py-3" role="alert">
                                <div class="fs-1 me-3">✔</div>
                                <div>
                                    <h4 class="alert-heading mb-1 fw-bold">Данные корректны!</h4>
                                    <p class="mb-0 text-secondary-emphasis">Строка успешно прошла валидацию.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger d-flex align-items-center py-3" role="alert">
                                <div class="fs-1 me-3">❌</div>
                                <div>
                                    <h4 class="alert-heading mb-1 fw-bold">Ошибка Валидации!</h4>
                                    <p class="mb-0 text-danger-emphasis"><?= $errorMessage ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php elseif ($result === false && $simulatorError): ?>
                        <div class="alert alert-warning text-center fw-bold py-3">
                            ⚠️ Внимание! Не удалось подключиться к TransferSimulator.<br>
                            <small class="fw-normal text-muted">Проверьте, запущена ли Java-программа на порту 4444.</small>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($lastLogs)): ?>
                        <hr class="my-4 border-secondary">
                        <h5 class="mb-3">📋 История последних проверок (JSON-лог)</h5>
                        <div class="table-responsive">
                            <table class="table table-dark table-bordered table-sm">
                                <thead>
                                    <tr><th>Время</th><th>Тип</th><th>Полученное значение</th><th>Статус</th></tr>
                                </thead>
                                <tbody>
                                <?php foreach ($lastLogs as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['timestamp']) ?></td>
                                        <td><?= htmlspecialchars($log['type']) ?></td>
                                        <td class="font-monospace small"><?= htmlspecialchars(mb_substr($log['received_value'], 0, 50)) ?></td>
                                        <td>
                                            <?php if ($log['simulator_error']): ?>
                                                <span class="badge bg-secondary">Симулятор недоступен</span>
                                            <?php elseif ($log['is_valid']): ?>
                                                <span class="badge bg-success">Валидно</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Невалидно</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="small text-muted">* Полный лог хранится в файле <code><?= LOG_FILE ?></code></div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>