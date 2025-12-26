<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

echo '<h1>Тест исправления роутинга</h1>';

// 1. Проверяем модуль
if (!CModule::IncludeModule('company.catalogrest')) {
    echo '❌ Модуль не загружен<br>';
    die();
}
echo '✅ Модуль загружен<br>';

// 2. Проверяем контроллеры
echo '<h2>2. Проверка контроллеров</h2>';
$controllers = [
    'Company\CatalogRest\Controller\CategoryController',
    'Company\CatalogRest\Controller\CatalogController'
];

foreach ($controllers as $controllerClass) {
    if (class_exists($controllerClass)) {
        echo "✅ {$controllerClass} найден<br>";
    } else {
        echo "❌ {$controllerClass} не найден<br>";
    }
}

// 3. Прямой тест контроллера
echo '<h2>3. Прямой тест CategoryController</h2>';
try {
    $controller = new Company\CatalogRest\Controller\CategoryController();
    $result = $controller->listAction();
    $json = $result->getContent();
    
    echo '✅ Контроллер работает<br>';
    echo 'Результат: <pre>' . htmlspecialchars($json) . '</pre><br>';
    
    // Проверяем JSON
    $data = json_decode($json, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo '✅ Валидный JSON<br>';
        echo 'success: ' . ($data['success'] ? 'true' : 'false') . '<br>';
        if (isset($data['data'])) {
            echo 'Элементов: ' . count($data['data']) . '<br>';
        }
    } else {
        echo '❌ Невалидный JSON: ' . json_last_error_msg() . '<br>';
    }
} catch (Exception $e) {
    echo '❌ Ошибка: ' . $e->getMessage() . '<br>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

// 4. Проверка обработчика событий
echo '<h2>4. Проверка обработчика событий</h2>';
$eventManager = \Bitrix\Main\EventManager::getInstance();
$handlers = $eventManager->findEventHandlers('main', 'OnPageStart');

echo 'Обработчиков OnPageStart: ' . count($handlers) . '<br>';
foreach ($handlers as $i => $handler) {
    echo "{$i}: модуль = {$handler['TO_MODULE_ID']}, класс = {$handler['TO_CLASS']}<br>";
}

// 5. Тест API URL
echo '<h2>5. Тестируем API URL</h2>';
$testUrls = [
    '/api/v1/test',
    '/api/v1/catalog/categories'
];

foreach ($testUrls as $url) {
    $fullUrl = 'http://' . $_SERVER['HTTP_HOST'] . $url;
    echo "<h3>Тестируем: <a href='{$fullUrl}' target='_blank'>{$url}</a></h3>";
    
    // Через file_get_contents
    $context = stream_context_create([
        'http' => ['timeout' => 3],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ]);
    
    $response = @file_get_contents($fullUrl, false, $context);
    
    if ($response === false) {
        echo '❌ Нет ответа<br>';
        echo 'Ошибка: ' . (error_get_last()['message'] ?? 'неизвестно') . '<br>';
    } else {
        echo '✅ Ответ получен (' . strlen($response) . ' байт)<br>';
        
        // Проверяем JSON
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo '✅ Валидный JSON<br>';
            echo '<pre>' . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
        } else {
            echo '❌ Не JSON: ' . json_last_error_msg() . '<br>';
            echo 'Первые 200 символов: <pre>' . htmlspecialchars(substr($response, 0, 200)) . '...</pre>';
        }
    }
    echo '<hr>';
}

// 6. Если API не работает, создаем простой обработчик
echo '<h2>6. Альтернативное решение</h2>';
echo '<p>Если роутинг не работает, создайте файл-обработчик:</p>';
echo '<code>/api/index.php</code> - см. инструкцию ниже';

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
?>