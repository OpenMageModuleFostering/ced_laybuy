<?php
$installer = $this;
$installer->startSetup();
$installer->run("
		CREATE TABLE IF NOT EXISTS `".$this->getTable('laybuy/report')."` (
		`id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
		`order_id` text NOT NULL COMMENT 'ORDER ID',
		`firstname` varchar(100) NOT NULL COMMENT 'FIRST NAME',
		`lastname` varchar(100) DEFAULT NULL COMMENT 'LAST NAME',
		`address` text COMMENT 'ADDRESS',
		`suburb` text COMMENT 'SUBURB',
		`state` text COMMENT 'STATE',
		`country` text COMMENT 'COUNTRY',		`postcode` int(11) DEFAULT NULL COMMENT 'POSTCODE',
		`email` text COMMENT 'EMAIL',
		`amount` double NOT NULL COMMENT 'AMOUNT',
		`currency` varchar(5) NOT NULL COMMENT 'CURRENCY',
		`downpayment` double NOT NULL COMMENT 'DOWNPAYMENT',
		`months` int(11) NOT NULL COMMENT 'MONTHS',
		`downpayment_amount` double NOT NULL COMMENT 'DOWNPAYMENT_AMOUNT',
		`payment_amounts` double NOT NULL COMMENT 'PAYMENT_AMOUNTS',
		`first_payment_due` datetime NOT NULL COMMENT 'FIRST_PAYMENT_DUE',
		`last_payment_due` datetime NOT NULL COMMENT 'LAST_PAYMENT_DUE',
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
");
$installer->endSetup();