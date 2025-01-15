<?php
namespace IDangerous\ExtendSubscribeOptions\Plugin\Newsletter;

use Magento\Newsletter\Controller\Manage\Save;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Message\ManagerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;

class Manage
{
    protected $customerRepository;
    protected $customerSession;
    protected $request;
    protected $messageManager;
    protected $customerFactory;
    protected $customerResource;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        Session $customerSession,
        RequestInterface $request,
        ManagerInterface $messageManager,
        CustomerFactory $customerFactory,
        CustomerResource $customerResource
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->customerFactory = $customerFactory;
        $this->customerResource = $customerResource;
    }

    public function beforeExecute(Save $subject)
    {
        try {
            $customerId = $this->customerSession->getCustomerId();

            if ($customerId) {
                $subscriptionData = $this->request->getParam('subscription', []);

                // Load customer using model
                $customer = $this->customerFactory->create();
                $customer->load($customerId);

                // Set attributes
                $customer->setData('allow_call', isset($subscriptionData['allow_call']) ? 1 : 0);
                $customer->setData('allow_sms', isset($subscriptionData['allow_sms']) ? 1 : 0);
                $customer->setData('allow_whatsapp', isset($subscriptionData['allow_whatsapp']) ? 1 : 0);

                // Save customer attributes
                $this->customerResource->save($customer);

                $this->messageManager->addSuccessMessage(__('Communication preferences have been saved.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not save communication preferences: %1', $e->getMessage()));
        }
    }
}