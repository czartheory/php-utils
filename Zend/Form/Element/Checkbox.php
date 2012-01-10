<?php
/*
 * Copyright 2011 Czar Theory, LLC
 * All rights reserved.
 */

namespace CzarTheory\Zend\Form\Element;

/**
 * Description of Checkbox
 * @author Matthew Larson <matthew@czarTheory.com>
 */
class Checkbox extends \Zend_Form_Element
{
	/**
	 * Is the checkbox checked?
	 * @var bool
	 */
	public $checked = false;

	/**
	 * Value when checked
	 * @var string
	 */
	protected $_checkedValue = '1';

	/**
	 * Value when not checked
	 * @var string
	 */
	protected $_uncheckedValue = '0';

	/**
	 * Current value
	 * @var string 0 or 1
	 */
	protected $_value = '0';

	public function __construct($spec, $options = null)
	{
		parent::__construct($spec, $options);
		$filter = new \Zend_Filter_Boolean();
		$filter->setType(\Zend_Filter_Boolean::ALL);
		$this->addFilter($filter);
	}
	
	/**
	 * Set options
	 *
	 * Intercept checked and unchecked values and set them early; test stored
	 * value against checked and unchecked values after configuration.
	 *
	 * @param  array $options
	 * @return Zend_Form_Element_Checkbox
	 */
	public function setOptions(array $options)
	{
		if(array_key_exists('checkedValue', $options)) {
			$this->setCheckedValue($options['checkedValue']);
			unset($options['checkedValue']);
		}
		if(array_key_exists('uncheckedValue', $options)) {
			$this->setUncheckedValue($options['uncheckedValue']);
			unset($options['uncheckedValue']);
		}
		parent::setOptions($options);

		$curValue = $this->getValue();
		$test = array($this->getCheckedValue(), $this->getUncheckedValue());
		if(!in_array($curValue, $test)) {
			$this->setValue($curValue);
		}

		return $this;
	}

	/**
	 * Set value
	 *
	 * If value matches checked value, sets to that value, and sets the checked
	 * flag to true.
	 *
	 * Any other value causes the unchecked value to be set as the current
	 * value, and the checked flag to be set as false.
	 *
	 *
	 * @param  mixed $value
	 * @return Zend_Form_Element_Checkbox
	 */
	public function setValue($value)
	{
		if($value == $this->getCheckedValue()) {
			parent::setValue($value);
			$this->checked = true;
		} else {
			parent::setValue($this->getUncheckedValue());
			$this->checked = false;
		}
		return $this;
	}

	/**
	 * Set checked value
	 * @param  string $value
	 * @return Zend_Form_Element_Checkbox
	 */
	public function setCheckedValue($value)
	{
		$this->_checkedValue = (string) $value;
		$this->options['checkedValue'] = $value;
		return $this;
	}

	/**
	 * Get value when checked
	 * @return string
	 */
	public function getCheckedValue()
	{
		return $this->_checkedValue;
	}

	/**
	 * Set unchecked value
	 * @param  string $value
	 * @return Zend_Form_Element_Checkbox
	 */
	public function setUncheckedValue($value)
	{
		$this->_uncheckedValue = (string) $value;
		$this->options['uncheckedValue'] = $value;
		return $this;
	}

	/**
	 * Get value when not checked
	 * @return string
	 */
	public function getUncheckedValue()
	{
		return $this->_uncheckedValue;
	}

	/**
	 * Set checked flag
	 *
	 * @param  bool $flag
	 * @return Zend_Form_Element_Checkbox
	 */
	public function setChecked($flag)
	{
		$this->checked = (bool) $flag;
		if($this->checked) {
			$this->setValue($this->getCheckedValue());
		} else {
			$this->setValue($this->getUncheckedValue());
		}
		return $this;
	}

	/**
	 * Get checked flag
	 * @return bool
	 */
	public function isChecked()
	{
		return $this->checked;
	}

