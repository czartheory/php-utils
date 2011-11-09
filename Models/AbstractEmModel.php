<?php
namespace CzarTheory\Models;
use Doctrine\ORM\EntityManager;

/**
 * Manages a local reference to a Doctrine EntityManager.
 */
abstract class AbstractEmModel
{
	/** @var EntityManager */
	protected $_em = null;
	
	/**
	 * Initializes the interally used entity manager and then calls calls {@link _init()}.
	 *
	 * @param string $emName (Optional) The name of the desired entity manager.
	 */
	public function __construct($emName = null)
	{
		$this->_initEntityManager($emName);
		$this->_init();
	}

	/** Optional hook for child classes to extend constructor. */
	protected function _init(){}

	/**
	 * sets the entity manager using the optionally specified name.
	 *
	 * @param   string  $emName (Optional) The name of the desired entity manager.
	 * @return  EntityManager The constructed entity manager.
	 */
	protected function _initEntityManager($emName)
	{
		if($this->_em === null) {
			$doctrine = \Zend_Registry::get('doctrine');
			$this->_em = $doctrine->getEntityManager($emName);
		}
	}
}
