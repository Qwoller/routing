<?php

namespace Ahml\Api\Controllers;

use Ahml\Api\Models\StatisticalIndexModel;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\ActionFilter\ContentType;
use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Response;
use Bitrix\Main\SystemException;

class StatisticalIndexController extends Controller {

    /**
     * Устанавливает фильтры
     *
     * @return ContentType[]
     */
    protected function getDefaultPreFilters() : array {
        return [];
    }

    /**
     * Подключает модель для работы с данными статистического индекса
     *
     * @return ExactParameter[]
     * @throws BinderArgumentException
     */
    public function getAutoWiredParameters() : array {
        return [
            new ExactParameter(
                StatisticalIndexModel::class,
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
     * Возвращает список типов результатов с показателями и их значениями
     *
     * @throws LoaderException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getIndicatorsAction(StatisticalIndexModel $stat) : array {
        return $stat->getResultsWithIndicators();
    }

    /**
     * Возвращает список доменов
     *
     * @param StatisticalIndexModel $stat
     * @return array
     */
    public function getDomainsAction(StatisticalIndexModel $stat) : array {
        return $stat->getDomains();
    }

    /**
     * Возвращает список средних значений индекса
     *
     * @param StatisticalIndexModel $stat
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getAverageValuesAction(StatisticalIndexModel $stat): array {
        return [
            'region' => $stat->getAverageValuesForRegion(),
            'domains' => $stat->getAverageValuesForDomains()
        ];
    }
}