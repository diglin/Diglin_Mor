<?php

class Diglin_Mor_Model_Mor extends Mage_Core_Model_Abstract
{
    const MOR_PATH_REGISTER_USER = '/billing/api/user_register';
    const MOR_PATH_LOGIN = '/billing/api/login';
    const MOR_PATH_LOGOUT = '/billing/api/logout';
    const MOR_PATH_GET_BALANCE = '/billing/api/balance';
    const MOR_PATH_CHARGE_ACCOUNT = '/billing/api/change_user_balance';
    const MOR_PATH_GET_HISTORY_CALLS = '/billing/api/user_calls';
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('mor/mor');
    }
    
    /**
     * Triggered on event customer_save_after
     * 
     * @param Varien_Event_Observer $observer
     */
    public function saveMorCustomer ($observer){
        
        $customer = $observer->getEvent()->getCustomer();
        
        if(!Mage::getStoreConfigFlag('mor/customer/register_allowed') || $customer->getMorUserSaved()){ // $customer->getMorUserSaved() prevent loop
            return;
        }
        
        Mage::log('Save user' . $customer->getId() . ' to MOR');
        
        try{
            $vars = new Varien_Object();
            $vars->setId(md5(uniqid()));
            $vars->setUsername('user'.$customer->getId());
            $vars->setPassword($customer->getPassword());
            $vars->setPassword2($customer->getPassword());
            $vars->setFirstName($customer->getFirstname());
            $vars->setLastName($customer->getLastname());
            $vars->setEmail($customer->getEmail());
            /*
            $vars->setCountryId();
            $vars->setDeviceType();
            */
            
            //@todo to test in MOR 10
            $response = Mage::helper('mor')->callMorApi(self::MOR_PATH_REGISTER_USER, $vars);
            if($response->status == 'Registration successful'){
                $customer->setMorUserid($vars->getId());
                $customer->setMorUserSaved(true);
                $customer->save();
            }else{
                Mage::throwException(Mage::helper('mor')->__('User %s not saved in MOR with the following error %s.', $customer->getId(), $response->error));
            }
            
        } catch(Exception $e){
            Mage::log($e->__toString());
            /* @var $session Mage_Customer_Model_Session */
            $session = Mage::getSingleton('customer/session');
            $session->addError(Mage::helper('mor')->__('An error occured while saving your information. Please, contact the owner of this shop.'));
        }
    }
    
    /**
     * Triggered on event customer_login
     * 
     * @param Varien_Event_Observer $observer
     */
    public function onLoginToMor ($observer)
    {
        if(!Mage::getStoreConfigFlag('mor/customer/loginlogout_allowed')){
            return;
        }

        $customer = $observer->getEvent()->getCustomer();
        $helper = Mage::helper('mor');
        /* @var $session Mage_Customer_Model_Session */
        $session = Mage::getSingleton('customer/session');
            
        Mage::log('Login user' . $customer->getId() . ' to MOR');
        
        try {
            $vars = new Varien_Object();
            $vars->setU('user'.$customer->getId());
            $vars->setP($customer->getPassword());
            
            $response = Mage::helper('mor')->callMorApi(self::MOR_PATH_LOGIN, $vars);
            
            if($response->status == 'failed'){
                Mage::throwException($helper->__('An error occured with MOR login with user %s and password %s', 'user'.$customer->getId(), $customer->getPassword()));
            }
            
            $session->setMorPass($customer->encryptPassword($customer->getPassword()));
            
        } catch (Exception $e) {
            Mage::log($e->__toString());
            $session->addError($helper->__('The log in has not been correctly done in our system. Please, contact the owner of this shop.'));
        }
    }
    
    /**
     * Triggered on event customer_logout
     * 
     * @param Varien_Event_Observer $observer
     */
    public function onLogoutToMor ($observer){
        if(!Mage::getStoreConfigFlag('mor/customer/loginlogout_allowed')){
            return;
        }
        
        $customer = $observer->getEvent()->getCustomer();
        $helper = Mage::helper('mor');
        /* @var $session Mage_Customer_Model_Session */
        $session = Mage::getSingleton('customer/session');
            
        Mage::log('Logout user' . $customer->getId() . ' to MOR');
        
        try {
            $vars = new Varien_Object();
            $vars->setU('user'.$customer->getId());
            $vars->setP($customer->decryptPassword($session->getMorPass()));
            
            $response = Mage::helper('mor')->callMorApi(self::MOR_PATH_LOGOUT, $vars);
            
            if($response->status == 'failed'){
                Mage::throwException($helper->__('An error occured with MOR logout with user %s and password %s', 'user'.$customer->getId(), $customer->getPassword()));
            }
            
            $session->setMorPass();
        } catch (Exception $e) {
            Mage::log($e->__toString());
            $session->addError($helper->__('The log out has not been correctly done in our system. Please, contact the owner of this shop.'));
        }
    }
    
    /**
     * Triggered on event sales_order_save_commit_after
     * 
     * @param Varien_Event_Observer $observer
     */
    public function onSalesChargeMorAccount($observer){
        /* @var $order Mage_Sales_Model_Order */
        $order = $observer->getEvent()->getOrder();
        /* var $hellper Diglin_Mor_Helper_Data */
        $helper = Mage::helper('mor');
        
        if(!Mage::getStoreConfigFlag('mor/sales/chargerefund_allowed', $order->getStoreId())){
            return;
        }
        
        $customerId = $order->getCustomerId();
        $morOrderStatusActivation = Mage::getStoreConfig('mor/sales/order_status_activation', $order->getStoreId());
        $availableStatuses = array($morOrderStatusActivation, Mage_Sales_Model_Order_Item::STATUS_INVOICED);
        $toCharge = 0;
        $toRefund = 0;
        $isVirtualCard = false;
        
        foreach ($order->getAllItems() as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            if ($product->getId() && $product->getIsVirtualCard()) {
                $isVirtualCard = true;
                if (in_array($item->getStatusId(), $availableStatuses)) {
                    $toCharge += $item->getQtyInvoiced() * $item->getPriceInclTax();
                }
                
                if($item->getStatusId() == Mage_Sales_Model_Order_Item::STATUS_REFUNDED){
                    $toRefund += $item->getQtyRefunded() * $item->getPriceInclTax();
                }
            }
        }
        
        if(!$isVirtualCard || (($toCharge + $toRefund) == 0)){
            return;
        }
        
        try{
            //@todo to test in MOR 10
            
            // Get the current Balance
            $vars = new Varien_Object();
            $vars->setUsername('user'.$customerId);
            $response = $helper->callMorApi(self::MOR_PATH_GET_BALANCE, $vars);

            if($response->error){//@todo check the error format
                Mage::throwException($helper->__('Problem occured while getting the user "user%s" balance with the following error %s', $customerId, $response->error));
            }else{
                //@todo get the balance
                $oldBalance = $response->balance;
            }
            
            // Change the user balance
            $vars = new Varien_Object();
            $newBalance = $oldBalance + $toCharge - $toRefund;
            $vars->setUsername('user'.$customerId);
            $vars->setU();//@todo check which username to give here 
            $vars->setP();//@todo check which password to give here
            $vars->setBalance($newBalance);
            $vars->setHash($helper->generateHashKey($vars));
            
            $response = $helper->callMorApi(self::MOR_PATH_LOGOUT, $vars);
            
            if($response->status == 'failed'){
                Mage::throwException($helper->__('An error occured while changing the balance of the user "user%s"', 'user', $customerId));
            }
        } catch (Exception $e) {
            Mage::log($e->__toString());
            /* @var $session Mage_Customer_Model_Session */
            Mage::getSingleton('customer/session')->addError($helper->__('An error occured while changing your balance of your account. Please, contact the owner of this shop.'));
        }
        
    }
}