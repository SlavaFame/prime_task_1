<?php
defined('B_PROLOG_INCLUDED') || die();

global $APPLICATION;

if ($APPLICATION->GetGroupRight('company.catalogrest') >= 'R') {
    return [
        'parent_menu' => 'global_menu_store',
        'sort' => 100,
        'text' => 'REST API Каталога',
        'title' => 'REST API Каталога',
        'icon' => 'catalog_menu_icon',
        'items_id' => 'menu_company_catalogrest',
        'items' => [
            [
                'text' => 'Выгрузка товаров',
                'title' => 'Выгрузка в Excel',
                'url' => 'company_catalogrest_export.php?lang=' . LANGUAGE_ID
            ],
            [
                'text' => 'Настройки',
                'title' => 'Настройки модуля',
                'url' => 'company_catalogrest_settings.php?lang=' . LANGUAGE_ID
            ]
        ]
    ];
}

return false;
?>