<?php

declare(strict_types=1);

namespace Spyck\DashboardBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class SpyckDashboardBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $builder->setParameter('spyck.dashboard.chart.command', $config['chart']['command']);
        $builder->setParameter('spyck.dashboard.chart.directory', $config['chart']['directory']);

        $builder->setParameter('spyck.dashboard.mailer.from.email', $config['mailer']['from']['email']);
        $builder->setParameter('spyck.dashboard.mailer.from.name', $config['mailer']['from']['name']);
    }
}