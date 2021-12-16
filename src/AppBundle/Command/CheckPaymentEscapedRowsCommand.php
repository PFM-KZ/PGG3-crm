<?php

namespace AppBundle\Command;

use GCRM\CRMBundle\Service\FileActionsModel;
use GCRM\CRMBundle\Service\PaymentImporter\Ing;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CheckPaymentEscapedRowsCommand extends Command
{
    private $fileActionsModel;
    private $container;

    public function __construct(FileActionsModel $fileActionsModel, ContainerInterface $container)
    {
        $this->fileActionsModel = $fileActionsModel;
        $this->container = $container;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('appbundle:check-payment-escaped-rows');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $kernelRootDir = $this->container->get('kernel')->getRootDir();
        $ingPath = $kernelRootDir . '/../' . Ing::RELATIVE_DIR_PATH . '/' . Ing::DIR_NAME;

        $result = [];
        $this->fileActionsModel->generateFilesStructure($ingPath, $result);

        $result = array_values($result)[0];

        $buffer = [];

        foreach ($result as $filename) {
            if ($filename == 'tmp.txt') {
                continue;
            }

            $fileContent = file_get_contents($ingPath . '/' . $filename);
            preg_match_all('/\\\\"/', $fileContent, $matches);

            // found
            if (count($matches[0])) {
                // get line
                $handler = fopen($ingPath . '/' . $filename, 'r');

                $index = 1;
                $foundRow = false;
                while(!feof($handler)) {
                    $row = fgets($handler);

                    // add next element that were omited
                    if ($foundRow) {
                        $buffer[$filename][] = $row;
                        $foundRow = false;
                    }

                    preg_match('/\\\\"/', $row, $m);
                    if (count($m)) {
                        if (!array_key_exists($filename, $buffer)) {
                            $buffer[$filename] = [];
                        }

                        $foundRow = true;
                    }
                    $index++;
                }

                fclose($handler);
            }
        }

        dump($buffer);
        dump('Success');
    }
}