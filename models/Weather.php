<?php

namespace app\models;

use linslin\yii2\curl\Curl;
use Yii;
use yii\base\Model;

class Weather extends Model {
    /** @var string */
    public $source;
    
    /** @var string */
    public $productioncenter;
    
    /** @var Forecast */
    public $forecast;

    public $sourceJson;

    public static function getXmlFromBmkg($code = 'SumateraUtara') {
        $url = 'https://data.bmkg.go.id/DataMKG/MEWS/DigitalForecast/DigitalForecast-'.$code.'.xml';

        $curl = new Curl();

        $response = $curl->get($url);

        $xml = @simplexml_load_string($response);

        return $xml;
    }


    public static function getWeatherData($code = 'SumateraUtara') {
        $cache = Yii::$app->cache;

        $weatherJson = $cache->get('weather_json');
        if (!$weatherJson) {
            $weatherJson = json_encode(self::getXmlFromBmkg($code));

            $cache->set('location_json', $weatherJson, 3600);
        }
    

        $weatherArray = @json_decode($weatherJson, true) ?: [];

        $weatherObj = new Weather($weatherArray);
        $weatherObj->sourceJson = $weatherJson;

        return $weatherObj;
    }

    public function __construct($data = [])
    {
        $this->source = @$data['@attributes']['source'];   
        $this->productioncenter = @$data['@attributes']['productioncenter'];

        $this->forecast = new Forecast(@$data['forecast']);
    }

    public function getAttributes($names = null, $except = [])
    {
        return [
            'source' => $this->source,
            'productioncenter' => $this->productioncenter,
            'forecast' => $this->forecast,
        ];
    }

}

class Forecast extends Model {
    /** @var string */
    public $domain;

    /** @var Issue */
    public $issue;

    /** @var Area[] */
    public $areas;

    public function __construct($data) {
        $this->domain = @$data['@attributes']['domain'];
        $this->issue = new Issue(@$data['issue']);
        
        $this->areas = [];
        foreach (@$data['area'] ?: [] as $area) {
            $this->areas[] = new Area($area);
        }
    }

    public function getAttributes($names = null, $except = [])
    {
        return [
            'domain' => $this->domain,
            'issue' => $this->issue,
            'areas' => $this->areas,
        ];
    } 
}

class Issue extends Model {
    /** @var string */
    public $timestamp;

    /** @var int */
    public $year;

    /** @var int */
    public $month;

    /** @var int */
    public $day;

    /** @var int */
    public $hour;

    /** @var int */
    public $minute;

    /** @var int */
    public $second;

    public function __construct($data = [])
    {
        $this->timestamp = @$data['timestamp'];
        $this->year = (int)@$data['year'];
        $this->month = (int)@$data['month'];
        $this->day = (int)@$data['day'];
        $this->hour = (int)@$data['hour'];
        $this->minute = (int)@$data['minute'];
        $this->second = (int)@$data['second'];
    }

    public function getAttributes($names = null, $except = [])
    {
        return [
            'timestamp' => $this->timestamp,
            'year' => $this->year,
            'month' => $this->month,
            'day' => $this->day,
            'hour' => $this->hour,
            'minute' => $this->minute,
            'second' => $this->second,
        ];
    }
}

class Area extends Model {
    /** @var string */
    public $id;

    /** @var float */
    public $latitude;

    /** @var float */
    public $longitude;

    /** @var string */
    public $coordinate;

    /** @var string */
    public $type;

    /** @var string */
    public $region;

    /** @var int */
    public $level;

    /** @var string */
    public $description;

    /** @var string */
    public $domain;

    /** @var string */
    public $tags;

    /** @var string[] */
    public $name;

