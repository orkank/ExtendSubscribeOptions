<?php
namespace IDangerous\ExtendSubscribeOptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Psr\Log\LoggerInterface;

class CustomerRegisterSuccess implements ObserverInterface
{
    protected $request;
    protected $customerRepository;
    protected $customerFactory;
    protected $customerResource;
    protected $logger;

    public function __construct(
        RequestInterface $request,
        CustomerRepositoryInterface $customerRepository,
        CustomerFactory $customerFactory,
        CustomerResource $customerResource,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->customerResource = $customerResource;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            $customer = $observer->getEvent()->getCustomer();

            if (!$customer || !$customer->getId()) {
                return;
            }

            // Get subscription data from POST request first (most reliable for form submissions)
            $postData = $this->request->getPostValue();
            $subscriptionData = isset($postData['subscription']) ? $postData['subscription'] : [];

            // Fallback to getParam if not in POST
            if (empty($subscriptionData)) {
                $subscriptionData = $this->request->getParam('subscription', []);
            }

            $this->logger->info("CustomerRegisterSuccess Observer called", [
                'customer_id' => $customer->getId(),
                'email' => $customer->getEmail(),
                'subscription_data' => $subscriptionData,
                'post_data_keys' => array_keys($postData ?? [])
            ]);

            // Process subscription data - check if checkboxes are checked
            // For checkboxes, if the key exists in the array, the checkbox is checked (value=1)
            // If the key doesn't exist, the checkbox is unchecked (value=0)
            $allowCall = (isset($subscriptionData['allow_call']) && $subscriptionData['allow_call']) ? 1 : 0;
            $allowSms = (isset($subscriptionData['allow_sms']) && $subscriptionData['allow_sms']) ? 1 : 0;
            $allowWhatsapp = (isset($subscriptionData['allow_whatsapp']) && $subscriptionData['allow_whatsapp']) ? 1 : 0;

            // Load customer model to update table columns directly
            // This is needed because attributes are defined as both EAV and table columns
            $customerModel = $this->customerFactory->create();
            $this->customerResource->load($customerModel, $customer->getId());

            // Set attributes on model (this updates customer_entity table columns)
            $customerModel->setData('allow_call', $allowCall);
            $customerModel->setData('allow_sms', $allowSms);
            $customerModel->setData('allow_whatsapp', $allowWhatsapp);

            // Save customer model
            $this->customerResource->save($customerModel);

            $this->logger->info("Subscription preferences saved via observer to customer_entity table", [
                'customer_id' => $customer->getId(),
                'allow_call' => $allowCall,
                'allow_sms' => $allowSms,
                'allow_whatsapp' => $allowWhatsapp
            ]);

            // Verify the save worked
            $this->customerResource->load($customerModel, $customer->getId());
            $this->logger->info("Verification after save", [
                'customer_id' => $customer->getId(),
                'saved_allow_call' => $customerModel->getData('allow_call'),
                'saved_allow_sms' => $customerModel->getData('allow_sms'),
                'saved_allow_whatsapp' => $customerModel->getData('allow_whatsapp')
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Error in CustomerRegisterSuccess observer", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}