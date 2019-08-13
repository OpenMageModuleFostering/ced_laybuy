<?php 
class Ced_LayBuy_Block_Express_Layform extends Mage_Core_Block_Template
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
		//parent::_construct();
		
		$laybuyMark = Mage::getConfig()->getBlockClassName('core/template');
		$laybuyMark = new $laybuyMark;
		//$laybuyMark->setTemplate('laybuy/form/laybuy.phtml')
		//->setLayBuyTitle(Mage::helper('laybuy')->__('A recurring payment solution'))
		//->setPaymentAcceptanceMarkSrc('https://lay-buys.com/gateway/LAY-BUY.png')
		//->setPaymentAcceptanceMarkHref('https://lay-buys.com/');
		$note = 'Please Choose Your Payment Plan';
		$this->setTemplate('laybuy/express/extraexpress.phtml')
		->setMethodTitle('')
		->setExtraMessage('<b>'.$this->__('%s',$note).'</b>');
		//->setMethodLabelAfterHtml($laybuyMark->toHtml());
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