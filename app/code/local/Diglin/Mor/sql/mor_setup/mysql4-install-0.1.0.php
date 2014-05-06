<?php
$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup */

$attribute = array(
    'frontend_label' => 'MOR User ID',
    'visible'      => false,
    'required'     => false,
    'type'         => 'int',
    'input'        => 'text',
    'is_unique'	    => true,
);

$installer->addAttribute('customer','mor_userid', $attribute);