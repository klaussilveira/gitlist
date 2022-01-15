<?php

declare(strict_types=1);

namespace GitList;

use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\WebpackEncoreBundle\WebpackEncoreBundle;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getProjectDir(): string
    {
        return __DIR__ . '/../';
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/var/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir() . '/var/log';
    }

    public function registerBundles(): iterable
    {
        $bundles = [
            FrameworkBundle::class,
            TwigBundle::class,
            MonologBundle::class,
            WebpackEncoreBundle::class,
        ];

        if ($this->debug) {
            $bundles[] = DebugBundle::class;
        }

        foreach ($bundles as $bundle) {
            yield new $bundle();
        }
    }

    private function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        $confDir = $this->getProjectDir() . '/config';
        $loader->load($confDir . '/config.yml');
        $loader->load($confDir . '/framework.yml');
        $loader->load($confDir . '/services.yml');
    }

    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $confDir = $this->getProjectDir() . '/config';
        $routes->import($confDir . '/routes.yml');

        if ($this->environment == 'dev') {
            $routes->import($confDir . '/dev/routes.yml');
        }
    }
}
