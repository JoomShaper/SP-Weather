<?php
    /*------------------------------------------------------------------------
    # mod_sp_weather - Weather Module by JoomShaper.com
    # ------------------------------------------------------------------------
    # Author    JoomShaper http://www.joomshaper.com
    # Copyright (C) 2010 - 2014 JoomShaper.com. All Rights Reserved.
    # License - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
    # Websites: http://www.joomshaper.com
    -------------------------------------------------------------------------*/

    // no direct access
    defined( '_JEXEC' ) or die( 'Restricted access' );

    $location   = (trim($params->get('locationTranslated'))=='') ? $params->get('location') : $params->get('locationTranslated');
    $forecast = $data['forecast'];
    $data = $data['query']['results']['rss']['channel'];

    ?>
    <div id="sp-weather-id<?php echo $moduleID; ?>" class="sp-weather flat-layout">

        <div class="sp-weather-current">
            <div class="media">
                <div class="pull-left">
                    <div class="sp-weather-icon">
                        <i class="meteocons-<?php echo  $helper->iconFont( $data['item']['condition']['code'] ) ?>" title="<?php echo $helper->txt2lng($data['item']['condition']['text']); ?>" alt="<?php echo $helper->txt2lng($data['item']['condition']['text']); ?>"></i>
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

                    <?php if( ($params->get('condition')) && ($params->get('humidity')) ) { ?>
                    <div class="sp-condition-humidity">
                        <?php if($params->get('humidity')) { ?>
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
                        $data['wind']['direction'] = $compass[round($data['wind']['direction'] / 22.5)];
                        echo JText::_($data['wind']['direction']) . JText::_('SP_WEATHER_AT') . $helper->Numeric2Lang($data['wind']['speed']) . ' ' . JText::_(strtoupper($data['units']['speed'])); ?>
                    </div>
                    <?php } ?>

                </div>
            </div><!--/.media-->	
        </div><!--/.sp-weather-current-->

        <?php if ($params->get('forecast')!='disabled') { ?>
        <div class="sp-weather-forcasts">
            <?php

            $fcast = (int) $params->get('forecast');
            $j = 1;
            unset($forecast[0]);

            foreach($forecast as $i=>$value )
            { 

                if($fcast<$j) break;

                if ($params->get('tmpl_layout')=='list') { ?>
                <div class="list list-<?php echo ($i%2 ? 'even' : 'odd') ?>">

                    <div class="media">
                        <div class="pull-left">
                            <div class="sp-weather-icon">
                                <i class="meteocons-<?php echo  $helper->iconFont( $value['code'] ) ?>" title="<?php echo $helper->txt2lng($value['text']); ?>" alt="<?php echo $helper->txt2lng($value['text']); ?>"></i>
                            </div>
                        </div>
                        <div class="media-body">
                            <div class="sp-weather-day">
                                <?php echo $helper->txt2lng($value['day']); ?>
                            </div>
                            <div class="sp-weather-temp">
                                <?php echo $helper->convertUnit( $value['low'], $data['units']['temperature']) . '&nbsp;' . $params->get('separator') . '&nbsp;' . $helper->convertUnit( $value['high'], $data['units']['temperature']); ?>
                            </div>
                        </div>
                    </div>
                </div>				
                <?php } else { ?> 
                <div class="grid grid-<?php echo ($i%2 ? 'even' : 'odd') ?>" style="width:<?php echo round(100/$fcast) ?>%">
                    <div class="media">
                        <div class="pull-left">
                            <div class="sp-weather-icon">
                                <i class="meteocons-<?php echo  $helper->iconFont( $value['code'] ) ?>" title="<?php echo $helper->txt2lng($value['text']); ?>" alt="<?php echo $helper->txt2lng($value['text']); ?>"></i>
                            </div>
                        </div>
                        <div class="media-body">
                            <div class="sp-weather-temp">
                                <?php echo $helper->convertUnit( $value['low'], $data['units']['temperature']) . '&nbsp;' . $params->get('separator') . '&nbsp;' . $helper->convertUnit( $value['high'], $data['units']['temperature']); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
                <?php
                $j++;
            } ?>
        </div>
        <?php } ?>
    </div>