<?php

use Ahml\Api\Controllers\RegionController;
use Ahml\Api\Controllers\StatisticalIndexController;
use Ahml\Api\Controllers\SubjectiveIndexController;
use Bitrix\Main\Routing\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes->prefix('api/v1')->group(function (RoutingConfigurator $routes) {

        /* установка заголовков для метода options */
        $routes->options('{all}', function () {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, OPTIONS');
        })->where('all', '([a-zA-Z-]|/)+');

        $routes->get('subjective-index/get-questions/', [SubjectiveIndexController::class, 'getQuestions']); // Список вопросов с ответами для субъективного индекса
        $routes->get('statistical-index/get-indicators/', [StatisticalIndexController::class, 'getIndicators']); // Список типов результатов и показателей со значениями для статистического индекса
        $routes->get('region-data/', [RegionController::class, 'getRegionData']); // Средние значения для регионов
        $routes->get('subjective-index/domains/', [SubjectiveIndexController::class, 'getDomains']);
        $routes->get('subjective-index/average-values/', [SubjectiveIndexController::class, 'getAverageValues']);
        $routes->get('statistical-index/domains/', [StatisticalIndexController::class, 'getDomains']);
        $routes->get('statistical-index/average-values/', [StatisticalIndexController::class, 'getAverageValues']);
        $routes->get('municipalities-data/', [SubjectiveIndexController::class, 'getQuestionsForMunicipalities']);
    });
};