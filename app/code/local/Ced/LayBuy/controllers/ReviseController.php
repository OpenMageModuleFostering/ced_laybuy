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
 * LayBuy Standard Checkout Controller
 *
 * @category    Ced
 * @package     Ced_LayBuy
 * @author 		Asheesh Singh<asheeshsingh@cedcoss.com>
 */
class Ced_LayBuy_ReviseController extends Mage_Core_Controller_Front_Action
{
    /**
     * When a customer cancel payment from laybuy.
     */
    public function cancelAction()
    {
		$session = Mage::getSingleton('checkout/session');
		Mage::log('Revise Order of LayBuy {{'."Order_id=".$order->getId()."|".$this->getRequest()->getParam('ErrorMessage')."}}", null, 'laybuy_failure.log');
		$session->addError($this->getRequest()->getParam('ErrorMessage'));
        $this->_redirect('checkout/onepage/failure',array('_secure' => true));
    }

    /**
     * when laybuy returns
     * The order information at this point is in POST
     */
    public function  successAction()
    {
		$format = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
		$status = array_change_key_case($this->getRequest()->getParams(),CASE_LOWER);
		
		if(isset($status['result']) && $status['result']=='FAILURE'){
			$this->_forward('cancel');
		}
		$session = Mage::getSingleton('checkout/session');
		try{
			$currentDate = date('Y-m-d h:i:s',time());
			$status['_secure'] = true;
			$str = print_r($status, true);
			$revise = Mage::getModel('laybuy/revise')->load($status['merchants_ref_no']);
			$state = 0;
			if(!isset($status['downpayment']) && !$revise->getPaymentType()){
				$status['downpayment'] = 100;
				$status['months'] = 0;
				$status['downpayment_amount'] = $status['amount'];
				$status['payment_amounts'] = 0;
				$status['first_payment_due'] = $currentDate;
				$status['last_payment_due'] = $currentDate;
				$status['paypal_profile_id'] = '';
				if(Mage::helper('laybuy')->processOrder($status['custom'],1))
					$state = 1;	
			}
			Mage::log('Revise Response Array From LayBuy {{'.$str."}} and order status is {{".$state."}}", null, 'laybuy_success.log');
			$status['first_payment_due'] = date('Y-m-d h:i:s', strtotime(str_replace('/','-',$status['first_payment_due'])));
			$status['last_payment_due'] = date('Y-m-d h:i:s', strtotime(str_replace('/','-',$status['last_payment_due'])));
			
			$session->setQuoteId($session->getLayBuyStandardQuoteId(true));
			Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
			$order = Mage::getModel('sales/order');
			$order->loadByIncrementId($status['custom']);
			
			if($order && $order->getId()){						
				$createdAt = Mage::helper('core')->formatDate($currentDate, Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true);
				$payment_info = $order->getPayment()->getData('additional_information');
				
				
				$payment_info['transactions'][$status['paypal_profile_id']][] = array(
													'txnID' => $status['dp_paypal_txn_id'],
													'type'	=> 'd',
													'paymentStatus' => 'Completed',
													'paymentDate' => $createdAt,
													'amount' => $status['downpayment_amount']
												);

				$order->getPayment()->setData('additional_information',$payment_info);
				
				$order->save();
				
				
				/* $createdAt = Mage::helper('core')->formatDate($order->getCreatedAt(), Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true);	*/
				$newStr = '<div class="grid"><div class="hor-scroll"><table cellspacing=0 class="data"><thead><tr class="headings"><th colspan=2 class=" no-link" style="text-align: center;"><span class="nobr">Instalment</span></th><th class=" no-link" style="text-align: center;"><span class="nobr">Date</span></th><th class=" no-link" style="text-align: center;"><span class="nobr">PayPal Transaction ID</span></th><th class=" no-link" style="text-align: center;"><span class="nobr">Status</span></th></tr></thead>';				
				$newStr .= '<colgroup>								
								<col width="100">								
								<col width="75">								
								<col width="183">								
								<col width="183">								
								<col width="98">							
							</colgroup>';				
				$months = (int)$status['months'];				
				$newStr .= '<tbody><tr class="even" ><td style="text-align: center;"> DP: </td><td style="text-align: center;"> '.Mage::app()->getLocale()->currency($status['currency'])->toCurrency($status['downpayment_amount']).' </td>'.						  
							'<td style="text-align: center;"> '.$createdAt.' </td>'.						  
							'<td style="text-align: center;">'.$status['dp_paypal_txn_id'].'</td>'.						  
							'<td style="text-align: center;"> Completed </td></tr>';								
				
				for($month=1;$month<=$months;$month++){					
					$newStr .= '<tr ';					
					if($month%2==0)						
						$newStr .= 'class="even"';					
					$newStr .= '>';					
					$newStr .= '<td style="text-align: center;"> Month '.$month.': </td><td style="text-align: center;"> '.Mage::app()->getLocale()->currency($status['currency'])->toCurrency($status['payment_amounts']).' </td>';									   					
					$date = date("Y-m-d h:i:s", strtotime($status['first_payment_due'] . " +".($month-1)." month"));					
					$date = Mage::helper('core')->formatDate($date, Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true);					
					$newStr .= '<td style="text-align: center;"> '.$date.' </td>';					
					$newStr .= 	'<td style="text-align: center;">&nbsp;</td>';					
					$newStr .= 	'<td style="text-align: center;"> Pending </td></tr>';									
				}
				
				$newStr .= '</tbody></table></div></div>';				
				$model = Mage::getModel('laybuy/report')->setData($status)														
														->setOrderId($status['custom'])														
														->setStoreId($order->getStoreId())														
														->setCreatedAt($currentDate)														
														->setStatus($state)														
														->setReport($newStr)														
														/* ->setTransaction(0) */														
														->save();
				$oldTransaction = Mage::getModel('laybuy/report')->load($revise->getTransactionId());
				$newStrReport = preg_replace('/Pending/i', 'Canceled', $oldTransaction->getReport());
				if(Mage::helper('laybuy')->cancelPaypalProfile($oldTransaction->getPaypalProfileId(),$oldTransaction->getStoreId())){
					$oldTransaction->setStatus(2)->setReport($newStrReport)->save();
					$revise->delete();
				}
			}
			$session->addSuccess($this->__('Payment was revised successfully.'));
		}catch(Exception $e){
			$status = array_change_key_case($this->getRequest()->getParams(),CASE_LOWER);
			if($status){
				$str = print_r($status, true);
				$session->addError($this->__('Payment Recieved.But transaction not saved please contact with us.'));
				Mage::log('Revise Exception Order of LayBuy {{'."Order_id=".$status['custom']."|".$status['errormessage']."|".'Response Array From LayBuy {{'.$status."}} and exception is {{".$e->getMessage()."}}", null, 'laybuy_failure.log');
			}else{
				$session->addError($this->__('Try Again Later.'));
			}
		}		
        $this->_redirect('checkout/onepage/success',array('_secure' => true));
    }
}