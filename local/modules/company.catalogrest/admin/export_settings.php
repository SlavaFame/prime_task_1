<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin.php';

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

// Проверка прав
if (!$USER->IsAdmin() || !check_bitrix_sessid()) {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

Loader::includeModule('company.catalogrest');

$moduleId = 'company.catalogrest';
$APPLICATION->SetTitle(Loc::getMessage('COMPANY_CATALOGREST_EXPORT_SETTINGS_TITLE'));

// Обработка сохранения формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    // Сохраняем настройки
    Option::set($moduleId, 'default_export_email', $_POST['default_email'] ?? '');
    Option::set($moduleId, 'export_limit', (int)($_POST['export_limit'] ?? 0));
    Option::set($moduleId, 'auto_export', $_POST['auto_export'] === 'Y' ? 'Y' : 'N');
    Option::set($moduleId, 'export_schedule', $_POST['export_schedule'] ?? 'daily');
    Option::set($moduleId, 'include_inactive', $_POST['include_inactive'] === 'Y' ? 'Y' : 'N');

    // Настройки Excel
    Option::set($moduleId, 'excel_auto_width', $_POST['excel_auto_width'] === 'Y' ? 'Y' : 'N');
    Option::set($moduleId, 'excel_add_filters', $_POST['excel_add_filters'] === 'Y' ? 'Y' : 'N');

    // Уведомление об успешном сохранении
    CAdminMessage::ShowMessage([
        'MESSAGE' => Loc::getMessage('COMPANY_CATALOGREST_SETTINGS_SAVED'),
        'TYPE' => 'OK',
    ]);
}

// Получаем текущие настройки
$defaultEmail = Option::get($moduleId, 'default_export_email', '');
$exportLimit = Option::get($moduleId, 'export_limit', 0);
$autoExport = Option::get($moduleId, 'auto_export', 'N');
$exportSchedule = Option::get($moduleId, 'export_schedule', 'daily');
$includeInactive = Option::get($moduleId, 'include_inactive', 'N');
$excelAutoWidth = Option::get($moduleId, 'excel_auto_width', 'Y');
$excelAddFilters = Option::get($moduleId, 'excel_add_filters', 'Y');
?>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php'; ?>