	/**
	 * Load default decorators
	 *
	 * @return void
	 */
	public function loadDefaultDecorators()
	{
		if($this->loadDefaultDecoratorsIsDisabled()) {
			return;
		}

		$decorators = $this->getDecorators();
		if(empty($decorators)) {
			$this->addDecorator(new DijitElement())
				->addDecorator('Errors')
				->addDecorator('Description', array('tag' => 'p', 'class' => 'description'))
				->addDecorator('Label', array('tag' => 'span', 'placement'=>'APPEND'))
				->addDecorator('HtmlTag', array('tag' => 'dd'));
		}
	}

	/**
	 * Set the view object
	 *
	 * Ensures that the view object has the dojo view helper path set.
	 *
	 * @param  Zend_View_Interface $view
	 * @return Zend_Dojo_Form_Element_Dijit
	 */
	public function setView($view = null)
	{
		if(null !== $view) {
			if(false === $view->getPluginLoader('helper')->getPaths('Zend_Dojo_View_Helper')) {
				$view->addHelperPath('Zend/Dojo/View/Helper', 'Zend_Dojo_View_Helper');
			}
		}
		return parent::setView($view);
	}
}

class DijitElement extends \Zend_Form_Decorator_ViewHelper
{
	/**
	 * Element attributes
	 * @var array
	 */
	protected $_attribs;

	/**
	 * Dijit option parameters
	 * @var array
	 */
	protected $_dijitParams = array();

	/**
	 * Get element attributes
	 * @return array
	 */
	public function getElementAttribs()
	{
		if(null === $this->_attribs) {
			$this->_attribs = parent::getElementAttribs();
			if(array_key_exists('dijitParams', $this->_attribs)) {
				$this->setDijitParams($this->_attribs['dijitParams']);
				unset($this->_attribs['dijitParams']);
			}
		}

		return $this->_attribs;
	}

	/**
	 * Get dijit option parameters
	 *
	 * @return array
	 */
	public function getDijitParams()
	{
		$this->getElementAttribs();
		return $this->_dijitParams;
	}

	/**
	 * Retrieve a single dijit option parameter
	 *
	 * @param  string $key
	 * @return mixed|null
	 */
	public function getDijitParam($key)
	{
		$this->getElementAttribs();
		$key = (string) $key;
		if(array_key_exists($key, $this->_dijitParams)) {
			return $this->_dijitParams[$key];
		}

		return null;
	}

	public function render($content)
	{
		$element = $this->getElement();
		$view = $element->getView();
		if(null === $view) {
			throw new \Zend_Form_Decorator_Exception('DijitElement decorator cannot render without a registered view object');
		}

		$options = null;
		$separator = $this->getSeparator();
		$attribs = $this->getElementAttribs();
		$name = $element->getFullyQualifiedName();

		$dijitParams = $this->getDijitParams();
		$dijitParams['required'] = $element->isRequired();
		$dijitParams['dojoType'] = 'czarTheory.dijits.CheckBox';
		$dijitParams['rootNode'] = 'input';

		$id = $element->getId();
		if($view->dojo()->hasDijit($id)) {
			trigger_error(sprintf('Duplicate dijit ID detected for id "%s; temporarily generating uniqid"', $id), E_USER_NOTICE);
			$base = $id;
			do {
				$id = $base . '-' . uniqid();
			} while($view->dojo()->hasDijit($id));
		}
		$attribs['id'] = $id;
		$attribs['name'] = $id;
		$attribs['type'] = 'checkbox';
		$attribs['value'] = $element->getCheckedValue();
		$attribs['uncheckedValue'] = $element->getUncheckedValue();
		$attribs['checked'] = $element->isChecked()? 'true' : 'false';

		if(array_key_exists('options', $attribs)) {
			$options = $attribs['options'];
		}

		$elementContent = $view->customDijit($name, null, $dijitParams, $attribs, $options);
		switch($this->getPlacement()) {
			case self::APPEND:
				return $content . $separator . $elementContent;
			case self::PREPEND:
				return $elementContent . $separator . $content;
			default:
				return $elementContent;
		}
	}
}

