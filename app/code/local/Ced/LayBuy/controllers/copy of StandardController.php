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
 * @copyright   Copyright LAY-BUYS (2011). (http://lay-buys.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 * LayBuy Standard Checkout Controller
 *
 * @category    Ced
 * @package     Ced_LayBuy
 */
class Ced_LayBuy_StandardController extends Mage_Core_Controller_Front_Action
{
	/**
     * Order instance
     */
    protected $_order;
    /**
     *  Get order
     *
     *  @return	  Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if ($this->_order == null) {
        }
        return $this->_order;
    }
    /**
     * Send expire header to ajax response
     *
     */
    protected function _expireAjax()
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }
    /**
     * Get singleton with laybuy strandard order transaction information
     *
     * @return Mage_LayBuy_Model_Standard
     */
    public function getStandard()
    {
        return Mage::getSingleton('laybuy/standard');
    }
    /**
     * When a customer chooses LayBuy on Checkout/Payment page
     *
     */
    public function redirectAction()
    {
		$session = Mage::getSingleton('checkout/session');
		$session->setLayBuyStandardQuoteId($session->getQuoteId());
		$this->getResponse()->setBody($this->getLayout()->createBlock('Ced_LayBuy_Block_Standard_Redirect')->toHtml());
		$session->unsQuoteId();
        $session->unsRedirectUrl();
    }
    /**
     * When a customer cancel payment from laybuy.
     */
    public function cancelAction()
    {
        $session = Mage::getSingleton('checkout/session');
		$session->setQuoteId($session->getLayBuyStandardQuoteId(true));
        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->save();
				Mage::log('Canceled Order of LayBuy {{'."Order_id=".$order->getId()."|".$this->getRequest()->getParam('ErrorMessage')."}}", null, 'laybuy_failure.log');
            }
        }
		$session->addError($this->getRequest()->getParam('ErrorMessage','Try Again Later.'));
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
			$status['_secure'] = true;
			$str = print_r($status, true);
			/* $status['first_payment_due'] = '13/12/13';
			$status['last_payment_due'] = '13/01/14'; */
			$status['first_payment_due'] = date('Y-m-d h:i:s', strtotime(str_replace('/','-',$status['first_payment_due'])));
			$status['last_payment_due'] = date('Y-m-d h:i:s', strtotime(str_replace('/','-',$status['last_payment_due'])));
			$session->setQuoteId($session->getLayBuyStandardQuoteId(true));
			Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
			$order = Mage::getModel('sales/order');
			$order->loadByIncrementId($status['custom']);
			$payment_info = array();
			if($order && $order->getId()){
				$order->sendNewOrderEmail();
				$order->setEmailSent(true); 
				$payment_info = $order->getPayment()->getData('additional_information');
				$payment_info['transactions'][$status['paypal_profile_id']][] = array(
													'txnID' => $status['dp_paypal_txn_id'],
													'type'	=> 'd',
													'paymentStatus' => 'Completed',
													'paymentDate' => $order->getCreatedAt(),
													'amount' => $status['downpayment_amount']
												);
				$order->getPayment()->setData('additional_information',$payment_info);
				$order->save();
				$createdAt = Mage::helper('core')->formatDate($order->getCreatedAt(), Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true);
				$newStr = '<div class="grid"><div class="hor-scroll"><table cellspacing=0 class="data"><thead><tr class="headings"><th colspan=2 class=" no-link" style="text-align: center;"><span class="nobr">Installment</span></th><th class=" no-link" style="text-align: center;"><span class="nobr">Date</span></th><th class=" no-link" style="text-align: center;"><span class="nobr">PayPal Transaction ID</span></th><th class=" no-link" style="text-align: center;"><span class="nobr">Status</span></th></tr></thead>';
				$newStr .= '<colgroup>
								<col width="100">
								<col width="75">
								<col width="183">
								<col width="183">
								<col width="98">
							</colgroup>';
				$months = (int)$status['months'];
				$newStr .= '<tbody><tr class="even" ><td style="text-align: center;"> DP: </td><td style="text-align: center;"> '.Mage::app()->getLocale()
                                       ->currency($status['currency'])
									   ->toCurrency($status['downpayment_amount']).' </td>'.
						  '<td style="text-align: center;"> '.$createdAt.' </td>'.
						  '<td style="text-align: center;">'.$status['dp_paypal_txn_id'].'</td>'.
						  '<td style="text-align: center;"> Completed </td></tr>';
				for($month=1;$month<=$months;$month++){
					$newStr .= '<tr ';
					if($month%2==0)
						$newStr .= 'class="even"';
					$newStr .= '>';
					$newStr .= '<td style="text-align: center;"> Month '.$month.': </td><td style="text-align: center;"> '.Mage::app()->getLocale()
                                       ->currency($status['currency'])
									   ->toCurrency($status['payment_amounts']).' </td>';
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
														->setCreatedAt($order->getCreatedAt())
														->setStatus(0)
														->setReport($newStr)
														/* ->setTransaction(0) */
														->save();
				Mage::log('Response Array From LayBuy {{'.$str."}}", null, 'laybuy_success.log');
			}
			$session->addSuccess($this->__('Payment was recieved successfully.'));
		}catch(Exception $e){
			$status = array_change_key_case($this->getRequest()->getParams(),CASE_LOWER);
			if($status){
				$str = print_r($status, true);
				$session->addError($this->__('Payment Recieved.But transaction not saved please contact us.'));
				Mage::log('Exception Order of LayBuy {{'."Order_id=".$status['custom']."|".$status['errormessage']."|".'Response Array From LayBuy {{'.$status."}}"."}}", null, 'laybuy_failure.log');
			}else{
				$session->addError($this->__('Try Again Later.'));
			}
		}	
        $this->_redirect('checkout/onepage/success',array('_secure' => true));
    }
	public function docalcAction(){
		$this->getResponse()->setBody(
			$this->getLayout()->createBlock('laybuy/standard_docalc')->toHtml()
		);
	}

	
}