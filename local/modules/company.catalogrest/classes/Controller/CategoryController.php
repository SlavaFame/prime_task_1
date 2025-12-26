<?php

namespace Company\CatalogRest\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\Json;
use Bitrix\Main\Loader;

class CategoryController extends Controller
{
    /**
     * Получение списка активных категорий
     */
    public function listAction($parentId = null)
    {
        try {
            if (!Loader::includeModule('iblock')) {
                return new Json([
                    'success' => false,
                    'error' => 'Модуль iblock не установлен'
                ]);
            }

            $iblockId = $this->getClothesIblockId();
            if (!$iblockId) {
                return new Json([
                    'success' => false,
                    'error' => 'Инфоблок "Одежда" не найден'
                ]);
            }

            $filter = [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                'GLOBAL_ACTIVE' => 'Y',
            ];

            if ($parentId !== null && $parentId > 0) {
                $filter['SECTION_ID'] = (int)$parentId;
            } else {
                $filter['SECTION_ID'] = 0;
            }

            $dbSections = \CIBlockSection::GetList(
                ['SORT' => 'ASC', 'ID' => 'ASC'],
                $filter,
                false,
                ['ID', 'NAME', 'CODE', 'PICTURE', 'IBLOCK_SECTION_ID'],
                ['nPageSize' => 100]
            );

            $categories = [];
            while ($section = $dbSections->GetNext()) {
                $categories[] = $this->formatCategory($section);
            }

            return new Json([
                'success' => true,
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return new Json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Форматирование категории
     */
    private function formatCategory($section)
    {
        $detailPage = '';
        if (!empty($section['CODE'])) {
            $detailPage = \CIBlock::ReplaceSectionUrl(
                '/catalog/#SECTION_CODE#/',
                $section,
                false,
                'S'
            );
        }

        $children = $this->getChildrenCategories($section['ID']);

        return [
            'id' => (int)$section['ID'],
            'name' => $section['NAME'],
            'detailPage' => $detailPage,
            'picture' => $this->getPictureUrl($section['PICTURE']),
            'children' => $children
        ];
    }

    /**
     * Получение дочерних категорий
     */
    private function getChildrenCategories($parentId)
    {
        $iblockId = $this->getClothesIblockId();
        if (!$iblockId) {
            return [];
        }

        $dbChildren = \CIBlockSection::GetList(
            ['SORT' => 'ASC', 'ID' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'SECTION_ID' => $parentId,
                'ACTIVE' => 'Y',
                'GLOBAL_ACTIVE' => 'Y'
            ],
            false,
            ['ID', 'NAME', 'CODE', 'PICTURE']
        );

        $children = [];
        while ($child = $dbChildren->GetNext()) {
            $children[] = [
                'id' => (int)$child['ID'],
                'name' => $child['NAME'],
                'detailPage' => \CIBlock::ReplaceSectionUrl(
                    '/catalog/#SECTION_CODE#/',
                    $child,
                    false,
                    'S'
                ),
                'picture' => $this->getPictureUrl($child['PICTURE']),
                'children' => []
            ];
        }

        return $children;
    }

    /**
     * Получение URL картинки
     */
    private function getPictureUrl($pictureId)
    {
        if (!$pictureId) {
            return '';
        }

        $file = \CFile::GetFileArray($pictureId);
        return $file ? $file['SRC'] : '';
    }

    /**
     * Получение ID инфоблока "Одежда"
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