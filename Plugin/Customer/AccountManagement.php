<?php
namespace IDangerous\ExtendSubscribeOptions\Plugin\Customer;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\Session;
use Psr\Log\LoggerInterface;

class AccountManagement
{
    protected $request;
    protected $session;
    protected $logger;

    public function __construct(
        RequestInterface $request,
        Session $session,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->session = $session;
        $this->logger = $logger;
    }

    /**
     * Store subscription data in session before form processing
     */
    public function storeSubscriptionDataInSession()
    {
        $subscriptionData = $this->request->getParam('subscription', []);
        if (!empty($subscriptionData)) {
            $this->session->setSubscriptionPreferences($subscriptionData);
            $this->logger->info("Stored subscription data in session", ['data' => $subscriptionData]);
        }
    }

    /**
     * Before plugin to set subscription attributes during customer creation
     *
     * @param AccountManagementInterface $subject
     * @param CustomerInterface $customer
     * @param string $password
     * @param string|null $redirectUrl
     * @return array
     */
    public function beforeCreateAccount(
        AccountManagementInterface $subject,
        CustomerInterface $customer,
        $password = null,
        $redirectUrl = null
    ) {
        // Store subscription data in session first
        $this->storeSubscriptionDataInSession();

        // Get subscription data from request or session
        $subscriptionData = $this->request->getParam('subscription', []);
        if (empty($subscriptionData)) {
            $subscriptionData = $this->session->getSubscriptionPreferences();
        }

        $this->logger->info("ExtendSubscribeOptions beforeCreateAccount", [
            'customer_email' => $customer->getEmail(),
            'subscription_data' => $subscriptionData
        ]);

        if (!empty($subscriptionData)) {
            $allowCall = isset($subscriptionData['allow_call']) ? 1 : 0;
            $allowSms = isset($subscriptionData['allow_sms']) ? 1 : 0;
            $allowWhatsapp = isset($subscriptionData['allow_whatsapp']) ? 1 : 0;

            // Set the custom attributes directly on the customer object
            $customer->setCustomAttribute('allow_call', $allowCall);
            $customer->setCustomAttribute('allow_sms', $allowSms);
            $customer->setCustomAttribute('allow_whatsapp', $allowWhatsapp);

            $this->logger->info("Set subscription attributes on customer", [
                'allow_call' => $allowCall,
                'allow_sms' => $allowSms,
                'allow_whatsapp' => $allowWhatsapp
            ]);

            // Clear session data after use
            $this->session->unsSubscriptionPreferences();
        } else {
            // Set default values
            $customer->setCustomAttribute('allow_call', 0);
            $customer->setCustomAttribute('allow_sms', 0);
            $customer->setCustomAttribute('allow_whatsapp', 0);

            $this->logger->info("No subscription data found, set defaults to 0");
        }

        return [$customer, $password, $redirectUrl];
    }
}