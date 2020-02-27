<?php

namespace MKDF\Keys;

use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\Router\Http\Literal;
use Zend\Router\Http\Segment;

return [
    'controllers' => [
        'factories' => [
            Controller\KeyController::class => Controller\Factory\KeyControllerFactory::class
        ],
    ],
    'service_manager' => [
        'aliases' => [
            Repository\MKDFKeysRepositoryInterface::class => Repository\MKDFKeysRepository::class
        ],
        'factories' => [
            Repository\MKDFKeysRepository::class => Repository\Factory\MKDFKeysRepositoryFactory::class,
            Feature\AccountKeysFeature::class => InvokableFactory::class
        ]
    ],
    'router' => [
        'routes' => [
            'key' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/my-account/key[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        'controller' => Controller\KeyController::class,
                        'action' => 'index'
                    ],
                ],
                // 'may_terminate' => true,
                // 'child_routes' => [
 //                                        //
 //                    // // Segment route for viewing one dataset
 //                    // 'details' => [
 //                    //     'type' => Segment::class,
 //                    //     'options' => [
 //                    //         'route' => '/[:id]',
 //                    //         'defaults' => [
 //                    //             'action' => 'details',
 //                    //         ],
 //                    //     ],
 //                    // ]
 //                ],
            ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'Key' => __DIR__ . '/../view',
        ],
    ],
    'controller_plugins' => [
        'factories' => [
            Controller\Plugin\MKDFKeysRepositoryPlugin::class => Controller\Plugin\Factory\MKDFKeysRepositoryPluginFactory::class,
        ],
        'aliases' => [
            'MKDFKeysRepository' => Controller\Plugin\MKDFKeysRepositoryPlugin::class,
        ]
    ],
    // The 'access_filter' key is used by the User module to restrict or permit
    // access to certain controller actions for unauthenticated visitors.
    'access_filter' => [
        'options' => [
            // The access filter can work in 'restrictive' (recommended) or 'permissive'
            // mode. In restrictive mode all controller actions must be explicitly listed
            // under the 'access_filter' config key, and access is denied to any not listed
            // action for users not logged in. In permissive mode, if an action is not listed
            // under the 'access_filter' key, access to it is permitted to anyone (even for
            // users not logged in. Restrictive mode is more secure and recommended.
            'mode' => 'restrictive'
        ],
        'controllers' => [
            Controller\KeyController::class => [
                // Allow anyone to visit "index" and "about" actions
                ['actions' => ['index'], 'allow' => '@'],
                ['actions' => ['details'], 'allow' => '@'],
                // Allow authenticated users to ...
                ['actions' => ['add','edit','delete','delete-confirm'], 'allow' => '@']
            ],
        ]
    ],
    'navigation' => [
        'default' => [
            //[
            //    'label' => 'Keys',
            //    'route' => 'key',
            //],
        ],
    ],
];
