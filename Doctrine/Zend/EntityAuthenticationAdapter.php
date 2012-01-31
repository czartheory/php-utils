<?php

namespace CzarTheory\Doctrine\Zend;

use CzarTheory\Utilities\Cryptography;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class EntityAuthenticationAdapter implements \Zend_Auth_Adapter_Interface
{
	/**
	 * connection to database
	 * @var EntityManager
	 */
	protected $_entityManager = null;

	/**
	 * entityRepository to be used to get entities
	 * @var EntityRepository
	 */
	protected $_entityRepository = null;

	/**
	 * The name of the entity class method which gets the user's username.
	 * @var string
	 */
	protected $_usernameGetMethod = null;

	/**
	 * The name of the entity class property used to identifying the user.
	 * @var string
	 */
	protected $_usernamePropertyName = null;

	/**
	 * The name of the entity class method which gets the user's hashed password.
	 * @var string
	 */
	protected $_passwordGetMethod = null;

	/**
	 * The name of the entity class method which gets the salt for the user's entity.
	 * @var string
	 */
	protected $_saltGetMethod = null;

	/** @var string */
	protected $_username = null;

	/** @var string */
	protected $_password = null;

	/** @var array */
	protected $_authenticateResultInfo = null;

	/** @var Object */
	protected $_resultEntity = null;

	/**
	 * Intitializes the Entity Authentication Adapter
	 * @param EntityManager $entityManager
	 * @param string $entityName The class name of the entity which contains the authentication data.
	 * @param string $usernameGetMethod The
	 * @param string $usernamePropertyName
	 * @param string $passwordGetMethod
	 * @param string $saltGetMethod
	 */
	public function __construct(
				EntityManager $entityManager,
				$entityName,
				$usernameGetMethod,
				$usernamePropertyName,
				$passwordGetMethod,
				$saltGetMethod)
	{
		if (!class_exists($entityName)) {
			throw new \InvalidArgumentException('Could not find a the specified entity class: ' . $entityName);
		}

		if (!method_exists($entityName, $usernameGetMethod)) {
			throw new \InvalidArgumentException('Could not find the username get method for the specified entity class: ' . $entityName . '::' . $usernameGetMethod);
		}

		if (!property_exists($entityName, $usernamePropertyName)) {
			throw new \InvalidArgumentException('Could not find the username property for the specified entity class: ' . $entityName . '::' . $usernamePropertyName);
		}

		if (!method_exists($entityName, $passwordGetMethod)) {
			throw new \InvalidArgumentException('Could not find the password get method for the specified entity class: ' . $entityName . '::' . $passwordGetMethod);
		}

		if (!method_exists($entityName, $saltGetMethod)) {
			throw new \InvalidArgumentException('Could not find the salt get method for the specified entity class: ' . $entityName . '::' . $saltGetMethod);
		}

		$this->_entityManager = $entityManager;
		$this->_entityRepository = $this->_entityManager->getRepository($entityName);
		$this->_usernameGetMethod = $usernameGetMethod;
		$this->_usernamePropertyName = $usernamePropertyName;
		$this->_passwordGetMethod = $passwordGetMethod;
		$this->_saltGetMethod = $saltGetMethod;
	}

	/**
	 * Sets the username to use for authentication.
	 * @param string $username
	 */
	public function setUsername($username)
	{
		$this->_username = $username;
	}

	/**
	 * Sets the password to use for authentication.
	 * @param string $password
	 */
	public function setPassword($password)
	{
		$this->_password = $password;
	}

	/**
	 * Gets the authenticated doctrine entity.
	 * @return Object
	 */
	public function getResultEntity()
	{
		return $this->_resultEntity;
	}

	/**
	 * authenticate() - defined by Zend_Auth_Adapter_Interface.  This method is called to
	 * attempt an authentication.  Previous to this call, this adapter would have already
	 * been configured with all necessary information to successfully connect to a database
	 * table and attempt to find a record matching the provided identity.
	 *
	 * @throws Zend_Auth_Adapter_Exception if answering the authentication query is impossible
	 * @return Zend_Auth_Result
	 */
	public function authenticate()
	{
		$this->_authenticateSetup();
		$authResult = $this->_authenticateCreateAuthResult();
		$entities = $this->_authenticateRetrieveEntities();
		if (!$this->_authenticateValidateResultSet($entities)) {
			$authResult = $this->_authenticateCreateAuthResult();
		} else {
			$authResult = $this->_authenticateValidateResult(array_shift($entities));
		}

		return $authResult;
	}

	/**
	 * Ensures that this instances is properly configured with all required peices of information (i.e. username and password).
	 * @throws \Zend_Auth_Adapter_Exception - in the event that setup was not done properly
	 */
	protected function _authenticateSetup()
	{
		$error = null;

		if (!isset($this->_username)) {
			throw new \Zend_Auth_Adapter_Exception('A value for the username was not provided prior to authentication.');
		}

		if (null === $this->_password) {
			throw new \Zend_Auth_Adapter_Excaption('A password value was not provided prior to authentication.');
		}

		$this->_authenticateResultInfo = array(
			'code' => \Zend_Auth_Result::FAILURE,
			'identity' => $this->_username,
			'messages' => array()
		);
	}

	/**
	 * Searches the database for the user's identity.
	 *
	 * @throws \Zend_Auth_Adapter_Exception when an invalid select object is encountered.
	 * @return array
	 */
	protected function _authenticateRetrieveEntities()
	{
		try {
			$entities = $this->_entityRepository->findBy(array($this->_usernamePropertyName => $this->_username));
		} catch (Exception $e) {
			throw new \Zend_Auth_Adapter_Exception('The supplied entity manager failed to get results from the repository.', 0, $e);
		}

		return $entities;
	}

	/**
	 * _authenticateValidateResultSet() - This method attempts to make certian that only one
	 * record was returned in the result set
	 *
	 * @param array $entities
	 * @return bool
	 */
	protected function _authenticateValidateResultSet(array $entities)
	{
		$count = count($entities);
		$result = false;
		if ($count < 1) {
			$this->_authenticateResultInfo['code'] = \Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;
			$this->_authenticateResultInfo['messages'][] = 'A record with the supplied identity could not be found.';
		} elseif (count($entities) > 1) {
			$this->_authenticateResultInfo['code'] = \Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS;
			$this->_authenticateResultInfo['messages'][] = 'More than one record matches the supplied identity.';
		} else {
			$this->_resultEntity = $entities[0];
			$result = true;
		}

		return $result;
	}

	/**
	 * Validates that the record in the result set is indeed a record that matched the identity provided to this adapter.
	 *
	 * @param Object $entity
	 * @return \Zend_Auth_Result
	 */
	protected function _authenticateValidateResult($entity)
	{
		$getPassword = $this->_passwordGetMethod;
		$getSalt = $this->_saltGetMethod;

		$entitySalt = $entity->$getSalt();
		$entityPassword = $entity->$getPassword();
		$hashedPassword = Cryptography::encryptPassword($this->_password, $entitySalt);
		$entityCredential = $entityPassword;

		if ($hashedPassword != $entityCredential) {
			$this->_authenticateResultInfo['code'] = \Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
			$this->_authenticateResultInfo['messages'][] = 'Supplied credential is invalid.';
		} else {
			$this->_authenticateResultInfo['code'] = \Zend_Auth_Result::SUCCESS;
			$this->_authenticateResultInfo['messages'][] = 'Authentication successful.';
		}

		return $this->_authenticateCreateAuthResult();
	}

	/**
	 * Creates a Zend_Auth_Result object from the information collected in the authentication result info array.
	 * @return \Zend_Auth_Result
	 */
	protected function _authenticateCreateAuthResult()
	{
		return new \Zend_Auth_Result(
						$this->_authenticateResultInfo['code'],
						$this->_authenticateResultInfo['identity'],
						$this->_authenticateResultInfo['messages']
		);
	}
}