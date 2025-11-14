<?php
namespace IDangerous\ExtendSubscribeOptions\Plugin\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

class AdminSave
{
    protected $customerRepository;
    protected $customerFactory;
    protected $customerResource;
    protected $request;
    protected $logger;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerFactory $customerFactory,
        CustomerResource $customerResource,
        RequestInterface $request,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->customerResource = $customerResource;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * After plugin to save subscription attributes when admin saves customer
     *
     * @param \Magento\Customer\Controller\Adminhtml\Index\Save $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterExecute(
        \Magento\Customer\Controller\Adminhtml\Index\Save $subject,
        $result
    ) {
        try {
            $customerId = $this->request->getParam('customer_id') ?: $this->request->getParam('id');

            if (!$customerId) {
                return $result;
            }

            $postData = $this->request->getPostValue();

            // Get subscription data from POST
            $allowCall = isset($postData['customer']['allow_call']) ? (int)$postData['customer']['allow_call'] : 0;
            $allowSms = isset($postData['customer']['allow_sms']) ? (int)$postData['customer']['allow_sms'] : 0;
            $allowWhatsapp = isset($postData['customer']['allow_whatsapp']) ? (int)$postData['customer']['allow_whatsapp'] : 0;

            // Load customer model to update table columns directly
            $customerModel = $this->customerFactory->create();
            $this->customerResource->load($customerModel, $customerId);

            // Set attributes on model (this updates customer_entity table columns)
            $customerModel->setData('allow_call', $allowCall);
            $customerModel->setData('allow_sms', $allowSms);
            $customerModel->setData('allow_whatsapp', $allowWhatsapp);

            // Save customer model
            $this->customerResource->save($customerModel);

            $this->logger->info("Admin saved subscription attributes to customer_entity table", [
                'customer_id' => $customerId,
                'allow_call' => $allowCall,
                'allow_sms' => $allowSms,
                'allow_whatsapp' => $allowWhatsapp
            ]);

        } catch (\Exception $e) {
            $this->logger->error("Error in AdminSave plugin", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $result;
    }
}

