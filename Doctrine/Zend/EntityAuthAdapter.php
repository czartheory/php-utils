<?php

/**
 * @see Zend_Auth_Adapter_Interface
 */
require_once 'Zend/Auth/Adapter/Interface.php';

/**
 * @see Zend_Auth_Result
 */
require_once 'Zend/Auth/Result.php';

namespace CzarTheory\Doctrine\Zend;


class EntityAuthAdapter implements Zend_Auth_Adapter_Interface
{
	/**
	 * connection to database
     * @var Doctrine\ORM\EntityManager
     */
	protected $_entityManager = null;

	/**
	 * name of entity to get from manager
	 * @var string
	 */
	protected $_entityName = null;

	/**
	 * entityRepository to be used to get entities
	 * @var Doctrine\ORM\EntityRepository
	 */
	protected $_entityRepository = null;


	/**
	 * name of method to get username
	 * @var string
	 */
	protected $_usernameGetMethod = null;

	/**
	 * name of property to search by username
	 * @var string
	 */
	protected $_usernamePropertyName = null;

	/**
	 * name of method to get password
	 * @var string
	 */
	protected $_passwordGetMethod = null;

	/**
	 * name of method to get password salt
	 * @var string
	 */
	protected $_saltGetMethod = null;

	/**
     * $_username - Identity value
     *
     * @var string
     */
    protected $_username = null;

    /**
     * $_password - password values
     *
     * @var string
     */
    protected $_password = null;


	/**
     * $_authenticateResultInfo
     *
     * @var array
     */
    protected $_authenticateResultInfo = null;

	 /**
		* $_resultEntity
		* @var Object
		*/
	 protected $_resultEntity = null;

	/**
     * __construct() - Sets configuration options
     *
     * @param  \Doctrine\ORM\EntityManager            $enityManager
     * @param  string                   $_passwordTreatment
     * @return void
     */
	public function  __construct(\Doctrine\ORM\EntityManager $entityManager = null,
								 $entityName = null,
								 $usernameGetMethod = null,
								 $usernamePropertyName = null,
								 $passwordGetMethod = null,
								 $saltGetMethod = null)
	{
		if(null !== $entityManager){
			$this->setEnitytManager($entityManager);
		}

		if(null !== $entityName){
			$this->setEntityName($entityName);
		}

		if(null !== $usernameGetMethod){
			$this->setUsernameGetMethod($usernameGetMethod);
		}

		if(null !== $usernamePropertyName){
			$this->setUsernamePropertyName($usernamePropertyName);
		}

		if(null !== $passwordGetMethod){
			$this->setPasswordGetMethod($passwordGetMethod);
		}

		if(null !== $saltGetMethod){
			$this->setSaltGetMethod($saltGetMethod);
		}
	}


    /**
	 * encryptPassword - function for encrypting passwords
	 *
	 * @param string $password
	 * @param string $salt
	 * @return string
	 */
	public static function encryptPassword($password,$salt)
	{
		return crypt($password, $salt);
	}

	/**
	 * generateSalt() - gets a pseudo-random word for pasword salting
	 *
	 * @return string
	 */
	public static function generateSalt()
	{
		$randomSalt = substr(str_replace('+', '.', base64_encode(pack('N4', mt_rand(0,2147483647), mt_rand(0,2147483647), mt_rand(0,2147483647), mt_rand(0,2147483647)))), 0, 22);
		return '$6$rounds=500' . $randomSalt . '$';
	}


    /**
     * getEntityManager() - get the connection to the database
     *
     * @return Doctrine\ORM\EntityManager|null
     */
    public function getEntityManager()
    {
        return $this->_entityManager;
    }


	/**
	 * setEntityManager() - the connection to the database (for the most part)
	 *
	 * @param Doctrine\ORM\EntityManager $entityManager
	 * @return Application_Model_PersonAuthAdapter Provides a fluent interface
	 */
	public function setEnitytManager($entityManager)
	{
		$this->_entityManager = $entityManager;
		return $this;
	}

