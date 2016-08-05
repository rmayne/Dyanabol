<?php
/**
 * rmayne
 *
 * @link      http://github.com/rmayne
 * @copyright Copyright (c)2016 rmayne
 * @license   MIT
 */
 
return array(
    'controllers' => array(
        'invokables' => array(
            'Dyanabol\Controller\ObjectsController' => 'Dyanabol\Controller\ObjectsController',
            'Dyanabol\Controller\ObjectController' => 'Dyanabol\Controller\ObjectController',

        ),
    ),

    'router' => array(
        'routes' => array(
            'home' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        'controller' => 'Dyanabol\Controller\ObjectsController',
                    ),
                ),
            ),
            'resources' => array(
                'type'    => 'Segment',
                'options' => array(
                    'route'    => '/:entity[/:id]',
                    'constraints' => array(
                        'id'     => '[0-9]+',
                        'resource'     => '[a-z]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Dyanabol\Controller\ObjectController',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),
);