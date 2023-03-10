<?php

namespace Ahml\Api\Models;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CIBlockElement;

class StatisticalIndexModel {
    private const TYPE_INDEX_CODE = 'StatisticalIndex';
    private const IBLOCK_CODE = 'indicators';
    private int $regionId;
    private int $domainId;
    private int $year;

    /**
     * Constructor
     *
     * @param int $regionId
     * @param int $domainId
     * @param int $year
     */
    private function __construct(int $regionId, int $domainId, int $year) {
        $this->regionId = $regionId;
        $this->domainId = $domainId;
        $this->year = $year;
    }

    /**
     * Возвращает объект StatisticalIndexModel
     *
     * @param int $regionId
     * @param int $domainId
     * @param int $year
     * @return StatisticalIndexModel
     */
    public static function getObject(int $regionId, int $domainId, int $year) : StatisticalIndexModel {
        return new StatisticalIndexModel($regionId, $domainId, $year);
    }

    /**
     * Возвращает класс HighLoad-блока
     *
     * @param string $hlName
     * @return string
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getHighLoadBlockClass(string $hlName) : string {
        Loader::includeModule('highloadblock');
        $hlBlock = HighloadBlockTable::getList([
            'filter' => ['=NAME' => $hlName]
        ])->fetch();
        return HighloadBlockTable::compileEntity($hlBlock)->getDataClass();
    }

    /**
     * Возвращает массив типов результатов с показателями
     *
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getResultsWithIndicators() : array {
        $entityDataClass = $this->getHighLoadBlockClass('Results');
        $result = $entityDataClass::getList(array(
            'select' => ['ID', 'UF_NAME', 'UF_FILE', 'UF_FULL_DESCRIPTION'],
            'order' => ['UF_SORT' => 'ASC'],
        ));
        $typeResults = [];
        while ($arRow = $result->Fetch()) {
            $indicators = $this->getIndicators($arRow['ID']);
            if(!empty($indicators)) {
                $typeResults[] = [
                    'title' => $arRow['UF_NAME'],
                    'notes' => $arRow['UF_FULL_DESCRIPTION'],
                    'headerAside' => $this->getResultValue($arRow['ID']),
                    'icon' => (!empty($arRow['UF_FILE'])) ? \CFile::GetPath($arRow['UF_FILE']) : '',
                    'data' => $indicators
                ];
            }
        }
        return $typeResults;
    }

    /**
     * Возвращает среднее значение типа результата
     *
     * @param int $resultId
     * @return string
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getResultValue(int $resultId) : string {
        $entityDataClass = $this->getHighLoadBlockClass('AverageTypeResultsValuesForRegions');
        $value = $entityDataClass::getList(array(
            'select' => ['UF_VALUE'],
            'filter' => [
                'UF_TYPE_RESULT' => $resultId,
                'UF_YEAR' => $this->year,
                'UF_REGION' => $this->regionId,
            ],
        ))->Fetch()['UF_VALUE'];
        return (!empty($value)) ? $value : '';
    }

    /**
     * Возвращает xml id элемента HighLoad-блока
     *
     * @param int $itemId
     * @param string $highLoadBlockCode
     * @return string
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getXmlId(int $itemId, string $highLoadBlockCode) : string {
        $entityDataClass = $this->getHighLoadBlockClass($highLoadBlockCode);
        $xmlId = $entityDataClass::getList(array(
            'select' => ['UF_XML_ID'],
            'filter' => ['ID' => $itemId],
        ))->fetch()['UF_XML_ID'];
        return (!empty($xmlId)) ? $xmlId : '';
    }

    /**
     * Возвращает значение показателя
     *
     * @param int $indicatorId
     * @return string
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getIndicatorValue(int $indicatorId) : string {
        $entityDataClass = $this->getHighLoadBlockClass(self::TYPE_INDEX_CODE);
        $value = $entityDataClass::getList(array(
            'select' => ['UF_INDICATOR_VALUE'],
            'filter' => [
                'UF_REGION' => $this->regionId,
                'UF_INDICATOR' => $indicatorId,
                'UF_YEAR' => $this->year
            ],
        ))->Fetch()['UF_INDICATOR_VALUE'];
        return (!empty($value)) ? $value : '';
    }

    /**
     * Возвращает массив показателей со значениями
     *
     * @param int $typeResultId
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getIndicators(int $typeResultId) : array {
        Loader::includeModule('iblock');
        $res = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_CODE' => self::IBLOCK_CODE,
                'PROPERTY_RESULT_TYPE' => $this->getXmlId($typeResultId, 'Results'),
                'PROPERTY_DOMEN' => $this->getXmlId($this->domainId, 'Domains')
            ],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'NAME', 'DETAIL_TEXT']
        );

        $indicators = [];
        while ($ar = $res->Fetch()) {
            $indicatorValue = $this->getIndicatorValue($ar['ID']);
            if(!empty($indicatorValue)) {
                $indicators[] = [
                    'rowTitle' => $ar['NAME'],
                    'text' => $ar['DETAIL_TEXT'],
                    'value' => $indicatorValue
                ];
            }
        }

        return $indicators;
    }

    /**
     * Возвращает идентификатор статистического индекса
     *
     * @return string
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getIndexId() : string {
        $entityDataClass = $this->getHighLoadBlockClass('Indexes');
        $result = $entityDataClass::getList(array(
            'select' => ['ID'],
            'order' => ['UF_SORT' => 'ASC'],
            'filter' => ['UF_HL_CODE' => self::TYPE_INDEX_CODE],
        ))->fetch();

        return (!empty($result['ID'])) ? $result['ID'] : '';
    }

    /**
     * Возвращает список доменов
     *
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getDomains() : array {
        $entityDataClass = $this->getHighLoadBlockClass('Domains');
        $result = $entityDataClass::getList([
            'select' => ['ID', 'UF_NAME', 'UF_FILE'],
            'order' => ['UF_SORT' => 'ASC'],
            'filter' => ['UF_INDEX' => $this->getIndexId()],
        ]);

        $domains = [];
        while ($arRow = $result->Fetch()) {
            $domains[] = [
                'id' => $arRow['ID'],
                'name' => $arRow['UF_NAME'],
                'icon' => (!empty($arRow['UF_FILE'])) ? \CFile::GetPath($arRow['UF_FILE']) : '',
            ];
        }
        return $domains;
    }

    /**
     * Возвращает среднее значение для региона
     *
     * @return string
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getAverageValuesForRegion() : string {
        $entityDataClass = $this->getHighLoadBlockClass('AverageValuesForRegions');
        $value = $entityDataClass::getList(array(
            'select' => ['UF_VALUE'],
            'order' => ['ID' => 'DESC'],
            'filter' => ['UF_REGION' => $this->regionId, 'UF_INDEX' => $this->getIndexId(), 'UF_YEAR' => $this->year],
        ))->Fetch()['UF_VALUE'];
        return (!empty($value)) ? $value : '';
    }

    /**
     * Возвращает средние значения для доменов региона
     *
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getAverageValuesForDomains() : array {
        $entityDataClass = $this->getHighLoadBlockClass('AverageDomainValuesForRegions');
        $result = $entityDataClass::getList(array(
            'select' => ['UF_VALUE', 'UF_DOMEN'],
            'order' => ['ID' => 'DESC'],
            'filter' => [
                'UF_REGION' => $this->regionId,
                'UF_INDEX' => $this->getIndexId(),
                'UF_YEAR' => $this->year
            ],
        ));

        $values = [];
        while ($value = $result->Fetch()) {
            $values[] = [
                'id' => $value['UF_DOMEN'],
                'value' => $value['UF_VALUE'],
            ];
        }

        return $values;
    }
}