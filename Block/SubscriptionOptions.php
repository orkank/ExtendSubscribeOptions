<?php
namespace IDangerous\ExtendSubscribeOptions\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

class SubscriptionOptions extends Template
{
    protected $scopeConfig;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    public function isEmailEnabled()
    {
        return $this->scopeConfig->getValue('subscription_options/general/enable_email');
    }

    public function isCallEnabled()
    {
        return $this->scopeConfig->getValue('subscription_options/general/enable_call');
    }

    public function isSmsEnabled()
    {
        return $this->scopeConfig->getValue('subscription_options/general/enable_sms');
    }

    public function isWhatsappEnabled()
    {
        return $this->scopeConfig->getValue('subscription_options/general/enable_whatsapp');
    }

    public function getOptionLabel($type)
    {
        $value = $this->scopeConfig->getValue("subscription_options/general/{$type}_label");
        return $value ?: __('Allow for ' . ucfirst($type));
    }

    public function getOptionSubtitle($type)
    {
        return $this->scopeConfig->getValue("subscription_options/general/{$type}_subtitle");
    }

    public function getOptionDescription($type)
    {
        return $this->scopeConfig->getValue("subscription_options/general/{$type}_description");
    }

    public function isAnyOptionEnabled()
    {
        return $this->isEmailEnabled() || $this->isCallEnabled() || $this->isSmsEnabled() || $this->isWhatsappEnabled();
    }
}