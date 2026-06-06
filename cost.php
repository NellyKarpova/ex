<?php
require_once 'functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: index.php');
    exit;
}

$costResult = null;
$selectedCustomerId = null;
$error = '';
$customersList = [];
$selectedCustomerName = '';

$allUsers = getAllUsers();
$customers = getAllCustomers();

foreach ($allUsers as $u) {
    if ($u['role'] === 'user' && !empty($u['customer_id'])) {
        $custName = '';
        foreach ($customers as $c) {
            if ($c['id'] == $u['customer_id']) {
                $custName = $c['name'];
                break;
            }
        }
        $customersList[] = [
            'customer_id' => $u['customer_id'],
            'login'       => $u['login'],
            'customer_name' => $custName
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_id'])) {
    $selectedCustomerId = (int)$_POST['customer_id'];
    $costResult = getOrderCostByCustomerId($selectedCustomerId);
    
    if (isset($costResult['error'])) {
        $error = $costResult['error'];
        $costResult = null;
    } else {
        foreach ($customersList as $c) {
            if ($c['customer_id'] == $selectedCustomerId) {
                $selectedCustomerName = $c['customer_name'];
                break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Расчёт себестоимости заказов</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
<div class="container">
    <h1>Расчёт себестоимости заказов (по материалам)</h1>
    <p><a href="admin.php">← Назад в админ-панель</a></p>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($customersList)): ?>
        <div class="error">Нет пользователей с привязкой к заказчику.</div>
    <?php else: ?>
        <form method="post">
            <label>Выберите заказчика (пользователя):</label>
            <select name="customer_id" required>
                <option value="">-- выберите --</option>
                <?php foreach ($customersList as $c): ?>
                    <option value="<?= $c['customer_id'] ?>" <?= ($selectedCustomerId == $c['customer_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['login']) ?> (<?= htmlspecialchars($c['customer_name']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Рассчитать себестоимость</button>
        </form>
    <?php endif; ?>

    <?php if ($costResult !== null && !isset($costResult['error'])): ?>
        <?php if (!empty($costResult)): ?>
            <button id="savePdfBtn" class="pdf-button">💾 Сохранить PDF на сервере</button>
            <div id="pdfExportContent" style="display: none;">
                <div class="pdf-content">
                    <h1>Отчёт о себестоимости заказов</h1>
                    <div class="date">Дата формирования: <?= date('d.m.Y H:i:s') ?></div>
                    <p><strong>Заказчик:</strong> <?= htmlspecialchars($selectedCustomerName) ?> (ID: <?= $selectedCustomerId ?>)</p>
                    <table>
                        <thead>
                            <tr><th>Номер заказа</th><th>Дата</th><th>Себестоимость, руб.</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalAll = 0;
                            foreach ($costResult as $order): 
                                $cost = (float)$order['total_cost'];
                                $totalAll += $cost;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($order['number_of_order']) ?></td>
                                <td><?= date('d.m.Y', strtotime($order['date'])) ?></td>
                                <td style="text-align: right;"><?= number_format($cost, 2, '.', ' ') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td colspan="2" style="text-align: right;"><strong>ИТОГО себестоимость:</strong></td>
                                <td style="text-align: right;"><strong><?= number_format($totalAll, 2, '.', ' ') ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="footer">
                        * Расчёт выполнен на основе норм расхода материалов и их актуальных цен на дату заказа.<br>
                        ** Отчёт сгенерирован автоматически.
                    </div>
                </div>
            </div>

            <!-- Экранная таблица -->
            <h3>Результат расчёта</h3>
            <table border="1" cellpadding="8" style="border-collapse: collapse; width: 100%;">
                <thead style="background: #f0f0f0;">
                    <tr><th>Номер заказа</th><th>Дата</th><th>Полная себестоимость (руб.)</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($costResult as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['number_of_order']) ?></td>
                            <td><?= htmlspecialchars($order['date']) ?></td>
                            <td><?= number_format($order['total_cost'], 2, '.', ' ') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div id="saveResult" style="margin-top: 15px;"></div>

            <script>
                document.getElementById('savePdfBtn').addEventListener('click', function() {
                    const element = document.getElementById('pdfExportContent');
                    const btn = this;
                    const resultDiv = document.getElementById('saveResult');
                    
                    btn.disabled = true;
                    resultDiv.innerHTML = '<p>⏳ Генерация PDF и отправка на сервер...</p>';
                    
                    // Показываем блок для экспорта
                    element.style.display = 'block';
                    
                    const opt = {
                        margin: [0.5, 0.5, 0.5, 0.5],
                        filename: 'temp.pdf', // временное имя
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: { scale: 2, useCORS: true },
                        jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
                    };
                    
                    html2pdf().set(opt).from(element).outputPdf('blob').then(function(pdfBlob) {
                        // Скрываем блок обратно
                        element.style.display = 'none';
                        
                        // Отправляем Blob на сервер
                        const formData = new FormData();
                        formData.append('pdf_file', pdfBlob, 'sebestoimost_<?= $selectedCustomerId ?>.pdf');
                        
                        return fetch('save_pdf.php', {
                            method: 'POST',
                            body: formData
                        });
                    }).then(function(response) {
                        return response.json();
                    }).then(function(data) {
                        if (data.success) {
                            resultDiv.innerHTML = `<p style="color: green;">✅ PDF сохранён на сервере: <a href="${data.file}" target="_blank">${data.file}</a></p>`;
                        } else {
                            resultDiv.innerHTML = `<p style="color: red;">❌ Ошибка: ${data.error}</p>`;
                        }
                    }).catch(function(error) {
                        console.error(error);
                        resultDiv.innerHTML = '<p style="color: red;">❌ Ошибка при создании или отправке PDF</p>';
                        element.style.display = 'none';
                    }).finally(function() {
                        btn.disabled = false;
                    });
                });
            </script>
        <?php else: ?>
            <p>У выбранного заказчика нет заказов.</p>
        <?php endif; ?>
    <?php elseif ($costResult !== null && isset($costResult['error'])): ?>
        <div class="error"><?= htmlspecialchars($costResult['error']) ?></div>
    <?php endif; ?>
</div>
</body>
</html>