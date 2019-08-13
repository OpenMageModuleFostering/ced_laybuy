<?php
$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup */

$installer->startSetup();

$installer->run("

ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `laybuy_init` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `laybuy_months` VARCHAR( 255 ) NOT NULL ;



ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `laybuy_init` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `laybuy_months` VARCHAR( 255 ) NOT NULL ;

");

$installer->endSetup();