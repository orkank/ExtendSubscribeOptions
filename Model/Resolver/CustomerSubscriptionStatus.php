<?php
declare(strict_types=1);

namespace IDangerous\ExtendSubscribeOptions\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Psr\Log\LoggerInterface;

class CustomerSubscriptionStatus implements ResolverInterface
{
    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var CustomerResource
     */
    private $customerResource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param GetCustomer $getCustomer
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerFactory $customerFactory
     * @param CustomerResource $customerResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetCustomer $getCustomer,
        CustomerRepositoryInterface $customerRepository,
        CustomerFactory $customerFactory,
        CustomerResource $customerResource,
        LoggerInterface $logger
    ) {
        $this->getCustomer = $getCustomer;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->customerResource = $customerResource;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, $value = null, array $args = null)
    {
        // Check if customer is authenticated
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        try {
            // Get the authenticated customer
            $customer = $this->getCustomer->execute($context);

            if (!$customer->getId()) {
                throw new GraphQlAuthorizationException(__('Customer not found.'));
            }

            // Load customer model directly to get all attributes
            $customerModel = $this->customerFactory->create();
            $this->customerResource->load($customerModel, $customer->getId());

            // Get subscription status from customer model data
            $allowCall = $this->getAttributeValueFromModel($customerModel, 'allow_call');
            $allowSms = $this->getAttributeValueFromModel($customerModel, 'allow_sms');
            $allowWhatsapp = $this->getAttributeValueFromModel($customerModel, 'allow_whatsapp');

            $subscriptionStatus = [
                'customer_id' => (int) $customer->getId(),
                'email' => $customer->getEmail(),
                'allow_call' => $allowCall,
                'allow_sms' => $allowSms,
                'allow_whatsapp' => $allowWhatsapp
            ];

            $this->logger->info('Customer subscription status requested via GraphQL', [
                'customer_id' => $customer->getId(),
                'subscription_status' => $subscriptionStatus
            ]);

            return $subscriptionStatus;

        } catch (GraphQlAuthorizationException $e) {
            // Re-throw authorization exceptions
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Error retrieving customer subscription status via GraphQL: ' . $e->getMessage());
            throw new GraphQlAuthorizationException(__('Unable to retrieve subscription status.'));
        }
    }

        /**
     * Get custom attribute value as boolean from customer model
     *
     * @param \Magento\Customer\Model\Customer $customerModel
     * @param string $attributeCode
     * @return bool
     */
    private function getAttributeValueFromModel($customerModel, string $attributeCode): bool
    {
        try {
            // Get data directly from customer model
            $value = $customerModel->getData($attributeCode);

            // Log for debugging
            $this->logger->info('Getting attribute from model', [
                'attribute_code' => $attributeCode,
                'value' => $value,
                'customer_id' => $customerModel->getId()
            ]);

            // Convert to boolean - attribute stores as "1" or "0" or null
            return (bool) $value;

        } catch (\Exception $e) {
            $this->logger->error('Error retrieving subscription attribute from model: ' . $attributeCode . ' - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get custom attribute value as boolean (legacy method)
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string $attributeCode
     * @return bool
     */
    private function getAttributeValue($customer, string $attributeCode): bool
    {
        try {
            $customAttribute = $customer->getCustomAttribute($attributeCode);

            if ($customAttribute === null) {
                return false;
            }

            $value = $customAttribute->getValue();

            // Convert to boolean - attribute stores as string "1" or "0"
            return (bool) $value;

        } catch (\Exception $e) {
            $this->logger->error('Error retrieving subscription attribute: ' . $attributeCode . ' - ' . $e->getMessage());
            return false;
        }
    }
}