<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

// Массив настроек для отображения
$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('COMPANY_CATALOGREST_OPTIONS_MAIN'),
        'TITLE' => Loc::getMessage('COMPANY_CATALOGREST_OPTIONS_MAIN_TITLE'),
        'OPTIONS' => [
            [
                'export_limit',
                Loc::getMessage('COMPANY_CATALOGREST_OPTION_EXPORT_LIMIT'),
                0,
                ['text', 10]
            ],
            [
                'default_export_email',
                Loc::getMessage('COMPANY_CATALOGREST_OPTION_DEFAULT_EMAIL'),
                '',
                ['text', 50]
            ],
            [
                'auto_export',
                Loc::getMessage('COMPANY_CATALOGREST_OPTION_AUTO_EXPORT'),
                'N',
                ['checkbox']
            ],
            [
                'include_inactive',
                Loc::getMessage('COMPANY_CATALOGREST_OPTION_INCLUDE_INACTIVE'),
                'N',
                ['checkbox']
            ],
        ]
    ],
    [
        'DIV' => 'edit2',
        'TAB' => Loc::getMessage('COMPANY_CATALOGREST_OPTIONS_EXCEL'),
        'TITLE' => Loc::getMessage('COMPANY_CATALOGREST_OPTIONS_EXCEL_TITLE'),
        'OPTIONS' => [
            [
                'excel_auto_width',
                Loc::getMessage('COMPANY_CATALOGREST_OPTION_EXCEL_AUTO_WIDTH'),
                'Y',
                ['checkbox']
            ],
            [
                'excel_add_filters',
                Loc::getMessage('COMPANY_CATALOGREST_OPTION_EXCEL_ADD_FILTERS'),
                'Y',
                ['checkbox']
            ],
            [
                'excel_show_grid',
                Loc::getMessage('COMPANY_CATALOGREST_OPTION_EXCEL_SHOW_GRID'),
                'Y',
                ['checkbox']
            ],
        ]
    ],
    [
        'DIV' => 'edit3',
        'TAB' => Loc::getMessage('COMPANY_CATALOGREST_OPTIONS_API'),
        'TITLE' => Loc::getMessage('COMPANY_CATALOGREST_OPTIONS_API_TITLE'),
        'OPTIONS' => [
            [
                'api_enabled',
                Loc::getMessage('COMPANY_CATALOGREST_OPTION_API_ENABLED'),
                'Y',
                ['checkbox']
            ],
            [
                'api_cache_time',
                Loc::getMessage('COMPANY_CATALOGREST_OPTION_API_CACHE_TIME'),
                3600,
                ['text', 10]
            ],
            [
                'api_max_limit',
                Loc::getMessage('COMPANY_CATALOGREST_OPTION_API_MAX_LIMIT'),
                100,
                ['text', 10]
            ],
        ]
    ],
];

// Обработка сохранения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['Update'] && check_bitrix_sessid()) {
    foreach ($aTabs as $tab) {
        __AdmSettingsSaveOptions('company.catalogrest', $tab['OPTIONS']);
    }

    // Перезагрузка кеша маршрутов
    \Bitrix\Main\Data\Cache::createInstance()->clean('routing_config');

    LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode('company.catalogrest') . '&lang=' . LANGUAGE_ID);
}

// Отображение формы
$tabControl = new CAdminTabControl('tabControl', $aTabs);
$tabControl->Begin();
?>

<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode('company.catalogrest') ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>

    <?php
    foreach ($aTabs as $tab) {
        $tabControl->BeginNextTab();
        __AdmSettingsDrawList('company.catalogrest', $tab['OPTIONS']);
    }
    ?>

    <?php $tabControl->Buttons(['btnApply' => false, 'btnCancel' => false, 'btnSaveAndAdd' => false]); ?>

    <input type="submit" name="Update" value="<?= Loc::getMessage('COMPANY_CATALOGREST_OPTIONS_SAVE') ?>">
    <input type="reset" name="reset" value="<?= Loc::getMessage('COMPANY_CATALOGREST_OPTIONS_RESET') ?>">
</form>

<?php $tabControl->End(); ?>