	/**
	 * sets the name of the entity to be used
	 *
	 * @param string $entityName
	 * @return Application_Model_PersonAuthAdapter
	 */
	public function setEntityName($entityName)
	{
		$this->_entityName = $entityName;
		return $this;
	}

	/**
	 * sets the method name to be used to get a username from the entity
	 *
	 * @param string $usernameGetMethod
	 * @return Application_Model_PersonAuthAdapter
	 */
	public function setUsernameGetMethod($usernameGetMethod)
	{
		$this->_usernameGetMethod = $usernameGetMethod;
		return $this;
	}


	/**
	 * sets the method name to be used to get a username from the entity
	 *
	 * @param string $usernameGetMethod
	 * @return Application_Model_PersonAuthAdapter
	 */
	public function setUsernamePropertyName($usernamePropertyName)
	{
		$this->_usernamePropertyName = $usernamePropertyName;
		return $this;
	}

	/**
	 * setst the method name to be used to get a password from the entity
	 *
	 * @param string $passwordGetMethod
	 * @return Application_Model_PersonAuthAdapter
	 */
	public function setPasswordGetMethod($passwordGetMethod)
	{
		$this->_passwordGetMethod = $passwordGetMethod;
		return $this;
	}

	/**
	 * setst the method name to be used to get a password from the entity
	 *
	 * @param string $saltGetMethod
	 * @return Application_Model_PersonAuthAdapter
	 */
	public function setSaltGetMethod($saltGetMethod)
	{
		$this->_saltGetMethod = $saltGetMethod;
		return $this;
	}


	/**
     * setUsername() - set the value to be used as the identity
     *
     * @param  string $value
     * @return \ZendX_Doctrine_Auth_Adapter Provides a fluent interface
     */
    public function setUsername($value)
    {
        $this->_username = $value;
        return $this;
    }

    /**
     * setPassword() - set the credential value to be used, optionally can specify a treatment
     * to be used, should be supplied in parameterized form, such as 'MD5(?)' or 'PASSWORD(?)'
     *
     * @param  string $password
     * @return ZendX_Doctrine_Auth_Adapter Provides a fluent interface
     */
    public function setPassword($password)
    {
        $this->_password = $password;
        return $this;
    }

		/**
		* getResultEntity() - Returns the doctrine entity that was authenticated
		*
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
        $resultIdentities = $this->_authenticateRetreiveEntities();

        if (($authResult = $this->_authenticateValidateResultset($resultIdentities)) instanceof \Zend_Auth_Result) {
            return $authResult;
        }

        $authResult = $this->_authenticateValidateResult(array_shift($resultIdentities));
        return $authResult;
    }


	/**
     * _authenticateSetup() - This method abstracts the steps involved with making sure
     * that this adapter was indeed setup properly with all required peices of information.
     *
     * @throws \Zend_Auth_Adapter_Exception - in the event that setup was not done properly
     * @return true
     */
    protected function _authenticateSetup()
    {
        $exception = null;

        if ($this->_entityManager === null) {
            $exception = 'An entity manager was not set.';
		} elseif ($this->_entityName === null){
			$exception = 'The Entity Name was not set';
		} elseif ($this->_passwordGetMethod === null){
			$exception = 'The password get method was not set';
		} elseif ($this->_usernameGetMethod === null){
			$exception = 'the username get method was not set';
		} elseif ($this->_saltGetMethod === null){
			$exception = 'the salt get method was not set';
		} elseif ($this->_entityRepository === null){
			$exception = $this->_setupEntityRepository();
        } elseif ($this->_username == '') {
            $exception = 'A value for the username was not provided prior to authentication with ZendX_Doctrine_Auth_Adapter.';
        } elseif ($this->_password === null) {
            $exception = 'A credential value was not provided prior to authentication with ZendX_Doctrine_Auth_Adapter.';
        }

        if (null !== $exception) {
            /**
             * @see Zend_Auth_Adapter_Exception
             */
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new \Zend_Auth_Adapter_Exception($exception);
        }

        $this->_authenticateResultInfo = array(
            'code'     => \Zend_Auth_Result::FAILURE,
            'identity' => $this->_username,
            'messages' => array()
            );

        return true;
    }

