<?php
AddEventHandler('main', 'OnPageStart', function() {
    if (defined('SITE_ID') && SITE_ID === 's1') { // Только для основного сайта
        \Bitrix\Main\Loader::includeModule('company.catalogrest');
    }
});