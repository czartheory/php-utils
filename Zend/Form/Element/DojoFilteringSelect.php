<?php

namespace CzarTheory\Zend\Form\Element;

/**
 * Description of DojoFilteringSelect
 * Allows placeholder text to go inside of a filteringSelect if empty value is defined
 */
class DojoFilteringSelect extends \Zend_Dojo_Form_Element_FilteringSelect
{

	public function __construct($spec, $options = null)
	{
		$this->setAttrib(' value', '');
		parent::__construct($spec, $options);
	}

	public function setValue($value)
	{
		if (is_array($value)) {
			$value = $value['id'];
		}

		if (is_object($value) && method_exists($value, 'getId')) {
			$value = $value->getId();
		}

		$this->setAttrib(' value', $value);
		parent::setValue($value);
	}

}
