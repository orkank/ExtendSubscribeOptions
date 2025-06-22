<?php
declare(strict_types=1);

namespace IDangerous\ExtendSubscribeOptions\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class SubscriptionOptionsConfig implements ResolverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Configuration paths
     */
    private const CONFIG_PATH_PREFIX = 'subscription_options/general/';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, $value = null, array $args = null)
    {
        try {
            $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();

            $config = [
                'call' => $this->getOptionConfig('call', $storeId),
                'sms' => $this->getOptionConfig('sms', $storeId),
                'whatsapp' => $this->getOptionConfig('whatsapp', $storeId)
            ];

            $this->logger->info('Subscription options config requested via GraphQL', [
                'store_id' => $storeId,
                'config' => $config
            ]);

            return $config;

        } catch (\Exception $e) {
            $this->logger->error('Error retrieving subscription options config via GraphQL: ' . $e->getMessage());

            // Return default config in case of error
            return [
                'call' => ['enabled' => false, 'label' => '', 'description' => ''],
                'sms' => ['enabled' => false, 'label' => '', 'description' => ''],
                'whatsapp' => ['enabled' => false, 'label' => '', 'description' => '']
            ];
        }
    }

    /**
     * Get configuration for a specific subscription option
     *
     * @param string $optionType
     * @param int $storeId
     * @return array
     */
    private function getOptionConfig(string $optionType, int $storeId): array
    {
        $enabledPath = self::CONFIG_PATH_PREFIX . 'enable_' . $optionType;
        $labelPath = self::CONFIG_PATH_PREFIX . $optionType . '_label';
        $descriptionPath = self::CONFIG_PATH_PREFIX . $optionType . '_description';

        $enabled = (bool) $this->scopeConfig->getValue(
            $enabledPath,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $label = (string) $this->scopeConfig->getValue(
            $labelPath,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $description = (string) $this->scopeConfig->getValue(
            $descriptionPath,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return [
            'enabled' => $enabled,
            'label' => $label ?: ucfirst($optionType), // Fallback to capitalized option type
            'description' => $description ?: ''
        ];
    }
}