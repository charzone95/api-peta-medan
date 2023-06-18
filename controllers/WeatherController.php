<?php

namespace app\controllers;

use app\models\Weather;
use linslin\yii2\curl\Curl;
use Yii;
use yii\rest\Controller;
use yii\web\Response;

class WeatherController extends Controller {

    public function actionIndex() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $weather = Weather::getWeatherData();

        return $weather;
    }
}