<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

// Определяем, установка или удаление
$isUninstall = isset($_REQUEST['uninstall']) && $_REQUEST['uninstall'] === 'Y';
$step = isset($_REQUEST['step']) ? (int)$_REQUEST['step'] : 1;
?>

<form action="<?=$APPLICATION->GetCurPage()?>">
    <?=bitrix_sessid_post()?>
    <input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
    <input type="hidden" name="id" value="company.catalogrest">

    <?php if ($isUninstall): ?>
        <input type="hidden" name="uninstall" value="Y">
        <input type="hidden" name="step" value="2">

        <div style="padding: 20px; max-width: 600px;">
            <h3><?=Loc::getMessage('COMPANY_CATALOGREST_UNINSTALL_CONFIRM')?></h3>

            <p style="color: #d9534f; margin-bottom: 20px;">
                <strong><?=Loc::getMessage('COMPANY_CATALOGREST_UNINSTALL_WARNING')?></strong>
            </p>

            <div style="margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                <label>
                    <input type="checkbox" name="savedata" value="Y" checked>
                    <?=Loc::getMessage('COMPANY_CATALOGREST_SAVE_TABLES')?>
                </label>
                <p style="margin: 5px 0 0 20px; font-size: 12px; color: #6c757d;">
                    <?=Loc::getMessage('COMPANY_CATALOGREST_SAVE_TABLES_HELP')?>
                </p>
            </div>

            <div style="margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                <label>
                    <input type="checkbox" name="savesettings" value="Y" checked>
                    <?=Loc::getMessage('COMPANY_CATALOGREST_SAVE_SETTINGS')?>
                </label>
                <p style="margin: 5px 0 0 20px; font-size: 12px; color: #6c757d;">
                    <?=Loc::getMessage('COMPANY_CATALOGREST_SAVE_SETTINGS_HELP')?>
                </p>
            </div>

            <div style="margin-top: 25px;">
                <input type="submit"
                       name="uninstall"
                       value="<?=Loc::getMessage('COMPANY_CATALOGREST_UNINSTALL_BUTTON')?>"
                       class="adm-btn adm-btn-save"
                       onclick="return confirm('<?=Loc::getMessage('COMPANY_CATALOGREST_UNINSTALL_FINAL_CONFIRM')?>')">

                <a href="/bitrix/admin/module_admin.php?lang=<?=LANGUAGE_ID?>"
                   class="adm-btn"
                   style="margin-left: 10px;">
                    <?=Loc::getMessage('COMPANY_CATALOGREST_CANCEL_BUTTON')?>
                </a>
            </div>
        </div>

    <?php else: ?>
        <input type="hidden" name="install" value="Y">
        <input type="hidden" name="step" value="2">

        <div style="padding: 20px; max-width: 600px;">
            <h3><?=Loc::getMessage('COMPANY_CATALOGREST_INSTALL_TITLE')?></h3>
            <p><?=Loc::getMessage('COMPANY_CATALOGREST_INSTALL_DESCRIPTION')?></p>

            <div style="margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 5px;">
                <strong><?=Loc::getMessage('COMPANY_CATALOGREST_REQUIREMENTS')?>:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <li>PHP 7.4+</li>
                    <li>Модуль iblock</li>
                    <li>Модуль catalog</li>
                    <li>Модуль main (роутинг)</li>
                </ul>
            </div>

            <div style="margin-top: 25px;">
                <input type="submit"
                       name="install"
                       value="<?=Loc::getMessage('COMPANY_CATALOGREST_INSTALL_BUTTON')?>"
                       class="adm-btn adm-btn-save">

                <a href="/bitrix/admin/module_admin.php?lang=<?=LANGUAGE_ID?>"
                   class="adm-btn"
                   style="margin-left: 10px;">
                    <?=Loc::getMessage('COMPANY_CATALOGREST_CANCEL_BUTTON')?>
                </a>
            </div>
        </div>
    <?php endif; ?>
</form>