    /** @var Parameter[] */
    public $parameters;

    
    public function __construct($data = [])
    {
        $this->id = @$data['@attributes']['id'];
        $this->latitude = (float)@$data['@attributes']['latitude'];
        $this->longitude = (float)@$data['@attributes']['longitude'];
        $this->coordinate = @$data['@attributes']['coordinate'];
        $this->type = @$data['@attributes']['type'];
        $this->region = @$data['@attributes']['region'];
        $this->level = (int)@$data['@attributes']['level'];
        $this->description = @$data['@attributes']['description'];
        $this->domain = @$data['@attributes']['domain'];
        $this->tags = @$data['@attributes']['tags'];
        $this->name = @$data['name'];

        $this->parameters = [];
        foreach (@$data['parameter'] ?: [] as $parameter) {
            $this->parameters[] = new Parameter($parameter);
        }
    }

    public function getAttributes($names = null, $except = [])
    {
        return [
            'id' => $this->id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'coordinate' => $this->coordinate,
            'type' => $this->type,
            'region' => $this->region,
            'level' => $this->level,
            'description' => $this->description,
            'domain' => $this->domain,
            'tags' => $this->tags,
            'name' => $this->name,
            'parameters' => $this->parameters,
        ];
    }
}

class Parameter extends Model {
    const TYPE_HUMIDITY = 'hu';
    const TYPE_MIN_HUMIDITY = 'humin';
    const TYPE_MAX_HUMIDITY = 'humax';
    const TYPE_TEMPERATURE = 't';
    const TYPE_MIN_TEMPERATURE = 'tmin';
    const TYPE_MAX_TEMPERATURE = 'tmax';
    const TYPE_WIND_SPEED = 'ws';
    const TYPE_WIND_DIRECTION = 'wd';
    const TYPE_WEATHER = 'weather';

    /** @var string */
    public $id;

    /** @var string */
    public $description;

    /** @var string */
    public $type;

    /** @var HumidityTimerange[]|TemperatureTimerange[]|WindSpeedTimerange[]|WeatherTimerange[]|TemperatureMinMaxTimerange[]|HumidityMinMaxTimerange[]|WindDirectionTimerange[] */
    public $timeranges;

    
    public function __construct($data = [])
    {
        $this->id = @$data['@attributes']['id'];
        $this->description = @$data['@attributes']['description'];
        $this->type = @$data['@attributes']['type'];

        $timerangeType = '';

        switch ($this->id) {
            case self::TYPE_HUMIDITY:
                $timerangeType = HumidityTimerange::class;
                break;
            case self::TYPE_TEMPERATURE:
                $timerangeType = TemperatureTimerange::class;
                break;
            case self::TYPE_WEATHER:
                $timerangeType = WeatherTimerange::class;
                break;
            case self::TYPE_WIND_SPEED:
                $timerangeType = WindSpeedTimerange::class;
                break;
            case self::TYPE_WIND_DIRECTION:
                $timerangeType = WindDirectionTimerange::class;
                break;
            case self::TYPE_MIN_HUMIDITY:
            case self::TYPE_MAX_HUMIDITY:
                $timerangeType = HumidityMinMaxTimerange::class;
                break;
            case self::TYPE_MIN_TEMPERATURE:
            case self::TYPE_MAX_TEMPERATURE:
                $timerangeType = TemperatureMinMaxTimerange::class;
                break;
        }

        $this->timeranges = [];
        if ($timerangeType) {
            foreach (@$data['timerange'] ?: [] as $timerange) {
                $this->timeranges[] = new $timerangeType($timerange);
            }
        }
    }

    public function getAttributes($names = null, $except = [])
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'type' => $this->type,
            'timeranges' => $this->timeranges,
        ];
    }
}

class HumidityTimerange extends Model {
    /** @var string */
    public $type;

    /** @var int */
    public $h;

    /** @var string */
    public $datetime;

    /** @var float */
    public $value;

    public function __construct($data = [])
    {
        $this->type = @$data['@attributes']['type'];
        $this->h = (int)@$data['@attributes']['h'];
        $this->datetime = @$data['@attributes']['datetime'];
        $this->value = (float)@$data['value'];
    }

    public function getAttributes($names = null, $except = [])
    {
        return [
            'type' => $this->type,
            'h' => $this->h,
            'datetime' => $this->datetime,
            'value' => $this->value,
        ];
    }
}

class TemperatureTimerange extends Model {
    /** @var string */
    public $type;

    /** @var int */
    public $h;

    /** @var string */
    public $datetime;

