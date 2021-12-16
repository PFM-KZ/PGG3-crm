<?php

namespace AppBundle\Command;

use AppBundle\Service\ConfigModel;
use GCRM\CRMBundle\Entity\Settings\Brand;
use GCRM\CRMBundle\Entity\Settings\System;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class AppConfigUpdateCommand extends ContainerAwareCommand
{
    /** @var ConfigModel $configModel */
    private $configModel;

    function __construct(ConfigModel $configModel)
    {
        $this->configModel = $configModel;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:config:update')
            ->setDescription('Updates app configuration: settings etc.')
            ->addOption('clear', InputOption::VALUE_OPTIONAL, null, 'Clear stands for clearing every configuration value which is not present anymore in app config')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clear = $input->getOption('clear');

        $result = $this->configModel->loadSystemSettings('default_system_settings', System::class, $clear);
        $output->writeln('System settings:');
        $output->writeln('Added ' . $result['added'] . ' new settings');
        $output->writeln('Deleted ' . $result['deleted'] . ' settings');
        $output->writeln(' ');

        $result = $this->configModel->loadSystemSettings('default_brand_settings', Brand::class, $clear);
        $output->writeln('Brand settings:');
        $output->writeln('Added ' . $result['added'] . ' new settings');
        $output->writeln('Deleted ' . $result['deleted'] . ' settings');
        $output->writeln(' ');

        $output->writeln('App is now up to date with config.');
    }

}
