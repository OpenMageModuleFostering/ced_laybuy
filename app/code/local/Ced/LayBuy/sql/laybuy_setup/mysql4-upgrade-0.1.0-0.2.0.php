<?php
$installer = $this;
$installer->startSetup();
$installer->getConnection()->addColumn($this->getTable('laybuy/report'),'store_id','int');
$installer->getConnection()->addColumn($this->getTable('laybuy/report'),'created_at','datetime');
$installer->getConnection()->addColumn($this->getTable('laybuy/report'),'status','tinyint');
$installer->endSetup();