<?php

namespace Company\CatalogRest\General;

use Bitrix\Main\Loader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExcelExporter
{
    /**
     * Экспорт товаров в Excel и отправка на email
     */
    public function exportToEmail($email)
    {
        try {
            // Создаем Excel файл
            $filePath = $this->createExcelFile();

            // Отправляем email
            $this->sendEmail($email, $filePath);

            // Удаляем временный файл
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            return true;

        } catch (\Exception $e) {
            error_log("ExcelExporter error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Создание Excel файла
     */
    private function createExcelFile()
    {
        // Подключаем модули
        if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog')) {
            throw new \Exception('Необходимые модули не установлены');
        }

        // Создаем документ
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Товары');

        // Заголовки
        $headers = ['ID', 'Наименование', 'Категория', 'Ссылка', 'Кол-во предложений', 'Минимальная цена'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Получаем данные
        $products = $this->getProductsData();
        $row = 2;

        foreach ($products as $product) {
            $sheet->setCellValue('A' . $row, $product['ID']);
            $sheet->setCellValue('B' . $row, $product['NAME']);
            $sheet->setCellValue('C' . $row, $product['CATEGORY_PATH']);
            $sheet->setCellValue('D' . $row, $product['DETAIL_PAGE']);
            $sheet->setCellValue('E' . $row, $product['OFFERS_COUNT']);
            $sheet->setCellValue('F' . $row, $product['MIN_PRICE']);
            $row++;
        }

        // Применяем стили
        $this->applyStyles($sheet, $row - 1);

        // Сохраняем файл
        $fileName = sys_get_temp_dir() . '/catalog_export_' . date('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($fileName);

        return $fileName;
    }

    /**
     * Получение данных товаров
     */
    private function getProductsData()
    {
        $iblockId = $this->getClothesIblockId();
        if (!$iblockId) {
            throw new \Exception('Инфоблок "Одежда" не найден');
        }

        $products = [];

        // Получаем товары с сортировкой
        $dbProducts = \CIBlockElement::GetList(
            [
                'IBLOCK_SECTION_NAME' => 'ASC',
                'NAME' => 'ASC'
            ],
            [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                'ACTIVE_DATE' => 'Y'
            ],
            false,
            false,
            ['ID', 'NAME', 'CODE', 'IBLOCK_SECTION_ID']
        );

        while ($product = $dbProducts->Fetch()) {
            $categoryPath = $this->getCategoryPath($product['IBLOCK_SECTION_ID']);
            $offersCount = $this->getOffersCount($product['ID']);
            $minPrice = $this->getMinPrice($product['ID']);

            $products[] = [
                'ID' => $product['ID'],
                'NAME' => $product['NAME'],
                'CATEGORY_PATH' => $categoryPath,
                'DETAIL_PAGE' => $this->getDetailPageUrl($product['CODE']),
                'OFFERS_COUNT' => $offersCount,
                'MIN_PRICE' => $minPrice
            ];
        }

        return $products;
    }

    /**
     * Получение пути категории
     */
    private function getCategoryPath($sectionId)
    {
        if (!$sectionId) {
            return '';
        }

        $iblockId = $this->getClothesIblockId();
        $path = [];

        $dbSection = \CIBlockSection::GetNavChain($iblockId, $sectionId, ['ID', 'NAME']);

        while ($section = $dbSection->Fetch()) {
            $path[] = $section['NAME'];
        }

        return implode(' / ', $path);
    }

    /**
     * Количество торговых предложений
     */
    private function getOffersCount($productId)
    {
        if (!Loader::includeModule('catalog')) {
            return 0;
        }

        $offers = \CCatalogSKU::getOffersList(
            [$productId],
            0,
            ['ACTIVE' => 'Y'],
            ['ID']
        );

        return isset($offers[$productId]) ? count($offers[$productId]) : 0;
    }

    /**
     * Минимальная цена
     */
    private function getMinPrice($productId)
    {
        if (!Loader::includeModule('catalog')) {
            return 0;
        }

        $minPrice = 0;
        $offers = \CCatalogSKU::getOffersList(
            [$productId],
            0,
            ['ACTIVE' => 'Y'],
            ['ID', 'CATALOG_PRICE_1']
        );

        if (isset($offers[$productId])) {
            foreach ($offers[$productId] as $offer) {
                $price = (float)($offer['CATALOG_PRICE_1'] ?? 0);
                if ($price > 0 && ($minPrice === 0 || $price < $minPrice)) {
                    $minPrice = $price;
                }
            }
        }

        return $minPrice;
    }

    /**
     * URL детальной страницы
     */
    private function getDetailPageUrl($code)
    {
        if (!$code) {
            return '';
        }

        $siteUrl = \Bitrix\Main\Context::getCurrent()->getRequest()->getHttpHost();
        $detailUrl = \CIBlock::ReplaceDetailUrl(
            '/catalog/#ELEMENT_CODE#/',
            ['CODE' => $code],
            false,
            'E'
        );

        return 'https://' . $siteUrl . $detailUrl;
    }

    /**
     * ID инфоблока "Одежда"
     */
    private function getClothesIblockId()
    {
        $iblock = \CIBlock::GetList([], ['CODE' => 'clothes', 'TYPE' => 'catalog'])->Fetch();
        return $iblock ? (int)$iblock['ID'] : null;
    }

    /**
     * Применение стилей
     */
    private function applyStyles($sheet, $lastRow)
    {
        // Автоширина колонок
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Стиль заголовков
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E6E6E6']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        // Рамки для данных
        $dataStyle = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ];
        $sheet->getStyle('A2:F' . $lastRow)->applyFromArray($dataStyle);
    }

    /**
     * Отправка email
     */
    private function sendEmail($email, $filePath)
    {
        $arFields = [
            'EMAIL_TO' => $email,
            'SUBJECT' => 'Выгрузка товаров из каталога',
            'FILE_PATH' => $filePath
        ];

        // Пытаемся найти почтовое событие
        $eventName = 'CATALOG_EXPORT_TO_EXCEL';

        // Проверяем существование события
        $eventType = new \CEventType();
        $dbEvent = $eventType->GetList(['TYPE_ID' => $eventName]);

        if (!$dbEvent->Fetch()) {
            // Создаем событие если нет
            $eventType->Add([
                'LID' => 'ru',
                'EVENT_NAME' => $eventName,
                'NAME' => 'Выгрузка товаров в Excel'
            ]);

            // Создаем шаблон
            $eventMessage = new \CEventMessage();
            $eventMessage->Add([
                'ACTIVE' => 'Y',
                'EVENT_NAME' => $eventName,
                'LID' => 's1',
                'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
                'EMAIL_TO' => '#EMAIL_TO#',
                'SUBJECT' => '#SUBJECT#',
                'BODY_TYPE' => 'html',
                'MESSAGE' => '<p>Во вложении выгрузка товаров.</p>'
            ]);
        }
        
        // Отправляем
        return \CEvent::Send($eventName, 's1', $arFields);
    }
}
?>