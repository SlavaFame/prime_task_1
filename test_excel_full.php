<?php
// Полный тест Excel выгрузки
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

echo '<h1>🧪 Тестирование Excel выгрузки</h1>';

// ЯВНО подключаем модули перед тестами
if (!CModule::IncludeModule('iblock')) {
    echo '❌ Модуль IBlock не загружен<br>';
    die();
}

if (!CModule::IncludeModule('company.catalogrest')) {
    echo '❌ Модуль не загружен<br>';
    die();
}

// Проверяем наличие необходимых библиотек
echo '<h2>1. Проверка зависимостей</h2>';

// Проверка PhpSpreadsheet
if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    echo '✅ PhpSpreadsheet установлен<br>';
} else {
    echo '❌ PhpSpreadsheet НЕ установлен!<br>';
    echo 'Установите: <code>composer require phpoffice/phpspreadsheet</code><br>';
    
    // Пробуем загрузить вручную
    $vendorAutoload = $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
    if (file_exists($vendorAutoload)) {
        require_once $vendorAutoload;
        echo '✅ Автозагрузчик vendor загружен<br>';
    } else {
        echo '⚠️ Автозагрузчик vendor не найден<br>';
    }
}

// Проверяем класс ExcelExporter
echo '<h2>2. Проверка класса ExcelExporter</h2>';
$exporterClass = 'Company\CatalogRest\General\ExcelExporter';

if (!class_exists($exporterClass)) {
    // Пробуем загрузить вручную
    $exporterFile = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/company.catalogrest/classes/General/ExcelExporter.php';
    if (file_exists($exporterFile)) {
        require_once $exporterFile;
        echo '✅ Файл загружен вручную<br>';
    } else {
        echo '❌ Файл не найден: ' . $exporterFile . '<br>';
        die();
    }
}

