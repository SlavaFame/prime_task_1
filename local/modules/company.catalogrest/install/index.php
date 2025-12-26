<?php
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\EventManager;

Loc::loadMessages(__FILE__);

class company_catalogrest extends CModule
{
    public $MODULE_ID = 'company.catalogrest';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = 'REST API для каталога';
        $this->MODULE_DESCRIPTION = 'Модуль для REST API каталога товаров и выгрузки в Excel';
        $this->PARTNER_NAME = 'Company';
        $this->PARTNER_URI = 'https://company.com';
    }

    public function DoInstall()
    {
        global $APPLICATION;

        ModuleManager::registerModule($this->MODULE_ID);

        // Копируем файлы
        $this->InstallFiles();

        // Регистрируем события
        $this->InstallEvents();

        // Настройки по умолчанию
        $this->InstallDB();

        $APPLICATION->IncludeAdminFile(
            'Установка модуля ' . $this->MODULE_NAME,
            __DIR__ . '/step1.php'
        );
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $context = \Bitrix\Main\Application::getInstance()->getContext();
        $request = $context->getRequest();

        $step = (int)($request->get('step') ?: 1);

        if ($step < 2) {
            $APPLICATION->IncludeAdminFile(
                'Удаление модуля ' . $this->MODULE_NAME,
                __DIR__ . '/step1.php'
            );
            return;
        }

        // Проверяем чекбоксы
        $saveData = $request->get('savedata') === 'Y';
        $saveSettings = $request->get('savesettings') === 'Y';

        if (!$saveData) {
            $this->UnInstallDB();
        }

        $this->UnInstallFiles();
        $this->UnInstallEvents();

        if (!$saveSettings) {
            Option::delete($this->MODULE_ID);
        }

        ModuleManager::unRegisterModule($this->MODULE_ID);

        ?>
        <script>
            alert('Модуль успешно удален');
            window.location.href = '/bitrix/admin/module_admin.php?lang=<?=LANGUAGE_ID?>';
        </script>
        <?php
    }

    public function InstallFiles()
    {
        // Путь к нашим админ-файлам
        $sourceDir = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/company.catalogrest/admin/';
        $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/';

        // Создаем директорию если нет
        if (!is_dir($sourceDir)) {
            mkdir($sourceDir, 0755, true);
        }

        // Создаем простые файлы по умолчанию
        $this->createDefaultAdminFiles($sourceDir);

        // Копируем файлы
        $files = ['company_catalogrest_export.php', 'company_catalogrest_settings.php'];
        foreach ($files as $file) {
            $source = $sourceDir . $file;
            $target = $targetDir . $file;

            if (file_exists($source)) {
                copy($source, $target);
                error_log("[company.catalogrest] Скопирован файл: {$file}");
            }
        }

        return true;
    }

    public function UnInstallFiles()
    {
        $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/';

        $files = ['company_catalogrest_export.php', 'company_catalogrest_settings.php'];
        foreach ($files as $file) {
            $path = $targetDir . $file;
            if (file_exists($path)) {
                unlink($path);
                error_log("[company.catalogrest] Удален файл: {$file}");
            }
        }

        return true;
    }

    private function createDefaultAdminFiles($dir)
    {
        // Создаем export.php
        $export = '<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php";

$APPLICATION->SetTitle("Выгрузка товаров в Excel");

require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php";
?>
<div style="padding: 20px;">
    <h1>Выгрузка товаров каталога "Одежда"</h1>
    
    <div style="margin: 20px 0; padding: 15px; background: #d4edda; border-radius: 5px;">
        <h3>✅ Модуль установлен и готов к работе</h3>
        <p>Эта страница подтверждает успешную установку модуля REST API для каталога.</p>
    </div>
    
    <div style="margin: 30px 0;">
        <h3>Доступные функции:</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
            <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h4>📊 REST API</h4>
                <ul>
                    <li><a href="/api/v1/catalog/categories" target="_blank">Список категорий</a></li>
                    <li><a href="#" onclick="testCategory()">Товары в категории</a></li>
                    <li><a href="#" onclick="testProduct()">Детали товара</a></li>
                </ul>
            </div>
            
            <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h4>📧 Выгрузка</h4>
                <form method="post" style="margin-top: 10px;">
                    <div style="margin-bottom: 10px;">
                        <input type="email" name="email" placeholder="Ваш email" 
                               style="width: 100%; padding: 8px; border: 1px solid #ccc;" required>
                    </div>
                    <input type="submit" name="export" value="Выгрузить в Excel" 
                           class="adm-btn adm-btn-green" style="width: 100%;">
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function testCategory() {
    let id = prompt("Введите ID категории:", "1");
    if (id) window.open("/api/v1/catalog/categories/" + id + "/products", "_blank");
}
function testProduct() {
    let id = prompt("Введите ID товара:", "1");
    if (id) window.open("/api/v1/catalog/products/" + id, "_blank");
}
</script>

<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php";
?>';

        file_put_contents($dir . 'company_catalogrest_export.php', $export);

        // Создаем settings.php
        $settings = '<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php";

$APPLICATION->SetTitle("Настройки модуля");

require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php";
?>
<div style="padding: 20px;">
    <h1>Настройки модуля REST API для каталога</h1>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin: 30px 0;">
        <div>
            <h3>Основные настройки</h3>
            <form method="post">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">
                        <strong>Email по умолчанию:</strong>
                    </label>
                    <input type="email" name="default_email" 
                           style="width: 100%; padding: 8px; border: 1px solid #ccc;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">
                        <strong>Лимит товаров:</strong>
                    </label>
                    <input type="number" name="limit" value="0" min="0" 
                           style="width: 100px; padding: 8px; border: 1px solid #ccc;">
                    <div style="font-size: 12px; color: #666; margin-top: 5px;">
                        0 = без ограничений
                    </div>
                </div>
                
                <div style="margin: 20px 0;">
                    <input type="submit" name="save" value="Сохранить" 
                           class="adm-btn adm-btn-save">
                </div>
            </form>
        </div>
        
        <div>
            <h3>Информация о модуле</h3>
            <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <p><strong>ID модуля:</strong> company.catalogrest</p>
                <p><strong>Версия:</strong> 1.0.0</p>
                <p><strong>Путь:</strong> /local/modules/company.catalogrest/</p>
                <p><strong>API Endpoints:</strong></p>
                <ul style="margin-left: 20px;">
                    <li>/api/v1/catalog/categories</li>
                    <li>/api/v1/catalog/categories/{id}/products</li>
                    <li>/api/v1/catalog/products/{id}</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php";
?>';

        file_put_contents($dir . 'company_catalogrest_settings.php', $settings);
    }

    public function InstallDB()
    {
        // Настройки по умолчанию
        Option::set($this->MODULE_ID, 'default_email', '');
        Option::set($this->MODULE_ID, 'export_limit', 0);

        return true;
    }

    public function UnInstallDB()
    {
        try {
            $connection = \Bitrix\Main\Application::getConnection();
            $connection->query("DROP TABLE IF EXISTS b_company_catalogrest_log");
        } catch (\Exception $e) {
            // Игнорируем ошибку
        }

        return true;
    }

    public function InstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler(
            'main',
            'OnPageStart',
            $this->MODULE_ID,
            'Company\\CatalogRest\\General\\RouterConfig',
            'registerRoutes'
        );

        return true;
    }

    public function UnInstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler(
            'main',
            'OnPageStart',
            $this->MODULE_ID,
            'Company\\CatalogRest\\General\\RouterConfig',
            'registerRoutes'
        );

        return true;
    }
}
?>