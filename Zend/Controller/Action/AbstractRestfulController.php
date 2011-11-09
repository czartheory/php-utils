<?php

namespace CzarTheory\Zend\Controller\Action;

use CzarTheory\Models\RestfulService;

abstract class AbstractRestfulController extends \Zend_Controller_Action
{
	protected function _dispatchRest(RestfulService $service, \Zend_form $form)
	{
		$request = $this->getRequest();
		$id = $request->getParam('id');
		$output = null;

		$method = strtolower($request->getMethod());
		switch($method)
		{
			case 'get':
				if(null !== $id) $output = array($service->get($id));
				else {
					//@todo generate criteria if any exists
					$output = $service->getAll();
				}
			break;

			case 'post':
				$post = $request->getPost();
				if(!$form->isValid($post)) break;
				$output = array($service->create($post));
			break;

			case 'put':
				$put = null;
				if(null === $id) {
					//@todo implement a more suitable response
					$this->_response->setHttpResponceCode(400);
					break;
				}
				parse_str($request->getRawBody(),$put);
				if(!$form->isValid($put)) break;
				$output = array($service->update($id,$put));
			break;

			case 'delete':
				if(null === $id) {
					//@todo implement a more suitable response
					$this->_response->setHttpResponseCode(400);
				} else {
					$service->delete($id);
				}
			break;

			default:
				//@todo implement a more suitable response
				$this->_response->setHttpResponseCode(400);
		}
		return $output;
	}
}
