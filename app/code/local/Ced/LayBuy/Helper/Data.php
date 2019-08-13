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
 * LayBuy Data helper
 */
 
class Ced_LayBuy_Helper_Data extends Mage_Core_Helper_Abstract{

	public function getStatuses(){
		return array(
              '0' => $this->__("Pending"),
			  '1' => $this->__("Completed"),
              '-1'=> $this->__('Cancelled'),
			   '-2' => $this->__('Revise Requested'),
			  '2' => $this->__('Revised'),
          );
	}			
	public function fetchFromLaybuy($config){			
		$url = $config['hostname'];					
		$matchedData = $this->getMatchingData();
		/*$orderIds = *//* $this->getMatchingOrderIds() *//*$matchedData['orderIds']; */
		$profileIds = $matchedData['profileIds'];
		$data ='';					
		$data .= "mid=".$config['username']."&";					
		/* $data .= "custom=".$orderIds."&"; */
		$data .= "profileIds=".$profileIds;
		/*  echo $data;die;  */
		$ch = curl_init();					
		curl_setopt($ch, CURLOPT_URL,$url);					
		curl_setopt($ch, CURLOPT_POST, 1);					
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);					
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); /* use this to suppress output */					
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false); /* tell cURL to graciously accept an SSL certificate */					
		$result = curl_exec ($ch);					
		curl_close ($ch);		
		/* print_r($result); die; */
		return json_decode($result);				
	}
	
	public function getMatchingOrderIds(){
		$result = '';
		$collection = Mage::getModel('laybuy/report')->getCollection()
							/* ->addFieldToFilter('last_payment_due',array('lt'=>date('Y-m-d h:i:s',time()))) */
							   ->addFieldToFilter('status',array('eq'=>0)) ;
		foreach($collection as $report){
			$result .= $report->getData('laybuy_ref_no').",";
			/* $result .= $report->getData('order_id').","; */
		}
		return rtrim($result,',');
	}
	
	public function getMatchingData(){
		$result = array();
		$orderIds = '';
		$profileIds = '';
		$collection = Mage::getModel('laybuy/report')->getCollection()
							/* ->addFieldToFilter('last_payment_due',array('lt'=>date('Y-m-d h:i:s',time()))) */
							   ->addFieldToFilter('status',array('eq'=>0));
		foreach($collection as $report){
			$orderIds .= $report->getData('laybuy_ref_no').",";
			$profileIds .= $report->getData('paypal_profile_id').",";
			/* $result .= $report->getData('order_id').","; */
		}
		$result['orderIds'] = rtrim($orderIds,',');
		$result['profileIds'] = rtrim($profileIds,',');
		return $result;
	}
	
	public function cancelTransaction($report){
		$newStrReport = preg_replace('/Pending/i', 'Canceled', $report->getReport());
		try{
			Mage::log('cancel transaction called',null,'laybuy_success.log');
			if($this->cancelPaypalProfile($report->getPaypalProfileId(),$report->getStoreId())){
				if($this->processOrder($report->getOrderId(),0)){
					$report->setReport($newStrReport);
					$report->setStatus(-1);
					$report->save();
					return true;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}catch(Exception $e){
			return false;
		}
	}
	
	public function cancelPaypalProfile($paypalProfileId,$storeId){
		if(!$paypalProfileId)
			return true;
		Mage::log('cancel paypal profile called',null,'laybuy_success.log');
		$url = 'https://lay-buys.com/vtmob/deal5cancel.php';		
		$data ='';			
		$data .= "&mid=".Mage::getStoreConfig('payment/laybuy/membership_number',$storeId);
		$data .= "&paypal_profile_id=".$paypalProfileId;
		$ch = curl_init();		
		curl_setopt($ch, CURLOPT_URL,$url);		
		curl_setopt($ch, CURLOPT_POST, 1);		
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); /* use this to suppress output */		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false); /* tell cURL to graciously accept an SSL certificate */		
		$result = curl_exec ($ch);
		if($result == 'success'){
			$result = print_r($result,true);
			Mage::log('Cancel Request Array to LayBuy {{'.$data."}}", null, 'laybuy_success.log');
			curl_close ($ch);
			return true;
		}else{
			Mage::log('Cancel Response Array From LayBuy {{'.$result."}}", null, 'laybuy_failure.log');
			curl_close ($ch);
			return false;
		}
	}
	
	public function processOrder($orderIncrementId,$flag,$transactions,$profileId){
		$order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
		if ($order->getId()) {
			if(!$flag){
				if($order->canCancel()){
					$order->cancel()->save();
					Mage::log('Revise Request process order error3 {{'.$order->getId()."-".$order->getStatus()."}}", null, 'laybuy_failure.log');
				}		
				return true;
			}
			if($flag==2){
				$payment_info = $order->getPayment()->getData('additional_information');
				$payment_info['transactions'][$profileId] = json_decode(json_encode($transactions),true);
				$order->getPayment()->setData('additional_information',$payment_info)->save();
				$order->save();
				/* $str = print_r($order->getPayment()->getData('additional_information'),true); */
				Mage::log('Revise Request process order success4 {{'.$order->getId()."-".$order->getStatus()."}}", null, 'laybuy_success.log');
				return true;
			}
			try {
				if(!$order->canInvoice()){
					Mage::log('Revise Request process order error1 {{'.$order->getId()."-".$order->getStatus()."}}", null, 'laybuy_failure.log');
					return false;
				}
				$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
				if (!$invoice->getTotalQty()) {
					Mage::log('Revise Request process order error2 {{'.$order->getId()."-".$order->getStatus()."}}", null, 'laybuy_failure.log');
					return false;
				}
				$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
				$invoice->register();
				$invoice->getOrder()->setIsInProcess(true);
				$transactionSave = Mage::getModel('core/resource_transaction')
										->addObject($invoice)
										->addObject($invoice->getOrder());
				$transactionSave->save();
				Mage::log('Revise Request process order success3 {{'.$order->getId()."-".$invoice->getOrder()->getStatus()."}}", null, 'laybuy_success.log');
				return true;
			}catch (Mage_Core_Exception $e) {
				Mage::log('Revise Request process order error {{'.$e->getMessage()."}}", null, 'laybuy_failure.log');
				return false;
			}
		}
		return false;
	}
	
	public function revisePlan($revise){
		/* $order = Mage::getModel('sales/order')->loadByIncrementId($revise->getOrderId()); */
		$storeId = $revise->getStoreId();
		
		$url = 'https://lay-buys.com/vtmob/deal5.php';		
		$data ='';		
		$data .= "eml=".$revise->getEmail();		
		$data .= "&prc=".$revise->getAmount();
		$data .= "&curr=".$revise->getCurrency();
		if($revise->getPaymentType()==1){
			/* Lay-Buy Payment */
			$data .= "&pp=1";
			$data .= "&pplan=1";
		}else{
			/* Buy-Now Payment */
			$data .= "&pp=0";
			$data .= "&pplan=0";
		}
		$data .= "&init=".$revise->getDownpayment();
		$data .= "&mnth=".$revise->getMonths();
		$data .= "&mid=".Mage::getStoreConfig('payment/laybuy/membership_number',$storeId);
		$data .= "&convrate=1";
		$data .= "&id=".$revise->getId()."-".$revise->getOrderId();
		$data .="&CANCELURL=".Mage::getBaseUrl().'laybuy/revise/cancel/';
		$data .="&RETURNURL=".Mage::getBaseUrl().'laybuy/revise/success/';
		$ch = curl_init();		
		curl_setopt($ch, CURLOPT_URL,$url);		
		curl_setopt($ch, CURLOPT_POST, 1);		
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); /* use this to suppress output */		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false); /* tell cURL to graciously accept an SSL certificate */		
		$result = curl_exec ($ch);
		if($result == 'success'){
			$result = print_r($result,true);
			Mage::log('Revise Request Array to LayBuy {{'.$data."}}", null, 'laybuy_success.log');
			curl_close ($ch);
			return true;
		}else{
			Mage::log('Revise Response Array From LayBuy {{'.$result."}}", null, 'laybuy_failure.log');
			curl_close ($ch);
			return false;
		}
	}
}