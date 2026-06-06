<?php
// Адрес запущенного Java-симулятора
define('SIMULATOR_URL', 'http://localhost:4444/TransferSimulator/');

$result = null;
$generatedValue = null;
$type = null;
$isValid = false;
$errorMessage = "";

// Список всех доступных тестов для удобного вывода в форму
$testTypes = [
    'fullName'     => ['label' => 'Проверка ФИО', 'param' => 'fullName', 'mock' => 'Тестовый+Запрос+Иванович'],
    'snils'        => ['label' => 'Проверка СНИЛС', 'param' => 'snils', 'mock' => '112-233-445+95'],
    'inn'          => ['label' => 'Проверка ИНН', 'param' => 'inn', 'mock' => '770101001'],
    'mobilePhone'  => ['label' => 'Проверка Телефона', 'param' => 'mobilePhone', 'mock' => '%2B79991112233'],
    'identityCard' => ['label' => 'Проверка Паспорта', 'param' => 'identityCard', 'mock' => '4508+123456'],
    'email'        => ['label' => 'Проверка Email', 'param' => 'email', 'mock' => 'test@example.com']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';

    if (array_key_exists($type, $testTypes)) {
        // 1. Строим URL динамически на основе выбранного типа
        $config = $testTypes[$type];
        $url = SIMULATOR_URL . $type . "?" . $config['param'] . "=" . $config['mock'];

        // 2. Делаем запрос к симулятору
        $response = @file_get_contents($url);

        if ($response !== false) {
            $data = json_decode($response, true);
            $generatedValue = $data['value'] ?? 'Неизвестно';

            // 3. Логика валидации (Регулярные выражения против "искажений" симулятора)
            switch ($type) {
                case 'fullName':
                    // Только русские буквы, пробелы и дефисы
                    $isValid = preg_match('/^[А-Яа-яЁё\s-]+$/u', $generatedValue);
                    $errorMessage = "ФИО содержит запрещенные символы (цифры, знаки '=', '+', '(' и т.д.).";
                    break;

                case 'snils':
                    // Формат СНИЛС: 112-233-445 95
                    $isValid = preg_match('/^\d{3}-\d{3}-\d{3}\s\d{2}$/', $generatedValue);
                    $errorMessage = "Формат СНИЛС нарушен или содержит недопустимые знаки на конце.";
                    break;

                case 'inn':
                    // ИНН должен состоять строго из 10 или 12 цифр
                    $isValid = preg_match('/^(\d{10}|\d{12})$/', $generatedValue);
                    $errorMessage = "ИНН должен содержать только 10 или 12 цифр без пробелов и спецсимволов.";
                    break;

                case 'mobilePhone':
                    // Телефон: должен начинаться с +7 или 8 и содержать 10 цифр (например +79991112233)
                    // Симулятор может подсунуть спецсимволы в середину или конец
                    $isValid = preg_match('/^(\+7|8)\d{10}$/', $generatedValue);
                    $errorMessage = "Неверный формат мобильного телефона. Ожидается +7XXXXXXXXXX без лишних знаков.";
                    break;

                case 'identityCard':
                    // Паспорт РФ: 4 цифры серии, пробел, 6 цифр номера (например 4508 123456)
                    $isValid = preg_match('/^\d{4}\s\d{6}$/', $generatedValue);
                    $errorMessage = "Формат паспорта нарушен. Должно быть: 4 цифры серии, пробел, 6 цифр номера.";
                    break;

                case 'email':
                    // Валидация Email стандартными средствами PHP
                    $isValid = filter_var($generatedValue, FILTER_VALIDATE_EMAIL) !== false;
                    $errorMessage = "Строка не является валидным email-адресом.";
                    break;
            }

            $result = true;
        } else {
            $result = false; // Симулятор недоступен
        }
    }
}


?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Комплексный Валидатор Данных</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card bg-secondary text-white shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h3 class="mb-0">Панель Валидации</h3>
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
                        <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">Сгенерировать и Проверить</button>
                    </form>

                    <hr class="my-4 border-secondary">

                    <?php if ($result === true): ?>
                        <div class="card bg-dark text-white mb-4 border-secondary">
                            <div class="card-body text-center">
                                <span class="text-muted d-block small mb-1">Значение, полученное от TransferSimulator:</span>
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
                                    <p class="mb-0 text-secondary-emphasis">Строка успешно прошла регулярное выражение и соответствует стандартам.</p>
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

                    <?php elseif ($result === false && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                        <div class="alert alert-warning text-center fw-bold py-3">
                            ⚠️ Внимание! Не удалось подключиться к TransferSimulator.<br>
                            <small class="fw-normal text-muted">Проверьте, запущена ли Java-программа в фоне на порту 4444.</small>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
            <div class="text-center mt-3 text-muted">
                <small>Работает в связке с Ktor API (порт 4444)</small>
            </div>
        </div>
    </div>
</div>

</body>
</html>