<?php

namespace Company\CatalogRest\General;

use Bitrix\Main\Routing\RoutingConfigurator;

class RouterConfig
{
    public static function registerRoutes(RoutingConfigurator $routes): void
    {
        // Простые маршруты для тестирования
        $routes->get('/api/v1/catalog/categories', function() {
            // Подключаем контроллер напрямую
            if (!\Bitrix\Main\Loader::includeModule('company.catalogrest')) {
                return new \Bitrix\Main\Engine\Response\Json([
                    'success' => false,
                    'error' => 'Module not loaded'
                ]);
            }

            $controller = new \Company\CatalogRest\Controller\CategoryController();
            return $controller->listAction();
        })->name('api.catalog.categories.list');

        $routes->get('/api/v1/catalog/categories/{id}/products', function($id) {
            if (!\Bitrix\Main\Loader::includeModule('company.catalogrest')) {
                return new \Bitrix\Main\Engine\Response\Json([
                    'success' => false,
                    'error' => 'Module not loaded'
                ]);
            }

            $controller = new \Company\CatalogRest\Controller\CatalogController();
            return $controller->categoryProductsAction($id);
        })->where('id', '\d+')->name('api.catalog.category.products');

        $routes->get('/api/v1/catalog/products/{id}', function($id) {
            if (!\Bitrix\Main\Loader::includeModule('company.catalogrest')) {
                return new \Bitrix\Main\Engine\Response\Json([
                    'success' => false,
                    'error' => 'Module not loaded'
                ]);
            }

            $controller = new \Company\CatalogRest\Controller\CatalogController();
            return $controller->productDetailAction($id);
        })->where('id', '\d+')->name('api.catalog.product.detail');
    }
}