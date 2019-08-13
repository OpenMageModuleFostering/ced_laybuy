<?php
$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup */

$installer->startSetup();

$installer->run("
CREATE TABLE IF NOT EXISTS `{$installer->getTable('laybuy/laybuyresp')}` (
  `resp_id` int(100) NOT NULL AUTO_INCREMENT,
  `order_id` int(100) NOT NULL,
  `response` text NOT NULL,
  PRIMARY KEY (`resp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
");

$installer->endSetup();
