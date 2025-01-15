<?php
namespace IDangerous\ExtendSubscribeOptions\Plugin\Customer;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session;
class AccountManagement
{
    protected $request;
    protected $customerRepository;
    protected $logger;
    protected $customerSession;
    public function __construct(
        RequestInterface $request,
        LoggerInterface $logger,
        CustomerRepositoryInterface $customerRepository,
        Session $customerSession
    ) {
        $this->request = $request;
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
    }

    /**
     * After plugin to execute logic after customer registration
     *
     * @param \Magento\Customer\Model\AccountManagement $subject
     * @param CustomerInterface $result
     * @param CustomerInterface $customer
     * @param string $password
     * @param string|null $redirectUrl
     * @return CustomerInterface
     */
    public function afterCreateAccount(
      \Magento\Customer\Model\AccountManagement $subject,
      $result,
      CustomerInterface $customer,
      $password = null,
      $redirectUrl = null
    ) {
        // Custom logic after registration
        // Example: Log the new customer ID
        $customerId = $result->getId();
        $this->logger->info("Customer with ID $customerId has been registered.");
        $subscriptionData = $this->request->getParam('subscription', []);

        $customer = $this->customerRepository->getById($customerId);

        $customer->setCustomAttribute('allow_call', isset($subscriptionData['allow_call']) ? 1 : 0);
        $customer->setCustomAttribute('allow_sms', isset($subscriptionData['allow_sms']) ? 1 : 0);
        $customer->setCustomAttribute('allow_whatsapp', isset($subscriptionData['allow_whatsapp']) ? 1 : 0);

        $this->customerRepository->save($customer);

        return $result;
    }
}