if (class_exists($exporterClass)) {
    echo '✅ Класс ExcelExporter найден<br>';
    
    try {
        $exporter = new $exporterClass();
        echo '✅ Объект создан<br>';
        
        // Тестируем методы через рефлексию
        $reflection = new ReflectionClass($exporter);
        
        echo '<h2>3. Тест приватных методов</h2>';
        
        // 3.1. Тест getClothesIblockId()
        echo '<h3>3.1. Метод getClothesIblockId()</h3>';
        $method = $reflection->getMethod('getClothesIblockId');
        $method->setAccessible(true);
        $iblockId = $method->invoke($exporter);
        
        if ($iblockId) {
            echo "✅ Инфоблок найден: ID = {$iblockId}<br>";
            
            // Проверяем реальность инфоблока
            if (CModule::IncludeModule('iblock')) {
                $iblock = CIBlock::GetByID($iblockId)->Fetch();
                if ($iblock) {
                    echo "✅ Инфоблок существует: {$iblock['NAME']} ({$iblock['CODE']})<br>";
                }
            }
        } else {
            echo '❌ Инфоблок "Одежда" не найден!<br>';
            echo 'Создайте инфоблок с кодом "clothes"<br>';
        }
        
        // 3.2. Тест getProductsData()
        echo '<h3>3.2. Метод getProductsData()</h3>';
        if ($iblockId) {
            $method = $reflection->getMethod('getProductsData');
            $method->setAccessible(true);
            
            $products = $method->invoke($exporter);
            
            echo '✅ Товаров получено: ' . count($products) . '<br>';
            
            if (count($products) > 0) {
                echo '<h4>Первые 3 товара:</h4>';
                echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
                echo '<tr style="background: #f0f0f0;">';
                echo '<th>ID</th><th>Название</th><th>Категория</th><th>Цена</th><th>Предложений</th><th>Ссылка</th>';
                echo '</tr>';
                
                foreach (array_slice($products, 0, 3) as $product) {
                    echo '<tr>';
                    echo '<td>' . ($product['ID'] ?? '') . '</td>';
                    echo '<td>' . htmlspecialchars($product['NAME'] ?? '') . '</td>';
                    echo '<td>' . htmlspecialchars($product['CATEGORY_PATH'] ?? '') . '</td>';
                    echo '<td>' . ($product['MIN_PRICE'] ?? '0') . '</td>';
                    echo '<td>' . ($product['OFFERS_COUNT'] ?? '0') . '</td>';
                    echo '<td>' . htmlspecialchars(substr($product['DETAIL_PAGE'] ?? '', 0, 50)) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                if (count($products) > 3) {
                    echo '... и еще ' . (count($products) - 3) . ' товаров<br>';
                }
                
                // Проверяем сортировку
                echo '<h4>Проверка сортировки:</h4>';
                $categories = array_column($products, 'CATEGORY_PATH');
                $sortedCategories = $categories;
                sort($sortedCategories);
                
                if ($categories === $sortedCategories) {
                    echo '✅ Сортировка по категории правильная<br>';
                } else {
                    echo '⚠️ Сортировка по категории может быть неправильной<br>';
                }
                
                // Проверяем форматы данных
                echo '<h4>Проверка форматов данных:</h4>';
                $sample = $products[0];
                $checks = [
                    'ID существует' => isset($sample['ID']),
                    'Название существует' => isset($sample['NAME']) && !empty($sample['NAME']),
                    'Категория существует' => isset($sample['CATEGORY_PATH']),
                    'Ссылка существует' => isset($sample['DETAIL_PAGE']),
                    'Цена числовая' => isset($sample['MIN_PRICE']) && is_numeric($sample['MIN_PRICE']),
                    'Кол-во предложений числовое' => isset($sample['OFFERS_COUNT']) && is_numeric($sample['OFFERS_COUNT']),
                ];
                
                foreach ($checks as $check => $result) {
                    echo ($result ? '✅ ' : '❌ ') . $check . '<br>';
                }
            } else {
                echo '⚠️ Нет товаров для выгрузки<br>';
            }
        }
        
        // 3.3. Тест создания Excel файла
        echo '<h3>3.3. Тест создания Excel файла (без отправки email)</h3>';
        
        if (count($products) > 0) {
            try {
                // Создаем временный файл
                $tempFile = tempnam(sys_get_temp_dir(), 'excel_test_') . '.xlsx';
                
                // Используем рефлексию для вызова createExcelFile
                $method = $reflection->getMethod('createExcelFile');
                $method->setAccessible(true);
                
                $filePath = $method->invoke($exporter);
                
                if (file_exists($filePath)) {
                    echo '✅ Excel файл создан: ' . $filePath . '<br>';
                    echo 'Размер: ' . filesize($filePath) . ' байт<br>';
                    
                    // Проверяем, что это валидный Excel файл
                    if (filesize($filePath) > 1000) {
                        echo '✅ Файл имеет нормальный размер<br>';
                        
                        // Можно попробовать прочитать
                        if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                            try {
                                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                                $sheet = $spreadsheet->getActiveSheet();
                                
                                echo '✅ Файл успешно загружен PhpSpreadsheet<br>';
                                echo 'Лист: ' . $sheet->getTitle() . '<br>';
                                echo 'Колонок: ' . $sheet->getHighestColumn() . '<br>';
                                echo 'Строк: ' . $sheet->getHighestRow() . '<br>';
                                
                                // Проверяем заголовки
                                $expectedHeaders = ['ID', 'Наименование', 'Категория', 'Ссылка', 'Кол-во предложений', 'Минимальная цена'];
                                $actualHeaders = [];
                                for ($col = 'A'; $col <= 'F'; $col++) {
                                    $actualHeaders[] = $sheet->getCell($col . '1')->getValue();
                                }
                                
                                if ($actualHeaders === $expectedHeaders) {
                                    echo '✅ Заголовки правильные<br>';
                                } else {
                                    echo '❌ Заголовки не совпадают:<br>';
                                    echo 'Ожидалось: ' . implode(', ', $expectedHeaders) . '<br>';
                                    echo 'Получено: ' . implode(', ', $actualHeaders) . '<br>';
                                }
                                
                                // Проверяем стили
                                $headerStyle = $sheet->getStyle('A1:F1');
                                if ($headerStyle->getFont()->getBold()) {
                                    echo '✅ Заголовки жирные ✓<br>';
                                } else {
                                    echo '❌ Заголовки не жирные<br>';
                                }
                                
                                // Проверяем рамки
                                $borders = $sheet->getStyle('A1:F' . $sheet->getHighestRow())->getBorders();
                                $hasBorders = $borders->getAllBorders()->getBorderStyle() !== \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE;
                                
                                if ($hasBorders) {
                                    echo '✅ Рамки есть ✓<br>';
                                } else {
                                    echo '❌ Рамок нет<br>';
                                }
                                
                                // Проверяем гиперссылки
                                $hasHyperlinks = false;
                                for ($row = 2; $row <= min(5, $sheet->getHighestRow()); $row++) {
                                    $cell = $sheet->getCell('D' . $row);
                                    if ($cell->hasHyperlink()) {
                                        $hasHyperlinks = true;
                                        break;
                                    }
                                }
                                
                                if ($hasHyperlinks) {
                                    echo '✅ Гиперссылки есть ✓<br>';
                                } else {
                                    echo '⚠️ Гиперссылок нет (возможно товары без ссылок)<br>';
                                }
                                
                                // Показываем пример данных
                                echo '<h4>Пример данных из Excel:</h4>';
                                echo '<table border="1" cellpadding="5">';
                                echo '<tr>';
                                foreach ($actualHeaders as $header) {
                                    echo '<th>' . htmlspecialchars($header) . '</th>';
                                }
                                echo '</tr>';
                                
                                for ($row = 2; $row <= min(4, $sheet->getHighestRow()); $row++) {
                                    echo '<tr>';
                                    for ($col = 'A'; $col <= 'F'; $col++) {
                                        $value = $sheet->getCell($col . $row)->getValue();
                                        echo '<td>' . htmlspecialchars($value) . '</td>';
                                    }
                                    echo '</tr>';
                                }
                                echo '</table>';
                                
                            } catch (Exception $e) {
                                echo '❌ Ошибка чтения Excel: ' . $e->getMessage() . '<br>';
                            }
                        }
                        
                        // Предлагаем скачать файл
                        echo '<h4>Скачать тестовый файл:</h4>';
                        echo '<a href="/download_excel.php?file=' . urlencode(basename($filePath)) . '" target="_blank" class="adm-btn">📥 Скачать Excel</a>';
                        
                        // Удаляем временный файл после теста
                        register_shutdown_function(function() use ($filePath) {
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        });
                        
                    } else {
                        echo '❌ Файл слишком маленький<br>';
                    }
                } else {
                    echo '❌ Файл не создан<br>';
                }
                
            } catch (Exception $e) {
                echo '❌ Ошибка создания Excel: ' . $e->getMessage() . '<br>';
                echo '<pre>' . $e->getTraceAsString() . '</pre>';
            }
        }
        
        // 3.4. Тест полной выгрузки с email (опционально)
        echo '<h3>3.4. Тест полной выгрузки (требует email)</h3>';
        
        if (isset($_GET['test_email']) && filter_var($_GET['test_email'], FILTER_VALIDATE_EMAIL)) {
            $testEmail = $_GET['test_email'];
            echo '<p>Тестируем отправку на: ' . htmlspecialchars($testEmail) . '</p>';
            
            try {
                $result = $exporter->exportToEmail($testEmail);
                
                if ($result) {
                    echo '✅ Выгрузка отправлена успешно<br>';
                    echo 'Проверьте почту на ' . htmlspecialchars($testEmail) . '<br>';
                } else {
                    echo '❌ Ошибка при отправке<br>';
                }
            } catch (Exception $e) {
                echo '❌ Исключение: ' . $e->getMessage() . '<br>';
            }
        } else {
            echo '<form method="get" style="margin: 10px 0; padding: 10px; background: #f0f0f0; border-radius: 5px;">';
            echo '<label>Тест отправки на email: </label>';
            echo '<input type="email" name="test_email" placeholder="your@email.com" style="padding: 5px; margin: 0 10px;">';
            echo '<input type="submit" value="Тестировать отправку" class="adm-btn">';
            echo '</form>';
        }
        
    } catch (Exception $e) {
        echo '❌ Ошибка при создании объекта: ' . $e->getMessage() . '<br>';
    }
} else {
    echo '❌ Класс не найден<br>';
}

