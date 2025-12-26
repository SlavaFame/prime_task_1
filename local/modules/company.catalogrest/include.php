<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('company.catalogrest', [
    'Company\CatalogRest\Controller\CategoryController' => 'classes/Controller/CategoryController.php',
    'Company\CatalogRest\Controller\CatalogController' => 'classes/Controller/CatalogController.php',
    'Company\CatalogRest\General\ExcelExporter' => 'classes/General/ExcelExporter.php',
]);

if (php_sapi_name() === 'cli') {
    require_once __DIR__ . '/classes/General/ExcelExporter.php';
}
?>