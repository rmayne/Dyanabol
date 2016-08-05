<?php
/**
 * rmayne
 *
 * @link      http://github.com/rmayne
 * @copyright Copyright (c)2016 rmayne
 * @license   MIT
 */

namespace Dyanabol\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Dyanabol\Model\Object;




class ObjectsController extends AbstractRestfulController
{
	protected $dataService;

	protected $entityTable;

	protected $links;

	protected $embedded;
	
	protected $allowedVerbsResource = array('GET', 'POST', 'PUT', 'DELETE');

	protected $allowedVerbsCollection = array('GET', 'POST', 'PUT', 'DELETE');

	protected function _getVerbs($id)
	{
		//if id is present, return the verb list for a resource
		if($id) {
			return $this->allowedVerbsResource;
		} else {
			return $this->allowedVerbsCollection;
		}
	}

	protected function notAllowed()
	{
		//stop execution
	    $this->response->setStatusCode(405);
	    throw new \Exception('Not Allowed');
	}

	public function checkVerbs($id = false)
	{
		//http verb allowed
		if(in_array($this->getRequest()->getMethod(), $this->_getVerbs($id))) {
			return true;
		} else {
			//http verb not allowed
			return false;
		}
	}

	protected function isAuthorized(){
		return password_verify(explode(" ", $this->getRequest()->getHeaders('Authorization')->toString())[2],
			$this->getServiceLocator()->get('config')['http_basic_auth']['hash']
		);
	}

	public function options()
	{
		// CORS .....
		$this->response->getHeaders()->addHeaders(array(
		    'Access-Control-Allow-Origin' => $this->getRequest()->getUri()->getHost(),
		    'Access-Control-Allow-Methods' => 'GET, POST, PUT, OPTIONS',
		    'Access-Control-Allow-Headers' => 'X-Custom-Header, content-type',
		    'Content-Type' => 'application/json',
	     ));
        $this->response->setStatusCode(405);
	}

	public function getList()
	{
		if($this->checkVerbs() && $this->isAuthorized()) {
		    $people = $this->getObjectTable()->getObjects()->toArray();
			$viewModel = new JsonModel($people);
			$viewModel->setTerminal(true);
			return $viewModel;
		} else {
		    $this->notAllowed();
		}
	}

	public function create($data)
	{
		// needs auth check and empty data array check
		if($this->checkVerbs() && $this->isAuthorized()) {
		    $person = new Object();
			$person->exchangeArray($data);
			$people = $this->getObjectTable()->saveObject($person);
			$viewModel = new JsonModel($people->getDataSource());
			$viewModel->setTerminal(true);
			return $viewModel;
		} else {
		    $this->notAllowed();
		}
	}

	public function getObjectTable()
    {
		if (!$this->entityTable) {
			$sm = $this->getServiceLocator();
			$this->entityTable = $sm->get('Dyanabol\Model\ObjectTable');
		}
		return $this->entityTable;
    } 
}