<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>

    <div style="max-width: 800px;">
        <?php
        $tabControl = new CAdminTabControl('tabControl', [
            [
                'DIV' => 'edit1',
                'TAB' => Loc::getMessage('COMPANY_CATALOGREST_TAB_MAIN'),
                'ICON' => '',
                'TITLE' => Loc::getMessage('COMPANY_CATALOGREST_TAB_MAIN_TITLE'),
            ],
            [
                'DIV' => 'edit2',
                'TAB' => Loc::getMessage('COMPANY_CATALOGREST_TAB_EXCEL'),
                'ICON' => '',
                'TITLE' => Loc::getMessage('COMPANY_CATALOGREST_TAB_EXCEL_TITLE'),
            ],
            [
                'DIV' => 'edit3',
                'TAB' => Loc::getMessage('COMPANY_CATALOGREST_TAB_SCHEDULE'),
                'ICON' => '',
                'TITLE' => Loc::getMessage('COMPANY_CATALOGREST_TAB_SCHEDULE_TITLE'),
            ],
        ]);

        $tabControl->Begin();
        ?>

        <?php $tabControl->BeginNextTab(); ?>

        <tr>
            <td width="40%">
                <label for="default_email">
                    <?= Loc::getMessage('COMPANY_CATALOGREST_DEFAULT_EMAIL') ?>:
                </label>
            </td>
            <td width="60%">
                <input type="email"
                       id="default_email"
                       name="default_email"
                       value="<?= htmlspecialcharsbx($defaultEmail) ?>"
                       size="50">
                <br>
                <small><?= Loc::getMessage('COMPANY_CATALOGREST_DEFAULT_EMAIL_HELP') ?></small>
            </td>
        </tr>

        <tr>
            <td>
                <label for="export_limit">
                    <?= Loc::getMessage('COMPANY_CATALOGREST_EXPORT_LIMIT') ?>:
                </label>
            </td>
            <td>
                <input type="number"
                       id="export_limit"
                       name="export_limit"
                       value="<?= (int)$exportLimit ?>"
                       min="0"
                       size="10">
                <br>
                <small><?= Loc::getMessage('COMPANY_CATALOGREST_EXPORT_LIMIT_HELP') ?></small>
            </td>
        </tr>

        <tr>
            <td>
                <label for="include_inactive">
                    <?= Loc::getMessage('COMPANY_CATALOGREST_INCLUDE_INACTIVE') ?>:
                </label>
            </td>
            <td>
                <input type="checkbox"
                       id="include_inactive"
                       name="include_inactive"
                       value="Y"
                       <?= $includeInactive === 'Y' ? 'checked' : '' ?>>
            </td>
        </tr>

        <?php $tabControl->BeginNextTab(); ?>

        <tr>
            <td>
                <label for="excel_auto_width">
                    <?= Loc::getMessage('COMPANY_CATALOGREST_EXCEL_AUTO_WIDTH') ?>:
                </label>
            </td>
            <td>
                <input type="checkbox"
                       id="excel_auto_width"
                       name="excel_auto_width"
                       value="Y"
                       <?= $excelAutoWidth === 'Y' ? 'checked' : '' ?>>
            </td>
        </tr>

        <tr>
            <td>
                <label for="excel_add_filters">
                    <?= Loc::getMessage('COMPANY_CATALOGREST_EXCEL_ADD_FILTERS') ?>:
                </label>
            </td>
            <td>
                <input type="checkbox"
                       id="excel_add_filters"
                       name="excel_add_filters"
                       value="Y"
                       <?= $excelAddFilters === 'Y' ? 'checked' : '' ?>>
            </td>
        </tr>

        <?php $tabControl->BeginNextTab(); ?>

        <tr>
            <td>
                <label for="auto_export">
                    <?= Loc::getMessage('COMPANY_CATALOGREST_AUTO_EXPORT') ?>:
                </label>
            </td>
            <td>
                <input type="checkbox"
                       id="auto_export"
                       name="auto_export"
                       value="Y"
                       <?= $autoExport === 'Y' ? 'checked' : '' ?>
                       onchange="toggleSchedule(this.checked)">
            </td>
        </tr>

        <tr id="schedule_row" style="<?= $autoExport === 'Y' ? '' : 'display: none;' ?>">
            <td>
                <label for="export_schedule">
                    <?= Loc::getMessage('COMPANY_CATALOGREST_EXPORT_SCHEDULE') ?>:
                </label>
            </td>
            <td>
                <select id="export_schedule" name="export_schedule">
                    <option value="daily" <?= $exportSchedule === 'daily' ? 'selected' : '' ?>>
                        <?= Loc::getMessage('COMPANY_CATALOGREST_SCHEDULE_DAILY') ?>
                    </option>
                    <option value="weekly" <?= $exportSchedule === 'weekly' ? 'selected' : '' ?>>
                        <?= Loc::getMessage('COMPANY_CATALOGREST_SCHEDULE_WEEKLY') ?>
                    </option>
                    <option value="monthly" <?= $exportSchedule === 'monthly' ? 'selected' : '' ?>>
                        <?= Loc::getMessage('COMPANY_CATALOGREST_SCHEDULE_MONTHLY') ?>
                    </option>
                </select>
            </td>
        </tr>

        <?php $tabControl->Buttons(['btnSave' => true, 'btnApply' => true]); ?>

        <script>
        function toggleSchedule(show) {
            document.getElementById('schedule_row').style.display = show ? '' : 'none';
        }
        </script>

        <?php $tabControl->End(); ?>
    </div>
</form>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php'; ?>