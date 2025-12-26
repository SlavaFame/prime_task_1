<?php
// Простой тест модуля
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

echo '<h1>Простой тест модуля company.catalogrest</h1>';

// 1. Проверка загрузки модуля
echo '<h2>1. Загрузка модуля</h2>';
if (CModule::IncludeModule('company.catalogrest')) {
    echo '✅ Модуль загружен<br>';
    
    // 2. Проверка классов
    echo '<h2>2. Проверка классов</h2>';
    $classes = [
        'Company\CatalogRest\Controller\CategoryController',
        'Company\CatalogRest\Controller\CatalogController', 
        'Company\CatalogRest\General\ExcelExporter'
    ];
    
    foreach ($classes as $class) {
        if (class_exists($class)) {
            echo "✅ {$class} найден<br>";
        } else {
            echo "❌ {$class} не найден<br>";
        }
    }
    
    // 3. Проверка инфоблока
    echo '<h2>3. Проверка инфоблока "Одежда"</h2>';
    if (CModule::IncludeModule('iblock')) {
        $iblock = CIBlock::GetList([], ['CODE' => 'clothes'])->Fetch();
        if ($iblock) {
            echo '✅ Инфоблок найден: ID=' . $iblock['ID'] . '<br>';
            
            // Тестируем контроллер
            echo '<h2>4. Тест контроллера категорий</h2>';
            try {
                $controller = new Company\CatalogRest\Controller\CategoryController();
                $result = $controller->listAction();
                echo '✅ Контроллер работает<br>';
                
                // Можно посмотреть результат
                echo '<button onclick="toggleJson()">Показать результат</button>';
                echo '<div id="jsonResult" style="display:none; background:#f5f5f5; padding:10px; margin:10px 0;">';
                echo '<pre>' . json_encode(json_decode($result->getContent()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                echo '</div>';
                
            } catch (Exception $e) {
                echo '❌ Ошибка контроллера: ' . $e->getMessage() . '<br>';
            }
        } else {
            echo '❌ Инфоблок "clothes" не найден!<br>';
            echo 'Создайте инфоблок с кодом "clothes" в типе "catalog"<br>';
        }
    } else {
        echo '❌ Модуль iblock не загружен<br>';
    }
    
    // 4. Тест ExcelExporter
    echo '<h2>5. Тест ExcelExporter</h2>';
    try {
        $exporter = new Company\CatalogRest\General\ExcelExporter();
        echo '✅ ExcelExporter создан<br>';
        
        // Тестируем приватный метод через рефлексию
        $reflection = new ReflectionClass($exporter);
        $method = $reflection->getMethod('getProductsData');
        $method->setAccessible(true);
        
        $products = $method->invoke($exporter);
        echo '✅ Данные товаров получены: ' . count($products) . ' записей<br>';
        
    } catch (Exception $e) {
        echo '❌ Ошибка ExcelExporter: ' . $e->getMessage() . '<br>';
    }
    
} else {
    echo '❌ Модуль не загружен<br>';
    echo 'Проверьте:<br>';
    echo '1. Модуль установлен в админке<br>';
    echo '2. Файл include.php существует<br>';
}

// 5. Тест API через file_get_contents
echo '<h2>6. Тест API напрямую</h2>';
$testUrls = [
    '/api/v1/catalog/categories',
    '/index.php?route=/api/v1/catalog/categories' // альтернативный путь
];

foreach ($testUrls as $url) {
    $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
               . "://$_SERVER[HTTP_HOST]$url";
    
    echo "Тестируем: <a href='$fullUrl' target='_blank'>$url</a><br>";
    
    $context = stream_context_create([
        'http' => ['timeout' => 3],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ]);
    
    $response = @file_get_contents($fullUrl, false, $context);
    
    if ($response === FALSE) {
        echo "❌ Нет ответа (ошибка: " . error_get_last()['message'] . ")<br>";
    } else {
        echo "✅ Ответ получен (" . strlen($response) . " байт)<br>";
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✅ JSON валиден<br>";
            if (isset($data['success'])) {
                echo "✅ success = " . ($data['success'] ? 'true' : 'false') . "<br>";
            }
        } else {
            echo "❌ Невалидный JSON: " . json_last_error_msg() . "<br>";
            echo "Ответ: <pre>" . htmlspecialchars($response) . "</pre>";
        }
    }
    echo '<hr>';
}

echo '<h2>7. Диагностика</h2>';
echo '<ul>';
echo '<li><a href="/bitrix/admin/module_admin.php" target="_blank">Проверить установку модуля</a></li>';
echo '<li><a href="/bitrix/admin/iblock_admin.php" target="_blank">Проверить инфоблоки</a></li>';
echo '<li><a href="test_api.php?action=categories" target="_blank">Тест API через скрипт</a></li>';
echo '</ul>';

?>
<script>
function toggleJson() {
    var div = document.getElementById('jsonResult');
    div.style.display = div.style.display === 'none' ? 'block' : 'none';
}
</script>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
?>