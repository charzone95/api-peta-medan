<?php

namespace app\models;

use linslin\yii2\curl\Curl;
use Yii;
use yii\base\Model;

class Location extends Model {
    public $id;
    public $nama;
    public $longitude;
    public $latitude;

    public function getAttributes($names = null, $except = [])
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
        ];
    }

    public static function getJsonFromAtcs() {
        $url = 'http://atcsdishub.pemkomedan.go.id/welcome/getDataLokasi?idk=1&idl=';

        $curl = new Curl();

        $response = $curl->get($url);

        return @$response ?: '';
    }

    public static function getLocationList() {
        $cache = Yii::$app->cache;

        $locationJson = $cache->get('location_json');
        if (!$locationJson) {
            $locationJson = self::getJsonFromAtcs();

            $cache->set('location_json', $locationJson, 3600);
        }

        $locationArray = @json_decode($locationJson, true) ?: [];

        $result = [];
        foreach ($locationArray as $locationData) {
            $temp = new self([
                'id' => (int)@$locationData['id_lokasi'],
                'nama' => @trim($locationData['nama_lokasi']),
                'latitude' => (double)@$locationData['lat_lokasi'],
                'longitude' => (double)@$locationData['lon_lokasi'],
            ]);

            $result[] = $temp;
        }

        return $result;
    }
}