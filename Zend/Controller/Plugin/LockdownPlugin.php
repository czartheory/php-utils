<?php

namespace CzarTheory\Zend\Controller\Plugin;

class LockdownPlugin extends \Zend_Controller_Plugin_Abstract
{

	public function dispatchLoopStartup(\Zend_Controller_Request_Abstract $request)
	{
		$request->setActionName('index');
		$request->setControllerName('index');
	}
}
