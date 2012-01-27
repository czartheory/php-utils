<?php
/*
 * Copyright 2012 Czar Theory, LLC
 * All rights reserved.
 */

namespace CzarTheory\Zend\Form\Decorator;

/**
 * Description of OverallFormErrors
 * 
 * This class is for adding custom form errors to a form.
 * It is a cleaner version of \Zend_Form_Decorator_FormErrors
 * 
 * @author Matthew Larson <matthew@czarTheory.com>
 */
class OverallFormErrors extends \Zend_Form_Decorator_Abstract
{
	/**
	 * Default values for markup options
	 * @var array
	 */
	protected $_options = array(
		'labelTag' => 'h3',
		'listTag' => 'ul',
		'itemTag' => 'li',
		'listClass' => 'form-errors',
		'labelClass' => '',
		'label' => 'This form has errors in it.',
	);

	public function setOptions(array $options)
	{
		$this->_options = array_merge($this->_options, $options);
	}
	
	/**
	 * Render errors
	 *
	 * @param  string $content
	 * @return string
	 */
	public function render($content)
	{
		$form = $this->getElement();
		if(!$form instanceof \Zend_Form) return $content;

		$markup = $this->_prepMessages($form);
		if(empty($markup)) return $content;

		$options = $this->_options;

		$label = $options['label'];
		if(!empty($label)) {
			$labelTag = $options['labelTag'];
			$labelClass = $options['labelClass'];
			if(!empty($labelClass)) $labelClass = ' class="' . $labelClass . '"';
			$label = '<' . $labelTag . $labelClass . '>' . $label . '</' . $labelTag . '>';
		}

		$listTag = $options['listTag'];
		$listClass = $options['listClass'];
		if(!empty($listClass)) $listClass = ' class="' . $listClass . '"';
		$listStart = '<' . $listTag . $listClass . '>';
		$listEnd = '</' . $listTag . '>';

		$markup = $label . $listStart . $markup . $listEnd;

		switch($this->getPlacement()) {
			case self::APPEND:
				return $content . $this->getSeparator() . $markup;
			case self::PREPEND:
				return $markup . $this->getSeparator() . $content;
		}
	}

	/**
	 * Recurse through a form object, rendering errors
	 *
	 * @param  Zend_Form $form
	 * @param  Zend_View_Interface $view
	 * @return string
	 */
	protected function _prepMessages(\Zend_Form $form)
	{
		$content = '';
		$options = $this->_options;
		$itemTag = $options['itemTag'];
		$itemStart = '<' . $itemTag . '>';
		$itemEnd = '</' . $itemTag . '>'; 
		
		$messages = $form->getErrorMessages();
		foreach($messages as $message){
			$content .= $itemStart . $message . $itemEnd;
		}

		return $content;
	}
}
