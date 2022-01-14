<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Service\ContractModel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Smsapi\Client\Feature\Sms\Bag\SendSmsBag;
use Smsapi\Client\SmsapiHttpClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TZiebura\SmsBundle\Interfaces\SmsSenderInterface;
use Wecoders\EnergyBundle\Entity\SmsMessage;
use Wecoders\EnergyBundle\Entity\SmsTemplate;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;

class SendSmsToSpecyficGroupCommand extends Command
{
    const SETTING_FROM = 'sms_from';
    const SETTING_API_KEY = 'smsapi_api_key';

    private $settingBatchSize = 'sms_batch_size';
    private $settingGroupCreationTime = 'sms_group_creation_time';
    private $settingSmsSendingTime = 'sms_sending_time';

    /** @var int $defaultBatchSize */
    private $defaultBatchSize = 150;



    private $em;
    private $container;
    private $spreadsheetReader;
    /** @var SmsSenderInterface $sender */
    private $sender;
    private $clientRepository;

    public function __construct(
        EntityManager      $em,
        ContainerInterface $container,
        SpreadsheetReader  $spreadsheetReader,
        SmsSenderInterface $sender
    )
    {
        $this->em = $em;
        $this->container = $container;
        $this->spreadsheetReader = $spreadsheetReader;
        $this->sender = $sender;
        $this->clientRepository = $em->getRepository(Client::class);

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('appbundle:send-sms-to-specific-group-in-file')
        ->addArgument('filename', InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $kernelRootDir = $this->container->get('kernel')->getRootDir();
        $name = $input->getArgument('filename');
        $fileActual = $kernelRootDir . '/../var/data/send-sms/'.$name.'.xlsx';

        dump('Pobieranie kodów...');
        $rows = $this->spreadsheetReader->fetchRows('Xlsx', $fileActual, 1, 'A');

        $smsApiKey = $this->em->getRepository('GCRM\CRMBundle\Entity\Settings')->findOneBy(['name' => self::SETTING_API_KEY]);
        $smsApiKey = $smsApiKey->getContent();

        $template = $this->getTemplateByCode(SmsTemplate::BANK_ACCOUNT_CHANGE);

        $smsMessage = new SmsMessage();

        foreach ($rows as $row) {
            $user = $this->clientRepository->find($row[0]);

            $tokensAndValues = array(
                '{_aktualny_nr_rachunku_bankowego_}' => $user->getBankAccountNumber()
            );

            if ($user->getTelephoneNr())
            {
                $sms = SendSmsBag::withMessage($user->getTelephoneNr(), $this->replaceTokens($template, $tokensAndValues));
                $sms->from = 'EnrexEnergy';
                $sms->encoding = 'UTF-8';

                $service = (new SmsapiHttpClient())
                    ->smsapiPlService($smsApiKey);
                $service->smsFeature()
                    ->sendSms($sms);
            }

        }


        dump('Success');
    }

    private function getTemplateByCode($templateCode)
    {
        return $this->em->getRepository(SmsTemplate::class)->findOneBy(['code' => SmsTemplate::BANK_ACCOUNT_CHANGE]);
    }

    private function replaceTokens($template, $tokensAndValues)
    {
        if(!$template) {
            return '';
        }
        $message = $template->getMessage();
        foreach($tokensAndValues as $token => $value) {
            $message = str_replace($token, $value, $message);
        }

        return $message;
    }

}