	/**
	 * sets up the entity repository and checks to see if all get methods are available
	 *
	 * @return string
	 */
	protected function _setupEntityRepository()
	{
		$this->_entityRepository = $this->_entityManager->getRepository($this->_entityName);
		if($this->_entityRepository === null) return "entity repository $this->_entityRepository could not be found.";

		if(method_exists($this->_entityName, $this->_usernameGetMethod) != true) return "username get method $this->_usernameGetMethod could not be found.";
		if(method_exists($this->_entityName, $this->_passwordGetMethod) != true) return "password get method $this->_passwordGetMethod could not be found.";
		if(method_exists($this->_entityName, $this->_saltGetMethod) != true) return "salt get method $this->_saltGetMethod could not be found.";

		return null;
	}


	/**
     * _authenticateQuerySelect() - This method searches the database for identities
     *
     * @throws Zend_Auth_Adapter_Exception - when a invalid select object is encoutered
     * @return array
     */
    protected function _authenticateRetreiveEntities()
    {
      try {
			$resultIdentities = $this->_entityRepository->findBy(array($this->_usernamePropertyName  => $this->_username));
		} catch (Exception $e) {
            /**
             * @see \Zend_Auth_Adapter_Exception
             */
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new \Zend_Auth_Adapter_Exception('The supplied entity manager failed to get results from the repository. MESSAGE: ' . $e->getMessage());
        }
        return $resultIdentities;
    }

	/**
     * _authenticateValidateResultSet() - This method attempts to make certian that only one
     * record was returned in the result set
     *
     * @param array $resultIdentities
     * @return true|Zend_Auth_Result
     */
    protected function _authenticateValidateResultSet(array $resultIdentities)
    {


        if (count($resultIdentities) < 1) {
            $this->_authenticateResultInfo['code'] = \Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;
            $this->_authenticateResultInfo['messages'][] = 'A record with the supplied identity could not be found.';
            return $this->_authenticateCreateAuthResult();
        } elseif (count($resultIdentities) > 1) {
            $this->_authenticateResultInfo['code'] = \Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS;
            $this->_authenticateResultInfo['messages'][] = 'More than one record matches the supplied identity.';
            return $this->_authenticateCreateAuthResult();
        }

        return true;
    }


    /**
     * _authenticateValidateResult() - This method attempts to validate that the record in the
     * result set is indeed a record that matched the identity provided to this adapter.
     *
     * @param Object $resultIdentity
     * @return Zend_Auth_Result
     */
    protected function _authenticateValidateResult($resultIdentity)
    {
		$getPassword = $this->_passwordGetMethod;
		$getSalt = $this->_saltGetMethod;

		$entitySalt = $resultIdentity->$getSalt();
		$entityPassword = $resultIdentity->$getPassword();
		$hashedPassword = Application_Model_PersonAuthAdapter::encryptPassword($this->_password, $entitySalt);
		$entityCredential = $entitySalt . $entityPassword;


        if ($hashedPassword != $entityCredential) {
            $this->_authenticateResultInfo['code'] = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
            $this->_authenticateResultInfo['messages'][] = 'Supplied credential is invalid.';
            return $this->_authenticateCreateAuthResult();
        }

		  //--- leftover from Zend_Auth_Adapter_DbTable.php. Not sure what it does!
        //unset($resultIdentity['zend_auth_credential_match']);
        $this->_resultEntity = $resultIdentity;

        $this->_authenticateResultInfo['code'] = \Zend_Auth_Result::SUCCESS;
        $this->_authenticateResultInfo['messages'][] = 'Authentication successful.';
        return $this->_authenticateCreateAuthResult();
    }



    /**
     * _authenticateCreateAuthResult() - This method creates a Zend_Auth_Result object
     * from the information that has been collected during the authenticate() attempt.
     *
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

