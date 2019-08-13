<?php
/**
 * Lay-Buys
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Ced
 * @package     Ced_LayBuy
 * @author 		Asheesh Singh<asheeshsingh@cedcoss.com>
 * @copyright   Copyright LAY-BUYS (2011). (http://lay-buys.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Ced_LayBuy_Model_System_Config_Backend_Unsetadvertize extends Mage_Core_Model_Config_Data
{
    /**
     * Cron settings after save
     * @return void
     */
    
     protected function _afterLoad()
    {
        if ($this->getValue()) {
            $value = $this->getValue();
            $this->setValue($value);
        }
    }
 
    protected function _beforeSave()
    {
        //$this->getValue();
        $enable=0;
        $enable = Mage::getModel('core/config_data')->load('payment/laybuy/active', 'path')->getValue();
        if($enable==0){
           $this->setValue('0');
        }
        
    }

    protected function _afterSave()
    {
        $enable=0;
        $enable =  Mage::getModel('core/config_data')->load('payment/laybuy/active', 'path')->getValue();
        if($enable==0){
            Mage::getModel('core/config_data')
            ->load('laybuy/advertize/active', 'path')
            ->setValue('0')
            ->setPath('laybuy/advertize/active')
            ->save();
        }
        return parent::_afterSave();
    }

}