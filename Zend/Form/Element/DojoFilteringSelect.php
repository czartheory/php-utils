<?php

namespace CzarTheory\Zend\Form\Element;
/**
 * Description of DojoFilteringSelect
 * Allows placeholder text to go inside of a filteringSelect if empty value is defined
 */
class DojoFilteringSelect extends \Zend_Dojo_Form_Element_FilteringSelect
{
	public function setValue($value)
	{
		$this->setAttrib(' value', $value);
		parent::setValue($value);
	}
}
