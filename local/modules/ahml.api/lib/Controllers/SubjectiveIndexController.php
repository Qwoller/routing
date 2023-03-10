<?php

namespace Ahml\Api\Controllers;

use Ahml\Api\Models\SubjectiveIndexModel;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\ActionFilter\ContentType;
use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Response;
use Bitrix\Main\SystemException;

class SubjectiveIndexController extends Controller {

    /**
     * Устанавливает фильтры
     *
     * @return ContentType[]
     */
    protected function getDefaultPreFilters() : array {
        return [];
    }

    /**
     * Подключает модель для работы с данными субъективного индекса
     *
     * @return ExactParameter[]
     * @throws BinderArgumentException
     */
    public function getAutoWiredParameters() : array {
        return [
            new ExactParameter(
                SubjectiveIndexModel::class,
                'stat',
                function(string $className, int $regionId, int $domainId, int $year) {
                    return $className::getObject($regionId, $domainId, $year);
                }
            )
        ];
    }

    /**
     * Устанавливает заголовки ответа
     *
     * @param Response $response
     * @return void
     */
    public function finalizeResponse(Response $response) {
        header('Access-Control-Allow-Origin: *');
    }

    /**
     * Возвращает списки главных и дополнительных вопросов с ответами
     *
     * @param SubjectiveIndexModel $stat
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getQuestionsAction(SubjectiveIndexModel $stat) : array {
        return [
            'main' => $stat->getMainQuestions(),
            'sub' => $stat->getMainQuestions(),
        ];
    }

    /**
     * Возвращает список доменов
     *
     * @param SubjectiveIndexModel $stat
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getDomainsAction(SubjectiveIndexModel $stat) : array {
        return $stat->getDomains();
    }

    /**
     * Возвращает список средних значений индекса
     *
     * @param SubjectiveIndexModel $stat
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getAverageValuesAction(SubjectiveIndexModel $stat): array {
        return [
            'region' => $stat->getAverageValuesForRegion(),
            'domains' => $stat->getAverageValuesForDomains()
        ];
    }

    /**
     * Возвращает списки главных и дополнительных вопросов с ответами для муниципалитетов
     *
     * @param SubjectiveIndexModel $stat
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getQuestionsForMunicipalitiesAction(SubjectiveIndexModel $stat) : array {
        return [
            'main' => $stat->getMainQuestionsForMunicipalities(),
            'sub' => $stat->getAdditionalQuestionsForMunicipalities(),
        ];
    }
}