<?php

/**
 * @copyright Czar Theory LLC all rights reserved
 */

namespace CzarTheory\Zend\Form\Validator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * Description of UniqueEntityProperty
 *
 * This validator checks to see if a given property of a given Entity-type is unique
 * Validates if a desired property is available.
 */
class UniqueEntityProperty extends \Zend_Validate_Abstract
{

	/** @var EntityRepository */
	protected $_repository;

	/** @var string */
	protected $_propertyName;

	/** @var Entity */
	protected $_entityId;

	const MSG_UNIQUE = 'msgUnique';

	protected $_messageTemplates = array(
		self::MSG_UNIQUE => "'%value%' is already taken. Please choose a different value.",
	);

	/**
	 * Constructor
	 *
	 * @param EntityManager $em the doctrine EntityManager to connect with
	 * @param string $entityName the fully qualified class name of the entity
	 * @param string $propertyName the property name in question
	 * @param string $entityId (optional) the id of an existing entity (when modifying)
	 */
	public function __construct(EntityManager $em, $entityName, $propertyName, $entityId = null)
	{
		$this->_repository = $em->getRepository($entityName);
		$this->_propertyName = $propertyName;
		$this->_entityId = $entityId;
	}

	/**
	 * Sets a custome invalid message
	 * @param string $message the custome message to use when invalid
	 */
	public function setInvalidMessage($message)
	{
		$this->_messageTemplates[self::MSG_UNIQUE] = $message;
	}

	/**
	 * Checks if value is valid.
	 *
	 * If an existing Entity was provided at instanciation, the Entity
	 * will first be checked against the value given. If there's a match,
	 * it will return true (to allow pre-existing unchanged property to validate)
	 *
	 * @param mixed $value
	 * @return boolean true if valid, false if invalid
	 */
	public function isValid($value)
	{
		$this->_setValue($value);

		$existing = $this->_repository->findOneBy(array($this->_propertyName => $value));

		if($existing != null) {
			if($existing->getId() == $this->_entityId) return true;
			$this->_error(self::MSG_UNIQUE);
			return false;
		}
		return true;
	}
}
