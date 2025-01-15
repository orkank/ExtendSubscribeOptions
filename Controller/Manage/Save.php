<?php
namespace IDangerous\ExtendSubscribeOptions\Controller\Manage;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;

class Save implements HttpPostActionInterface
{
    protected $customerRepository;
    protected $customerSession;
    protected $messageManager;
    protected $redirect;

    public function __construct(
        Context $context,
        CustomerRepositoryInterface $customerRepository,
        Session $customerSession
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->messageManager = $context->getMessageManager();
        $this->redirect = $context->getRedirect();
    }

    public function execute()
    {
        try {
            $customerId = $this->customerSession->getCustomerId();
            $customer = $this->customerRepository->getById($customerId);

            $customer->setCustomAttribute('allow_call', isset($_POST['allow_call']) ? 1 : 0);
            $customer->setCustomAttribute('allow_sms', isset($_POST['allow_sms']) ? 1 : 0);
            $customer->setCustomAttribute('allow_whatsapp', isset($_POST['allow_whatsapp']) ? 1 : 0);

            $this->customerRepository->save($customer);
            $this->messageManager->addSuccessMessage(__('Communication preferences have been saved.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while saving communication preferences.'));
        }

        return $this->redirect->redirect($context->getResponse(), 'newsletter/manage');
    }
}