<?php
    /*------------------------------------------------------------------------
    # mod_sp_weather - Weather Module by JoomShaper.com
    # ------------------------------------------------------------------------
    # Author    JoomShaper http://www.joomshaper.com
    # Copyright (C) 2010 - 2012 JoomShaper.com. All Rights Reserved.
    # License - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
    # Websites: http://www.joomshaper.com
    -------------------------------------------------------------------------*/

    // no direct access
    defined( '_JEXEC' ) or die( 'Restricted access' );

    $location   = (trim($params->get('locationTranslated'))=='') ? $params->get('location') : $params->get('locationTranslated');
    $forecast = $data['forecast'];
    $data = $data['query']['results']['rss']['channel'];

?>
<div id="weather_sp1_id<?php echo $moduleID; ?>" class="weather_sp1">

    <div class="weather_sp1_c">
        <div class="weather_sp1_cleft">
            <img class="spw_icon_big" src="<?php echo  $helper->icon( $data['item']['condition']['code'] ) ?>" title="<?php 
                    echo $helper->txt2lng($data['item']['condition']['text']);
                ?>" alt="<?php echo $helper->txt2lng($data['item']['condition']['text']); ?>" />
            <br style="clear:both" />
            <p class="spw_current_temp">
                <?php if ($params->get('tempUnit')=='f') { ?>
                    <?php echo  $data['item']['condition']['temp']. JText::_('SP_WEATHER_F'); ?>	
                    <?php } else { ?>
                    <?php echo $data['item']['condition']['temp']. JText::_('SP_WEATHER_C'); ?>
                    <?php } ?>
            </p>
        </div>

        <div class="weather_sp1_cright">
            <?php if($params->get('city')==1) { ?>
                <p class="weather_sp1_city"><?php echo $location ?></p> 
                <?php } ?>

            <?php if($params->get('condition')==1) { ?>
                <div class="spw_row"><?php
                    echo $helper->txt2lng($data['item']['condition']['text']); ?></div>
                <?php } ?>

            <?php if($params->get('humidity')==1) { ?>
                <div class="spw_row"><?php echo JText::_('SP_WEATHER_HUMIDITY');  ?>: <?php echo $helper->Numeric2Lang($data['atmosphere']['humidity']); ?>%</div>
                <?php } ?>

            <?php if($params->get('wind')==1) { ?>
                <div class="spw_row"><?php echo JText::_('SP_WEATHER_WIND');  ?>: <?php 

                        $compass = array('N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW', 'N');

                        $data['wind']['direction'] = $compass[round($data['wind']['direction'] / 22.5)];

                    echo JText::_($data['wind']['direction']) . JText::_('SP_WEATHER_AT') . $helper->Numeric2Lang($data['wind']['speed']) . ' ' . JText::_(strtoupper($data['units']['speed'])); ?></div>
                <?php } ?>
        </div>
        <div style="clear:both"></div>		
    </div>

    <div style="clear:both"></div>
    <?php if ($params->get('forecast')!='disabled') { ?>
        <div class="weather_sp1_forecasts">
            <?php

                $fcast = (int) $params->get('forecast');
                $j = 1;
                unset($forecast[0]);

                foreach($forecast as $i=>$value )
                { 

                    if($fcast<$j) break;

                    if ($params->get('layout')=='list') { ?>
                    <div class="list_<?php echo ($i%2 ? 'even' : 'odd') ?>">
                        <span class="weather_sp1_list_day"><?php 
                            echo $helper->txt2lng($value['day']); ?></span>
                        <span class="weather_sp1_list_temp"><?php 
                            echo $helper->convertUnit( $value['low'], $data['units']['temperature']) . '&nbsp;' . $params->get('separator') . '&nbsp;' . $helper->convertUnit( $value['high'], $data['units']['temperature']); ?></span>
                        <span class="weather_sp1_list_icon"><img class="spw_icon" src="<?php 

                                    echo $helper->icon( $value['code'] ); ?>" align="right" title="<?php

                                    echo $helper->txt2lng( $value['text'] );

                                ?>" alt="<?php    echo $helper->txt2lng($value['text']); ?>" /></span>
                        <div style="clear:both"></div>
                    </div>				
                    <?php } else { ?> 
                    <div class="block_<?php echo ($i%2 ? 'even' : 'odd') ?>" style="float:left;width:<?php echo round(100/$fcast) ?>%">
                        <span class="weather_sp1_day"><?php 
                            echo $helper->txt2lng($value['day']); ?></span>
                        <br style="clear:both" />
                        <span class="weather_sp1_icon"><img  class="spw_icon" src="<?php echo $helper->icon( $value['code'] ); ?>" title="<?php 
                                    echo $helper->txt2lng($value['text']);
                                ?>" alt="<?php 
                                    echo $helper->txt2lng($value['text']);
                                ?>" />
                        </span><br style="clear:both" />
                        <span class="weather_sp1_temp"><?php 
                                echo $helper->convertUnit( $value['low'], $data['units']['temperature']) . '&nbsp;' . $params->get('separator') . '&nbsp;' . $helper->convertUnit( $value['high'], $data['units']['temperature']);
                        ?></span>
                        <br style="clear:both" />
                    </div>
                    <?php } ?>
                <?php

                    $j++;
            } ?>
        </div>
        <?php } ?>

    <div style="clear:both"></div>
</div>