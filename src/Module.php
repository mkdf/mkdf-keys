<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonModule for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace MKDF\Keys;

use MKDF\Core\Service\AccountFeatureManagerInterface;
use MKDF\Keys\Feature\AccountKeysFeature;
use Zend\Mvc\MvcEvent;
use MKDF\Keys\Repository\MKDFKeysRepositoryInterface;
    
class Module
{
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * This method is called once the MVC bootstrapping is complete and allows
     * to register event listeners.
     */
    public function onBootstrap(MvcEvent $event)
    {
        // Initialisation
        $repository = $event->getApplication()->getServiceManager()->get(MKDFKeysRepositoryInterface::class);
        $repository->init();
     
        $featureManager = $event->getApplication()->getServiceManager()->get(AccountFeatureManagerInterface::class);
        $featureManager->registerFeature($event->getApplication()->getServiceManager()->get(AccountKeysFeature::class));
    }
}