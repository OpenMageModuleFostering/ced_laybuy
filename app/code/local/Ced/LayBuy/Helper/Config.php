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
 * LayBuy Config helper
 */
 
class Ced_LayBuy_Helper_Config extends Mage_Core_Helper_Abstract
{
	protected $_storeId = null;
	public function getStoreId(){
		if(empty($this->_storeId))
			$this->_storeId = Mage::app()->getStore()->getId();
		return $this->_storeId;
	}
	/*
	 * Get the gateway submit url
	 */
	public function getSubmitUrl(){
		return Mage::getStoreConfig('payment/laybuy/submit_url');
	}	
	/*
	 * For form filed At LayBuy gateway
	 */
	public function getStandardCheckoutFormFields($data){
		return $data;
	}
	/*
	 * For form field At LayBuy gateway
	 */
	public function extractAndPrepareRequiredValueForFormFields($chekoutSession){
		$descKey = 'DESC';				$data = array();		
		$storeId = $this->getStoreId();
		$orderId = $chekoutSession->getLastRealOrderId();				if($orderId){
			$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);			$customerOptions = $order->getPayment()->getData('additional_information');
			$amount = $order->getData('grand_total');
			$currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
			$email = $order->getData('customer_email');
			/* for adding product's description */			$description ='';
			if(Mage::getStoreConfig('payment/laybuy/multipledesc',$storeId)){							foreach($order->getAllItems() as $_product){					$item = $_product;
					$_product = Mage::getModel('catalog/product')->load($_product->getProductId());					if($description){
						$description .= ' <br/> ';					}					$description .= strip_tags($_product->getShortDescription()).'(Qty '.(int)$item->getData('qty_ordered').')';
				}
			}else{	
				$definedDescription = Mage::getStoreConfig('payment/laybuy/desc',$storeId);				$description .= $definedDescription;			
			}			
			$data = array(
						'AMOUNT'	=> number_format($amount, 2, '.', ''),
						'MEMBER' 	=> Mage::getStoreConfig('payment/laybuy/membership_number',$storeId),
						'CURRENCY'  => $currency_code,
						'RETURNURL' => Mage::getBaseUrl().'laybuy/standard/success',
						'CANCELURL' => Mage::getBaseUrl().'laybuy/standard/cancel',
						 $descKey	=> $description,
						'CUSTOM'	=> $orderId,
						'EMAIL' 	=> $email,
						);									$data['BYPASSLAYBUY'] = Mage::getStoreConfig('payment/laybuy/bypasslaybuy');						
			$MAXD   = Mage::getStoreConfig('payment/laybuy/maxd',$storeId);
			$MIND   = Mage::getStoreConfig('payment/laybuy/mind',$storeId);
			$IMAGE  = Mage::getStoreConfig('payment/laybuy/image',$storeId);			if(isset($customerOptions['MONTHS']) && $customerOptions['MONTHS']){				$MONTHS = $customerOptions['MONTHS'];			}else{				$MONTHS = Mage::getStoreConfig('payment/laybuy/months',$storeId);			}						if(isset($customerOptions['INIT']) && $customerOptions['INIT']){				$INIT = $customerOptions['INIT'];			}else{				$INIT = 0;			}						/* Restrict maxiumum possible downpayment percentage to less then or equal 50% */			
			if(!$MIND || $MIND<20 || $MIND>50){
				$MIND = 20;
			}			
			if(!$MAXD || $MAXD<20 || $MAXD>50){
				$MAXD = 50;
			}			
			if($IMAGE){
				$IMAGE = Mage::getBaseUrl('media')."laybuy/".$IMAGE;
			}else{
				$IMAGE = 'http://lay-buys.com/lb2.jpg';
			}			
			if(!$MONTHS || $MONTHS<0){
				$MONTHS = 3;
			}							if($INIT){				$data['INIT'] = $INIT;			}						$data['MIND'] = $MIND;
			$data['MAXD'] = $MAXD;
			$data['IMAGE'] = $IMAGE;
			$data['MONTHS'] = $MONTHS;			}		
		return $data;
	}
}