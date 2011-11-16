<?php

namespace CzarTheory\Services;

use Doctrine\ORM\EntityManager;

/**
 * Manages a local reference to a Doctrine EntityManager.
 */
abstract class AbstractEntityManagerService
{
	/** @var EntityManager */
	protected $_em = null;

	/**
	 * Initializes the interally used entity manager and then calls calls {@link _init()}.
	 *
	 * @param  EntityManager|string|null $entityManagerOrName <b>(Optional)</b> An existing entity manager,
	 * the name of the desired entity manager, or null.
	 * @throws InvalidArgumentException If <var>$entityManagerOrName</var> is not null, a string, or and instance of EntityManager.
	 */
	public function __construct($entityManagerOrName = null)
	{
		$this->_initEntityManager($entityManagerOrName);
	}

	/**
	 * Sets the entity manager using the optionally specified name or EntityManager instance.
	 *
	 * @param  EntityManager|string|null $entityManagerOrName <b>(Optional)</b> An existing entity manager,
	 * the name of the desired entity manager, or null.
	 * @return EntityManager The constructed entity manager.
	 * @throws InvalidArgumentException If <var>$entityManagerOrName</var> is not null, a string, or and instance of EntityManager.
	 */
	protected function _initEntityManager($entityManagerOrName)
	{
		if($this->_em === null)
		{
			if (null === $entityManagerOrName || is_string($entityManagerOrName))
			{
				$doctrine = \Zend_Registry::get('doctrine');
				$this->_em = $doctrine->getEntityManager($entityManagerOrName);
			}
			elseif ($entityManagerOrName instanceof EntityManager)
			{
				$this->_em = $entityManagerOrName;
			}
			else
			{
				throw new \InvalidArgumentException('EntityManager instance, null, or entity manager name required.');
			}
		}
	}
}
