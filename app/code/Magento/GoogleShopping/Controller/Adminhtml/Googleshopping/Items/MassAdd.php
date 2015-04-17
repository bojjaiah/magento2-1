<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleShopping\Controller\Adminhtml\Googleshopping\Items;

class MassAdd extends \Magento\GoogleShopping\Controller\Adminhtml\Googleshopping\Items
{
    /**
     * Add (export) several products to Google Content
     *
     * @return \Magento\Framework\Controller\Result\Raw|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $flag = $this->_getFlag();
        if ($flag->isLocked()) {
            return $this->emptyResult();
        }

        session_write_close();
        ignore_user_abort(true);
        set_time_limit(0);

        $storeId = $this->_getStore()->getId();
        $productIds = $this->getRequest()->getParam('product', null);

        try {
            $flag->lock();
            $this->_objectManager->create('Magento\GoogleShopping\Model\MassOperations')
                ->setFlag($flag)
                ->addProducts($productIds, $storeId);
        } catch (\Zend_Gdata_App_CaptchaRequiredException $e) {
            // Google requires CAPTCHA for login
            $this->messageManager->addError(__($e->getMessage()));
            $flag->unlock();
            return $this->_redirectToCaptcha($e);
        } catch (\Exception $e) {
            $flag->unlock();
            $this->notifier->addMajor(
                __('An error has occurred while adding products to google shopping account.'),
                $e->getMessage()
            );
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            return $this->emptyResult();
        }

        $flag->unlock();
        return $this->emptyResult();
    }
}
