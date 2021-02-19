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
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\ModuleHelper;

$layout                 = $params->get('layout', 'default');
$moduleName             = basename(dirname(__FILE__));
$moduleID               = $module->id;
$document               = Factory::getDocument();
$api_key                = $params->get('api_key', '');
$platform               = $params->get('platform', 'openweathermap');
$getdataby              = $params->get('getdataby', 'locaion_name');
$forecast_limit         = $params->get('forecast', '7');
$moduleclass_sfx        = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');


if( $platform == 'apixu' ){
    Factory::getApplication()->enqueueMessage(Text::_('MOD_SPWEATHER_APIKEY_'. strtoupper($platform) .'_DESC'), 'error');
    return false;
}

if( $api_key == '' && $platform == 'darksky' ){
    Factory::getApplication()->enqueueMessage(Text::_('MOD_SPWEATHER_APIKEY_'. strtoupper($platform) .'_DESC'), 'warning');
    return false;
}

// if not API KEY throw error 
if( $api_key == '' && $platform != 'yahoo' ){
    $html = '<p class="alert alert-warning">' . Text::_('MOD_SPWEATHER_APIKEY_'. strtoupper($platform) .'_DESC') .'</p>';
    echo $html;
    return false;
}

// if not API KEY throw error 
if( $platform == 'openweathermap' && $getdataby != 'latlon' && $forecast_limit != 'disabled' ){
    $html = '<p class="alert alert-warning">' . Text::_('MOD_SPWEATHER_ERROR_LOCATION_NOT_ALLOWED') .'</p>';
    echo $html;
    return false;
}

//Include helper.php
require_once (dirname(__FILE__).'/helper.php');

$helper     = new modSPWeatherHelper($params,$moduleID);
$data       = $helper->getData();

if($data['status']) {
    //backward compatibility
    $data['query']['results']['channel'] = $data;
    if ($platform == 'weatherbit') {
        $location              = $data['current']->data[0]->city_name;
        $data['current']       = $data['current']->data[0];
        
        //backward compatibility
        $data['query']['results']['channel']['item']['condition']['text'] = $data['current']->weather->description;
        $data['query']['results']['channel']['item']['condition']['code'] = $data['current']->weather->icon;
        $data['query']['results']['channel']['atmosphere']['humidity'] = $data['current']->rh;
        $data['query']['results']['channel']['units']['speed'] = Text::_('SP_WEATHER_WIND_SPEED_UNIT_MS');
        $data['query']['results']['channel']['wind']['speed'] = round($data['current']->wind_spd, 2);
        $data['query']['results']['channel']['wind']['direction'] = (isset($data['current']->wind_dir) && $data['current']->wind_dir) ? $data['current']->wind_dir : '';
    } elseif ($platform == 'darksky') {
        $location              = count((array)explode('/', $data['current']->timezone)) ? str_replace('/', ', ', $data['current']->timezone): $data['current']->timezone;
        $data['current']       = $data['current']->currently;

        // //backward compatibility
        $data['query']['results']['channel']['item']['condition']['text'] = $data['current']->summary;
        $data['query']['results']['channel']['item']['condition']['code'] = $data['current']->icon;
        $data['query']['results']['channel']['atmosphere']['humidity'] = $data['current']->humidity;
        $data['query']['results']['channel']['units']['speed'] = Text::_('SP_WEATHER_WIND_SPEED_UNIT_MS');
        $data['query']['results']['channel']['wind']['speed'] = round($data['current']->windSpeed, 2);
        $data['query']['results']['channel']['wind']['direction'] = (isset($data['current']->windBearing) && $data['current']->windBearing) ? $data['current']->windBearing : '';
    } elseif ($platform == 'yahoo') {   
        $location              = $data['current']->location;
        $data['current']       = $data['current']->current;
        $data['current']->sys  = $location;
        
        //backward compatibility
        $data['query']['results']['channel']['item']['condition']['text'] = $data['current']->condition->text;
        $data['query']['results']['channel']['item']['condition']['code'] = $data['current']->condition->code;
        $data['query']['results']['channel']['atmosphere']['humidity'] = $data['current']->atmosphere->humidity;
        $data['query']['results']['channel']['units']['speed'] = Text::_('SP_WEATHER_WIND_SPEED_KPH');
        $data['query']['results']['channel']['wind']['speed'] = round($data['current']->wind->speed, 2);
        $data['query']['results']['channel']['wind']['direction'] = (isset($data['current']->wind->direction) && $data['current']->wind->direction) ? $data['current']->wind->direction : '';
    } else {
        if ($getdataby != 'latlon')
        {
            //backward compatibility
            $data['query']['results']['channel']['item']['condition']['text'] = $data['current']->weather[0]->description;
            $data['query']['results']['channel']['item']['condition']['code'] = $data['current']->weather[0]->icon;
            $data['query']['results']['channel']['atmosphere']['humidity'] = $data['current']->main->humidity;
            $data['query']['results']['channel']['units']['speed'] = Text::_('SP_WEATHER_WIND_SPEED_UNIT_MS');
            $data['query']['results']['channel']['wind']['speed'] = round($data['current']->wind->speed, 2);
            $data['query']['results']['channel']['wind']['direction'] = (isset($data['current']->wind->deg) && $data['current']->wind->deg) ? $data['current']->wind->deg : '';
        }
        else
        {
            $data['query']['results']['channel']['item']['condition']['text'] = $data['current']->weather[0]->description;
            $data['query']['results']['channel']['item']['condition']['code'] = $data['current']->weather[0]->icon;
            $data['query']['results']['channel']['atmosphere']['humidity'] = $data['current']->humidity;
            $data['query']['results']['channel']['units']['speed'] = Text::_('SP_WEATHER_WIND_SPEED_UNIT_MS');
            $data['query']['results']['channel']['wind']['speed'] = round($data['current']->wind_speed, 2);
            $data['query']['results']['channel']['wind']['direction'] = (isset($data['current']->wind_deg) && $data['current']->wind_deg) ? $data['current']->wind_deg : '';
        }
    }
    
    if ($params->get('tempUnit')=='f') {
        if ($platform == 'weatherbit') {
            $data['query']['results']['channel']['item']['condition']['temp'] = $helper->tempConvert($data['current']->temp, 'f');
        } elseif ($platform == 'darksky') {
            $data['query']['results']['channel']['item']['condition']['temp']  = $data['current']->main->temp;
        } elseif ($platform == 'yahoo') {
            $data['query']['results']['channel']['item']['condition']['temp']  = $data['current']->condition->temperature;
        } else {
            if ($getdataby != 'latlon')
            {
                $data['query']['results']['channel']['item']['condition']['temp']  = $helper->tempConvert($data['current']->main->temp, 'f');
            }
            else
            {
                $data['query']['results']['channel']['item']['condition']['temp']  = $helper->tempConvert($data['current']->temp, 'f');
            }
        }
    } else {
        if ($platform == 'weatherbit') {
            $data['query']['results']['channel']['item']['condition']['temp'] = $data['current']->temp;
        } elseif ($platform == 'darksky') {
            $data['query']['results']['channel']['item']['condition']['temp'] = round($helper->tempConvert($data['current']->temperature, 'c'), 2);
        } elseif ($platform == 'yahoo') {
            $data['query']['results']['channel']['item']['condition']['temp'] = round($helper->tempConvert($data['current']->condition->temperature, 'c'), 2);
        } else {
            if ($getdataby != 'latlon')
            {
                $data['query']['results']['channel']['item']['condition']['temp']  = $data['current']->main->temp;
            }
            else
            {
                $data['query']['results']['channel']['item']['condition']['temp']  = $data['current']->temp;
            }
        }
    }
    
    if ($params->get('forecast')!='disabled') {
        if ($platform == 'weatherbit') {
            $data['forecast']->sys = new stdClass();
            $data['forecast']->sys->city_name = $data['forecast']->city_name;
            $data['forecast']->sys->lon = $data['forecast']->lon;
            $data['forecast']->sys->country_code = $data['forecast']->country_code;
            $data['forecast']->sys->country_code = $data['forecast']->country_code;
            $data['forecast'] = (array)$data['forecast']->data;
        } elseif ($platform == 'darksky') {
            $data['forecast']->sys = new stdClass();
            $data['forecast']->sys->city_name = str_replace('/', ', ', $data['forecast']->timezone);
            $data['forecast'] = (array)$data['forecast']->daily->data;
        } elseif ($platform == 'yahoo') {
            $data['forecast'] = $data['forecast']->forecasts;
        } else {
            $data['forecast'] = (array)$data['forecast']->daily;
        }
    }
} else {
    return false;
}

