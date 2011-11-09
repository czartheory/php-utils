<?php

namespace CzarTheory\Zend\Controller\Plugin;

class ModularLayoutPlugin extends \Zend_Controller_Plugin_Abstract
{

	public function routeShutdown(\Zend_Controller_Request_Abstract $request)
	{
		$layout = \Zend_Layout::getMvcInstance();
		$module = strtolower($request->getModuleName());

		$layout->setLayoutPath(APPLICATION_PATH . '/modules/' . $module . '/layouts/scripts/');
		$layout->setLayout($module);

		$view = \Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');
		$view->headLink()->appendStylesheet("/styles/$module.css");
	}
}
