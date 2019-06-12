<?php
/**
 * @package mod_sp_weather
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2019 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
*/

//no direct accees
defined ('_JEXEC') or die ('Restricted access');

if ( $getdataby == 'locaion_id' && $platform == 'openweathermap' ) {
    $country        = ( isset($data['current']->sys->country) && $data['current']->sys->country ) ? $data['current']->sys->country : '';
    $location       = ( trim($params->get('locationTranslated')) =='' ) ? $data['current']->name .  ', ' . $country : $params->get('locationTranslated');
} else {
    if ($platform == 'apixu') {
        $city       = ( isset($data['current']->sys->name) && $data['current']->sys->name ) ? $data['current']->sys->name : '';
        $country    = ( isset($data['current']->sys->country) && $data['current']->sys->country ) ? $data['current']->sys->country : '';
        $location   = ( trim($params->get('locationTranslated'))=='' ) ? $city .  ', ' . $country : $params->get('locationTranslated');
    } elseif ($platform == 'weatherbit') {
        $city       = ( isset($data['current']->city_name) && $data['current']->city_name ) ? $data['current']->city_name : '';
        $country    = ( isset($data['current']->country_code) && $data['current']->country_code ) ? $data['current']->country_code : '';
        $location   = ( trim($params->get('locationTranslated'))=='' ) ? $city .  ', ' . $country : $params->get('locationTranslated');
    } elseif ($platform == 'darksky') {
        $location   = ( trim($params->get('locationTranslated'))=='' ) ? str_replace('_', ' ', $location) : $params->get('locationTranslated');
    } elseif ($platform == 'yahoo') {
        $city       = ( isset($data['current']->sys->city) && $data['current']->sys->city ) ? $data['current']->sys->city : '';
        $country    = ( isset($data['current']->sys->country) && $data['current']->sys->country ) ? $data['current']->sys->country : '';
        $location   = ( trim($params->get('locationTranslated'))=='' ) ? $city .  ', ' . $country : $params->get('locationTranslated');
    } else { 
        $location   = ( trim($params->get('locationTranslated'))=='' ) ? $params->get('location') : $params->get('locationTranslated');
    }
}

$forecast = ( isset($data['forecast']) && $data['forecast']) ? $data['forecast'] : array();
$data = $data['query']['results']['channel'];

if ($platform == 'apixu') {
    $weather_code = $data['current']->current->condition->code;
} else {
    $weather_code = $data['item']['condition']['code'];
}

?>

