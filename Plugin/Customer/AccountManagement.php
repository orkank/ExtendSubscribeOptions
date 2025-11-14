<?php
namespace IDangerous\ExtendSubscribeOptions\Plugin\Customer;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Psr\Log\LoggerInterface;

class AccountManagement
{
    protected $request;
    protected $session;
    protected $logger;
    protected $customerFactory;
    protected $customerResource;

    public function __construct(
        RequestInterface $request,
        Session $session,
        LoggerInterface $logger,
        CustomerFactory $customerFactory,
        CustomerResource $customerResource
    ) {
        $this->request = $request;
        $this->session = $session;
        $this->logger = $logger;
        $this->customerFactory = $customerFactory;
        $this->customerResource = $customerResource;
    }

    /**
     * Store subscription data in session before form processing
     */
    public function storeSubscriptionDataInSession()
    {
        $postData = $this->request->getPostValue();
        $subscriptionData = isset($postData['subscription']) ? $postData['subscription'] : [];
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

        // Get subscription data from POST request first, then try session
        $postData = $this->request->getPostValue();
        $subscriptionData = isset($postData['subscription']) ? $postData['subscription'] : [];

        // If not in POST, try to get from session
        if (empty($subscriptionData)) {
            $subscriptionData = $this->session->getSubscriptionPreferences();
        }

        // Also try getParam as fallback
        if (empty($subscriptionData)) {
            $subscriptionData = $this->request->getParam('subscription', []);
        }

        $this->logger->info("ExtendSubscribeOptions beforeCreateAccount", [
            'customer_email' => $customer->getEmail(),
            'subscription_data' => $subscriptionData,
            'post_data_keys' => array_keys($postData ?? [])
        ]);

        // Process subscription data - check if checkboxes are checked
        // For checkboxes, if the key exists in the array, the checkbox is checked (value=1)
        // If the key doesn't exist, the checkbox is unchecked (value=0)
        $allowCall = (isset($subscriptionData['allow_call']) && $subscriptionData['allow_call']) ? 1 : 0;
        $allowSms = (isset($subscriptionData['allow_sms']) && $subscriptionData['allow_sms']) ? 1 : 0;
        $allowWhatsapp = (isset($subscriptionData['allow_whatsapp']) && $subscriptionData['allow_whatsapp']) ? 1 : 0;

        // Set the custom attributes directly on the customer object
        $customer->setCustomAttribute('allow_call', $allowCall);
        $customer->setCustomAttribute('allow_sms', $allowSms);
        $customer->setCustomAttribute('allow_whatsapp', $allowWhatsapp);

        // Store in session for afterCreateAccount plugin to use
        $this->session->setSubscriptionPreferences([
            'allow_call' => $allowCall,
            'allow_sms' => $allowSms,
            'allow_whatsapp' => $allowWhatsapp
        ]);

        $this->logger->info("Set subscription attributes on customer", [
            'allow_call' => $allowCall,
            'allow_sms' => $allowSms,
            'allow_whatsapp' => $allowWhatsapp
        ]);

        return [$customer, $password, $redirectUrl];
    }

    /**
     * After plugin to save subscription attributes to customer_entity table
     * This is needed because attributes are defined as both EAV and table columns
     *
     * @param AccountManagementInterface $subject
     * @param CustomerInterface $result
     * @return CustomerInterface
     */
    public function afterCreateAccount(
        AccountManagementInterface $subject,
        CustomerInterface $result
    ) {
        try {
            // Get subscription data from session
            $subscriptionData = $this->session->getSubscriptionPreferences();

            if (!empty($subscriptionData) && $result->getId()) {
                // Load customer model to update table columns directly
                $customerModel = $this->customerFactory->create();
                $this->customerResource->load($customerModel, $result->getId());

                // Set attributes on model (this updates customer_entity table columns)
                $customerModel->setData('allow_call', $subscriptionData['allow_call'] ?? 0);
                $customerModel->setData('allow_sms', $subscriptionData['allow_sms'] ?? 0);
                $customerModel->setData('allow_whatsapp', $subscriptionData['allow_whatsapp'] ?? 0);

                // Save customer model
                $this->customerResource->save($customerModel);

                $this->logger->info("Saved subscription attributes to customer_entity table via afterCreateAccount", [
                    'customer_id' => $result->getId(),
                    'allow_call' => $subscriptionData['allow_call'] ?? 0,
                    'allow_sms' => $subscriptionData['allow_sms'] ?? 0,
                    'allow_whatsapp' => $subscriptionData['allow_whatsapp'] ?? 0
                ]);

                // Clear session data after use
                $this->session->unsSubscriptionPreferences();
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in afterCreateAccount plugin", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $result;
    }
}