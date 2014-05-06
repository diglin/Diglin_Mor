<?php

class Diglin_Mor_Model_Mysql4_Mor_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('mor/mor');
    }
}