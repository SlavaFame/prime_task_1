<?php
$company_catalogrest_default_option = [
    // Основные настройки
    'export_limit' => 0, // без ограничений
    'default_export_email' => '',
    'auto_export' => 'N',
    'export_schedule' => 'daily',

    // Настройки Excel
    'excel_auto_width' => 'Y',
    'excel_add_filters' => 'Y',
    'excel_show_grid' => 'Y',
    'excel_header_color' => 'E6E6E6',

    // Настройки данных
    'include_inactive' => 'N',
    'export_only_available' => 'Y',
    'price_type' => 'BASE',

    // Настройки API
    'api_enabled' => 'Y',
    'api_cache_time' => 3600,
    'api_max_limit' => 100,

    // Настройки почты
    'email_subject' => 'Выгрузка товаров из каталога',
    'email_template' => 'CATALOG_EXPORT_TO_EXCEL',
    'email_cc' => '',

    // Настройки безопасности
    'log_export' => 'Y',
    'log_api_calls' => 'N',
    'enable_debug' => 'N',
];