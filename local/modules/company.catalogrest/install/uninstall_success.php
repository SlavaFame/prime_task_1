<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>

<div style="padding: 20px; max-width: 600px;">
    <h3><?=Loc::getMessage('COMPANY_CATALOGREST_UNINSTALL_SUCCESS_TITLE')?></h3>
    
    <div style="margin: 20px 0; padding: 15px; background: #dff0d8; border: 1px solid #d6e9c6; border-radius: 5px;">
        <p style="color: #3c763d; margin: 0;">
            <?=Loc::getMessage('COMPANY_CATALOGREST_UNINSTALL_SUCCESS_TEXT')?>
        </p>
    </div>
    
    <p><?=Loc::getMessage('COMPANY_CATALOGREST_UNINSTALL_SUCCESS_INFO')?></p>
    
    <ul style="margin: 15px 0 20px 20px;">
        <li><?=Loc::getMessage('COMPANY_CATALOGREST_UNINSTALL_SUCCESS_ITEM_1')?></li>
        <li><?=Loc::getMessage('COMPANY_CATALOGREST_UNINSTALL_SUCCESS_ITEM_2')?></li>
        <li><?=Loc::getMessage('COMPANY_CATALOGREST_UNINSTALL_SUCCESS_ITEM_3')?></li>
    </ul>
    
    <div style="margin-top: 20px;">
        <a href="/bitrix/admin/module_admin.php?lang=<?=LANGUAGE_ID?>" class="adm-btn">
            <?=Loc::getMessage('COMPANY_CATALOGREST_UNINSTALL_SUCCESS_BUTTON')?>
        </a>
    </div>
</div>

<script>
// Автоматический переход через 5 секунд
setTimeout(function() {
    window.location.href = '/bitrix/admin/module_admin.php?lang=<?=LANGUAGE_ID?>';
}, 5000);
</script>