<?php
/**
 * Description of RestfulHandler
 *
 * This helper allows for REST-ful requests to easily be handled.
 * It allows one to simply pass a form and a service to the dispatchRest method
 * This method will take care of the REST (figuring out request method, interacting
 * with the service, setting the correct response code, and redirecting-if-required)
 *
 * @copyright 2012 Czar Theory LLC. All rights reserved
 */

namespace CzarTheory\Zend\Controller\Action\Helper;

use CzarTheory\Services\RestfulService;

class RestfulHandler extends \Zend_Controller_Action_Helper_Abstract
{

	/**
	 * Uses a form and a RestfulService to fulfill a RESTful HTTP request.
	 *
	 * @param RestfulService $service
	 * @param \Zend_form $form
	 * @param string $id the Id of the item being requested/modified
	 */
	protected function _dispatchRest(RestfulService $service, \Zend_form $form, $id = null)
	{
		$request = $this->getRequest();
		$method = strtolower($request->getMethod());

		$view = $this->getActionController()->view;
		$view = $this->view;

		$postHappened = false;

		switch($method) {
			case 'get':
				if(null !== $id) $view->entityService = $service->get($id);
				else $view->restfulService = $service;
				break;

			case 'post':
				//Let the form know what is happening... for validation
				$form->setMethod(\Zend_Form::METHOD_POST);
				$form->setMethod('post');

				$post = $request->getPost();

				if(!$form->isValid($post)){
					$view->error = array('invalid' => $form->getMessages());
					$this->_response->setHttpResponseCode(400);

				} elseif(!$service->canCreate()){
					$view->error = array(
						'message' => "User is Not Authorized",
						'class' => get_class($service),
						'method' => 'create',
					);
					$this->_response->setHttpResponseCode(403);

				} else {
					$view->entityService = $service->create($form->getValidValues($post));
					$postHappened = true;
				}
				break;

			case 'put':
				$form->setMethod(\Zend_Form::METHOD_PUT);
				$put = null;
				parse_str($request->getRawBody(), $put);
				if(null === $id) {
					$view->error = "No Id Provided";
					$this->_response->setHttpResponceCode(400);

				} elseif(!$form->isValid($put)){
					$view->error = array('invalid' => $form->getMessages());
					$this->_response->setHttpResponseCode(400);

				} elseif(!$service->canUpdate($id)){
					$view->error = array(
						'message' => "User is Not Authorized",
						'class' => get_class($service),
						'method' => 'update',
					);
					$this->_response->setHttpResponseCode(403);

				} else {
					$view->entityService = $service->update($id, $form->getValidValues($put));
				}
				break;

			case 'delete':
				if(null === $id) {
					$view->error = "No Id Provided";
					$this->_response->setHttpResponseCode(400);

				} elseif (!$service->canDelete($id)){
					$view->error = array(
						'message' => "User is Not Authorized",
						'class' => get_class($service),
						'method' => 'update',
					);
					$this->_response->setHttpResponseCode(403);

				} else {
					$service->delete($id);
					$view->deleteHappened = true;
				}
				break;

			default:
				$view->error = "Unrecognized Method: $method";
				$this->_response->setHttpResponseCode(501);
		}
	}
}