    /** @var float[] */
    public $values;

    public function __construct($data = [])
    {
        $this->type = @$data['@attributes']['type'];
        $this->h = (int)@$data['@attributes']['h'];
        $this->datetime = @$data['@attributes']['datetime'];
        
        $this->values = []; 
        
        foreach (@$data['value'] ?: [] as $value) {
            $this->values[] = (float)$value;
        }
    }

    public function getAttributes($names = null, $except = [])
    {
        return [
            'type' => $this->type,
            'h' => $this->h,
            'datetime' => $this->datetime,
            'values' => $this->values,
        ];
    }
}

class HumidityMinMaxTimerange extends Model {
    /** @var string */
    public $type;

    /** @var string */
    public $day;

    /** @var string */
    public $datetime;

    /** @var float */
    public $value;

    public function __construct($data = [])
    {
        $this->type = @$data['@attributes']['type'];
        $this->day = (int)@$data['@attributes']['day'];
        $this->datetime = @$data['@attributes']['datetime'];
        $this->value = (float)@$data['value'];
    }

    public function getAttributes($names = null, $except = [])
    {
        return [
            'type' => $this->type,
            'day' => $this->day,
            'datetime' => $this->datetime,
            'value' => $this->value,
        ];
    }
}

class TemperatureMinMaxTimerange extends Model {
    /** @var string */
    public $type;

    /** @var string */
    public $day;

    /** @var string */
    public $datetime;

    /** @var float[] */
    public $values;

    public function __construct($data = [])
    {
        $this->type = @$data['@attributes']['type'];
        $this->day = (int)@$data['@attributes']['day'];
        $this->datetime = @$data['@attributes']['datetime'];
        $this->values = [];

        foreach (@$data['value'] ?: [] as $value) {
            $this->values[] = (float)$value;
        }
    }

    public function getAttributes($names = null, $except = [])
    {
        return [
            'type' => $this->type,
            'day' => $this->day,
            'datetime' => $this->datetime,
            'values' => $this->values,
        ];
    }
}

class WeatherTimerange extends Model {
    /** @var string */
    public $type;

    /** @var int */
    public $h;

    /** @var string */
    public $datetime;

    /** @var int */
    public $value;

    public function __construct($data = [])
    {
        $this->type = @$data['@attributes']['type'];
        $this->h = (int)@$data['@attributes']['h'];
        $this->datetime = @$data['@attributes']['datetime'];
        $this->value = (int)@$data['value'];
    }

    public function getAttributes($names = null, $except = [])
    {
        return [
            'type' => $this->type,
            'h' => $this->h,
            'datetime' => $this->datetime,
            'value' => $this->value,
        ];
    }
}

class WindDirectionTimerange extends Model {
    /** @var string */
    public $type;

    /** @var int */
    public $h;

    /** @var string */
    public $datetime;

    /** @var string[] */
    public $values;

    public function __construct($data = [])
    {
        $this->type = @$data['@attributes']['type'];
        $this->h = (int)@$data['@attributes']['h'];
        $this->datetime = @$data['@attributes']['datetime'];
        $this->values = @$data['value']; 
    }

    public function getAttributes($names = null, $except = [])
    {
        return [
            'type' => $this->type,
            'h' => $this->h,
            'datetime' => $this->datetime,
            'values' => $this->values,
        ];
    }
}

class WindSpeedTimerange extends Model {
    /** @var string */
    public $type;

    /** @var int */
    public $h;

    /** @var string */
    public $datetime;

    /** @var float[] */
    public $values;

    public function __construct($data = [])
    {
        $this->type = @$data['@attributes']['type'];
        $this->h = (int)@$data['@attributes']['h'];
        $this->datetime = @$data['@attributes']['datetime'];
        
        $this->values = []; 
        
        foreach (@$data['value'] ?: [] as $value) {
            $this->values[] = (float)$value;
        }
    }

    public function getAttributes($names = null, $except = [])
    {
        return [
            'type' => $this->type,
            'h' => $this->h,
            'datetime' => $this->datetime,
            'values' => $this->values,
        ];
    }
}

