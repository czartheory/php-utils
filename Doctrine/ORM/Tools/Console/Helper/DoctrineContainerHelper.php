<?php

namespace CzarTheory\Doctrine\ORM\Tools\Console\Helper;

use Bisna\Application\Container\DoctrineContainer;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Helper\Helper;

/**
 * Doctrine CLI Connection Helper.
 * @author Matthew Larson
 * @copyright 2011 Czar Theory
 */
class DoctrineContainerHelper extends Helper
{
	/**
	 * Doctrine Container
	 * @var DoctrineContainer
	 */
	protected $_doctrine;
	/** @var string */
	protected $_emName;
	/** @var string */
	protected $_connName;

	/**
	 * Constructor
	 * @param Connection $connection Doctrine Database Connection
	 */
	public function __construct(DoctrineContainer $doctrine, $emName = null, $connName = null)
	{
		$this->_doctrine = $doctrine;
		$this->_emName = $emName;
		$this->_connName = $connName;
	}

	/**
	 * Retrieves Doctrine ORM EntityManager
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		return $this->_doctrine->getEntityManager($this->_emName);
	}

	/**
	 * Retrieves Doctrine Database Connection
	 * @return Connection
	 */
	public function getConnection()
	{
		return $this->_doctrine->getConnection($this->_connName);
	}

	/**
	 * Retreives the location of source annotations
	 * @return string
	 */
	public function getAnnotationSource()
	{
		return $this->_doctrine->getAnnotationSource($this->_emName);
	}

	/**
	 * Retreives the location to where destination entities are to be created
	 * @return string
	 */
	public function getEntityDestination()
	{
		return $this->_doctrine->getEntityDestination($this->_emName);
	}

	/**
	 * @see Helper
	 */
	public function getName()
	{
		return 'entityManager';
	}
}
