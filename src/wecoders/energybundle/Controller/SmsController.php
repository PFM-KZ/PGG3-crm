<?php

namespace Wecoders\EnergyBundle\Controller;

use AppBundle\Service\UploadedFileHelper;
use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Controller\AdminController;
use GCRM\CRMBundle\Entity\Settings;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\EasyAdminModel;
use GCRM\CRMBundle\Service\InvoiceModel;
use GCRM\CRMBundle\Service\ZipModel;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use TZiebura\SmsBundle\Interfaces\SmsSenderInterface;
use Wecoders\EnergyBundle\Entity\SmsClientGroup;
use Wecoders\EnergyBundle\Entity\SmsMessage;
use Wecoders\EnergyBundle\Entity\SmsTemplate;
use Wecoders\EnergyBundle\Form\SmsTestSender;
use Wecoders\EnergyBundle\Service\CustomDocumentTemplateModel;
use Wecoders\EnergyBundle\Service\DocumentModel;
use Wecoders\EnergyBundle\Service\DocumentPackageToGenerateModel;
use Wecoders\EnergyBundle\Service\EnveloModel;
use Wecoders\EnergyBundle\Service\SmsClientGroupModel;
use Wecoders\EnergyBundle\Form\ClientGroupWithPaymentDateType;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;

class SmsController extends AdminController
{
    private $smsClientGroupModel;

    private $sender;

    protected $em;

    function __construct(
        ZipModel $zipModel,
        InvoiceModel $invoiceModel,
        \Wecoders\EnergyBundle\Service\InvoiceModel $wecodersInvoiceModel,
        DocumentPackageToGenerateModel $documentPackageToGenerateModel,
        DocumentModel $documentModel,
        UploadedFileHelper $uploadedFileHelper,
        SpreadsheetReader $spreadsheetReader,
        ClientModel $clientModel,
        CustomDocumentTemplateModel $customDocumentTemplateModel,
        EnveloModel $enveloModel,
        SmsClientGroupModel $smsClientGroupModel,
        SmsSenderInterface $sender,
        EntityManagerInterface $em,
        EasyAdminModel $easyAdminModel
    )
    {
        parent::__construct(
            $zipModel,
            $invoiceModel,
            $wecodersInvoiceModel,
            $documentPackageToGenerateModel,
            $documentModel,
            $uploadedFileHelper,
            $spreadsheetReader,
            $clientModel,
            $customDocumentTemplateModel,
            $enveloModel,
            $easyAdminModel
        );

        $this->smsClientGroupModel = $smsClientGroupModel;
        $this->sender = $sender;
        $this->em = $em;
    }

    /**
     * @Route("/create-sms-client-group-with-payment-date", name="createSmsClientGroupWithPaymentDate")
     */
    public function createSmsClientGroupWithPaymentDate(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_SUPERADMIN', null, 'Access denied.');
        $clientGroupFormWithPaymentDate = $this->createForm(ClientGroupWithPaymentDateType::class);

        $clientGroupFormWithPaymentDate->handleRequest($request);

        if ($clientGroupFormWithPaymentDate->isSubmitted() && $clientGroupFormWithPaymentDate->isValid()) {
            $data = $clientGroupFormWithPaymentDate->getData();
            $dateOfPayment = $data['paymentDate'];
            $title = $data['groupName'];
            $smsClientGroup = $this->smsClientGroupModel->createSmsClientGroupWithCustomPaymentDate($title, [$dateOfPayment]);

            $this->addFlash('success', 'Utworzono grupę SMS');
            return $this->redirectToRoute('createSmsClientGroupWithPaymentDate');
        }

        return $this->render('@WecodersEnergyBundle/default/client-group-with-payment-dates.html.twig', [
            'clientGroupFormWithPaymentDate' => $clientGroupFormWithPaymentDate->createView(),
        ]);
    }

    public function showSmsClientGroupAction()
    {
        $entity = $this->entity['class'];
        $id = $this->request->query->get('id');

        $entity = $this->em->getRepository($entity)->find($id);

        return $this->render('admin/sms/showSmsClientGroup.html.twig', [
            'group' => $entity,
        ]);
    }

    /**
     * @Route("/manual-sms-sender", name="manualSmsSender")
     */
    public function manualSmsSender(Request $request)
    {
        $form = $this->createForm(SmsTestSender::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $telephone = $form->get('telephone')->getData();

            // fetch config and send SMS
            /** @var Settings $fromSetting */
            $fromSetting = $this->em->getRepository('GCRM\CRMBundle\Entity\Settings')->findOneBy(['name' => SmsClientGroupModel::SETTING_FROM]);
            if (!$fromSetting || !$fromSetting->getContent()) {
                $this->addFlash('error', 'Parametr ' . SmsClientGroupModel::SETTING_FROM . ' nie został ustawiony. SMS nie został wysłany.');
                $this->redirectToRoute('manualSmsSender');
            }
            $this->sender->setFrom($fromSetting->getContent());

            /** @var Settings\ $smsApiKeySetting */
            $smsApiKeySetting = $this->em->getRepository('GCRM\CRMBundle\Entity\Settings')->findOneBy(['name' => SmsClientGroupModel::SETTING_API_KEY]);
            $smsApiKey = null;
            if ($smsApiKeySetting) {
                $smsApiKey = $smsApiKeySetting->getContent() ?: null;
            }
            if (!$smsApiKey) {
                $this->addFlash('error', 'Parametr ' . SmsClientGroupModel::SETTING_API_KEY . ' nie został ustawiony. SMS nie został wysłany.');
                $this->redirectToRoute('manualSmsSender');
            }
            $this->sender->createService($smsApiKey);

            $message = new \TZiebura\SmsBundle\Entity\SmsMessage();
            $message->setNumber($telephone);

            $message->setMessage('Test');

            $this->sender->sendSms($message);
            if ($message->getStatusCode() == SmsMessage::STATUS_SUCCESS) {
                $this->addFlash('success', 'SMS o treści Test został wysłany na wskazany numer.');
            } elseif ($message->getStatusCode() == SmsMessage::STATUS_ERROR) {
                $this->addFlash('error', 'Wystąpił błąd (#' . $message->getErrorCode() . ') - ' . $message->getErrorMessage());
            } else {
                $this->addFlash('error', 'Nie udało się wysłać wiadomości. Spróbuj jeszcze raz. Jeśli sytuacja będzie się powtarzać skontaktuj się z administratorem.');
            }
            $this->redirectToRoute('manualSmsSender');
        }

        return $this->render('admin/sms/manualSmsSender.html.twig', [
            'form' => $form->createView()
        ]);
    }

}