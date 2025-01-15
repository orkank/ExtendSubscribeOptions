<?php
namespace IDangerous\ExtendSubscribeOptions\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class AddCustomerAttributes implements DataPatchInterface, PatchRevertableInterface
{
    private $moduleDataSetup;
    private $customerSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
    }

    public function apply()
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $attributes = [
            'allow_call' => 'Allow for Call',
            'allow_sms' => 'Allow for SMS',
            'allow_whatsapp' => 'Allow for WhatsApp'
        ];

        foreach ($attributes as $attributeCode => $attributeLabel) {
            $customerSetup->addAttribute(
                Customer::ENTITY,
                $attributeCode,
                [
                    'type' => 'int',
                    'label' => $attributeLabel,
                    'input' => 'boolean',
                    'required' => false,
                    'visible' => true,
                    'user_defined' => true,
                    'system' => false,
                    'position' => 100,
                    'admin_checkout' => 1,
                    'is_used_in_grid' => 1,
                    'is_visible_in_grid' => 1,
                    'is_filterable_in_grid' => 1,
                    'is_searchable_in_grid' => 1
                ]
            );

            // Get the attribute ID
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeCode);

            // Add attribute to forms
            $attribute->addData([
                'used_in_forms' => [
                    'adminhtml_customer',
                    'customer_account_create',
                    'customer_account_edit',
                    'checkout_register',
                    'newsletter_manage'
                ]
            ]);
            $attribute->save();
        }

        return $this;
    }

    public function revert()
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $customerSetup->removeAttribute(Customer::ENTITY, 'allow_call');
        $customerSetup->removeAttribute(Customer::ENTITY, 'allow_sms');
        $customerSetup->removeAttribute(Customer::ENTITY, 'allow_whatsapp');
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}