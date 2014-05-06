<?php
class Diglin_Mor_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
    	$this->_redirect('*/customer');
    }
}