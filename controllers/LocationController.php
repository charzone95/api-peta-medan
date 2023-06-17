<?php

namespace app\controllers;

use app\models\Location;
use linslin\yii2\curl\Curl;
use Yii;
use yii\rest\Controller;
use yii\web\Response;

class LocationController extends Controller {

    public function actionList() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $list = Location::getLocationList();

        return $list;
    }
}