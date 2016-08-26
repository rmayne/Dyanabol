<?php
namespace Dyanabol;

use Dyanabol\Model\ObjectTable;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;

class Module
{ 

  public function onBootstrap(MvcEvent $e)
  {
    $eventManager = $e->getApplication()->getEventManager();
    $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'onError'), 0);
    $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, array($this, 'onError'), 0);
  }

  public function onError($e){
    $error = $e->getError();
    if (!$error) {
        return;
    }

    $exception = $e->getParam('exception');

    if ($exception) {
      $data = array(
          'Error' => $exception->getMessage(),
      );
    } else {
      $data = array(
        'Error'   => 'Unable to complete request',
      );
    }

    if ($error == 'error-router-no-match') {
      $data['Error'] = 'Resource unavailable';
    }

    $model = new JsonModel($data);

    $e->setResult($model);
    return $model;
  }

  public function getConfig()
  {
      return include __DIR__ . '/config/module.config.php';
  }

  public function getAutoloaderConfig()
  {
      return array(
          'Zend\Loader\StandardAutoloader' => array(
              'namespaces' => array(
                  __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
              ),
          ),
      );
  }

  public function getServiceConfig()
  {
     return array(
         'factories' => array(
             'Dyanabol\Model\ObjectTable' =>  function($sm) {
                $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                 $table = new ObjectTable($dbAdapter);
                 return $table;
             },
          ),
      );
  }
}
