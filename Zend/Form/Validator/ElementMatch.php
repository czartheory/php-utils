<?php
/**
 * @copyright Czar Theory LLC all rights reserved
 */

namespace CzarTheory\Zend\Form\Validator;

/**
 * Description of ExsistingEntityProperty
 *
 * This validator checks to see if a given property of a given Entity-type is exists
 * Validates if a desired property exists in the database
 */
class ElementMatch extends \Zend_Validate_Abstract
{
	/** @var string */
	protected $_otherElement;

	/** @var EntityRepository */
	const MSG_MATCH = 'msgMatch';

	protected $_messageTemplates = array(
		self::MSG_MATCH => "This value is expected to match another in the form, but it doesn't. Please recheck.",
	);

	/**
	 * Constructor
	 * @param string $elementName the element to match against
	 */
	public function __construct($elementName)
	{
		$this->_otherElement = $elementName;
	}

	/**
	 * Sets a custome invalid message
	 * @param string $message the custome message to use when invalid
	 */
	public function setInvalidMessage($message)
	{
		$this->_messageTemplates[self::MSG_MATCH] = $message;
	}

	/**
	 * Checks if value is valid.
	 *
	 * @param mixed $value
	 * @param array $context
	 * @return boolean true if valid, false if invalid
	 */
	public function isValid($value, $context = null)
	{
		$value = (string) $value;
		$this->_setValue($value);
		
		$matchName = $this->_otherElement;

		if(is_array($context)) {
			if(isset($context[$matchName])
				&& ($value == $context[$matchName])) {
				return true;
			}
		}

		$this->_error(self::MSG_MATCH);
		return false;
	}
}
