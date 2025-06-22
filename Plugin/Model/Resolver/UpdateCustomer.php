<?php
declare(strict_types=1);

namespace IDangerous\ExtendSubscribeOptions\Plugin\Model\Resolver;

use Magento\CustomerGraphQl\Model\Resolver\UpdateCustomer as Subject;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Psr\Log\LoggerInterface;

class UpdateCustomer
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var ExtractCustomerData
     */
    private $extractCustomerData;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param ExtractCustomerData $extractCustomerData
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        ExtractCustomerData $extractCustomerData,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->extractCustomerData = $extractCustomerData;
        $this->logger = $logger;
    }

    /**
     * Process subscription attributes in updateCustomer mutation
     *
     * @param Subject $subject
     * @param callable $proceed
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed
     */
    public function aroundResolve(
        Subject $subject,
        callable $proceed,
        Field $field,
        $context,
        ResolveInfo $info,
        $value = null,
        array $args = null
    ) {
        // Call the original resolver first
        $result = $proceed($field, $context, $info, $value, $args);

        // Check if subscription attributes are provided in the input
        if (isset($args['input']) && is_array($args['input'])) {
            $input = $args['input'];
            $subscriptionAttributes = ['allow_call', 'allow_sms', 'allow_whatsapp'];

            $hasSubscriptionData = false;
            foreach ($subscriptionAttributes as $attribute) {
                if (isset($input[$attribute])) {
                    $hasSubscriptionData = true;
                    break;
                }
            }

                        if ($hasSubscriptionData && isset($result['customer'])) {
                try {
                    // Get the customer object from the result - result['customer'] is array format with 'model' key
                    $customerData = $result['customer'];
                    if (isset($customerData['model'])) {
                        $customer = $customerData['model'];
                    } else {
                        // Fallback: try to get customer by ID if model not available
                        $customerId = $customerData['id'] ?? null;
                        if ($customerId) {
                            $customer = $this->customerRepository->getById($customerId);
                        } else {
                            throw new \Exception('Cannot find customer object to update subscription attributes');
                        }
                    }

                    // Update subscription attributes
                    foreach ($subscriptionAttributes as $attribute) {
                        if (isset($input[$attribute])) {
                            $customer->setCustomAttribute($attribute, $input[$attribute] ? '1' : '0');
                        }
                    }

                    // Save the customer with updated subscription attributes
                    $updatedCustomer = $this->customerRepository->save($customer);

                    // Convert back to array format for GraphQL response
                    $result['customer'] = $this->extractCustomerData->execute($updatedCustomer);

                    $this->logger->info('Customer subscription attributes updated via standard updateCustomer mutation', [
                        'customer_id' => $updatedCustomer->getId(),
                        'subscription_data' => array_intersect_key($input, array_flip($subscriptionAttributes))
                    ]);

                } catch (\Exception $e) {
                    $this->logger->error('Error updating subscription attributes in updateCustomer mutation: ' . $e->getMessage());
                    // Don't throw exception, just log it - the main customer update was successful
                }
            }
        }

        return $result;
    }
}