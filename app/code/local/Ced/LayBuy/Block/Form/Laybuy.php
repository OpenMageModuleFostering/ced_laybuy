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

/**
 * Block for LayBuy payment method form
 */
class Ced_LayBuy_Block_Form_Laybuy extends Mage_Payment_Block_Form
{
	protected $_storeId = null;
	
	public function getStoreId(){
		if(empty($this->_storeId)){
			$this->_storeId =  Mage::app()->getStore()->getId();	
		}
		return $this->_storeId;	
	}
	
	/**
     * Block construction. Set block template.
     */
    protected function _construct()
    {
        parent::_construct();
		
		$laybuyMark = Mage::getConfig()->getBlockClassName('core/template');
        $laybuyMark = new $laybuyMark;
        $laybuyMark->setTemplate('laybuy/form/laybuy.phtml')
			 ->setLayBuyTitle(Mage::helper('laybuy')->__('A recurring payment solution'))
			 ->setPaymentAcceptanceMarkSrc('https://lay-buys.com/gateway/LAY-BUY.png')
			 ->setPaymentAcceptanceMarkHref('https://lay-buys.com/');
		$note = 'Please Choose Your Payment Plan';
		$this->setTemplate('laybuy/form/extra.phtml')
			 ->setMethodTitle('')
			 ->setExtraMessage('<b>'.$this->__('%s',$note).'</b>')
			 ->setMethodLabelAfterHtml($laybuyMark->toHtml());
    }
	
	public function getArray($type){
		$totals = Mage::getSingleton('checkout/session')->getQuote()->getTotals();
		$grandtotal = round($totals["grand_total"]->getValue());
		return Mage::getModel('laybuy/report')->getArray($type,$grandtotal,$this->getStoreId());
	}
	
	public function getConfigData($field){
		return Mage::getStoreConfig('payment/laybuy/'.$field,$this->getStoreId());
	}

}