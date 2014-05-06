<?php
class Diglin_Mor_CustomerController extends Mage_Core_Controller_Front_Action
{
	/**
     * Check customer authentication
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $action = $this->getRequest()->getActionName();
        $loginUrl = Mage::helper('customer')->getLoginUrl();

        if (!Mage::getSingleton('customer/session')->authenticate($this, $loginUrl)) {
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
        }
    }
    
    
    public function indexAction()
    {
    	$this->_forward('history', 'customer', 'mor', null);
    }
    
    public function historyAction(){
		$this->loadLayout();
		$this->_initLayoutMessages('customer/session');
        
        /* @var $helper Diglin_Mor_Helper_Data */
        $helper = Mage::helper('mor');
        $session = Mage::getSingleton('customer/session');
        $customer = $session->getCustomer();
        $balance = 0;
        
        try{
			$vars = new Varien_Object();
			$vars->setUserId($customer->getMorUserid());
			$vars->setPeriodStart();
			$vars->setPeriodEnd();
			$vars->setDirection();
			//$vars->setDevice();// values: all, numeric value of device_id
			//$vars->setCalltype();// values: all, answered, busy, no_answer, failed, missed, missed_inc, missed_inc_all, missed_not_processed_inc
			$vars->setHash($helper->generateHashKey($vars));
			
            $response = $helper->callMorApi(Diglin_Mor_Model_Mor::MOR_PATH_GET_HISTORY_CALLS, $vars);
            if($response->error){
                Mage::throwException($helper->__('An error occured while retreiving user history with the error %s.', $response->error));
            }else{
                //@todo retreive list of calls history
                $list = $response;
            }
            
            // Get the current Balance
            $vars = new Varien_Object();
            $vars->setUsername('user'.$customer->getId());
            $response = $helper->callMorApi(Diglin_Mor_Model_Mor::MOR_PATH_GET_BALANCE, $vars);

            if($response->error){//@todo check the error format
                Mage::throwException($helper->__('Problem occured while getting the user "user%s" balance with the following error %s', $customer->getId(), $response->error));
            }else{
                //@todo get the balance
                $balance = $response->balance;
            }
		
        	$block = $this->getLayout()->getBlock('mor_customer_history');
            if ($block) {
                $block->setCallHistoryItems($list);
                $block->setUserBalance($balance);    
                $block->setRefererUrl($this->_getRefererUrl());
            }
            $headBlock = $this->getLayout()->getBlock('head');
            if ($headBlock) {
                $headBlock->setTitle(Mage::helper('mor')->__('My Calls History & Balance'));
            }
            $this->renderLayout();
        }catch(Exception $e){
    		Mage::getSingleton('core/session')->addError($e->getMessage());
            $this->_redirectError(Mage::getUrl('customer/account/index', array('_secure'=>true)));
            return;
    	}
    }
}