<div id="sp-weather-id<?php echo $moduleID; ?>" class="sp-weather<?php echo $moduleclass_sfx; ?> flat-layout">

    <div class="sp-weather-current">
        <div class="media">
            <div class="pull-left">
                <div class="sp-weather-icon">
                <?php if( $platform == 'yahoo' || $platform == 'openweathermap' ) {?>
                    <i class="meteocons-<?php echo $helper->iconFont( $weather_code ); ?>" title="<?php echo $helper->txt2lng($data['item']['condition']['text']); ?>" alt="<?php echo $helper->txt2lng($data['item']['condition']['text']); ?>"></i>
                <?php } else { ?>
                    <?php 
                        if ($platform == 'apixu') {
                            $weather_icon = $data['current']->current->condition->icon;
                        } else {
                            $weather_icon = $helper->icon( $data['item']['condition']['code'] );
                        }
                    ?>
                    <img class="spw_icon_big" src="<?php echo $weather_icon; ?>" title="<?php echo $helper->txt2lng($data['item']['condition']['text']); ?>" alt="<?php echo $helper->txt2lng($data['item']['condition']['text']); ?>" />
                <?php } ?>
                    
                </div>
                <div class="sp-weather-current-temp">
                    <?php if ($params->get('tempUnit')=='f') { ?>
                        <?php echo  $data['item']['condition']['temp']. JText::_('SP_WEATHER_F'); ?>    
                    <?php } else { ?>
                        <?php echo $data['item']['condition']['temp']. JText::_('SP_WEATHER_C'); ?>
                    <?php } ?>
                </div>
            </div>

            <div class="media-body">
                <?php if($params->get('city')==1) { ?>
                <h4 class="media-heading sp-weather-city"><?php echo $location ?></h4> 
                <?php } ?>

                <?php if( ($params->get('condition')) || ($params->get('humidity')) ) { ?>
                <div class="sp-condition-humidity">
                    <?php if($params->get('condition')) { ?>
                    <span class="sp-condition">
                        <?php echo $helper->txt2lng($data['item']['condition']['text']); ?>
                    </span>
                    <?php } ?>
                    <?php if($params->get('humidity')) { ?>
                    <span class="sp-humidity">
                        <?php echo JText::_('SP_WEATHER_HUMIDITY');  ?>: <?php echo $helper->Numeric2Lang($data['atmosphere']['humidity']); ?>%
                    </span>
                    <?php } ?>
                </div>
                <?php } ?>

                <?php if($params->get('wind')==1) { ?>
                    <div class="spw_row">
                        <?php echo JText::_('SP_WEATHER_WIND');  ?>: <?php

                        $compass = array('N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW', 'N');
                        
                        if ($platform == 'apixu') {
                            $data['wind']['direction'] = (isset($data['wind']['direction']) && $data['wind']['direction']) ? $data['wind']['direction'] . JText::_('SP_WEATHER_AT') : '';
                        } else {
                            $data['wind']['direction'] = (isset($data['wind']['direction']) && $data['wind']['direction']) ? $compass[round($data['wind']['direction'] / 22.5)] . JText::_('SP_WEATHER_AT') : '';
                        }

                        echo JText::_($data['wind']['direction']) . $helper->Numeric2Lang($data['wind']['speed']) . ' ' . JText::_(strtoupper($data['units']['speed'])); ?>
                    </div>
                <?php } ?>

            </div>
        </div><!--/.media-->	
    </div><!--/.sp-weather-current-->

    <?php if ($params->get('forecast')!='disabled') { ?>
    <div class="sp-weather-forcasts layout-<?php echo $params->get('tmpl_layout', ''); ?>">
        <?php
        $fcast = (int) $params->get('forecast');
        $j = 1;
        $date = new JDate();
        foreach($forecast as $i=>$value ) {
            if ($platform == 'apixu') {
                $min_temp       = (isset($value->day->mintemp_c) && $value->day->mintemp_c) ? $value->day->mintemp_c : $value->day->avgtemp_c;
                $max_temp       = (isset($value->day->maxtemp_c) && $value->day->maxtemp_c) ? $value->day->maxtemp_c : $value->day->avgtemp_c;
                $raw_date       = $value->date_epoch;
                $weather_date   = $helper->txt2lng(JHtml::date($value->date_epoch, 'D'));
                $weather_icon   = $value->day->condition->icon;
                $weather_code   = $value->day->condition->icon;
                $weather_title  = $value->day->condition->text;
                $weather_desc   = $value->day->condition->text;
                
                if ($params->get('tempUnit')=='f') {
                    $min_temp_converted = $helper->convertUnit( $helper->tempConvert( $min_temp ) , 'f' );
                    $max_temp_converted = $helper->convertUnit( $helper->tempConvert($max_temp) , 'f' );
                } else {
                    $min_temp_converted = $helper->convertUnit( $min_temp , 'c' );
                    $max_temp_converted = $helper->convertUnit( $max_temp, 'c' );
                }
            } elseif ($platform == 'weatherbit') {
                $min_temp       = (isset($value->min_temp) && $value->min_temp) ? $value->min_temp : $value->temp;
                $max_temp       = (isset($value->max_temp) && $value->max_temp) ? $value->max_temp : $value->temp;
                $raw_date       = $value->datetime;
                $weather_date   = $helper->txt2lng(JHtml::date($value->datetime, 'D'));
                $weather_icon   = $helper->icon( $value->weather->icon );
                $weather_title  = $value->weather->description;
                $weather_desc   = $value->weather->description;

                if ($params->get('tempUnit')=='f') {
                    $min_temp_converted = $helper->convertUnit( $helper->tempConvert( $min_temp ) , 'f' );
                    $max_temp_converted = $helper->convertUnit( $helper->tempConvert($max_temp) , 'f' );
                } else {
                    $min_temp_converted = $helper->convertUnit( $min_temp , 'c' );
                    $max_temp_converted = $helper->convertUnit( $max_temp, 'c' );
                }
            } elseif ($platform == 'darksky') {
                $min_temp       = (isset($value->temperatureMin) && $value->temperatureMin) ? $value->temperatureMin : $value->temperatureLow;
                $max_temp       = (isset($value->temperatureMax) && $value->temperatureMax) ? $value->temperatureMax : $value->temperatureHigh;
                $raw_date       = $value->time;
                $weather_date   = $helper->txt2lng(JHtml::date($value->time, 'D'));
                $weather_icon   = $helper->icon( $value->icon );
                $weather_title  = (isset($value->precipType) && $value->precipType) ? $value->precipType : $value->summary;
                $weather_desc   = $value->summary;

                if ($params->get('tempUnit')=='f') {
                    $min_temp_converted = $helper->convertUnit( $min_temp , 'f' );
                    $max_temp_converted = $helper->convertUnit( $max_temp, 'f' );
                } else {
                    $min_temp_converted = $helper->convertUnit( round($helper->tempConvert( $min_temp, 'c' ), 2) , 'c' );
                    $max_temp_converted = $helper->convertUnit( round($helper->tempConvert( $max_temp, 'c' ), 2) , 'c' );
                }
            } elseif ($platform == 'yahoo') {
                $min_temp       = (isset($value->low) && $value->low) ? $value->low : '';
                $max_temp       = (isset($value->high) && $value->high) ? $value->high : '';
                $raw_date       = $value->date;
                $weather_date   = $helper->txt2lng(JHtml::date($value->date, 'D'));
                $weather_code   = $value->code;
                $weather_title  = (isset($value->text) && $value->text) ? $value->text : '';
                $weather_desc   = $value->text;

                if ($params->get('tempUnit')=='f') {
                    $min_temp_converted = $helper->convertUnit( $min_temp , 'f' );
                    $max_temp_converted = $helper->convertUnit( $max_temp, 'f' );
                } else {
                    $min_temp_converted = $helper->convertUnit( round($helper->tempConvert( $min_temp, 'c' ), 2) , 'c' );
                    $max_temp_converted = $helper->convertUnit( round($helper->tempConvert( $max_temp, 'c' ), 2) , 'c' );
                }

            } else {
                $min_temp       = (isset($value->temp->min) && $value->temp->min) ? $value->temp->min : $value->main->temp_min;
                $max_temp       = (isset($value->temp->max) && $value->temp->max) ? $value->temp->max : $value->main->temp_max;
                $raw_date       = $value->dt;
                $weather_date   = $helper->txt2lng(JHtml::date($value->dt, 'D'));
                $weather_code   = $value->weather[0]->icon;
                $weather_title  = $value->weather[0]->main;
                $weather_desc   = $value->weather[0]->description;

                if ($params->get('tempUnit')=='f') {
                    $min_temp_converted = $helper->convertUnit( $helper->tempConvert( $min_temp ) , 'f' );
                    $max_temp_converted = $helper->convertUnit( $helper->tempConvert($max_temp) , 'f' );
                } else {
                    $min_temp_converted = $helper->convertUnit( $min_temp , 'c' );
                    $max_temp_converted = $helper->convertUnit( $max_temp, 'c' );
                }
            }

            // unset today's and less than today's forecast
            if(JHtml::date($date, 'Ymd') >= JHtml::date($raw_date, 'Ymd')) {
                continue;
            }
            
            if($fcast<$j) break;
            if ($params->get('tmpl_layout')=='list') { ?>
                <div class="list list-<?php echo ($i%2 ? 'even' : 'odd') ?>">
                    <div class="media">
                        <div class="pull-left">
                            <div class="sp-weather-icon">
                                <?php if($platform == 'openweathermap' || $platform == 'yahoo') { ?>
                                    <i class="meteocons-<?php echo $helper->iconFont( $weather_code ) ?>" title="<?php echo $helper->txt2lng($weather_title); ?>" alt="<?php echo $helper->txt2lng($weather_desc); ?>"></i>
                                <?php } else { ?>
                                    <img class="spw_icon" src="<?php echo $weather_icon; ?>" align="right" title="<?php echo $helper->txt2lng( $weather_title ); ?>" alt="<?php echo $helper->txt2lng($weather_desc); ?>" />
                                <?php } ?>    
                            </div>
                        </div>

                        <div class="media-body">
                            <div class="sp-weather-day">
                                <?php echo $weather_date; ?>
                            </div>
                            <div class="sp-weather-temp">
                                <?php echo $min_temp_converted . '&nbsp;' . $params->get('separator') . '&nbsp;' . $max_temp_converted; ?>
                            </div>
                        </div>
                    </div>
                </div>				
            <?php } else { ?> 
                <div class="grid grid-<?php echo ($i%2 ? 'even' : 'odd') ?>" style="width:<?php echo round(100/$fcast) ?>%">
                    <div class="media">
                        <div class="pull-left">
                            <div class="sp-weather-icon">
                                <?php if($platform == 'openweathermap' || $platform == 'yahoo') { ?>
                                    <i class="meteocons-<?php echo  $helper->iconFont( $weather_code ) ?>" title="<?php echo $helper->txt2lng( $weather_title); ?>" alt="<?php echo $helper->txt2lng($weather_desc); ?>"></i>
                                <?php } else { ?>
                                    <img class="spw_icon" src="<?php echo $weather_icon; ?>" align="right" title="<?php echo $helper->txt2lng( $weather_title ); ?>" alt="<?php echo $helper->txt2lng($weather_desc); ?>" />
                                <?php }  ?>
                            </div>
                        </div>
                        <div class="media-body">
                            <div class="sp-weather-temp">
                                <?php echo $min_temp_converted . '&nbsp;' . $params->get('separator') . '&nbsp;' . $max_temp_converted; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php $j++; } ?>
    </div>
    <?php } ?>
</div>