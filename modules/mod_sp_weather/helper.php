<?php
/**
 * @package mod_sp_weather
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2021 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
*/

//no direct accees
defined ('_JEXEC') or die ('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\Folder;

class modSPWeatherHelper {
    
    private $results = array('status'=> false);
    private $errors  = false;
    private $location;
    private $forecast_limit;
    private $getdataby;
    private $locaion_id;
    private $location_latlon;
    private $api_key;
    private $y_appid;
    private $y_clientid;
    private $y_clientsecret;
    private $platform;
    private $params;
    private $moduleID;
    private $moduledir;
    private $api;
    private $cache_time;
    private $nightIDs = array(27,29,31,33);
    private $iconURL = 'https://openweathermap.org/img/w/%s.png';
    
    /**
    * Init Class Params
    * 
    * @param object $params
    * @param int $id
    */
    public function __construct($params, $id) {
        
        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.folder');
        $this->params           = $params;
        $this->moduleID         = $id;
        $this->moduledir        = basename(dirname(__FILE__));
        $this->location         = str_replace(', ', ',', $this->params->get('location', 'London,GB'));
        $this->forecast_limit   = $this->params->get('forecast', '7');
        $this->getdataby        = $this->params->get('getdataby', 'locaion_name');
        $this->locationid       = $this->params->get('locationid', '2643743');
        $this->location_latlon  = $this->params->get('location_latlon', '48.139130, 11.580220');
        $this->location_ip      = $this->params->get('location_ip', '88.198.50.103');
        $this->platform         = $this->params->get('platform', 'openweathermap');
        $this->cache_time       = $this->params->get('cacheTime', '900');
        $this->api_key          = $this->params->get('api_key', '');
        // yahoo
        $this->y_appid        = $this->params->get('y_app_id', '3vYYEy30');
        $this->y_clientid     = $this->params->get('y_client_id', 'dj0yJmk9NFZaM2lMQUpvWUlpJmQ9WVdrOU0zWlpXVVY1TXpBbWNHbzlNQS0tJnM9Y29uc3VtZXJzZWNyZXQmeD02ZQ--');
        $this->y_clientsecret = $this->params->get('y_client_secret', 'f72fcfd3cbda626223778eb01af31f2cb1e2cfa9');

        //icon
        if( $this->platform ==  'weatherbit' ) {
            $this->iconURL = 'https://www.weatherbit.io/static/img/icons/%s.png';
        } elseif( $this->platform ==  'darksky' ) {
            $this->iconURL = 'https://darksky.net/images/weather-icons/%s.png';
        } elseif( $this->platform ==  'yahoo' ) {
            $this->iconURL = 'https://s.yimg.com/os/mit/media/m/weather/images/icons/l/%d%s-100567.png';
        }

        // get current data
        $this->results['current']  = $this->_getWeatherData('current');

        // load current
        if($data_decode = json_decode($this->results['current'])) {
            if ($this->platform == 'weatherbit') {
                if (isset($data_decode->data) && count((array)$data_decode->data)) {
                    $this->results['status'] = true;
                    $this->results['current'] = $data_decode;
                }
            } elseif ($this->platform == 'darksky') {
                if (isset($data_decode->currently) && count((array)$data_decode->currently)) {
                    $this->results['status'] = true;
                    $this->results['current'] = $data_decode;
                }
            } elseif ($this->platform == 'yahoo') {
                if (isset($data_decode->current) && count((array)$data_decode->current)) {
                    $this->results['status'] = true;
                    $this->results['current'] = $data_decode;
                }
            } else {
                if (isset($data_decode->main) && count((array)$data_decode->main)) {
                    $this->results['status'] = true;
                    $this->results['current'] = $data_decode;
                }
                else
                {
                    $this->results['status'] = true;
                    $this->results['current'] = $data_decode->current;
                }
            }
        } else {
            $this->throwError('CANNOT_DECODE_CURRENT_DATA');
        }
        
        // get forecast
        if($this->forecast_limit != 'disabled') { // if forecast is enable
            $this->results['forecast']  = $this->_getWeatherData('forecast');
            if($forecast_decode = json_decode($this->results['forecast']))  {
                if ($this->platform == 'weatherbit') {
                    if( count((array)$forecast_decode->data) && $forecast_decode->data ) {
                        $this->results['forecast_status'] = true;
                        $this->results['forecast'] = (object) $forecast_decode;
                    } else {
                        $this->throwError('CANNOT_FIND_FORECAST_DATA');
                    }
                } elseif ($this->platform == 'darksky') {
                    if( count((array)$forecast_decode->daily->data) && $forecast_decode->daily->data ) {
                        $this->results['forecast_status'] = true;
                        $this->results['forecast'] = (object) $forecast_decode;
                    } else {
                        $this->throwError('CANNOT_FIND_FORECAST_DATA');
                    }
                } elseif ($this->platform == 'yahoo') {
                    if( count((array)$forecast_decode->forecasts) && $forecast_decode->forecasts ) {
                        $this->results['forecast_status'] = true;
                        $this->results['forecast'] = (object) $forecast_decode;
                    } else {
                        $this->throwError('CANNOT_FIND_FORECAST_DATA');
                    }
                } else {
                    if( count((array)$forecast_decode->daily) && $forecast_decode->daily ) {
                        $this->results['forecast_status'] = true;
                        $this->results['forecast'] = (object) $forecast_decode;
                    } else {
                        $this->throwError('CANNOT_FIND_FORECAST_DATA');
                    }
                }
            } else {
                $this->throwError('CANNOT_DECODE_FORECAST_DATA');
            }
        }

    }

    //Get Weather data
    private function _getWeatherData($type = 'current') {
        if($type == 'forecast') {
            $this->forecast_limit +=1;
            if ($this->platform == 'weatherbit') {
                if($this->getdataby == 'latlon') {
                    $location_latlon = explode(',', str_replace(', ', ',', $this->location_latlon));
                    $this->api = 'https://api.weatherbit.io/v2.0/forecast/daily?lat='. $location_latlon[0] .'&lon='. $location_latlon[1] .'&units=m&days='. $this->forecast_limit .'&key=' . $this->api_key;
                } elseif($this->getdataby == 'ip') {
                    $this->api = 'https://api.weatherbit.io/v2.0/forecast/daily?ip='. $this->location_ip .'&units=m&days='. $this->forecast_limit .'&key=' . $this->api_key;
                } else {
                    $this->api = 'https://api.weatherbit.io/v2.0/forecast/daily?city='. $this->location .'&units=m&days='. $this->forecast_limit .'&key=' . $this->api_key;
                }
            } elseif ($this->platform == 'darksky') {
                $this->api  = 'https://api.darksky.net/forecast/' . $this->api_key .'/'. $this->location_latlon .'?exclude=currently,flags,hourly,minutely&lang=bn';
            } elseif ($this->platform == 'yahoo') {
                $this->api  = 'https://weather-ydn-yql.media.yahoo.com/forecastrss';      
            } else {
                if ($this->getdataby == 'latlon')
                {
                    $location_latlon = explode(',', str_replace(', ', ',', $this->location_latlon));
                    $this->api  = 'https://api.openweathermap.org/data/2.5/onecall?lat='. $location_latlon[0] .'&lon='. $location_latlon[1] .'&exclude=current,minutely,hourly,alerts&units=metric&appid=' . $this->api_key;
                }
            }
        } else {
            if ($this->platform == 'weatherbit') {
                if($this->getdataby == 'latlon') {
                    $location_latlon = explode(',', str_replace(', ', ',', $this->location_latlon));
                    $this->api  = 'https://api.weatherbit.io/v2.0/current?lat='. $location_latlon[0] .'&lon='. $location_latlon[1] .'&key=' . $this->api_key;
                } elseif($this->getdataby == 'ip') {
                    $this->api  = 'https://api.weatherbit.io/v2.0/current?ip='. $this->location_ip .'&key=' . $this->api_key;
                } else {
                    $this->api  = 'https://api.weatherbit.io/v2.0/current?city='. $this->location .'&key=' . $this->api_key;
                }
            } elseif ($this->platform == 'darksky') {
                $this->api  = 'https://api.darksky.net/forecast/' . $this->api_key .'/'. $this->location_latlon .'?exclude=daily,flags,hourly,minutely';
            } elseif ($this->platform == 'yahoo') {
                $this->api  = 'https://weather-ydn-yql.media.yahoo.com/forecastrss';
            } else {
                if($this->getdataby == 'locaion_id') {
                    $this->api       = 'http://api.openweathermap.org/data/2.5/weather?id='. $this->locationid .'&units=metric&appid=' . $this->api_key;
                } 
                elseif ($this->getdataby == 'latlon')
                {
                    $location_latlon = explode(',', str_replace(', ', ',', $this->location_latlon));
                    $this->api  = 'https://api.openweathermap.org/data/2.5/onecall?lat='. $location_latlon[0] .'&lon='. $location_latlon[1] .'&exclude=minutely,hourly,daily,alerts&units=metric&appid=' . $this->api_key;
                }
                else {
                    $this->api  = 'https://api.openweathermap.org/data/2.5/weather?q='. $this->location .'&units=metric&appid=' . $this->api_key;
                }
            }   
        }
        
        $results['data'] = array();
        // check cache dir or create cache dir
        $cache_path = JPATH_CACHE.'/'.$this->moduledir;
        if (!Folder::exists($cache_path)){
            Folder::create(JPATH_CACHE.'/'.$this->moduledir.'/'); 
        }
        
        if ($type == 'forecast') { // if data is forecast
            $cache_file = JPATH_CACHE.'/'.$this->moduledir.'/'.$this->moduleID.'-'.'forecast.json';
        } else { // if data is current weather
            $cache_file = JPATH_CACHE.'/'.$this->moduledir.'/'.$this->moduleID.'-'.'current.json';
        }

        // check cache file is exist and time isn't over:: default time is: 30 mins
        if (file_exists($cache_file) && (filemtime($cache_file) > (time() - 60 * $this->cache_time ))) {
            $results['data'] =  file_get_contents($cache_file);
		} else {
            if($this->platform == 'yahoo') {
                $results['data'] = self::getYahooWeatherData();
                $data_decode = json_decode($results['data']);
                
                if($data_decode) {
                    if ($type == 'forecast') {
                        $forecast_data = array();
                        $forecast_data['location']  = $data_decode->location;
                        $forecast_data['forecasts'] = $data_decode->forecasts;
                        $results['data'] = json_encode($forecast_data);
                    } else {
                        $current_data = array();
                        $current_data['location']   = $data_decode->location;
                        $current_data['current']    = $data_decode->current_observation;
                        $results['data']            = json_encode($current_data);
                    }
                } else {
                    $this->throwError($results['data'],'',true);
                    return false;
                }
                
            } else {
                if( ini_get('allow_url_fopen') ) {
                    $results['data'] = @file_get_contents($this->api);
                    try {
                        if($results['data'] === FALSE){
                            $this->throwError('MAKESURE_FOPEN_OR_LCOATION', $this->platform);
                        }
                    } catch (Exception $ex) {
                        $this->throwError('MAKESURE_FOPEN_OR_LCOATION', $this->platform);
                    }
                } else {
                    $results['data'] = $this->curl($this->api);
                }   
            }

            if( isset($results['data']) && !empty($results['data']) && count((array)$results['data']) ) {
                file_put_contents($cache_file, $results['data'], LOCK_EX);
            }
        }

        return $results['data'];
    }

    // *** **** Yahoo **** *** //
    protected function buildBaseString($baseURI, $method, $params) {
        $r = array();
        ksort($params);
        foreach($params as $key => $value) {
            $r[] = "$key=" . rawurlencode($value);
        }
        return $method . "&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
    }
    protected function buildAuthorizationHeader($oauth) {
        $r = 'Authorization: OAuth ';
        $values = array();
        foreach($oauth as $key=>$value) {
            $values[] = "$key=\"" . rawurlencode($value) . "\"";
        }
        $r .= implode(', ', $values);
        return $r;
    }

    protected function getYahooWeatherData( ) {
        $query = array(
            'location' => $this->location,
            'format' => 'json',
        );
        $oauth = array(
            'oauth_consumer_key' => $this->y_clientid,
            'oauth_nonce' => uniqid(mt_rand(1, 1000)),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0'
        );
        $base_info = self::buildBaseString($this->api, 'GET', array_merge($query, $oauth));
        
        $composite_key = rawurlencode($this->y_clientsecret) . '&';
        $oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
        $oauth['oauth_signature'] = $oauth_signature;
        $header = array(
            self::buildAuthorizationHeader($oauth),
            'X-Yahoo-App-Id: ' . $this->y_appid
        );
        $options = array(
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_HEADER => false,
            CURLOPT_URL => $this->api . '?' . http_build_query($query),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        );
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        curl_close($ch);
        $return_data = json_decode($response);

        return $response;
    }
    // *** **** END::yahoo **** *** //
    

    // Get Curl data
    protected function curl($url) {
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	    $data = curl_exec($ch);
        curl_close($ch);
        
	    return $data;
    }
    
    /**
    * Convert numeric number to language
    * 
    * @param int | string $number
    * @return language formatted text
    */
    public function Numeric2Lang($number, $prefix = 'SP_') {
        $number = (array) str_split($number);
        $formated = '';
        foreach($number as $no) {
            if (ctype_digit($no)) {
                $formated.=Text::_($prefix . $no);    
            } else $formated.=$no;
        }
        return $formated;
    }


    /**
    * Weather condition text converter
    * 
    * @param string $text
    * @return string
    */
    public function txt2lng($text) {
        $trans = array(" " => "_", "/" => "_", "(" => "", ')'=>'');
        $text = strtr($text, $trans);
        return Text::_('SP_WEATHER_'.strtoupper($text));
    }

    /**
    * Convert temparature
    * 
    * @param mixed $value
    * @param mixed $unit
    * @param mixed $tempType
    */
    public function convertUnit($value, $unit) {    
        $txt  = $this->Numeric2Lang($value);
        $txt .= ( strtolower($unit)=='c') ? Text::_('SP_WEATHER_'. 'C') : Text::_('SP_WEATHER_'. 'F');
        return $txt;
    }    

    /**
    * weather condition to icon file name
    * 
    * @param mixed $icon
    * @param mixed $path
    */
    public function icon($condition) {
        if($this->platform == 'yahoo') {
            $condition = (int) $condition;
            $at = in_array($condition, $this->nightIDs, true)?'n':'d';
            $icon =  sprintf($this->iconURL,$condition,$at);
            return  $icon;
        } else {
            return sprintf($this->iconURL, $condition);
        }
    } 

    /**
    * weather condition to icon font
    * 
    * @param mixed $icon
    * @param mixed $path
    */
    public function iconFont($condition = '') {
        if($this->platform ==  'openweathermap') {
            $night          = (strpos($condition, 'n') !== false) ?'-night':'';
            $cond_number    = (int)substr($condition, 0, -1);
            $fontIcon       = array(
                "0"     => 'other',
                "1"     => 'sunny',
                "2"     => 'cloudy',
                "3"     => 'mostly-cloudy',
                "4"     => 'partly-cloudy',
                "9"     => 'chance-of-storm',
                "10"    => 'rain',
                "11"    => 'thunderstorm', 
                "13"    => 'snow',
                "50"    => 'foggy',
            );
            return $fontIcon[$cond_number] . $night;
        } elseif($this->platform == 'yahoo') {
            $night      = in_array($condition, $this->nightIDs, true)?'-night':'';
            $fontIcon   = array(
                "0"     => 'other',
                "1"     => 'storm',
                "2"     => 'storm',
                "3"     => 'chance-of-storm',
                "4"     => 'thunderstorm',          
                "5"     => 'rain-and-snow',
                "6"     => 'sleet',
                "7"     => 'sleet',     
                "8"     => 'rain',    
                "9"     => 'rain',     
                "10"    => 'rain',
                "11"    => 'rain',
                "12"    => 'rain',
                "13"    => 'chance-of-snow',                               
                "14"    => 'snow',
                "15"    => 'snow',
                "16"    => 'snow',
                "17"    => 'chance-of-storm',  
                "18"    => 'rain',
                "19"    => 'dusty',
                "20"    => 'foggy',
                "21"    => 'hazy',
                "22"    => 'smoke',
                "23"    => 'cloudy',
                "24"    => 'cloudy',      
                "25"    => 'snow',
                "26"    => 'cloudy',
                "27"    => 'mostly-cloudy',
                "28"    => 'mostly-cloudy',
                "29"    => 'partly-cloudy',
                "30"    => 'partly-cloudy',
                "31"    => 'sunny',
                "32"    => 'sunny',
                "33"    => 'sunny',
                "34"    => 'partly-cloudy',
                "35"    => 'thunderstorm',
                "36"    => 'sunny',
                "37"    => 'thunderstorm',
                "38"    => 'chance-of-storm',
                "39"    => 'chance-of-storm',
                "40"    => 'rain',
                "41"    => 'snow',
                "42"    => 'snow',
                "43"    => 'snow',
                "44"    => 'partly-cloudy',
                "45"    => 'chance-of-storm',
                "46"    => 'chance-of-snow',
                "47"    => 'chance-of-storm',
                "3200"  => 'other'
            );

            return $fontIcon[$condition] . $night;
        } 
        // elseif($this->platform == 'weatherbit') {
        //     echo '<pre>';
        //     print_r($condition);
        //     echo '</pre>';die();
        // }
        
    }

    /**
    * Run function to load data from source
    * @return string
    */
    public function getData() {
        return $this->results;
    }

    // convert temperature 
    public function tempConvert($value, $convert_type = 'f') {
        if($convert_type == 'f') { // convert celsius to fahrenheit (f for fahrenheit)
            return $value * 1.8 + 32;
        } else { // convert fahrenheit to celsius
            return ($value - 32) / 1.8;
        }
    }

    // throw common error
    public function throwError($message = 'COMMON', $platform = 'YAHOO', $custom_msg = false) {
        if(!$this->errors) {
            $this->errors = true;
            $this->results['status'] = false;
            $this->results['message']  = '';
            if($platform) {
                $platform_error = Text::_('MOD_SPWEATHER_ERROR_PLATFORM_'. strtoupper($this->platform));
            }
            if ($message == 'INSERT_API_KEY') {
                $this->results['message'] .= '<p class="alert alert-warning">' . Text::_('MOD_SPWEATHER_ERROR_'. $message) .'</p>';
            } elseif($custom_msg) {
                $this->results['message'] .= '<p class="alert alert-warning">' . $message .'</p>';
            } else {
                $this->results['message'] .= '<p class="alert alert-warning">' . Text::_('MOD_SPWEATHER_ERROR_'. $message) . ' ' . Text::_('MOD_SPWEATHER_ERROR_LOCATION_ERROR') . ' '. $platform_error .'</p>';
            }   
            echo $this->results['message'];
        }
    }

}
