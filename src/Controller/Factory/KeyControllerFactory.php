<?php
namespace MKDF\Keys\Controller\Factory;

use MKDF\Keys\Controller\KeyController;
use MKDF\Keys\Repository\MKDFKeysRepositoryInterface;
use MKDF\Core\Repository\MKDFCoreRepositoryInterface;
use MKDF\Stream\Repository\MKDFStreamRepositoryInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Zend\Session\SessionManager;

class KeyControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get("Config");
        $repository = $container->get(MKDFKeysRepositoryInterface::class);
        $core_repository = $container->get(MKDFCoreRepositoryInterface::class);
        $stream_repository = $container->get(MKDFStreamRepositoryInterface::class);
        $sessionManager = $container->get(SessionManager::class);
        return new KeyController($repository, $core_repository, $stream_repository, $config);
    }
}