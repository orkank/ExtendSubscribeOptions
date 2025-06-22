<?php
declare(strict_types=1);

namespace IDangerous\ExtendSubscribeOptions\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Magento\Framework\Api\DataObjectHelper;

use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Customer\Model\CustomerFactory;
use Psr\Log\LoggerInterface;

class UpdateCustomerSubscriptionOptions implements ResolverInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var ExtractCustomerData
     */
    private $extractCustomerData;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;



    /**
     * @var CustomerResource
     */
    private $customerResource;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param GetCustomer $getCustomer
     * @param ExtractCustomerData $extractCustomerData
     * @param DataObjectHelper $dataObjectHelper
     * @param CustomerResource $customerResource
     * @param CustomerFactory $customerFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        GetCustomer $getCustomer,
        ExtractCustomerData $extractCustomerData,
        DataObjectHelper $dataObjectHelper,
        CustomerResource $customerResource,
        CustomerFactory $customerFactory,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->getCustomer = $getCustomer;
        $this->extractCustomerData = $extractCustomerData;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->customerResource = $customerResource;
        $this->customerFactory = $customerFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, $value = null, array $args = null)
    {
        if (!isset($args['input']) || !is_array($args['input']) || empty($args['input'])) {
            throw new GraphQlInputException(__('Specify the "input" value.'));
        }

        $customer = $this->getCustomer->execute($context);
        if (!$customer->getId()) {
            throw new GraphQlAuthorizationException(__('Current customer does not have access to the resource.'));
        }

        // Reload customer from repository to ensure we have all attributes
        $customer = $this->customerRepository->getById($customer->getId());

        $input = $args['input'];

                try {
            // Load customer model directly for attribute manipulation
            $customerModel = $this->customerFactory->create();
            $this->customerResource->load($customerModel, $customer->getId());

            $subscriptionAttributes = ['allow_call', 'allow_sms', 'allow_whatsapp'];

            // Update subscription attributes using customer model
            foreach ($subscriptionAttributes as $attributeCode) {
                if (isset($input[$attributeCode])) {
                    $value = $input[$attributeCode] ? '1' : '0';

                    // Set attribute on customer model
                    $customerModel->setData($attributeCode, $value);

                    $this->logger->info('Setting ' . $attributeCode . ' attribute on model', [
                        'customer_id' => $customerModel->getId(),
                        'value' => $value,
                        'input_value' => $input[$attributeCode]
                    ]);
                }
            }

            // Save customer model directly
            $this->customerResource->save($customerModel);

            // Reload customer from repository to get updated data
            $customer = $this->customerRepository->getById($customer->getId());

                        // Log all custom attributes after saving
            $customAttributes = $customer->getCustomAttributes();
            $attributeData = [];
            foreach ($customAttributes as $attribute) {
                $attributeData[$attribute->getAttributeCode()] = $attribute->getValue();
            }
            $this->logger->info('Customer custom attributes after save', [
                'customer_id' => $customer->getId(),
                'attributes' => $attributeData
            ]);

            $updatedCustomer = $customer;

            $this->logger->info('Customer subscription options updated via GraphQL', [
                'customer_id' => $updatedCustomer->getId(),
                'allow_call' => $input['allow_call'] ?? 'not_set',
                'allow_sms' => $input['allow_sms'] ?? 'not_set',
                'allow_whatsapp' => $input['allow_whatsapp'] ?? 'not_set'
            ]);

            // Convert customer object to array format for GraphQL
            $customerData = $this->extractCustomerData->execute($updatedCustomer);

            return [
                'customer' => $customerData
            ];

        } catch (\Exception $e) {
            $this->logger->error('Error updating customer subscription options via GraphQL: ' . $e->getMessage());
            throw new GraphQlInputException(__('Unable to update customer subscription options: %1', $e->getMessage()));
        }
    }
}