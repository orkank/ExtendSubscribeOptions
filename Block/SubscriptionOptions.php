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

    public function isAnyOptionEnabled()
    {
        return $this->isCallEnabled() || $this->isSmsEnabled() || $this->isWhatsappEnabled();
    }
}