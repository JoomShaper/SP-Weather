<?php
/**
 * @package mod_sp_weather
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2021 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
*/

//no direct accees
defined ('_JEXEC') or die ('Restricted access');

use Joomla\CMS\Date\Date;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

if ( $getdataby == 'locaion_id' && $platform == 'openweathermap' ) {
    $country = ( isset($data['current']->sys->country) && $data['current']->sys->country ) ? $data['current']->sys->country : '';
    $location   = (trim($params->get('locationTranslated')) =='') ? $data['current']->name .  ', ' . $country : $params->get('locationTranslated');
} else {
    if ($platform == 'weatherbit') {
        $city      = ( isset($data['current']->city_name) && $data['current']->city_name ) ? $data['current']->city_name : '';
        $country    = ( isset($data['current']->country_code) && $data['current']->country_code ) ? $data['current']->country_code : '';
        $location   = (trim($params->get('locationTranslated'))=='') ? $city .  ', ' . $country : $params->get('locationTranslated');
    } elseif ($platform == 'darksky') {
        $location   = (trim($params->get('locationTranslated'))=='') ? str_replace('_', ' ', $location) : $params->get('locationTranslated');
    } elseif ($platform == 'yahoo') {
        $city      = ( isset($data['current']->sys->city) && $data['current']->sys->city ) ? $data['current']->sys->city : '';
        $country    = ( isset($data['current']->sys->country) && $data['current']->sys->country ) ? $data['current']->sys->country : '';
        $location   = (trim($params->get('locationTranslated'))=='') ? $city .  ', ' . $country : $params->get('locationTranslated');
    } else { 
        $location   = (trim($params->get('locationTranslated'))=='') ? $params->get('location') : $params->get('locationTranslated');
    }
}

$forecast = ( isset($data['forecast']) && $data['forecast']) ? $data['forecast'] : array();
$data = $data['query']['results']['channel'];