// 4. Проверка консольной команды
echo '<h2>4. Проверка консольной команды</h2>';

$consoleFile = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/company.catalogrest/lib/console/ExportProductsCommand.php';
if (file_exists($consoleFile)) {
    echo '✅ Файл консольной команды существует<br>';
    
    // Проверяем содержимое
    $content = file_get_contents($consoleFile);
    if (strpos($content, 'catalog:export-products') !== false) {
        echo '✅ Команда catalog:export-products найдена<br>';
        echo '<code>php bitrix/console.php catalog:export-products email@example.com</code><br>';
    }
} else {
    echo '❌ Файл консольной команды не найден<br>';
    echo 'Создайте: /local/modules/company.catalogrest/lib/console/ExportProductsCommand.php<br>';
}

// 5. Итог
echo '<h2>🎯 Итог тестирования Excel выгрузки</h2>';
echo '<ul>';
echo '<li>✅ ExcelExporter класс: ' . (class_exists($exporterClass) ? 'Работает' : 'Не работает') . '</li>';
echo '<li>✅ PhpSpreadsheet: ' . (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet') ? 'Установлен' : 'Не установлен') . '</li>';
echo '<li>✅ Товары для выгрузки: ' . (isset($products) ? count($products) : 'Неизвестно') . '</li>';
echo '<li>✅ Создание файла: ' . (isset($filePath) && file_exists($filePath) ? 'Успешно' : 'Не тестировалось') . '</li>';
echo '</ul>';

echo '<h3>Следующие шаги:</h3>';
echo '<ol>';
echo '<li>Если PhpSpreadsheet не установлен: <code>composer require phpoffice/phpspreadsheet</code></li>';
echo '<li>Проверить почтовые события в админке</li>';
echo '<li>Протестировать через админку: <a href="/bitrix/admin/company_catalogrest_export.php" target="_blank">Выгрузка товаров</a></li>';
echo '</ol>';

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
?>