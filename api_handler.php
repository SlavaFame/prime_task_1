<?php
// Улучшенный обработчик API - без классов для простоты
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

header('Content-Type: application/json; charset=utf-8');

// Подключаем модуль
if (!CModule::IncludeModule('company.catalogrest')) {
    echo json_encode(['success' => false, 'error' => 'Модуль не загружен']);
    exit;
}

// Определяем путь
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$path = '';

// Обработка разных способов вызова
if (isset($_GET['route'])) {
    $path = $_GET['route'];
} elseif (strpos($requestUri, '/api/v1/') === 0) {
    $path = $requestUri;
} elseif (isset($_GET['action'])) {
    $path = getPathFromAction($_GET);
}

// Обработка тестового endpoint
if ($path === '/api/v1/test' || (isset($_GET['test']) && $_GET['test'] == '1')) {
    echo json_encode([
        'success' => true,
        'message' => 'API Handler работает!',
        'timestamp' => date('Y-m-d H:i:s'),
        'module' => 'company.catalogrest'
    ]);
    exit;
}

// Основные endpoints
if ($path === '/api/v1/catalog/categories') {
    handleCategories();

} elseif (preg_match('#^/api/v1/catalog/categories/(\d+)/products$#', $path, $matches)) {
    handleCategoryProducts($matches[1]);

} elseif (preg_match('#^/api/v1/catalog/products/(\d+)$#', $path, $matches)) {
    handleProductDetail($matches[1]);

} else {
    echo json_encode([
        'success' => false,
        'error' => 'Endpoint not found: ' . $path,
        'available_endpoints' => [
            '/api/v1/test',
            '/api/v1/catalog/categories',
            '/api/v1/catalog/categories/{id}/products',
            '/api/v1/catalog/products/{id}'
        ]
    ]);
}

/**
 * Обработка категорий
 */
function handleCategories() {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/company.catalogrest/classes/Controller/CategoryController.php';
    $controller = new Company\CatalogRest\Controller\CategoryController();
    echo $controller->listAction($_GET['parent_id'] ?? null)->getContent();
}

/**
 * Обработка товаров в категории
 */
function handleCategoryProducts($categoryId) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/company.catalogrest/classes/Controller/CatalogController.php';
    $controller = new Company\CatalogRest\Controller\CatalogController();
    echo $controller->categoryProductsAction(
        $categoryId,
        $_GET['page'] ?? 1,
        $_GET['limit'] ?? 50
    )->getContent();
}

/**
 * Обработка деталей товара
 */
function handleProductDetail($productId) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/company.catalogrest/classes/Controller/CatalogController.php';
    $controller = new Company\CatalogRest\Controller\CatalogController();
    echo $controller->productDetailAction($productId)->getContent();
}

/**
 * Преобразование параметра action в путь
 */
function getPathFromAction($params) {
    switch ($params['action']) {
        case 'categories':
            return '/api/v1/catalog/categories';
        case 'products':
            return '/api/v1/catalog/categories/' . ($params['category_id'] ?? '1') . '/products';
        case 'product':
            return '/api/v1/catalog/products/' . ($params['id'] ?? '1');
        default:
            return '';
    }
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
?>