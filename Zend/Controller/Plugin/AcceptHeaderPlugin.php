<?php

namespace CzarTheory\Zend\Controller\Plugin;

class AcceptHeaderPlugin extends \Zend_Controller_Plugin_Abstract
{

	public function dispatchLoopStartup(\Zend_Controller_Request_Abstract $request)
	{
		$this->getResponse()->setHeader('Vary', 'Accept');
		$header = $request->getHeader('Accept');
		switch(true) {
			case (strstr($header, 'application/json')):
				$request->setParam('format', 'json');
			break;

			case (strstr($header, 'application/xml') && (!strstr($header, 'html'))):
				$request->setParam('format', 'xml');
			break;
		
			default:
			break;
		}
	}
}
