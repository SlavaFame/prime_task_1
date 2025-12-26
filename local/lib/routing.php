<?php

use Bitrix\Main\Routing\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    // API для категорий
    $routes->prefix('api/v1')->group(function (RoutingConfigurator $routes) {
        // Получение списка категорий
        $routes->get('catalog/categories', [
            'module' => 'company.catalogrest',
            'controller' => 'CategoryController',
            'action' => 'list'
        ])->name('api.catalog.categories.list');

        // Получение товаров в категории
        $routes->get('catalog/categories/{id}/products', [
            'module' => 'company.catalogrest',
            'controller' => 'CatalogController',
            'action' => 'categoryProducts'
        ])->where('id', '[0-9]+')
          ->name('api.catalog.category.products');

        // Детальная информация о товаре
        $routes->get('catalog/products/{id}', [
            'module' => 'company.catalogrest',
            'controller' => 'CatalogController',
            'action' => 'productDetail'
        ])->where('id', '[0-9]+')
          ->name('api.catalog.product.detail');
    });
};