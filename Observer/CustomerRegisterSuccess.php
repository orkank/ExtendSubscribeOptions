<?php
namespace IDangerous\ExtendSubscribeOptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;

class CustomerRegisterSuccess implements ObserverInterface
{
    protected $request;
    protected $customerRepository;
    protected $logger;

    public function __construct(
        RequestInterface $request,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            $customer = $observer->getEvent()->getCustomer();

            if (!$customer || !$customer->getId()) {
                return;
            }

            // Get subscription data from request
            $subscriptionData = $this->request->getParam('subscription', []);
            $postData = $this->request->getPostValue();
            $postSubscriptionData = isset($postData['subscription']) ? $postData['subscription'] : [];
            $finalSubscriptionData = !empty($subscriptionData) ? $subscriptionData : $postSubscriptionData;

            $this->logger->info("CustomerRegisterSuccess Observer called", [
                'customer_id' => $customer->getId(),
                'email' => $customer->getEmail(),
                'subscription_data' => $finalSubscriptionData
            ]);

            if (!empty($finalSubscriptionData)) {
                // Load the customer to get the latest version
                $customerData = $this->customerRepository->getById($customer->getId());

                $allowCall = isset($finalSubscriptionData['allow_call']) ? 1 : 0;
                $allowSms = isset($finalSubscriptionData['allow_sms']) ? 1 : 0;
                $allowWhatsapp = isset($finalSubscriptionData['allow_whatsapp']) ? 1 : 0;

                // Set the custom attributes
                $customerData->setCustomAttribute('allow_call', $allowCall);
                $customerData->setCustomAttribute('allow_sms', $allowSms);
                $customerData->setCustomAttribute('allow_whatsapp', $allowWhatsapp);

                // Save the customer
                $this->customerRepository->save($customerData);

                $this->logger->info("Subscription preferences saved via observer", [
                    'customer_id' => $customer->getId(),
                    'allow_call' => $allowCall,
                    'allow_sms' => $allowSms,
                    'allow_whatsapp' => $allowWhatsapp
                ]);

                // Verify the save worked
                $verifyCustomer = $this->customerRepository->getById($customer->getId());
                $callAttr = $verifyCustomer->getCustomAttribute('allow_call');
                $smsAttr = $verifyCustomer->getCustomAttribute('allow_sms');
                $whatsappAttr = $verifyCustomer->getCustomAttribute('allow_whatsapp');

                $this->logger->info("Verification after save", [
                    'customer_id' => $customer->getId(),
                    'saved_allow_call' => $callAttr ? $callAttr->getValue() : 'NULL',
                    'saved_allow_sms' => $smsAttr ? $smsAttr->getValue() : 'NULL',
                    'saved_allow_whatsapp' => $whatsappAttr ? $whatsappAttr->getValue() : 'NULL'
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in CustomerRegisterSuccess observer", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}