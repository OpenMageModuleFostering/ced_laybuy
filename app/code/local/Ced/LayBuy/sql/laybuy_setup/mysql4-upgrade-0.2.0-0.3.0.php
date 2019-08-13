<?php
$installer = $this;
$installer->startSetup();
$installer->getConnection()->addColumn($this->getTable('laybuy/report'),'report','text');
$installer->endSetup();