<?php
namespace IDangerous\ExtendSubscribeOptions\Block\Newsletter;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\CustomerFactory;

class Additional extends Template
{
    protected $scopeConfig;
    protected $customerSession;
    protected $customerFactory;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Session $customerSession,
        CustomerFactory $customerFactory,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->customerFactory = $customerFactory;
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

    public function getCustomerPreference($attribute)
    {
        try {
            $customerId = $this->customerSession->getCustomerId();
            if ($customerId) {
                $customer = $this->customerFactory->create();
                $customer->load($customerId);

                return (bool)$customer->getData($attribute);
            }
        } catch (\Exception $e) {
            // Log error if needed
        }
        return false;
    }
}