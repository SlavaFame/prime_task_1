<?php

namespace Company\CatalogRest\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\Json;
use Bitrix\Main\Loader;

class CatalogController extends Controller
{
    /**
     * Получение товаров в категории
     */
    public function categoryProductsAction($id, $page = 1, $limit = 50)
    {
        try {
            if (!Loader::includeModule('iblock')) {
                return new Json([
                    'success' => false,
                    'error' => 'Модуль iblock не установлен'
                ]);
            }

            // ✅ ИСПРАВЛЕНО: используем СВОЙ метод getClothesIblockId()
            $iblockId = $this->getClothesIblockId();
            if (!$iblockId) {
                return new Json([
                    'success' => false,
                    'error' => 'Инфоблок "Одежда" не найден'
                ]);
            }

            // Проверяем существование категории
            $section = \CIBlockSection::GetByID($id)->Fetch();
            if (!$section) {
                return new Json([
                    'success' => false,
                    'error' => 'Категория не найдена'
                ]);
            }

            // Валидация параметров
            if ($limit > 100) $limit = 100;

            // Навигация
            $navParams = [
                'nPageSize' => $limit,
                'iNumPage' => $page,
                'bShowAll' => false
            ];

            // Получаем товары с сортировкой по индексу
            $dbProducts = \CIBlockElement::GetList(
                ['SORT' => 'ASC', 'NAME' => 'ASC'],
                [
                    'IBLOCK_ID' => $iblockId,
                    'SECTION_ID' => $id,
                    'INCLUDE_SUBSECTIONS' => 'Y',
                    'ACTIVE' => 'Y',
                    'ACTIVE_DATE' => 'Y'
                ],
                false,
                $navParams,
                [
                    'ID', 'NAME', 'CODE', 'DETAIL_PICTURE', 'PREVIEW_PICTURE',
                    'IBLOCK_SECTION_ID'
                ]
            );

            $products = [];
            while ($product = $dbProducts->GetNext()) {
                $products[] = $this->formatProductShort($product);
            }

            // Получаем общее количество
            $totalCount = $dbProducts->NavRecordCount;

            return new Json([
                'success' => true,
                'data' => $products,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'total' => $totalCount,
                    'pages' => ceil($totalCount / $limit)
                ]
            ]);

        } catch (\Exception $e) {
            return new Json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Детальная информация о товаре
     */
    public function productDetailAction($id)
    {
        try {
            if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog')) {
                return new Json([
                    'success' => false,
                    'error' => 'Необходимые модули не установлены'
                ]);
            }

            // ✅ ИСПРАВЛЕНО: используем СВОЙ метод getClothesIblockId()
            $iblockId = $this->getClothesIblockId();
            if (!$iblockId) {
                return new Json([
                    'success' => false,
                    'error' => 'Инфоблок "Одежда" не найден'
                ]);
            }

            // Получаем элемент
            $dbProduct = \CIBlockElement::GetList(
                [],
                ['ID' => $id, 'IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'],
                false,
                false,
                [
                    'ID', 'NAME', 'CODE', 'DETAIL_TEXT', 'PREVIEW_TEXT',
                    'PROPERTY_BRAND', 'PROPERTY_MANUFACTURER', 'PROPERTY_MATERIAL'
                ]
            );

            $product = $dbProduct->Fetch();
            if (!$product) {
                return new Json([
                    'success' => false,
                    'error' => 'Товар не найден'
                ]);
            }

            // Получаем галерею
            $gallery = $this->getProductGallery($id, $iblockId);

            // Получаем характеристики
            $characteristics = [
                'brand' => $product['PROPERTY_BRAND_VALUE'] ?? '',
                'manufacturer' => $product['PROPERTY_MANUFACTURER_VALUE'] ?? '',
                'material' => $product['PROPERTY_MATERIAL_VALUE'] ?? ''
            ];

            // Получаем торговые предложения
            $offers = $this->getProductOffers($id);

            return new Json([
                'success' => true,
                'data' => [
                    'id' => (int)$product['ID'],
                    'name' => $product['NAME'],
                    'detailPage' => $this->getDetailPageUrl($product['CODE']),
                    'gallery' => $gallery,
                    'characteristics' => $characteristics,
                    'offers' => $offers
                ]
            ]);

        } catch (\Exception $e) {
            return new Json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Форматирование краткой информации о товаре
     */
    private function formatProductShort($product)
    {
        return [
            'id' => (int)$product['ID'],
            'name' => $product['NAME'],
            'detailPage' => $this->getDetailPageUrl($product['CODE']),
            'picture' => $this->getProductMainPicture($product),
            'priceFrom' => $this->getMinPrice($product['ID'])
        ];
    }

    /**
     * Получение главной картинки товара
     */
    private function getProductMainPicture($product)
    {
        $pictureId = $product['DETAIL_PICTURE'] ?: $product['PREVIEW_PICTURE'];
        if (!$pictureId) {
            return '';
        }

        $file = \CFile::GetFileArray($pictureId);
        return $file ? $file['SRC'] : '';
    }

    /**
     * Получение URL детальной страницы
     */
    private function getDetailPageUrl($code)
    {
        if (!$code) {
            return '';
        }

        return \CIBlock::ReplaceDetailUrl(
            '/catalog/#ELEMENT_CODE#/',
            ['CODE' => $code],
            false,
            'E'
        );
    }

    /**
     * Получение минимальной цены (цены "от")
     */
    private function getMinPrice($productId)
    {
        $price = 0;

        // Получаем все предложения товара
        $offers = \CCatalogSKU::getOffersList(
            [$productId],
            0,
            ['ACTIVE' => 'Y'],
            ['ID', 'CATALOG_PRICE_1']
        );

        if (isset($offers[$productId])) {
            foreach ($offers[$productId] as $offer) {
                $offerPrice = (float)($offer['CATALOG_PRICE_1'] ?? 0);
                if ($offerPrice > 0 && ($price === 0 || $offerPrice < $price)) {
                    $price = $offerPrice;
                }
            }
        }

        return $price;
    }

    /**
     * Получение галереи товара
     */
    private function getProductGallery($productId, $iblockId)
    {
        $gallery = [];

        $dbFiles = \CIBlockElement::GetProperty(
            $iblockId,
            $productId,
            [],
            ['CODE' => 'MORE_PHOTO']
        );

        while ($file = $dbFiles->Fetch()) {
            if ($file['VALUE']) {
                $fileInfo = \CFile::GetFileArray($file['VALUE']);
                if ($fileInfo) {
                    $gallery[] = $fileInfo['SRC'];
                }
            }
        }

        return $gallery;
    }

    /**
     * Получение торговых предложений
     */
    private function getProductOffers($productId)
    {
        $offers = [];

        $dbOffers = \CCatalogSKU::getOffersList(
            [$productId],
            0,
            ['ACTIVE' => 'Y'],
            ['ID', 'NAME', 'CODE', 'PROPERTY_COLOR', 'PROPERTY_SIZE']
        );

        if (isset($dbOffers[$productId])) {
            foreach ($dbOffers[$productId] as $offer) {
                $offers[] = [
                    'id' => (int)$offer['ID'],
                    'name' => $offer['NAME'] ?? '',
                    'article' => $offer['CODE'] ?? '',
                    'color' => $offer['PROPERTY_COLOR_VALUE'] ?? '',
                    'size' => $offer['PROPERTY_SIZE_VALUE'] ?? ''
                ];
            }
        }

        return $offers;
    }

    /**
     * ✅ ДОБАВЛЕНО: Получение ID инфоблока "Одежда"
     * ТОЧНАЯ КОПИЯ метода из CategoryController
     */
    private function getClothesIblockId()
    {
        $iblock = \CIBlock::GetList(
            [],
            ['CODE' => 'clothes', 'TYPE' => 'catalog']
        )->Fetch();

        return $iblock ? (int)$iblock['ID'] : null;
    }
}
?>