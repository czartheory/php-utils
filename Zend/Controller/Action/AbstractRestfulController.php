<?php

namespace CzarTheory\Zend\Controller\Action;

use CzarTheory\Services\RestfulService;

abstract class AbstractRestfulController extends \Zend_Controller_Action
{
	/**
	 * Info for child class to be aware of http method being used.
	 * @var string
	 */
	protected $_httpMethod;

	/**
	 * Flag for child class, to know if a redirect may be necessary
	 * @var boolean
	 */
	protected $_postHappened = false;

	/**
	 * Uses a form and a RestfulService to fulfill a RESTful HTTP request.
	 *
	 * @param RestfulService $service
	 * @param \Zend_form $form
	 * @param string $id the Id of the item being requested/modified
	 */
	protected function _dispatchRest(RestfulService $service, \Zend_form $form = null, $id = null)
	{
		$request = $this->getRequest();
		$method = strtolower($request->getMethod());
		$view = $this->view;
		$this->_httpMethod = $method;
		$view->restfulService = $service;

		switch($method) {
			case 'get':
				$query = $request->getQuery();
				// Convert values from their string representations
				foreach ($query as $key => $value)
				{
					// pull out sort if it's defined
					if (preg_match('/^sort\((.*)\)$/', $key, $matches))
					{
						$orderBy = array();
						foreach (explode(',', $matches[1]) as $ordering)
						{
							$orderBy[substr($ordering, 1)] = (substr($ordering, 0, 1) == '-' ? 'DESC' : 'ASC');
						}

						$service->setOrderBy($orderBy);
						unset($query[$key]);
					}
					elseif (is_numeric($value))
					{
						if (strpos($value, '.'))
						{
							$query[$key] = doubleval($value);
						}
						else
						{
							$query[$key] = intval($value);
						}
					}
				}

				$service->setCriteria($query);
				$limit = $request->getParam('rangeLimit');
				if(isset($limit)){
					$offset = $request->getParam('rangeOffset');
					$service->setPagination($limit, $offset);
					$this->_response->setHeader('Content-Range', sprintf('items %d-%d/%d', $offset, $offset + $limit - 1, $service->count()));
				}

				if(null !== $id)
				{
					$view->entityService = $service->get($id);
				}
				break;

			case 'post':
				$form->setMethod(\Zend_Form::METHOD_POST);
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
					$this->_postHappened = true;
				}
				break;

			case 'put':
				$form->setMethod(\Zend_Form::METHOD_PUT);
				$put = null;
				parse_str($request->getRawBody(), $put);
				if(null === $id) {
					$view->error = "No Id Provided";
					$this->_response->setHttpResponseCode(400);

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
