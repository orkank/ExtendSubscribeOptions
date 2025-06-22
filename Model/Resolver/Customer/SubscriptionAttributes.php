<?php
declare(strict_types=1);

namespace IDangerous\ExtendSubscribeOptions\Model\Resolver\Customer;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;

class SubscriptionAttributes implements ResolverInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, $value = null, array $args = null)
    {
        $customer = null;

        // Handle different value structures
        if (is_array($value) && isset($value['model'])) {
            // Standard structure with model key
            $customer = $value['model'];
        } elseif (is_object($value) && method_exists($value, 'getCustomAttribute')) {
            // Direct customer object
            $customer = $value;
        } else {
            $this->logger->error('Customer data is missing or invalid in subscription resolver', [
                'value_type' => gettype($value),
                'value_keys' => is_array($value) ? array_keys($value) : 'not_array'
            ]);
            return false;
        }

        $fieldName = $field->getName();

        try {
            // Get the custom attribute value
            $customAttribute = $customer->getCustomAttribute($fieldName);

            if ($customAttribute === null) {
                // If custom attribute doesn't exist, return false as default
                return false;
            }

            $attributeValue = $customAttribute->getValue();

            // Convert to boolean - attribute stores as string "1" or "0"
            return (bool) $attributeValue;

        } catch (\Exception $e) {
            $this->logger->error('Error retrieving subscription attribute: ' . $e->getMessage(), [
                'field_name' => $fieldName,
                'customer_id' => method_exists($customer, 'getId') ? $customer->getId() : 'unknown'
            ]);
            return false;
        }
    }
}