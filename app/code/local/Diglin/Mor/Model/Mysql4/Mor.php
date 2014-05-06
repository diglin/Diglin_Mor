<?php

class Diglin_Mor_Model_Mysql4_Mor extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the mor_id refers to the key field in your database table.
        $this->_init('mor/mor', 'mor_id');
    }
}