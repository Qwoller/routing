<?php

namespace Ahml\Api\Models;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Result;
use Bitrix\Main\SystemException;

class RegionModel {

    private array $region;

    /**
     * Constructor
     *
     * @param string $regionCode
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function __construct(string $regionCode) {
        $this->region = $this->getRegion($regionCode);
    }

    /**
     * Возвращает объект региона по его символьному коду
     *
     * @param string $regionCode
     * @return RegionModel
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function byCode(string $regionCode) : RegionModel {
        return new RegionModel($regionCode);
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
     * Возвращает id инфоблока
     *
     * @param string $code
     * @return mixed|string
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getIblockId(string $code) {
        Loader::includeModule('iblock');
        $iblock = IblockTable::getList([
            'select' => ['ID'],
            'filter' => ['CODE' => $code]
        ])->Fetch();

        return (!empty($iblock['ID'])) ? $iblock['ID'] : '';
    }

    /**
     * Возвращает инфо региона по его коду
     *
     * @param string $regionCode
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getRegion(string $regionCode) : array {
        Loader::includeModule('iblock');
        $region = SectionTable::getList([
            'select' => ['ID','NAME','CODE'],
            'filter' => [
                'IBLOCK_ID' => $this->getIblockId('regions'),
                'CODE' => $regionCode
            ],
        ])->fetch();

        return (!empty($region)) ? $region : [];
    }

    /**
     * Возвращает id типа индекса
     *
     * @param string $code
     * @return int|null
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getIndexId(string $code) : ?int {
        $className = $this->getHighLoadBlockClass('Indexes');
        $result = $className::getList(array(
            'select' => ['ID'],
            'filter' => [
                'UF_HL_CODE' => $code,
            ],
        ))->fetch();

        return $result['ID'];
    }

    /**
     * Возвращает объект Bitrix\Main\ORM\Query\Result со средними значениями
     * индекса по региону
     *
     * @param string $indexCode
     * @return Result
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getAverageValuesResultObject(string $indexCode) : Result {
        $className = $this->getHighLoadBlockClass('AverageValuesForRegions');
        return $className::getList(array(
            'select' => ['UF_YEAR', 'UF_VALUE'],
            'filter' => [
                'UF_REGION' => $this->region['ID'],
                'UF_INDEX' => $this->getIndexId($indexCode),
            ],
        ));
    }

    /**
     * Возвращает наименование региона
     *
     * @return string
     */
    public function getName() : string {
        return (!empty($this->region['NAME'])) ? $this->region['NAME'] : '';
    }

    /**
     * Возвращает ссылку на страницу региона
     *
     * @return string
     */
    public function getLink() : string {
        return (!empty($this->region['CODE'])) ? $this->region['CODE'] . '/' : '';
    }

    /**
     * Возвращает средние значения по субъективному индексу
     *
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getSubjectiveIndexAverageValues() : array {
        $result = $this->getAverageValuesResultObject('SubjectiveIndex');

        $averageValues = [];
        while($averageValue = $result->fetch()) {
            $averageValues[] = [
                'title' => $averageValue['UF_YEAR'],
                'min' => 0,
                'max' => 1,
                'val' => $averageValue['UF_VALUE']
            ];
        }
        return $averageValues;
    }

    /**
     * Возвращает средние значения по статистическому индексу
     *
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getStatisticalIndexAverageValues() : array {
        $result = $this->getAverageValuesResultObject('StatisticalIndex');

        $averageValues = [];
        while($averageValue = $result->fetch()) {
            $averageValues[] = [
                'title' => $averageValue['UF_YEAR'],
                'min' => -1,
                'max' => 1,
                'val' => $averageValue['UF_VALUE']
            ];
        }
        return $averageValues;
    }
}