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

		switch ($method) {
			case 'get':
		        $view->restfulService = $service;
				$query = $request->getQuery();

				// Convert values from their string representations
				$temp = null;
				foreach ($query as $key => $value) {
					if (substr($value, 0, 1) == '(' && substr($value, -1, 1) == ')') { // array
						$query[$key] = explode(',', substr($value, 1, -1));

					} elseif (is_numeric($value)) { // could start with a digit but not be a number
						if (strpos($value, '.') && (($temp = doubleval($value)) == $value)) {
							$query[$key] = $temp;
						} elseif (($temp = intval($value)) == $value) {
							$query[$key] = $temp;
						}
					}
				}

				// extract orderBy clause (i.e. sort)
				if (isset($query['sort'])) {
					$orderBy = array();
					$sorts = $query['sort'];
					if (!is_array($sorts)) {
                        $sorts = explode(',', $sorts);
					}

					foreach ($sorts as $ordering) {
						$orderBy[substr($ordering, 1)] = (substr($ordering, 0, 1) == '-' ? 'DESC' : 'ASC');
					}

					$service->setOrderBy($orderBy);
					unset($query['sort']);
				}

				// extract the limit & offset parts
				$service->addCriteria($query);
				$limit = $request->getParam('rangeLimit');
				if (isset($limit)) {
					$offset = $request->getParam('rangeOffset');
					$service->setPagination($limit, $offset);
					$this->_response->setHeader('Content-Range', sprintf('items %d-%d/%d', $offset, $offset + $limit - 1, $service->count()));
				}

				if (null !== $id) {
					$entityService = $service->get($id);
					if (isset($entityService)) {
						$view->entityService = $service->get($id);
					} else {
						$this->_response->setHttpResponseCode(403);
						$view->error = 'Resource not found.';
					}
				}
				break;

			case 'post':
				if (!isset($form)) {
					$this->_response->setHttpResponseCode(500);
					$view->error = 'Form validator missing for ' . strtoupper($method) . ' request.';
					break;
				}

				$form->setMethod(\Zend_Form::METHOD_POST);
				$post = $request->getPost();

				if (!$form->isValid($post)) {
					$view->error = array('invalid' => $form->getMessages());
					$this->_response->setHttpResponseCode(400);
				} elseif (!$service->canCreate()) {
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
				if (!isset($form)) {
					$this->_response->setHttpResponseCode(500);
					$view->error = 'Form validator missing for ' . strtoupper($method) . ' request.';
					break;
				}

				$form->setMethod(\Zend_Form::METHOD_PUT);
				$put = null;
				parse_str($request->getRawBody(), $put);
				if (null === $id) {
					$view->error = "No Id Provided";
					$this->_response->setHttpResponseCode(400);
				} elseif (!$form->isValid($put)) {
					$view->error = array('invalid' => $form->getMessages());
					$this->_response->setHttpResponseCode(400);
				} elseif (!$service->canUpdate($id)) {
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
				if (null === $id) {
					$view->error = "No Id Provided";
					$this->_response->setHttpResponseCode(400);
				} elseif (!$service->canDelete($id)) {
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