?>
    <div id="weather_sp1_id<?php echo $moduleID; ?>" class="weather_sp1<?php echo $moduleclass_sfx; ?> platform-<?php echo $platform; ?>">

        <div class="weather_sp1_c">
            <div class="weather_sp1_cleft">
                <?php 
                    
                    $weather_icon = $helper->icon( $data['item']['condition']['code'] );
                    
                ?>
                <img class="spw_icon_big" src="<?php echo $weather_icon; ?>" title="<?php echo $helper->txt2lng($data['item']['condition']['text']); ?>" alt="<?php echo $helper->txt2lng($data['item']['condition']['text']); ?>" />

                <br style="clear:both" />
                <p class="spw_current_temp">
                    <?php if ($params->get('tempUnit')=='f') { ?>
                        <?php echo $data['item']['condition']['temp'] . Text::_('SP_WEATHER_F'); ?>	
                    <?php } else { ?>
                        <?php echo $data['item']['condition']['temp'] . Text::_('SP_WEATHER_C'); ?>
                    <?php } ?>
                </p>
            </div>
            
            <div class="weather_sp1_cright">
                <?php if($params->get('city')==1) { ?>
                    <p class="weather_sp1_city">
                        <?php echo $location ?>
                    </p> 
                <?php } ?>

                <?php if($params->get('condition')==1) { ?>
                <div class="spw_row">
                    <?php echo $helper->txt2lng($data['item']['condition']['text']); ?>
                </div>
                <?php } ?>

                <?php if($params->get('humidity')==1) { ?>
                    <div class="spw_row">
                        <?php echo Text::_('SP_WEATHER_HUMIDITY');  ?>: <?php echo $helper->Numeric2Lang($data['atmosphere']['humidity']); ?>%
                    </div>
                <?php } ?>

                <?php if($params->get('wind')==1) { ?>
                    <div class="spw_row"><?php echo Text::_('SP_WEATHER_WIND');  ?>: <?php 
                        
                        $compass = array('N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW', 'N');
                        $data['wind']['direction'] =  (isset($data['wind']['direction']) && $data['wind']['direction']) ? $compass[round($data['wind']['direction'] / 22.5)] . Text::_('SP_WEATHER_AT') : '';
                       

                        echo Text::_($data['wind']['direction']) . $helper->Numeric2Lang($data['wind']['speed']) . ' ' . Text::_(strtoupper($data['units']['speed'])); ?>
                    </div>
                <?php } ?>
            </div> <!-- /.weather_sp1_cright -->

            <div style="clear:both"></div>		
        </div> <!-- /.weather_sp1_c -->

        <div style="clear:both"></div>
        <?php if ($params->get('forecast')!='disabled') { ?>
            <div class="weather_sp1_forecasts layout-<?php echo $params->get('tmpl_layout', ''); ?>" style="display: flex; flex-wrap: wrap; flex-direction: row;">
                <?php
                $fcast = (int) $params->get('forecast');
                $j = 1;
                $date = new Date();

                //unset($forecast[0]);
                foreach($forecast as $i=>$value ) {
                    if ($platform == 'weatherbit') {
                        $min_temp       = (isset($value->min_temp) && $value->min_temp) ? $value->min_temp : $value->temp;
                        $max_temp       = (isset($value->max_temp) && $value->max_temp) ? $value->max_temp : $value->temp;
                        $raw_date       = $value->datetime;
                        $weather_date   = $helper->txt2lng(HTMLHelper::date($value->datetime, 'D'));
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
                        $weather_date   = $helper->txt2lng(HTMLHelper::date($value->time, 'D'));
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
                        $weather_date   = $helper->txt2lng(HTMLHelper::date($value->date, 'D'));
                        $weather_icon   = $helper->icon( $value->code );
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
                        $weather_date   = $helper->txt2lng(HTMLHelper::date($value->dt, 'D'));
                        $weather_icon   = $helper->icon( $value->weather[0]->icon );
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

                    if(HTMLHelper::date($date, 'Ymd') >= HTMLHelper::date($raw_date, 'Ymd')) {
                        continue;
                    }

                    if($fcast<$j) break;
                    if ($params->get('tmpl_layout')=='list') { ?>
                        <div class="list_<?php echo ($i%2 ? 'even' : 'odd') ?>">
                            <span class="weather_sp1_list_day">
                                <?php echo $weather_date; ?>
                            </span>
                            <span class="weather_sp1_list_temp">
                                <?php echo $min_temp_converted . '&nbsp;' . $params->get('separator') . '&nbsp;' . $max_temp_converted; ?>
                            </span>

                            <span class="weather_sp1_list_icon">
                                <img class="spw_icon" src="<?php echo $weather_icon; ?>" align="right" title="<?php echo $helper->txt2lng( $weather_title ); ?>" alt="<?php echo $helper->txt2lng($weather_desc); ?>" />
                            </span>
                            <div style="clear:both"></div>
                        </div>
                        <?php } else { ?> 
                            <div class="block_<?php echo ($i%2 ? 'even' : 'odd') ?>" >
                                <span class="weather_sp1_day">
                                    <?php echo $weather_date; ?>
                                </span>
                                <br style="clear:both" />
                                <span class="weather_sp1_icon">
                                    <img  class="spw_icon" src="<?php echo $weather_icon; ?>" title="<?php echo $helper->txt2lng( $weather_title ); ?>" alt="<?php echo $helper->txt2lng( $weather_desc ); ?>" />
                                </span>
                                <br style="clear:both" />
                                <span class="weather_sp1_temp">
                                    <?php echo $min_temp_converted . '&nbsp;' . $params->get('separator') . '&nbsp;' . $max_temp_converted; ?>
                                </span> 
                            <br style="clear:both" />
                        </div>
                    <?php } ?>
                <?php $j++; } ?>
            </div>
        <?php } ?>

    <div style="clear:both"></div>
</div>