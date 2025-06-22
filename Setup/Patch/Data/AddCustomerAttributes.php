<?php
namespace IDangerous\ExtendSubscribeOptions\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;

class AddCustomerAttributes implements DataPatchInterface, PatchRevertableInterface
{
    private $moduleDataSetup;
    private $customerSetupFactory;
    private $attributeSetFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $attributes = [
            'allow_call' => 'Allow for Call',
            'allow_sms' => 'Allow for SMS',
            'allow_whatsapp' => 'Allow for WhatsApp'
        ];

        foreach ($attributes as $attributeCode => $attributeLabel) {
            // Remove attribute if it exists
            if ($customerSetup->getAttributeId('customer', $attributeCode)) {
                $customerSetup->removeAttribute('customer', $attributeCode);
            }

            $customerSetup->addAttribute(
                Customer::ENTITY,
                $attributeCode,
                [
                    'type' => 'int',
                    'label' => $attributeLabel,
                    'input' => 'select',
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'required' => false,
                    'visible' => true,
                    'user_defined' => true,
                    'position' => 999,
                    'system' => 0,
                    'default' => 0,
                    'unique' => false,
                    'sort_order' => 999,
                    'validate_rules' => null,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => false
                ]
            );

            $customerSetup->addAttributeToSet(
                'customer',
                $attributeSetId,
                $attributeGroupId,
                $attributeCode
            );

            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeCode)
                ->addData([
                    'attribute_set_id' => $attributeSetId,
                    'attribute_group_id' => $attributeGroupId,
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

        $this->moduleDataSetup->getConnection()->endSetup();
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