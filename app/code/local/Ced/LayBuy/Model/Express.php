<?php
/**
 * Magento
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
 * @category    Mage
 * @package     Mage_Paypal
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 *
 * PayPal Express Module
 */
class Ced_LayBuy_Model_Express extends Mage_Payment_Model_Method_Abstract
    implements Mage_Payment_Model_Recurring_Profile_MethodInterface
{
    protected $_code  = "laybuy_express";
    //protected $_formBlockType = 'laybuy/express_form';
   // protected $_infoBlockType = 'laybuy/payment_info';
	protected $_formBlockType = 'laybuy/form_laybuy';
	protected $_infoBlockType = 'laybuy/info_laybuy';
    /**
     * Website Payments Pro instance type
     *
     * @var $_proType string
     */
    protected $_proType = 'laybuy/pro';

    /**
     * Availability options
     */
    protected $_isGateway                   = false;
    protected $_canOrder                    = true;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canVoid                     = true;
    protected $_canUseInternal              = false;
    protected $_canUseCheckout              = true;
    protected $_canUseForMultishipping      = false;
    protected $_canFetchTransactionInfo     = true;
    protected $_canCreateBillingAgreement   = true;
    protected $_canReviewPayment            = true;

    /**
     * Website Payments Pro instance
     *
     * @var Mage_Paypal_Model_Pro
     */
    protected $_pro = null;

    /**
     * Payment additional information key for payment action
     * @var string
     */
    protected $_isOrderPaymentActionKey = 'is_order_action';

    /**
     * Payment additional information key for number of used authorizations
     * @var string
     */
    protected $_authorizationCountKey = 'authorization_count';

    public function __construct($params = array())
    {
    	$proInstance = array_shift($params);
        if ($proInstance && ($proInstance instanceof Ced_LayBuy_Model_Pro)) {
            $this->_pro = $proInstance;
        } else {
            $this->_pro = Mage::getModel($this->_proType);
        }
        $this->_pro->setMethod($this->_code);
        
    }

    /**
     * Store setter
     * Also updates store ID in config object
     *
     * @param Mage_Core_Model_Store|int $store
     */
    public function setStore($store)
    {
        $this->setData('store', $store);
        if (null === $store) {
            $store = Mage::app()->getStore()->getId();
        }
        $this->_pro->getConfig()->setStoreId(is_object($store) ? $store->getId() : $store);
        return $this;
    }

   /**
    * Can be used in regular checkout
    *
    * @return bool
    */
   public function canUseCheckout()
   {
       if (Mage::getStoreConfigFlag('payment/hosted_pro/active')
           && !Mage::getStoreConfigFlag('payment/hosted_pro/display_ec')
       ) {
           return false;
       }
       return parent::canUseCheckout();
   }

    /**
     * Whether method is available for specified currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        return $this->_pro->getConfig()->isCurrencyCodeSupported($currencyCode);
    }

    /**
     * Payment action getter compatible with payment model
     *
     * @see Mage_Sales_Model_Payment::place()
     * @return string
     */
    public function getConfigPaymentAction()
    {
    	/* echo get_class($this->_pro);
    	var_dump($this->_pro->getConfig()->getPaymentAction());die("herrooo"); */
        return 'authorize';
    }

    /**
     * Check whether payment method can be used
     * @param Mage_Sales_Model_Quote
     * @return bool
     */
    /* public function isAvailable($quote = null)
    {
        if (parent::isAvailable($quote) && $this->_pro->getConfig()->isMethodAvailable()) {
            return true;
        }
        return false;
    } */

    /**
     * Custom getter for payment configuration
     *
     * @param string $field
     * @param int $storeId
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        return $this->_pro->getConfig()->$field;
    }

    /**
     * Order payment
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return Mage_Paypal_Model_Express
     */
    public function order(Varien_Object $payment, $amount)
    {
        $this->_placeOrder($payment, $amount);

        $payment->setAdditionalInformation($this->_isOrderPaymentActionKey, true);

        if ($payment->getIsFraudDetected()) {
            return $this;
        }

        $order = $payment->getOrder();
        $orderTransactionId = $payment->getTransactionId();

        $api = $this->_callDoAuthorize($amount, $payment, $payment->getTransactionId());

        $state  = Mage_Sales_Model_Order::STATE_PROCESSING;
        $status = true;

        $formatedPrice = $order->getBaseCurrency()->formatTxt($amount);
        if ($payment->getIsTransactionPending()) {
            $message = Mage::helper('laybuy')->__('Ordering amount of %s is pending approval on gateway.', $formatedPrice);
            $state = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
        } else {
            $message = Mage::helper('laybuy')->__('Ordered amount of %s.', $formatedPrice);
        }

        $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER, null, false, $message);

        $this->_pro->importPaymentInfo($api, $payment);

        if ($payment->getIsTransactionPending()) {
            $message = Mage::helper('laybuy')->__('Authorizing amount of %s is pending approval on gateway.', $formatedPrice);
            $state = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
            if ($payment->getIsFraudDetected()) {
                $status = Mage_Sales_Model_Order::STATUS_FRAUD;
            }
        } else {
            $message = Mage::helper('laybuy')->__('Authorized amount of %s.', $formatedPrice);
        }

        $payment->resetTransactionAdditionalInfo();

        $payment->setTransactionId($api->getTransactionId());
        $payment->setParentTransactionId($orderTransactionId);

        $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, false,
            $message
        );

        $order->setState($state, $status);

        $payment->setSkipOrderProcessing(true);
        return $this;
    }

    /**
     * Authorize payment
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return Mage_Paypal_Model_Express
     */
    public function authorize(Varien_Object $payment, $amount)
    {
    	return $this->_placeOrder($payment, $amount);
    }

    /**
     * Void payment
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Paypal_Model_Express
     */
    public function void(Varien_Object $payment)
    {
        //Switching to order transaction if needed
        if ($payment->getAdditionalInformation($this->_isOrderPaymentActionKey)
            && !$payment->getVoidOnlyAuthorization()
        ) {
            $orderTransaction = $payment->lookupTransaction(
                false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER
            );
            if ($orderTransaction) {
                $payment->setParentTransactionId($orderTransaction->getTxnId());
                $payment->setTransactionId($orderTransaction->getTxnId() . '-void');
            }
        }
        $this->_pro->void($payment);
        return $this;
    }

    

    /**
     * Refund capture
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return Mage_Paypal_Model_Express
     */
    public function refund(Varien_Object $payment, $amount)
    {
        $this->_pro->refund($payment, $amount);
        return $this;
    }



    /**
     * Whether payment can be reviewed
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return bool
     */
    public function canReviewPayment(Mage_Payment_Model_Info $payment)
    {
        return parent::canReviewPayment($payment) && $this->_pro->canReviewPayment($payment);
    }

    /**
     * Attempt to accept a pending payment
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return bool
     */
    public function acceptPayment(Mage_Payment_Model_Info $payment)
    {
        parent::acceptPayment($payment);
        return $this->_pro->reviewPayment($payment, Mage_Paypal_Model_Pro::PAYMENT_REVIEW_ACCEPT);
    }

    /**
     * Attempt to deny a pending payment
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return bool
     */
    public function denyPayment(Mage_Payment_Model_Info $payment)
    {
        parent::denyPayment($payment);
        return $this->_pro->reviewPayment($payment, Mage_Paypal_Model_Pro::PAYMENT_REVIEW_DENY);
    }

    /**
     * Checkout redirect URL getter for onepage checkout (hardcode)
     *
     * @see Mage_Checkout_OnepageController::savePaymentAction()
     * @see Mage_Sales_Model_Quote_Payment::getCheckoutRedirectUrl()
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        return Mage::getUrl('laybuy/express/start');
    }

    /**
     * Fetch transaction details info
     *
     * @param Mage_Payment_Model_Info $payment
     * @param string $transactionId
     * @return array
     */
    public function fetchTransactionInfo(Mage_Payment_Model_Info $payment, $transactionId)
    {
        return $this->_pro->fetchTransactionInfo($payment, $transactionId);
    }

    /**
     * Validate RP data
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function validateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        return $this->_pro->validateRecurringProfile($profile);
    }

    /**
     * Submit RP to the gateway
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     * @param Mage_Payment_Model_Info $paymentInfo
     */
    public function submitRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile,
        Mage_Payment_Model_Info $paymentInfo
    ) {
        $token = $paymentInfo->
            getAdditionalInformation(Mage_Paypal_Model_Express_Checkout::PAYMENT_INFO_TRANSPORT_TOKEN);
        $profile->setToken($token);
        $this->_pro->submitRecurringProfile($profile, $paymentInfo);
    }

    /**
     * Fetch RP details
     *
     * @param string $referenceId
     * @param Varien_Object $result
     */
    public function getRecurringProfileDetails($referenceId, Varien_Object $result)
    {
        return $this->_pro->getRecurringProfileDetails($referenceId, $result);
    }

    /**
     * Whether can get recurring profile details
     */
    public function canGetRecurringProfileDetails()
    {
        return true;
    }

    /**
     * Update RP data
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        return $this->_pro->updateRecurringProfile($profile);
    }

    /**
     * Manage status
     *
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfileStatus(Mage_Payment_Model_Recurring_Profile $profile)
    {
        return $this->_pro->updateRecurringProfileStatus($profile);
    }

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        $result = parent::assignData($data);
        $key = Mage_Paypal_Model_Express_Checkout::PAYMENT_INFO_TRANSPORT_BILLING_AGREEMENT;
        if (is_array($data)) {
            $this->getInfoInstance()->setAdditionalInformation($key, isset($data[$key]) ? $data[$key] : null);
        }
        elseif ($data instanceof Varien_Object) {
            $this->getInfoInstance()->setAdditionalInformation($key, $data->getData($key));
        }
        return $result;
    }

    /**
     * Place an order with authorization or capture action
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return Mage_Paypal_Model_Express
     */
    protected function _placeOrder(Mage_Sales_Model_Order_Payment $payment, $amount)
    {
        $order = $payment->getOrder();

        // prepare api call
        $token = $payment->getAdditionalInformation(Mage_Paypal_Model_Express_Checkout::PAYMENT_INFO_TRANSPORT_TOKEN);
        $api = $this->_pro->getApi();
        $api->setToken($token);
           $api->setPayerId($payment->
                getAdditionalInformation(Mage_Paypal_Model_Express_Checkout::PAYMENT_INFO_TRANSPORT_PAYER_ID));
           $api->setAmount($amount);
            $api->setPaymentAction($this->_pro->getConfig()->paymentAction);
            $api->setNotifyUrl(Mage::getUrl('paypal/ipn/'));
           $api->setInvNum($order->getIncrementId());
           $api->setCurrencyCode($order->getBaseCurrencyCode());
           //$api->setPaypalCart(Mage::getModel('paypal/cart', array($order)));
           $api->setIsLineItemsEnabled($this->_pro->getConfig()->lineItemsEnabled);
        
        if ($order->getIsVirtual()) {
            $api->setAddress($order->getBillingAddress())->setSuppressShipping(true);
        } else {
            $api->setAddress($order->getShippingAddress());
            $api->setBillingAddress($order->getBillingAddress());
        }
		// call api and get details from it
		$api->callDoExpressCheckoutPayment();
        $this->_importToPayment($api, $payment);//$api
        
        return $this;
    }

    /**
     * Import payment info to payment
     *
     * @param Mage_Paypal_Model_Api_Nvp
     * @param Mage_Sales_Model_Order_Payment
     */
    protected function _importToPayment($api, $payment)
    {
        $payment->setTransactionId($api->getTransactionId())->setIsTransactionClosed(0)
            ->setAdditionalInformation('laybuy_express_checkout_redirect_required',
                $api->getRedirectRequired()
            );
        $model_obj=$api->getModelObject();
       if ($model_obj->getBillingAgreementId()) {
            $payment->setBillingAgreementData(array(
                'billing_agreement_id'  => $model_obj->getBillingAgreementId(),
                'method_code'           => 'laybuy_billing_agreement'
            ));
        }

        $this->_pro->importPaymentInfo($model_obj, $payment);
    }

    /**
     * Check void availability
     *
     * @param   Varien_Object $payment
     * @return  bool
     */
    public function canVoid(Varien_Object $payment)
    {
        if ($payment instanceof Mage_Sales_Model_Order_Invoice
            || $payment instanceof Mage_Sales_Model_Order_Creditmemo
        ) {
            return false;
        }
        $info = $this->getInfoInstance();
        if ($info->getAdditionalInformation($this->_isOrderPaymentActionKey)) {
            $orderTransaction = $info->lookupTransaction(
                false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER
            );
            if ($orderTransaction) {
                $info->setParentTransactionId($orderTransaction->getTxnId());
            }
        }

        return $this->_canVoid;
    }

    /**
     * Check capture availability
     *
     * @return bool
     */
    public function canCapture()
    {
        $payment = $this->getInfoInstance();
        $this->_pro->getConfig()->setStoreId($payment->getOrder()->getStore()->getId());

        if ($payment->getAdditionalInformation($this->_isOrderPaymentActionKey)) {
            $orderTransaction = $payment->lookupTransaction(false,
                Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER
            );
            if ($orderTransaction->getIsClosed()) {
                return false;
            }

            $orderValidPeriod = abs(intval($this->getConfigData('order_valid_period')));

            $dateCompass = new DateTime($orderTransaction->getCreatedAt());
            $dateCompass->modify('+' . $orderValidPeriod . ' days');
            $currentDate = new DateTime();

            if ($currentDate > $dateCompass || $orderValidPeriod == 0) {
                return false;
            }
        }
        return $this->_canCapture;
    }

    /**
     * Call DoAuthorize
     *
     * @param int $amount
     * @param Varien_Object $payment
     * @param string $parentTransactionId
     * @return Mage_Paypal_Model_Api_Abstract
     */
    protected function _callDoAuthorize($amount, $payment, $parentTransactionId)
    {
        $api = $this->_pro->resetApi()->getApi()
            ->setAmount($amount)
            ->setCurrencyCode($payment->getOrder()->getBaseCurrencyCode())
            ->setTransactionId($parentTransactionId)
            ->callDoAuthorization();

        $payment->setAdditionalInformation($this->_authorizationCountKey,
            $payment->getAdditionalInformation($this->_authorizationCountKey) + 1
        );

        return $api;
    }

    /**
     * Check transaction for expiration in PST
     *
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param int $period
     * @return boolean
     */
    protected function _isTransactionExpired(Mage_Sales_Model_Order_Payment_Transaction $transaction, $period)
    {
        $period = intval($period);
        if (0 == $period) {
            return true;
        }

        $transactionClosingDate = new DateTime($transaction->getCreatedAt(), new DateTimeZone('GMT'));
        $transactionClosingDate->setTimezone(new DateTimeZone('US/Pacific'));
        /**
         * 11:49:00 PayPal transactions closing time
         */
        $transactionClosingDate->setTime(11, 49, 00);
        $transactionClosingDate->modify('+' . $period . ' days');

        $currentTime = new DateTime(null, new DateTimeZone('US/Pacific'));

        if ($currentTime > $transactionClosingDate) {
            return true;
        }

        return false;
    }
    
    /***************************new code********************************/
    /**
     * Check whether payment method can be used
     * @param Mage_Sales_Model_Quote
     * @return bool
     */
 public function isAvailable($quote = null)
    {
		$controller_name=Mage::app()->getRequest()->getControllerName();
    	$action_name=Mage::app()->getRequest()->getActionName();
    	$module_name=Mage::app()->getRequest()->getModuleName();
	/* if($controller_name=="onepage" && $module_name=="checkout"){
    		return false;
    		exit();
    	} */
    	if(($controller_name=="product" && $module_name=="catalog" && $action_name=="view") || ($controller_name=="index" && $module_name=="cms" && $action_name=="index") || ($controller_name=="cart" && $module_name=="checkout" && $action_name=="index") || ($controller_name=="express" && $module_name=="laybuy" )){
		    	if ($status = parent::isAvailable($quote)) {
		    
		    		$storeId = $quote ? $quote->getStoreId() : null;
		    		 
		    		/* Condition for minimum checkout amount for method availability */
		    		$configTotal = Mage::getStoreConfig('laybuy/conditional_criteria/total',$storeId);
		    		$total = $quote ? $quote->getData('grand_total') : 0;
		    		if($total && $status && $configTotal){
		    			if($configTotal<$total){
		    				$status = true;
		    			}else{
		    				$status = false;
		    			}
		    		}
		    		 
		    		/* Condition for customer groups for method availability */
		    		if($status){
		    			if(Mage::getStoreConfig('laybuy/conditional_criteria/allowspecificgroup',$storeId)) {
		    				$configCustomerGroupId = explode(',',Mage::getStoreConfig('laybuy/conditional_criteria/customergroup',$storeId ));
		    				$customerGroupId = $quote ? $quote->getData('customer_group_id'):-5;
		    				if($customerGroupId != -5){
		    					if($configCustomerGroupId && in_array($customerGroupId,$configCustomerGroupId)){
		    						$status = true;
		    					}else{
		    						$status = false;
		    					}
		    				}
		    			}
		    		}
		    		 
		    		return $status;
		    	}
    	}
    	else{
    		return false;
    	}
    }
    
    /**
     * Validate payment method information object
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function validate()
    {
    	/**
    	 * to validate payment method is allowed for billing country or not
    	 */
    	parent::validate();
    
    	$paymentInfo = $this->getInfoInstance();
    	if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
    		$cartItems = $paymentInfo->getOrder()->getAllItems();
    		$storeId 	= $paymentInfo->getOrder()->getStoreId();
    	} else {
    		$cartItems = $paymentInfo->getQuote()->getAllItems();
    		$storeId 	= $paymentInfo->getQuote()->getStoreId();
    	}
    	$flagArr = $this->canUseForCategories($cartItems, $storeId);
    	if (!$flagArr[0]) {
    		Mage::throwException(Mage::helper('laybuy')->__('Selected payment type is not allowed for '.$flagArr[1].' products.'));
    	}
    	return $this;
    }
    public function returnRestrictedProductNames($product_id="",$storeId=0){
    	$status = true;
    	$productName = '';
    	$configCategories = explode(',',Mage::getStoreConfig('laybuy/conditional_criteria/categories',$storeId ));
    	$xproducts = explode(',',Mage::getStoreConfig('laybuy/conditional_criteria/xproducts',$storeId));
    	$cartItems=Mage::getModel('checkout/cart')->getQuote()->getAllItems();
    	foreach($cartItems as $_product){
    		$_product = Mage::getModel('catalog/product')->load($_product->getProductId());
    		if($xproducts && in_array($_product->getId(),$xproducts)){
    			$status = false;
    			$productName .= $_product->getName().',';
    		} elseif(!Mage::getStoreConfig('laybuy/conditional_criteria/allowspecificcategory',$storeId)) {
    			$status = true;
    		} elseif ($configCategories && count(array_diff($_product->getCategoryIds(),$configCategories))>0){
    			$status = false;
    			$productName .= $_product->getName().',';
    		}
    	}
    	$productName = rtrim($productName,',');
    
    	return $productName;
    }
    public function checkForCategoriesAndProducts($product_id="",$storeId=0){
    	$status = true;
    	$productName = '';
    	$cartItems="";
    	$configCategories = explode(',',Mage::getStoreConfig('laybuy/conditional_criteria/categories',$storeId ));
    	$xproducts = explode(',',Mage::getStoreConfig('laybuy/conditional_criteria/xproducts',$storeId));
    	$controller_name=Mage::app()->getRequest()->getControllerName();
    	$action_name=Mage::app()->getRequest()->getActionName();
    	$module_name=Mage::app()->getRequest()->getModuleName();
    	if($controller_name=="product" && $module_name="catalog" && $action_name=="view" && $product_id !=""){
    		$_product = Mage::getModel('catalog/product')->load($product_id);
    		if($xproducts && in_array($_product->getId(),$xproducts)){
    			$status = false;
    			$productName .= $_product->getName().',';
    		} elseif(!Mage::getStoreConfig('laybuy/conditional_criteria/allowspecificcategory',$storeId)) {
    			$status = true;
    		} elseif ($configCategories && count(array_diff($_product->getCategoryIds(),$configCategories))>0){
    			$status = false;
    			$productName .= $_product->getName().',';
    		}
    		 
    	}else{
    		$cartItems=Mage::getModel('checkout/cart')->getQuote()->getAllItems();
    		foreach($cartItems as $_product){
    			$_product = Mage::getModel('catalog/product')->load($_product->getProductId());
    			if($xproducts && in_array($_product->getId(),$xproducts)){
    				$status = false;
    				$productName .= $_product->getName().',';
    			} elseif(!Mage::getStoreConfig('laybuy/conditional_criteria/allowspecificcategory',$storeId)) {
    				$status = true;
    			} elseif ($configCategories && count(array_diff($_product->getCategoryIds(),$configCategories))>0){
    				$status = false;
    				$productName .= $_product->getName().',';
    			}
    			if($status==false){
    				break;
    			}
    		}
    		 
    	}
    	return $status;
    	 
    }
    
    /**
     * To check billing country is allowed for the payment method
     *
     * @return bool
     */
    public function canUseForCategories($cartItems = array(), $storeId=0)
    {
    	/*
    	 for specific categories, the flag will set up as 1
    	*/
    	$status = true;
    	$productName = '';
    	$configCategories = explode(',',Mage::getStoreConfig('laybuy/conditional_criteria/categories',$storeId ));
    	$xproducts = explode(',',Mage::getStoreConfig('laybuy/conditional_criteria/xproducts',$storeId));
    
    	foreach($cartItems as $_product){
    		$_product = Mage::getModel('catalog/product')->load($_product->getProductId());
    		if($xproducts && in_array($_product->getId(),$xproducts)){
    			$status = false;
    			$productName .= $_product->getName().',';
    		} elseif(!Mage::getStoreConfig('laybuy/conditional_criteria/allowspecificcategory',$storeId)) {
    			$status = true;
    		} elseif ($configCategories && count(array_diff($_product->getCategoryIds(),$configCategories))>0){
    			$status = false;
    			$productName .= $_product->getName().',';
    		}
    	}
    	$productName = rtrim($productName,',');
    
    	return array($status,$productName);
    }
    
    /***************************ends************************************/
}
