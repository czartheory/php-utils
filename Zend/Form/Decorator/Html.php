<?php

namespace CzarTheory\Zend\Form\Decorator;

/**
  * Description of Html
  *
  * This class is for adding arbitrary HTML to a form
  * It's very simple. It keeps the content exactly as was placed
  */
class Html extends \Zend_Form_Decorator_Abstract
{
	/** @var string */
	protected $_html;

	/**
	 * Constructor
	 *
	 * @param string $html
	 * @param array|Zend_Config $options
	 * @return void
	 */
	public function __construct($options = null)
	{
		if(!isset($options['html'])) throw new \InvalidArgumentException('HTML KEY MISSING FROM OPTIONS');
		$this->_html = $options['html'];
		parent::__construct($options);
	}

	/**
	 * Render content
	 *
	 * @param  string $content
	 * @return string
	 */
	public function render($content)
	{
		$html = $this->_html;

		switch($this->getPlacement()) {
			case self::APPEND:
				return $content . $this->getSeparator() . $html;
			case self::PREPEND:
				return $markup . $this->getSeparator() . $html;
		}
	}
}
