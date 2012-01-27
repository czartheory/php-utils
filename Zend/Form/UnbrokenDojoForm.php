<?php
/*
 * Copyright 2012 Czar Theory, LLC
 * All rights reserved.
 */

namespace CzarTheory\Zend\Form;

/**
 * Description of UnbrokenForm
 * 
 * This has minor fixes to Zend_Form:
 * 
 * GetValues returns arrays that are now congruent with
 * expected post data.
 * 
 * @author Matthew Larson <matthew@czarTheory.com>
 */
class UnbrokenDojoForm extends \Zend_Dojo_Form
{

	/**
	 * Retrieve all form element values
	 * 
	 * This function is a near-exact copy of the one
	 * form Zend_Form. All code changed is marked
	 *
	 * @param  bool $suppressArrayNotation
	 * @return array
	 */
	public function getValues($suppressArrayNotation = false)
	{
		$values = array();
		$formBelongTo = null;

		if($this->isArray()) {
			$formBelongTo = $this->getElementsBelongTo();
		}

		foreach($this->getElements() as $key => $element) {
			if(!$element->getIgnore()) {
				$merge = array();
				if(($belongsTo = $element->getBelongsTo()) !== $formBelongTo) {
					if('' !== (string) $belongsTo) {
						$key = $belongsTo . '[' . $key . ']';
					}
				}
				$merge = $this->_attachToArray($element->getValue(), $key);
				$values = $this->_array_replace_recursive($values, $merge);
			}
		}
		foreach($this->getSubForms() as $key => $subForm) {
			$merge = array();
			if(!$subForm->isArray()) {
				//The next line was copied and changed:
				//$merge[$key] = $subForm->getValues();
				$merge = $subForm->getValues();
			} else {
				$merge = $this->_attachToArray($subForm->getValues(true), $subForm->getElementsBelongTo());
			}
			$values = $this->_array_replace_recursive($values, $merge);
		}

		if(!$suppressArrayNotation &&
			$this->isArray() &&
			!$this->_getIsRendered()) {
			$values = $this->_attachToArray($values, $this->getElementsBelongTo());
		}

		return $values;
	}

	/**
	 * Returns only the valid values from the given form input.
	 *
	 * For models that can be saved in a partially valid state, for example when following the builder,
	 * prototype or state patterns it is particularly interessting to retrieve all the current valid
	 * values to persist them.
	 *
	 * This function is a near-exact copy of the one
	 * form Zend_Form. All code changed is marked
	 *
	 * @param  array $data
	 * @param  bool $suppressArrayNotation
	 * @return array
	 */
	public function getValidValues($data, $suppressArrayNotation = false)
	{
		$values = array();
		$eBelongTo = null;

		if($this->isArray()) {
			$eBelongTo = $this->getElementsBelongTo();
			$data = $this->_dissolveArrayValue($data, $eBelongTo);
		}
		$context = $data;
		foreach($this->getElements() as $key => $element) {
			if(!$element->getIgnore()) {
				$check = $data;
				if(($belongsTo = $element->getBelongsTo()) !== $eBelongTo) {
					$check = $this->_dissolveArrayValue($data, $belongsTo);
				}
				if(isset($check[$key])) {
					if($element->isValid($check[$key], $context)) {
						$merge = array();
						if($belongsTo !== $eBelongTo && '' !== (string) $belongsTo) {
							$key = $belongsTo . '[' . $key . ']';
						}
						$merge = $this->_attachToArray($element->getValue(), $key);
						$values = $this->_array_replace_recursive($values, $merge);
					}
					$data = $this->_dissolveArrayUnsetKey($data, $belongsTo, $key);
				}
			}
		}
		foreach($this->getSubForms() as $key => $form) {
			$merge = array();

			//Added the following line. changed $data to $subData within block
			$subData = isset($data[$key]) ? $data[$key] : $data;

			//Copied and changed the line below:
			//if(isset($data[$key]) && !$form->isArray()) {
			if(!$form->isArray()) {

				//Copied and changed the line below:
				//$tmp = $form->getValidValues($data[$key]);
				$tmp = $form->getValidValues($subData);
				if(!empty($tmp)) {
					//Copied and changed the line of code below:
					//$merge[$key] = $tmp;
					$merge = $tmp;
				}
			} else {
				$tmp = $form->getValidValues($subData, true);
				if(!empty($tmp)) {
					$merge = $this->_attachToArray($tmp, $form->getElementsBelongTo());
				}
			}
			$values = $this->_array_replace_recursive($values, $merge);
		}
		if(!$suppressArrayNotation &&
			$this->isArray() &&
			!empty($values) &&
			!$this->_getIsRendered()) {
			$values = $this->_attachToArray($values, $this->getElementsBelongTo());
		}

		return $values;
	}
}
