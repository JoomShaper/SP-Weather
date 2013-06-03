<?php
    /*------------------------------------------------------------------------
    # mod_sp_weather - Weather Module by JoomShaper.com
    # ------------------------------------------------------------------------
    # Author    JoomShaper http://www.joomshaper.com
    # Copyright (C) 2010 - 2012 JoomShaper.com. All Rights Reserved.
    # License - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
    # Websites: http://www.joomshaper.com
    -------------------------------------------------------------------------*/
    // http://developer.yahoo.com/weather/
    // no direct access
    defined('_JEXEC') or die('Restricted access');    
    $moduleName             = basename(dirname(__FILE__));
    $moduleID               = $module->id;
    $document               = JFactory::getDocument();
    $cssFile                = JPATH_THEMES. '/'.$document->template.'/css/'.$moduleName.'.css';

    //Include helper.php
    require_once (dirname(__FILE__).'/helper.php');
    $helper 				= new modSPWeatherHelper($params,$moduleID);
    $data                   = $helper->getData();
    $data['forecast']       = $helper->getForecastData();

    if(  is_array( $helper->error() )  )
    {
        JFactory::getApplication()->enqueueMessage( implode('<br /><br />', $helper->error()) , 'error');
    } else {
        if(file_exists($cssFile)) {
            $document->addStylesheet(JURI::base(true) . '/templates/'.$document->template.'/css/'. $moduleName. '.css');
        } else {
            $document->addStylesheet(JURI::base(true) . '/modules/'.$moduleName.'/assets/css/' . $moduleName . '.css');
        }
        require(JModuleHelper::getLayoutPath($moduleName));
	}