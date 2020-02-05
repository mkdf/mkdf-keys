<?php


namespace MKDF\Keys\Repository\Factory;

use MKDF\Keys\Repository\MKDFKeysRepository;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class MKDFKeysRepositoryFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get("Config");
        return new MKDFKeysRepository($config);
    }
}