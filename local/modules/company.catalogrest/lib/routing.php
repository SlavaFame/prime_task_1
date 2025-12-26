<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;

// Автозагрузка классов
Loader::registerAutoLoadClasses('company.catalogrest', [
    'Company\CatalogRest\General\RouterConfig' => '/classes/General/RouterConfig.php',
    'Company\CatalogRest\Controller\CategoryController' => '/classes/Controller/CategoryController.php',
    'Company\CatalogRest\Controller\CatalogController' => '/classes/Controller/CatalogController.php',
    'Company\CatalogRest\General\ExcelExporter' => '/classes/General/ExcelExporter.php',
]);

// Регистрируем обработчик для роутинга
EventManager::getInstance()->addEventHandler(
    'main',
    'OnBeforeProlog',
    function() {
        // Регистрируем роутинг только если это не админка
        if (!defined('ADMIN_SECTION') || ADMIN_SECTION !== true) {
            \Bitrix\Main\Loader::includeModule('company.catalogrest');

            // Получаем роутер
            $router = \Bitrix\Main\Application::getInstance()->getRouter();

            // Регистрируем конфигурацию
            $router->addConfig([\Company\CatalogRest\General\RouterConfig::class, 'registerRoutes']);
        }
    }
);

// Альтернативный способ для консоли
if (php_sapi_name() === 'cli') {
    // Подключаем необходимые файлы для консоли
    $moduleDir = __DIR__;
    require_once $moduleDir . '/classes/General/ExcelExporter.php';
}



?>