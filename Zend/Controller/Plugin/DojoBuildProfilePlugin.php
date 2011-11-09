<?php

namespace CzarTheory\Zend\Controller\Plugin;

class DojoBuildProfilePlugin extends \Zend_Controller_Plugin_Abstract
{
	protected $_buildProfile;
	protected $_build;

	public function dispatchLoopShutdown()
	{
		$this->_buildProfile = APPLICATION_PATH . '/../genscripts/custom.profile.js';
 		$this->generateBuildProfile();
	}

	public function generateBuildProfile()
	{
		$profile = $this->getBuild()->generateBuildProfile();
		file_put_contents($this->_buildProfile, $profile);
	}

	public function getBuild()
	{
		$viewRenderer = \Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
		$viewRenderer->initView();
		if(null === $this->_build) {
			$this->_build = new \Zend_Dojo_BuildLayer(array(
					'view' => $viewRenderer->view,
					'layerName' => 'custom.main',
					'consumeJavascript' => true,
					'consumeOnLoad' => true,
				));
		}
		return $this->_build;
	}
}
