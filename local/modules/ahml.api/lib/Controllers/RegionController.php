<?php

namespace Ahml\Api\Controllers;

use Ahml\Api\Models\RegionModel;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\ActionFilter\ContentType;
use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Response;
use Bitrix\Main\SystemException;

class RegionController extends Controller {

    /**
     * Устанавливает фильтры
     *
     * @return ContentType[]
     */
    protected function getDefaultPreFilters() : array {
        return [];
    }

    /**
     * Подключает модель для работы с данными регионов
     *
     * @return ExactParameter[]
     * @throws BinderArgumentException
     */
    public function getAutoWiredParameters() : array {
        return [
            new ExactParameter(
                RegionModel::class,
                'region',
                function(string $className, string $name) {
                    return $className::byCode($name);
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
     * Возвращает данные для вывода в карту регионов на главной
     *
     * @param RegionModel $region
     * @return array
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getRegionDataAction(RegionModel $region) : array {
        return [
            'name' => $region->getName(),
            'link' => $region->getLink(),
            'sub' => $region->getSubjectiveIndexAverageValues(),
            'stat' => $region->getStatisticalIndexAverageValues()
        ];
    }
}