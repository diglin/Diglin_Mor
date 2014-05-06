<?php
class Diglin_Mor_Model_System_Config_Source_Orderitemstatus
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Mage_Sales_Model_Order_Item::STATUS_PENDING,
                'label' => Mage::helper('mor')->__('Pending')
            ),
            array(
                'value' => Mage_Sales_Model_Order_Item::STATUS_INVOICED,
                'label' => Mage::helper('mor')->__('Invoiced')
            )
        );
    }
}