if ( $platform ) {
    if ($platform == 'weatherbit') {
         if ( (!empty($data['current'] && !count((array)$data['current'])) ) || $data['status'] !== true) {
            echo Text::sprintf('MOD_SPWEATHER_ERROR_CANNOT_GET_LOCATION_ERROR', $params->get('location'), $moduleName);
            return false;
        }
    } elseif ($platform == 'darksky') {
        if ( (!empty($data['current'] && !count((array)$data['current'])) ) || $data['status'] !== true) {
            echo Text::sprintf('MOD_SPWEATHER_ERROR_CANNOT_GET_LOCATION_ERROR', $params->get('location'), $moduleName);
            return false;
        }
    } elseif ($platform == 'yahoo') {
        if ( (!empty($data['current'] && !count((array)$data['current'])) ) || $data['status'] !== true) {
            echo Text::sprintf('MOD_SPWEATHER_ERROR_CANNOT_GET_LOCATION_ERROR', $params->get('location'), $moduleName);
            return false;
        }
    } else {
        if ( (isset($data['current']->main) && !empty($data['current']->main && !count((array)$data['current']->main)) ) || $data['status'] !== true) {
            echo Text::sprintf('MOD_SPWEATHER_ERROR_CANNOT_GET_LOCATION_ERROR', $params->get('location'), $moduleName);
            return false;
        }
        else if ( (isset($data['current']) && !empty($data['current'] && !count((array)$data['current'])) ) || $data['status'] !== true)
        {
            echo Text::sprintf('MOD_SPWEATHER_ERROR_CANNOT_GET_LOCATION_ERROR', $params->get('location'), $moduleName);
            return false;
        }
    }
}

if ( ($layout == '_:default') ) {
    $document->addStylesheet(Uri::base(true) . '/modules/'.$moduleName.'/assets/css/' . $moduleName . '.css');
} else {
    $document->addStylesheet(Uri::base(true) . '/modules/'.$moduleName.'/assets/css/flat.css');
}

require(ModuleHelper::getLayoutPath($moduleName, $layout));

