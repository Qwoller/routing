<?php

namespace Ahml\Api\Models;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\Model\Section;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CIBlockElement;

class SubjectiveIndexModel {
    private const TYPE_INDEX_CODE = 'SubjectiveIndex';
    private const IBLOCK_CODE = 'questions';
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
     * Возвращает объект SubjectiveIndexModel
     *
     * @param int $regionId
     * @param int $domainId
     * @param int $year
     * @return SubjectiveIndexModel
     */
    public static function getObject(int $regionId, int $domainId, int $year) : SubjectiveIndexModel {
        return new SubjectiveIndexModel($regionId, $domainId, $year);
    }

    /**
     * Возвращает класс HighLoad-блока
     *
     * @throws LoaderException
     * @throws ArgumentException
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
     * Возвращает id инфоблока битрикс
     *
     * @param string $iblockCode
     * @return string
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getIblockId(string $iblockCode = '') : string {
        Loader::includeModule('iblock');
        $iblock = IblockTable::getList([
            'filter' => ['CODE' => (!empty($iblockCode)) ? $iblockCode : self::IBLOCK_CODE],
            'select' => ['ID'],
            'limit' => 1,
        ])->fetch();

        return (!empty($iblock)) ? $iblock['ID'] : '';
    }

    /**
     * Возвращает массив вопросов с ответами
     *
     * @param bool $additional
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getQuestions(bool $additional = false) : array {
        Loader::includeModule('iblock');
        $entity = Section::compileEntityByIblock($this->getIblockId());
        $result = $entity::getList(array(
            "select" => ['ID', 'NAME'],
            "filter" => [
                'UF_DOMEN' => $this->domainId,
                'UF_ADDITIONAL_QUESTION' => $additional
            ],
        ));
        $mainQuestions = [];
        while ($question = $result->fetch()) {
            $answers = $this->getAnswersWithValues($question['ID']);
            if(!empty($answers)) {
                $mainQuestions[] = [
                    'qs' => $question['NAME'],
                    'ans' => $answers
                ];
            }
        }
        return $mainQuestions;
    }

    /**
     * Возвращает список вопросов с ответами по муниципалитетам
     *
     * @param bool $additional
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getQuestionsForMunicipalities(bool $additional = false) : array {
        Loader::includeModule('iblock');
        $entity = Section::compileEntityByIblock($this->getIblockId());
        $result = $entity::getList(array(
            "select" => ['ID', 'NAME'],
            "filter" => [
                'UF_DOMEN' => $this->domainId,
                'UF_ADDITIONAL_QUESTION' => $additional
            ],
        ));

        $municipalities = $this->getMunicipalities(); // TODO муниципалитеты

        $mainQuestions = [];
        while ($question = $result->fetch()) {
            $answers = $this->getAnswersWithValues($question['ID'], array_keys($municipalities));
            if(!empty($answers)) {
                foreach ($municipalities as $id => &$municipality) {
                    $municipality['items'] = $answers[$id];
                }
                $mainQuestions[] = [
                    'title' => $question['NAME'],
                    'data' => array_values($municipalities)
                ];
            }
        }

        return $mainQuestions;
    }

    /**
     * Возвращает список муниципалитетов
     *
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getMunicipalities() : array {
        Loader::includeModule('iblock');
        $entity = Section::compileEntityByIblock($this->getIblockId('regions'));
        $result = $entity::getList(array(
            'order' => ['SORT' => 'ASC'],
            'select' => ['ID', 'NAME'],
            'filter' => [
                'IBLOCK_SECTION_ID' => $this->regionId
            ],
        ));

        $municipalities = [];
        while($municipality = $result->fetch()){
            $municipalities[$municipality['ID']] = [
                'title' => $municipality['NAME'],
            ];
        }

        return $municipalities;
    }

    /**
     * Возвращает список главных вопросов для муниципалитетов
     *
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getMainQuestionsForMunicipalities() : array {
        return $this->getQuestionsForMunicipalities();
    }

    /**
     * Возвращает список дополнительных вопросов для муниципалитетов
     *
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getAdditionalQuestionsForMunicipalities() : array {
        return $this->getQuestionsForMunicipalities(true);
    }

    /**
     * Возвращает список главных вопросов домена с ответами
     *
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getMainQuestions() : array {
        return $this->getQuestions();
    }

    /**
     * Возвращает список дополнительных вопросов домена с ответами
     *
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getAdditionalQuestions() : array {
        return $this->getQuestions(true);
    }

    /**
     * Возвращает массив вариантов ответов и их значений
     * TODO сделать рефакторинг метода
     *
     * @param int $questionId
     * @param array $municipalitiesId
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getAnswersWithValues(int $questionId, array $municipalitiesId = []) : array {
        Loader::includeModule('iblock');
        $result = CIBlockElement::GetList(
            ['SORT' => 'ASC'],
            ['IBLOCK_CODE' => self::IBLOCK_CODE, 'SECTION_ID' => $questionId],
            false,
            false,
            ['ID', 'NAME']
        );

        $answers = [];
        $answersId = [];
        while ($answer = $result->Fetch()) {
            $answersId[] = $answer['ID'];
            $answers[$answer['ID']] = [
                'txt' => $answer['NAME']
            ];
        }
        $answerValues = $this->getAnswerValue($questionId, $answersId, $municipalitiesId);

        if(!empty($municipalitiesId)) {
            $newArAnswers = [];
            foreach ($answerValues as $answerValue) {
                $newArAnswers[$answerValue['UF_REGION']][] = [
                    'title' => $answers[$answerValue['UF_ANSWER_OPTION']]['txt'],
                    'value' => $answerValue['UF_VALUE'] . '%'
                ];
            }
            $answers = $newArAnswers;
        } else {
            foreach ($answerValues as $answerValue) {
                $answers[$answerValue['UF_ANSWER_OPTION']]['val'] = $answerValue['UF_VALUE'] . '%';
            }
            /* Убираем варианты ответов без значений */
            foreach ($answers as $index => $answer) {
                if(!isset($answer['val'])) {
                    unset($answers[$index]);
                }
            }
            $answers = array_values($answers);
        }

        return $answers;
    }

    /**
     * Возвращает значения вариантов ответа
     *
     * @param int $questionId
     * @param array $answerId
     * @param array $municipalitiesId
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getAnswerValue(int $questionId, array $answerId, array $municipalitiesId = []) : array {
        $entityDataClass = $this->getHighLoadBlockClass(self::TYPE_INDEX_CODE);
        $value = $entityDataClass::getList(array(
            'order' => [],
            'select' => ['UF_REGION', 'UF_VALUE', 'UF_ANSWER_OPTION'],
            'filter' => [
                'UF_REGION' => (!empty($municipalitiesId)) ? $municipalitiesId : $this->regionId,
                'UF_QUESTION' => $questionId,
                'UF_ANSWER_OPTION' => $answerId,
                'UF_YEAR' => $this->year
            ],
        ))->fetchAll();

        return (!empty($value)) ? $value : [];
    }

    /**
     * Возвращает идентификатор субъективного индекса
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