<?php 
class Ced_LayBuy_ExpressController extends Mage_Core_Controller_Front_Action
{
	const PAYER_ID       = 'payer_id';
	const PAYER_EMAIL    = 'email';
	const PAYER_STATUS   = 'payer_status';
	const ADDRESS_ID     = 'address_id';
	const ADDRESS_STATUS = 'address_status';
	const PROTECTION_EL  = 'protection_eligibility';
	const FRAUD_FILTERS  = 'collected_fraud_filters';
	const CORRELATION_ID = 'correlation_id';
	const AVS_CODE       = 'avs_result';
	const CVV2_MATCH     = 'cvv2_check_result';
	const CENTINEL_VPAS  = 'centinel_vpas_result';
	const CENTINEL_ECI   = 'centinel_eci_result';
	
	// Next two fields are required for Brazil
	const BUYER_TAX_ID   = 'buyer_tax_id';
	const BUYER_TAX_ID_TYPE = 'buyer_tax_id_type';
	
	const PAYMENT_STATUS = 'payment_status';
	const PENDING_REASON = 'pending_reason';
	const IS_FRAUD       = 'is_fraud_detected';
	const PAYMENT_STATUS_GLOBAL = 'paypal_payment_status';
	const PENDING_REASON_GLOBAL = 'paypal_pending_reason';
	const IS_FRAUD_GLOBAL       = 'paypal_is_fraud_detected';
	
	/**
	 * Possible buyer's tax id types (Brazil only)
	 */
	const BUYER_TAX_ID_TYPE_CPF = 'BR_CPF';
	const BUYER_TAX_ID_TYPE_CNPJ = 'BR_CNPJ';
	
	const PAYMENTSTATUS_NONE         = 'none';
	const PAYMENTSTATUS_COMPLETED    = 'completed';
	const PAYMENTSTATUS_DENIED       = 'denied';
	const PAYMENTSTATUS_EXPIRED      = 'expired';
	const PAYMENTSTATUS_FAILED       = 'failed';
	const PAYMENTSTATUS_INPROGRESS   = 'in_progress';
	const PAYMENTSTATUS_PENDING      = 'pending';
	const PAYMENTSTATUS_REFUNDED     = 'refunded';
	const PAYMENTSTATUS_REFUNDEDPART = 'partially_refunded';
	const PAYMENTSTATUS_REVERSED     = 'reversed';
	const PAYMENTSTATUS_UNREVERSED   = 'canceled_reversal';
	const PAYMENTSTATUS_PROCESSED    = 'processed';
	const PAYMENTSTATUS_VOIDED       = 'voided';
	
	/**
	 * PayPal payment transaction type
	 */
	const TXN_TYPE_ADJUSTMENT = 'adjustment';
	const TXN_TYPE_NEW_CASE   = 'new_case';
	
	
	/**
	 * Order
	 *
	 * @var Mage_Sales_Model_QuoteMage_Sales_Model_Quote
	 */
	protected $_order = null;
	protected $_amount=null;
	/**
	 * State helper variables
	 * @var string
	 */
	protected $_redirectUrl = '';
	/**
	 * Billing agreement that might be created during order placing
	 *
	 * @var Mage_Sales_Model_Billing_Agreement
	 */
	protected $_billingAgreement = null;
	/**
	 * Recurring payment profiles
	 *
	 * @var array
	 */
	protected $_recurringPaymentProfiles = array();
	protected $_init=null;
	protected $_months=null;
	/**
	 * Map for shipping address import/export (extends billing address mapper)
	 * @var array
	 */
	
	protected $_shippingAddressMap = array(
			'SHIPTOCOUNTRYCODE' => 'country_id',
			'SHIPTOSTATE' => 'region',
			'SHIPTOCITY'    => 'city',
			'SHIPTOSTREET'  => 'street',
			'SHIPTOSTREET2' => 'street2',
			'SHIPTOZIP' => 'postcode',
			'SHIPTOPHONENUM' => 'telephone',
			// 'SHIPTONAME' will be treated manually in address import/export methods
	);
	/**
	 * All payment information map
	 *
	 * @var array
	 */
	protected $_paymentMap = array(
			self::PAYER_ID       => 'paypal_payer_id',
			self::PAYER_EMAIL    => 'paypal_payer_email',
			self::PAYER_STATUS   => 'paypal_payer_status',
			self::ADDRESS_ID     => 'paypal_address_id',
			self::ADDRESS_STATUS => 'paypal_address_status',
			self::PROTECTION_EL  => 'paypal_protection_eligibility',
			self::FRAUD_FILTERS  => 'paypal_fraud_filters',
			self::CORRELATION_ID => 'paypal_correlation_id',
			self::AVS_CODE       => 'paypal_avs_code',
			self::CVV2_MATCH     => 'paypal_cvv2_match',
			self::CENTINEL_VPAS  => self::CENTINEL_VPAS,
			self::CENTINEL_ECI   => self::CENTINEL_ECI,
			self::BUYER_TAX_ID   => self::BUYER_TAX_ID,
			self::BUYER_TAX_ID_TYPE => self::BUYER_TAX_ID_TYPE,
	);
	
	/**
	 * System information map
	 *
	 * @var array
	*/
	protected $_systemMap = array(
			self::PAYMENT_STATUS => self::PAYMENT_STATUS_GLOBAL,
			self::PENDING_REASON => self::PENDING_REASON_GLOBAL,
			self::IS_FRAUD       => self::IS_FRAUD_GLOBAL,
	);
	
	protected $_importFromRequestFilters = array(
			'REDIRECTREQUIRED'  => '_filterToBool',
			'SUCCESSPAGEREDIRECTREQUESTED'  => '_filterToBool',
			'PAYMENTSTATUS' => '_filterPaymentStatusFromNvpToInfo',
	);
	public $_laybuyresponse=null;
	/**
	 * Global public interface map
	 * @var array
	 */
	protected $_globalMap = array(
			// each call
			'VERSION'      => 'version',
			'USER'         => 'api_username',
			'PWD'          => 'api_password',
			'SIGNATURE'    => 'api_signature',
			'BUTTONSOURCE' => 'build_notation_code',
	
			// for Unilateral payments
			'SUBJECT'      => 'business_account',
	
			// commands
			'PAYMENTACTION' => 'payment_action',
			'RETURNURL'     => 'return_url',
			'CANCELURL'     => 'cancel_url',
			'INVNUM'        => 'inv_num',
			'TOKEN'         => 'token',
			'CORRELATIONID' => 'correlation_id',
			'SOLUTIONTYPE'  => 'solution_type',
			'GIROPAYCANCELURL'  => 'giropay_cancel_url',
			'GIROPAYSUCCESSURL' => 'giropay_success_url',
			'BANKTXNPENDINGURL' => 'giropay_bank_txn_pending_url',
			'IPADDRESS'         => 'ip_address',
			'NOTIFYURL'         => 'notify_url',
			'RETURNFMFDETAILS'  => 'fraud_management_filters_enabled',
			'NOTE'              => 'note',
			'REFUNDTYPE'        => 'refund_type',
			'ACTION'            => 'action',
			'REDIRECTREQUIRED'  => 'redirect_required',
			'SUCCESSPAGEREDIRECTREQUESTED'  => 'redirect_requested',
			'REQBILLINGADDRESS' => 'require_billing_address',
			// style settings
	'PAGESTYLE'      => 'page_style',
	'HDRIMG'         => 'hdrimg',
	'HDRBORDERCOLOR' => 'hdrbordercolor',
	'HDRBACKCOLOR'   => 'hdrbackcolor',
	'PAYFLOWCOLOR'   => 'payflowcolor',
	'LOCALECODE'     => 'locale_code',
	'PAL'            => 'pal',
	
	// transaction info
	'TRANSACTIONID'   => 'transaction_id',
	'AUTHORIZATIONID' => 'authorization_id',
	'REFUNDTRANSACTIONID' => 'refund_transaction_id',
	'COMPLETETYPE'    => 'complete_type',
	'AMT' => 'amount',
	'ITEMAMT' => 'subtotal_amount',
	'GROSSREFUNDAMT' => 'refunded_amount', // possible mistake, check with API reference
	
	// payment/billing info
	'CURRENCYCODE'  => 'currency_code',
	'PAYMENTSTATUS' => 'payment_status',
	'PENDINGREASON' => 'pending_reason',
	'PROTECTIONELIGIBILITY' => 'protection_eligibility',
	'PAYERID' => 'payer_id',
	'PAYERSTATUS' => 'payer_status',
	'ADDRESSID' => 'address_id',
	'ADDRESSSTATUS' => 'address_status',
	'EMAIL'         => 'email',
	// backwards compatibility
	'FIRSTNAME'     => 'firstname',
	'LASTNAME'      => 'lastname',
	
	// shipping rate
	'SHIPPINGOPTIONNAME' => 'shipping_rate_code',
	'NOSHIPPING'         => 'suppress_shipping',
	
	// paypal direct credit card information
	'CREDITCARDTYPE' => 'credit_card_type',
	'ACCT'           => 'credit_card_number',
	'EXPDATE'        => 'credit_card_expiration_date',
	'CVV2'           => 'credit_card_cvv2',
	'STARTDATE'      => 'maestro_solo_issue_date', // MMYYYY, always six chars, including leading zero
	'ISSUENUMBER'    => 'maestro_solo_issue_number',
	'CVV2MATCH'      => 'cvv2_check_result',
	'AVSCODE'        => 'avs_result',
	// cardinal centinel
	'AUTHSTATUS3DS' => 'centinel_authstatus',
	'MPIVENDOR3DS'  => 'centinel_mpivendor',
	'CAVV'         => 'centinel_cavv',
	'ECI3DS'       => 'centinel_eci',
	'XID'          => 'centinel_xid',
	'VPAS'         => 'centinel_vpas_result',
	'ECISUBMITTED3DS' => 'centinel_eci_result',
	
	// recurring payment profiles
	//'TOKEN' => 'token',
	'SUBSCRIBERNAME'    =>'subscriber_name',
	'PROFILESTARTDATE'  => 'start_datetime',
	'PROFILEREFERENCE'  => 'internal_reference_id',
	'DESC'              => 'schedule_description',
	'MAXFAILEDPAYMENTS' => 'suspension_threshold',
	'AUTOBILLAMT'       => 'bill_failed_later',
	'BILLINGPERIOD'     => 'period_unit',
	'BILLINGFREQUENCY'    => 'period_frequency',
	'TOTALBILLINGCYCLES'  => 'period_max_cycles',
	//'AMT' => 'billing_amount', // have to use 'amount', see above
	'TRIALBILLINGPERIOD'      => 'trial_period_unit',
	'TRIALBILLINGFREQUENCY'   => 'trial_period_frequency',
	'TRIALTOTALBILLINGCYCLES' => 'trial_period_max_cycles',
	'TRIALAMT'            => 'trial_billing_amount',
	// 'CURRENCYCODE' => 'currency_code',
	'SHIPPINGAMT'         => 'shipping_amount',
	'TAXAMT'              => 'tax_amount',
	'INITAMT'             => 'init_amount',
	'FAILEDINITAMTACTION' => 'init_may_fail',
	'PROFILEID'           => 'recurring_profile_id',
	'PROFILESTATUS'       => 'recurring_profile_status',
	'STATUS'              => 'status',
	
	//Next two fields are used for Brazil only
	'TAXID'               => 'buyer_tax_id',
	'TAXIDTYPE'           => 'buyer_tax_id_type',
	
	'BILLINGAGREEMENTID' => 'billing_agreement_id',
	'REFERENCEID' => 'reference_id',
	'BILLINGAGREEMENTSTATUS' => 'billing_agreement_status',
	'BILLINGTYPE' => 'billing_type',
	'SREET' => 'street',
	'CITY' => 'city',
	'STATE' => 'state',
	'COUNTRYCODE' => 'countrycode',
	'ZIP' => 'zip',
	'PAYERBUSINESS' => 'payer_business',
	);
	/**
	 * Payment information response specifically to be collected after some requests
	 * @var array
	 */
	protected $_paymentInformationResponse = array(
			'PAYERID', 'PAYERSTATUS', 'CORRELATIONID', 'ADDRESSID', 'ADDRESSSTATUS',
			'PAYMENTSTATUS', 'PENDINGREASON', 'PROTECTIONELIGIBILITY', 'EMAIL', 'SHIPPINGOPTIONNAME', 'TAXID', 'TAXIDTYPE'
	);
	protected $_doExpressCheckoutPaymentResponse = array(
			'TRANSACTIONID', 'AMT', 'PAYMENTSTATUS', 'PENDINGREASON', 'REDIRECTREQUIRED'
	);
	
	protected $_billingaddress;
	protected $_shippingaddress;
	/**
	 * Map for billing address import/export
	 * @var array
	 */
	protected $_token;
	protected $_billingAddressMap = array (
			'BUSINESS' => 'company',
			'NOTETEXT' => 'customer_notes',
			'EMAIL' => 'email',
			'FIRSTNAME' => 'firstname',
			'LASTNAME' => 'lastname',
			'MIDDLENAME' => 'middlename',
			'SALUTATION' => 'prefix',
			'SUFFIX' => 'suffix',
	
			'COUNTRYCODE' => 'country_id', // iso-3166 two-character code
			'STATE'    => 'region',
			'CITY'     => 'city',
			'STREET'   => 'street',
			'STREET2'  => 'street2',
			'ZIP'      => 'postcode',
			'PHONENUM' => 'telephone',
	);
	public $_model_obj=null;
	protected $_checkout = null;
	protected $_payer_id=null;
	/**
	 * DoExpressCheckoutPayment request/response map
	 * @var array
	 */
	protected $_doExpressCheckoutPaymentRequest = array(
			'TOKEN', 'PAYERID', 'PAYMENTACTION', 'AMT', 'CURRENCYCODE', 'IPADDRESS', 'BUTTONSOURCE', 'NOTIFYURL',
			'RETURNFMFDETAILS', 'SUBJECT', 'ITEMAMT', 'SHIPPINGAMT', 'TAXAMT',
	);
	protected $_createBillingAgreementResponse = array('BILLINGAGREEMENTID');
	/**
	 * @var Mage_Paypal_Model_Config
	 */
	protected $_config = null;
	protected $_notify_url=null;
	protected $_payment_action=null;
	protected $_increment_id=null;
	protected $_currency_code=null;
	protected $_line_item_enabled=null;
	protected $_address=null;
	protected $_transaction_id=null;
	protected $_redirect_req=null;
	/**
	 * @var Mage_Sales_Model_Quote
	 */
	protected $_quote = false;
	public function setToken($token){
		$this->_token=$token;
	}
	public function setTransactionId($trans_id=null){
		$this->_transaction_id=$trans_id;
	}
	public function getTransactionId(){
		return $this->_transaction_id;
	}
	public function getModelObject(){
		return $this->_model_obj;
	}
	public function getRedirectRequired(){
		return $this->_redirect_req;
	}
	public function getToken($token){
			return $this->_token;
	}
	public function setPayerId($id=""){
		$this->_payer_id=$id;
	}
	public function getPayerId(){
		return $this->_payer_id;
	}
	public function setAmount($amount=""){
		$this->_amount=$amount;
	}
	public function getAmount(){
		return $this->_amount;
	}
	public function setPaymentAction($action=""){
		$this->_payment_action=$action;
	}
	public function getPaymentAction(){
		return $this->_payment_action;
	}
	public function setNotifyUrl($notify=""){
		$this->_notify_url=$notify;
	}
	public function getNotifyUrl(){
		return $this->_notify_url;
	}
	public function setInvNum($increment_id=""){
		$this->_increment_id=$increment_id;
	}
	public function getInvNum(){
		return $this->_increment_id;
	}
	public function setCurrencyCode($currency_code=""){
		$this->_currency_code=$currency_code;
	}
	public function getCurrencyCode(){
		return $this->_currency_code;
	}
	public function setIsLineItemsEnabled($line=""){
			$this->_line_item_enabled=$line;
	}
	public function getIsLineItemsEnabled(){
		return $this->_line_item_enabled;
	}
	public function setAddress($address=""){
		$this->_address=$address;
	}
	public function getAddress(){
		return $this->_address;
	}
	public function setBillingAddress($billing=""){
		$this->_billing_address=$billing;
	}
	public function getBillingAddress(){
		return $this->_billing_address;
	}
	/**
	 * Return checkout session object
	 *
	 * @return Mage_Checkout_Model_Session
	 */
	private function _getCheckoutSession()
	{
		return Mage::getSingleton('checkout/session');
	}
	
	/**
	 * Return checkout quote object
	 *
	 * @return Mage_Sale_Model_Quote
	 */
	private function _getQuote()
	{
		if (!$this->_quote) {
			$this->_quote = $this->_getCheckoutSession()->getQuote();
		}
		return $this->_quote;
	}
	public function setConfigObject($obj){
		$this->_config=$obj;
	}
	public function getConfigObject(){
		return $this->_config;
	}
	/**
	 * Instantiate quote and checkout
	 * @throws Mage_Core_Exception
	 */
	/* private function _initCheckout()
	{
		$quote = $this->_getQuote();
		if (!$quote->hasItems() || $quote->getHasError()) {
			$this->getResponse()->setHeader('HTTP/1.1','403 Forbidden');
			Mage::throwException(Mage::helper('paypal')->__('Unable to initialize Express Checkout.'));
		}
		
	} */
	
	public function indexAction(){
				
						$quote = $this->_getQuote();
						if (!$quote->hasItems() || $quote->getHasError()) {
							$url = Mage::helper('core/http')->getHttpReferer() ? Mage::helper('core/http')->getHttpReferer()  : Mage::getUrl();
							Mage::getSingleton('core/session')->addError(Mage::helper('laybuy')->__('Product is not added to Cart,unable to initialize Laybuy Express Checkout.Please try again.'));
							Mage::app()->getFrontController()->getResponse()->setRedirect($url);
							Mage::app()->getResponse()->sendResponse();
							exit;
							//$this->getResponse()->setHeader('HTTP/1.1','403 Forbidden');
							//Mage::throwException(Mage::helper('laybuy')->__('Unable to initialize Laybuy Express Checkout.'));
						}
						$model=Mage::getModel("laybuy/express");
						$product_id="";
						$store_id="";
						if(!$model->isAvailable($quote) || !$model->checkForCategoriesAndProducts($product_id,$store_id)){
									if(!$model->isAvailable($quote)){
										Mage::getSingleton('core/session')->addError(Mage::helper('laybuy')->__('Laybuy Express is not Available.'));
										$this->_redirect('checkout/cart/index');
									}
									if(!$model->checkForCategoriesAndProducts($product_id,$store_id)){
												$product_names="";
												$product_names=$model->returnRestrictedProductNames($product_id,$store_id);
												Mage::getSingleton('core/session')->addError(Mage::helper('laybuy')->__('Your cart contains restricted products that are not allowed to pay using Lay-Buy.Please first remove these products - '.$product_names.'.'));
													
									}
									$this->_redirect('checkout/cart/index');
						}
						else{
									$this->loadLayout();
									$this->renderLayout();
						}
				
	}
	public function formAction(){
			
				$quote = $this->_getQuote();
				$model=Mage::getModel("laybuy/express");
				$product_id="";
				$store_id="";
				if(!$model->isAvailable($quote) || !$model->checkForCategoriesAndProducts($product_id,$store_id)){
					if(!$model->checkForCategoriesAndProducts($product_id,$store_id)){
						$product_names="";
						$product_names=$model->returnRestrictedProductNames($product_id,$store_id);
						Mage::getSingleton('core/session')->addError(Mage::helper('laybuy')->__('Your cart contains restricted products that are not allowed to pay using Lay-Buy.Please first remove these products - '.$product_names.'.'));
							
					}
					$this->_redirect('checkout/cart/index');
				}
				
				if($this->getRequest()->getParams()){
					$quote=Mage::getSingleton('checkout/session')->getQuote();
					
					$email = $quote->getBillingAddress()->getEmail();
					
					if (!$email) $email = $quote->getCustomerEmail();
					
							$request=array();
							$amt="";
							$storeId="";
							$storeId=Mage::app()->getStore()->getId();
							if($checkout = Mage::getSingleton('checkout/session')){
								$totals = $checkout->getQuote()->getTotals();
								$amt = $totals["grand_total"]->getValue();
							}
							
							$currency_code ="";
							$currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
							$request["AMOUNT"]=$amt;
							$request["MEMBER"]=Mage::getStoreConfig('payment/laybuy/membership_number',$storeId);
							$request["CURRENCY"]=$currency_code;
							$request["RETURNURL"]=Mage::getUrl("laybuy/express/return");
							$request["CANCELURL"]=Mage::getUrl("laybuy/express/cancel");
							$request["GETEXPRESSRETURNURL"]=Mage::getUrl("laybuy/express/returnfromgetexpress");
							/* $request["RETURNURL"]="http://192.168.1.44/magento18/magento/index.php/laybuy/express/return/";
							$request["CANCELURL"]="http://192.168.1.44/magento18/magento/index.php/laybuy/express/cancel/";
							$request["GETEXPRESSRETURNURL"]="http://192.168.1.44/magento18/magento/index.php/laybuy/express/returnfromgetexpress";
							 */
							$cartItems = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();
							$description="";
							foreach ($cartItems as $item) {
								$_product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
								if($description){
									$description .= ' <br/> ';
								}
								$description .= strip_tags($_product->getShortDescription()).'(Qty '.(int)$item->getQty().')';
							}
							$request["DESC"]=$description;
							$reserved_order_id="";
							Mage::getSingleton('checkout/session')->getQuote()->reserveOrderId()->save();
							$reserved_order_id=Mage::getSingleton('checkout/session')->getQuote()->getReservedOrderId();
							$request["CUSTOM"]=$reserved_order_id;
							$request["EMAIL"]=$email;
							$request["BYPASSLAYBUY"]=Mage::getStoreConfig('payment/laybuy/bypasslaybuy');
							$request["VERSION"]='0.2';
							$params="";
							$params=$this->getRequest()->getParam("payment");
							$mnth="";
							$init="";
							
							$mnth= $params["laybuy_months"];
							$init=$params["laybuy_init"];
							
							$request["INIT"]=0;
							$MAXD   = Mage::getStoreConfig('payment/laybuy/maxd',$storeId);
							$MIND   = Mage::getStoreConfig('payment/laybuy/mind',$storeId);
							$IMAGE  = Mage::getStoreConfig('payment/laybuy/image',$storeId);
							if(isset($mnth) && $mnth){
								$MONTHS =$mnth;
							}else{
								$MONTHS = Mage::getStoreConfig('payment/laybuy/months',$storeId);
							}
							if(isset($init) && $init){
								$INIT = $init;
							}else{
								$INIT = 0;
							}
							/* Restrict maxiumum possible downpayment percentage to less then or equal 50% */
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
							}
							if($INIT){
								
								$request["INIT"]=$INIT;
							}
							$this->_init=$request["INIT"];
							
							$request["MIND"]=$MIND ;
							$request["MAXD"]=$MAXD;
							$request["IMAGE"]=$IMAGE;
							$request["MONTHS"]=$MONTHS;
							Mage::getSingleton('core/session')->setPaymentMonths($request["MONTHS"]);
							Mage::getSingleton('core/session')->setPaymentInit($request["INIT"]);
							$this->_months=$request["MONTHS"];
							$helper=Mage::helper('laybuy');
							$redirectURL="http://lay-buys.com/expressgateway/";
							$token="";
							
							if($token = $helper->postToLaybuy($redirectURL,$request)){
										$this->getResponse()->setBody($this->getLayout()->createBlock('Ced_LayBuy_Block_Express_Redirect')->setData('token',$token)->toHtml());
							}
							
							
				}
	}
	/**
	 * DoExpressCheckout call
	 * @link https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_DoExpressCheckoutPayment
	 */
	public function callDoExpressCheckoutPayment()
	{
		
		$payer_id="";
		if(Mage::getSingleton('core/session')->getPaymentPayerId()){
			$payer_id=Mage::getSingleton('core/session')->getPaymentPayerId();
		}
		$token="";
		if(Mage::getSingleton('core/session')->getLaybuyExpressToken()){
			$token=Mage::getSingleton('core/session')->getLaybuyExpressToken();
		}
		$sandbox="";
		if(Mage::getSingleton('core/session')->getSandbox()){
			$sandbox=Mage::getSingleton('core/session')->getSandbox();
		}
		$vpayment="";
		if(Mage::getSingleton('core/session')->getVpaymentId()){
			$vpayment=Mage::getSingleton('core/session')->getVpaymentId();
		}
		$quote="";
		$email ="";
		$city="";
		$firstname="";
		$lastname="";
		$company="";
		$street="";
		$region_id="";
		$region ="";
		$state="";
		$postcode="";
		$country_id="";
		$telephone="";
		$country_name="";
		$quote=Mage::getSingleton('checkout/session')->getQuote();
		$email = $quote->getBillingAddress()->getEmail();
		$city=$quote->getBillingAddress()->getCity();
		$firstname=$quote->getBillingAddress()->getFirstname();
		$lastname=$quote->getBillingAddress()->getLastname();
		$company=$quote->getBillingAddress()->getCompany();
		$street=$quote->getBillingAddress()->getStreet();
		$region_id=$quote->getBillingAddress()->getRegionId();
		$region = Mage::getModel('directory/region')->load($region_id);
		$state=$region->getName();
		$postcode=$quote->getBillingAddress()->getPostcode();
		$country_id=$quote->getBillingAddress()->getCountryId();
		$country = Mage::getModel('directory/country')->loadByCode($country_id);
		$country_name=$country->getName();
		$telephone=$quote->getBillingAddress()->getTelephone();
		
		$request=array();
		$cartItems = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();
		$description="";
		foreach ($cartItems as $item) {
			$_product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
			if($description){
				$description .= ' <br/> ';
			}
			$description .= strip_tags($_product->getShortDescription()).'(Qty '.(int)$item->getQty().')';
		}
		$request["DESC"]=$description;
		$request["TOKEN"]=$token;
		$request["PAYERID"]=$payer_id;
		$request["COUNTRY_NAME"]=$country_name;
		$request["CITY"]=$city;
		$request["FIRSTNAME"]=$firstname;
		$request["LASTNAME"]=$lastname;
		$request["COMPANY"]=$company;
		$street2="";
		$street1="";
		if(is_array($street)){
					$street1=$street[0];
					if(isset($street[1])){
						$street2=$street[1];
					}
					
		}
		$request["STREET1"]=$street1;
		$request["STREET2"]=$street2;
		$request["STATE"]=$state;
		$request["POSTCODE"]=$postcode;
		$request["COUNTRYID"]=$country_id;
		$request["TELEPHONE"]=$telephone;
		$amt="";
		$storeId="";
		$storeId=Mage::app()->getStore()->getId();
		if($checkout = Mage::getSingleton('checkout/session')){
			$totals = $checkout->getQuote()->getTotals();
			$amt = $totals["grand_total"]->getValue();
		}
		$currency_code ="";
		$currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
		$request["AMOUNT"]=$amt;
		
		$request["MEMBER"]=Mage::getStoreConfig('payment/laybuy/membership_number',$storeId);
		
		$request["CURRENCY"]=$currency_code;
		$cartItems = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();
		$description="";
		foreach ($cartItems as $item) {
			$_product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
			if($description){
				$description .= ' <br/> ';
			}
			$description .= strip_tags($_product->getShortDescription()).'(Qty '.(int)$item->getQty().')';
		}
		$request["DESC"]=$description;
		$reserved_order_id="";
		$reserved_order_id=Mage::getSingleton('checkout/session')->getQuote()->getReservedOrderId();
		$request["CUSTOM"]=$reserved_order_id;
		$request["EMAIL"]=$email;
		$request["BYPASSLAYBUY"]=Mage::getStoreConfig('payment/laybuy/bypasslaybuy');
		$request["VERSION"]='0.2';
		if( Mage::getSingleton('core/session')->getPaymentMonths()){
			$mnth= Mage::getSingleton('core/session')->getPaymentMonths();
		}
		if(Mage::getSingleton('core/session')->getPaymentInit()){
			$init=Mage::getSingleton('core/session')->getPaymentInit();
		}
		$request["INIT"]=0;
		$MAXD   = Mage::getStoreConfig('payment/laybuy/maxd',$storeId);
		$MIND   = Mage::getStoreConfig('payment/laybuy/mind',$storeId);
		$IMAGE  = Mage::getStoreConfig('payment/laybuy/image',$storeId);
		if(isset($mnth) && $mnth){
			$MONTHS =$mnth;
		}else{
			$MONTHS = Mage::getStoreConfig('payment/laybuy/months',$storeId);
		}
		if(isset($init) && $init){
			$INIT = $init;
		}else{
			$INIT = 0;
		}
		/* Restrict maxiumum possible downpayment percentage to less then or equal 50% */
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
		}
		if($INIT){
		
			$request["INIT"]=$INIT;
		}
		$request["MIND"]=$MIND ;
		$request["MAXD"]=$MAXD;
		$request["IMAGE"]=$IMAGE;
		$request["MONTHS"]=$MONTHS;
		$request["DO_EXPRESS"]="1";
		$request['SANDBOX']=$sandbox;
		$request['VPAYMENTID']=$vpayment;
		
		$helper=Mage::helper('laybuy');
		$redirectURL="https://lay-buys.com/expressgateway/doexpress/";
		
		$postdata ='';
		
		foreach($request as $lname=>$lvalue){
			/* if($lname == 'MEMBER') continue; */
			$postdata .= $lname."=".urlencode($lvalue)."&";
		}
		$postdata = rtrim($postdata,'&');
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$redirectURL);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
		$result = curl_exec($ch);
		curl_close ($ch);
		$result = json_decode($result,true);
		if($result['ACK']=="FAILURE"){
			print_r($result);die();
		}
		$doexpressresponse="";
		$laybuyresponse="";
		if(isset($result['doexpressresponse'])){
			$doexpressresponse=$result['doexpressresponse'];
		}
		if(isset($result['laybuyresponse'])){
			$laybuyresponse=$result['laybuyresponse'];
			$this->_laybuyresponse=$result['laybuyresponse'];
			$jsonresponse=json_encode($result['laybuyresponse']);
			$respmodel=Mage::getModel("laybuy/laybuyresp");
			$respmodel->setData("order_id",$result['laybuyresponse']["CUSTOM"]);
			$respmodel->setData("response",$jsonresponse);
			$respmodel->save();
		}
		if($laybuyresponse){
			$this->_transaction_id=$laybuyresponse['DP_PAYPAL_TXN_ID'];
		}
		if($doexpressresponse){
			$this->_redirect_req=$doexpressresponse['SUCCESSPAGEREDIRECTREQUESTED'];
		}
		/* $this->_prepareExpressCheckoutCallRequest($this->_doExpressCheckoutPaymentRequest);
		$request = $this->_exportToRequest($this->_doExpressCheckoutPaymentRequest);
		$this->_exportLineItems($request);
	
		if ($this->getAddress()) {
			$request = $this->_importAddresses($request);
			$request['ADDROVERRIDE'] = 1;
		}*/
		$response = $doexpressresponse;
				$this->_model_obj=new Ced_LayBuy_Model_Abstract();
				$this->_model_obj->_importFromResponse($this->_paymentInformationResponse, $response);//$this
				$this->_model_obj->_importFromResponse($this->_doExpressCheckoutPaymentResponse, $response);
				$this->_model_obj->_importFromResponse($this->_createBillingAgreementResponse, $response);
		
		
	}
	
	public function returnfromgetexpressAction(){
				if($this->getRequest()->getParams()){
						$data=$this->getRequest()->getParams();
						//var_dump($data);die("hellooooohiii");
						Mage::getSingleton('core/session')->setSandbox($data['sand']);
						Mage::getSingleton('core/session')->setVpaymentId($data['vpay']);
						$token=$data['TOKEN'];
						$this->_token=$data['TOKEN'];
						$payer_id=$data['PAYERID'];
						$this->_payer_id=$data['PAYERID'];
						Mage::getSingleton('core/session')->setPaymentPayerId($payer_id);
						Mage::getSingleton('core/session')->setLaybuyExpressToken($token);
						$this->_importFromResponse($this->_paymentInformationResponse, $data);
						$this->_exportAddressses($data);
						$this->_initCheckout();
						$quote = $this->_quote;
						$this->_ignoreAddressValidation();
						$billingAddress = $quote->getBillingAddress();
						$exportedBillingAddress = $this->getExportedBillingAddress();
						$quote->setCustomerEmail($billingAddress->getEmail());
						$quote->setCustomerPrefix($billingAddress->getPrefix());
						$quote->setCustomerFirstname($billingAddress->getFirstname());
						$quote->setCustomerMiddlename($billingAddress->getMiddlename());
						$quote->setCustomerLastname($billingAddress->getLastname());
						$quote->setCustomerSuffix($billingAddress->getSuffix());
						$quote->setCustomerNote($exportedBillingAddress->getData('note'));
						$this->_setExportedAddressData($billingAddress, $exportedBillingAddress);
						// import shipping address
						$exportedShippingAddress = $this->getExportedShippingAddress();
						if (!$quote->getIsVirtual()) {
							$shippingAddress = $quote->getShippingAddress();
							if ($shippingAddress) {
								if ($exportedShippingAddress) {
									$this->_setExportedAddressData($shippingAddress, $exportedShippingAddress);
									$shippingAddress->setCollectShippingRates(true);
									$shippingAddress->setSameAsBilling(0);
						
								}
						
								// import shipping method
								/* $code = '';
								 
								if ($this->_api->getShippingRateCode()) {
									if ($code = $this->_matchShippingMethodCode($shippingAddress, $this->_api->getShippingRateCode())) {
										// possible bug of double collecting rates :-/
										$shippingAddress->setShippingMethod($code)->setCollectShippingRates(true);
									}
								} 
								$quote->getPayment()->setAdditionalInformation(
										self::PAYMENT_INFO_TRANSPORT_SHIPPING_METHOD,
										$code
								);*/
							}
						}
						$payment = $quote->getPayment();
						
						$payment->setMethod("laybuy_express");
						$this->importToPayment($this, $payment);
						$payment->setAdditionalInformation('laybuy_express_checkout_payer_id', $payer_id)
						->setAdditionalInformation('laybuy_express_checkout_token', $token);
						$quote->collectTotals()->save();
						$this->_redirect('*/*/review');
						return;
				}
		
	}
	/**
	 * Check whether the payment was processed successfully
	 *
	 * @param Mage_Payment_Model_Info $payment
	 * @return bool
	 */
	public static function isPaymentSuccessful(Mage_Payment_Model_Info $payment)
	{
		$paymentStatus = $payment->getAdditionalInformation(self::PAYMENT_STATUS_GLOBAL);
		if (in_array($paymentStatus, array(
				self::PAYMENTSTATUS_COMPLETED, self::PAYMENTSTATUS_INPROGRESS, self::PAYMENTSTATUS_REFUNDED,
				self::PAYMENTSTATUS_REFUNDEDPART, self::PAYMENTSTATUS_UNREVERSED, self::PAYMENTSTATUS_PROCESSED,
		))) {
			return true;
		}
		$pendingReason = $payment->getAdditionalInformation(self::PENDING_REASON_GLOBAL);
		return self::PAYMENTSTATUS_PENDING === $paymentStatus
		&& in_array($pendingReason, array('authorization', 'order'));
	}
	/**
	 * Check whether the payment was processed unsuccessfully or failed
	 *
	 * @param Mage_Payment_Model_Info $payment
	 * @return bool
	 */
	public static function isPaymentFailed(Mage_Payment_Model_Info $payment)
	{
		$paymentStatus = $payment->getAdditionalInformation(self::PAYMENT_STATUS_GLOBAL);
		return in_array($paymentStatus, array(
				self::PAYMENTSTATUS_DENIED, self::PAYMENTSTATUS_EXPIRED, self::PAYMENTSTATUS_FAILED,
				self::PAYMENTSTATUS_REVERSED, self::PAYMENTSTATUS_VOIDED,
		));
	}
	/**
	 * Check whether the payment is in review state
	 *
	 * @param Mage_Payment_Model_Info $payment
	 * @return bool
	 */
	public static function isPaymentReviewRequired(Mage_Payment_Model_Info $payment)
	{
		$paymentStatus = $payment->getAdditionalInformation(self::PAYMENT_STATUS_GLOBAL);
		if (self::PAYMENTSTATUS_PENDING === $paymentStatus) {
			$pendingReason = $payment->getAdditionalInformation(self::PENDING_REASON_GLOBAL);
			return !in_array($pendingReason, array('authorization', 'order'));
		}
		return false;
	}
	/**
	 * Grab data from source and map it into payment
	 *
	 * @param array|Varien_Object|callback $from
	 * @param Mage_Payment_Model_Info $payment
	 */
	public function importToPayment($from, Mage_Payment_Model_Info $payment)
	{
		$fullMap = array_merge($this->_paymentMap, $this->_systemMap);
	
		if (is_object($from)) {
			$from = array($from, 'getDataUsingMethod');
		}
	
		Varien_Object_Mapper::accumulateByMap($from, array($payment, 'setAdditionalInformation'), $fullMap);
	
	}
	
	/**
	 * Import $this public data from a private response array
	 *
	 * @param array $privateResponseMap
	 * @param array $response
	 */
	protected function _importFromResponse(array $privateResponseMap, array $response)
	{
		$map = array();
		foreach ($privateResponseMap as $key) {
			if (isset($this->_globalMap[$key])) {
				$map[$key] = $this->_globalMap[$key];
			}
			if (isset($response[$key]) && isset($this->_importFromRequestFilters[$key])) {
				$callback = $this->_importFromRequestFilters[$key];
				$response[$key] = call_user_func(array($this, $callback), $response[$key], $key, $map[$key]);
			}
		}
		Varien_Object_Mapper::accumulateByMap($response, array($this, 'setDataUsingMethod'), $map);
	}
	
	/**
	 * Sets address data from exported address
	 *
	 * @param Mage_Sales_Model_Quote_Address $address
	 * @param array $exportedAddress
	 */
	protected function _setExportedAddressData($address, $exportedAddress)
	{
		foreach ($exportedAddress->getExportedKeys() as $key) {
			$oldData = $address->getDataUsingMethod($key);
			$isEmpty = null;
			if (is_array($oldData)) {
				foreach($oldData as $val) {
					if(!empty($val)) {
						$isEmpty = false;
						break;
					}
					$isEmpty = true;
				}
			}
			if (empty($oldData) || $isEmpty === true) {
				$address->setDataUsingMethod($key, $exportedAddress->getData($key));
			}
		}
	}
	protected function setExportedBillingAddress($address=""){
				$this->_billingaddress=$address;
	}
	protected function getExportedBillingAddress($address=""){
			return $this->_billingaddress;
	}
	protected function setExportedShippingAddress($address=""){
			$this->_shippingaddress=$address;
	}
	protected function getExportedShippingAddress($address=""){
			return $this->_shippingaddress;
	}
	/**
	 * Create billing and shipping addresses basing on response data
	 * @param array $data
	 */
	protected function _exportAddressses($data)
	{
		$address = new Varien_Object();
		Varien_Object_Mapper::accumulateByMap($data, $address, $this->_billingAddressMap);
		$address->setExportedKeys(array_values($this->_billingAddressMap));
		$this->_applyStreetAndRegionWorkarounds($address);
		$this->setExportedBillingAddress($address);
		// assume there is shipping address if there is at least one field specific to shipping
		if (isset($data['SHIPTONAME'])) {
			$shippingAddress = clone $address;
			Varien_Object_Mapper::accumulateByMap($data, $shippingAddress, $this->_shippingAddressMap);
			$this->_applyStreetAndRegionWorkarounds($shippingAddress);
			// PayPal doesn't provide detailed shipping name fields, so the name will be overwritten
			$firstName = $data['SHIPTONAME'];
			$lastName = null;
			if (isset($data['FIRSTNAME']) && $data['LASTNAME']) {
				$firstName = $data['FIRSTNAME'];
				$lastName = $data['LASTNAME'];
			}
			$shippingAddress->addData(array(
					'prefix'     => null,
					'firstname'  => $firstName,
					'middlename' => null,
					'lastname'   => $lastName,
					'suffix'     => null,
			));
			$this->setExportedShippingAddress($shippingAddress);
			
		}
	}
	/**
	 * Adopt specified address object to be compatible with Magento
	 *
	 * @param Varien_Object $address
	 */
	protected function _applyStreetAndRegionWorkarounds(Varien_Object $address)
	{
		// merge street addresses into 1
		if ($address->hasStreet2()) {
			$address->setStreet(implode("\n", array($address->getStreet(), $address->getStreet2())));
			$address->unsStreet2();
		}
		// attempt to fetch region_id from directory
		if ($address->getCountryId() && $address->getRegion()) {
			$regions = Mage::getModel('directory/country')->loadByCode($address->getCountryId())->getRegionCollection()
			->addRegionCodeOrNameFilter($address->getRegion())
			->setPageSize(1);
			/**********************new added code*************************/
			/*$region1="";
			$condition="";
			$region1=$address->getRegion();

			if (!empty($region1)) {
				$condition = is_array($region1) ? array('in' => $region1) : $region1;
				$regions->addFieldToFilter(array('e.code', 'e.default_name'), array($condition, $condition));
				echo $regions->getSelect();die("lkhj");
			}*/
			/**********************new added code ends*************************/
			foreach ($regions as $region) {
				$address->setRegionId($region->getId());
				$address->setExportedKeys(array_merge($address->getExportedKeys(), array('region_id')));
				break;
			}
		}
	}
	/**
	 * Make sure addresses will be saved without validation errors
	 */
	private function _ignoreAddressValidation()
	{
		$this->_quote->getBillingAddress()->setShouldIgnoreValidation(true);
		if (!$this->_quote->getIsVirtual()) {
			$this->_quote->getShippingAddress()->setShouldIgnoreValidation(true);
			if ( !$this->_quote->getBillingAddress()->getEmail()) {//!$this->_config->requireBillingAddress &&
				$this->_quote->getBillingAddress()->setSameAsBilling(1);
			}
		}
	}
	public function continueAction(){
				$quote = $this->_getQuote();
				if (!$quote->hasItems() || $quote->getHasError()) {
					$this->getResponse()->setHeader('HTTP/1.1','403 Forbidden');
					Mage::throwException(Mage::helper('laybuy')->__('Unable to initialize Express Checkout.'));
				}
				$this->_checkout = Mage::getSingleton("laybuy/express_checkout", array(
						'quote'  => $quote,
				));
				if ($this->_getQuote()->getIsMultiShipping()) {
					$this->_getQuote()->setIsMultiShipping(false);
					$this->_getQuote()->removeAllAddresses();
				}
				$customer = Mage::getSingleton('customer/session')->getCustomer();
				if ($customer && $customer->getId()) {
					$this->_checkout->setCustomerWithAddressChange(
							$customer, $this->_getQuote()->getBillingAddress(), $this->_getQuote()->getShippingAddress()
					);
				}
				$this->_checkout->prepareGiropayUrls(
						Mage::getUrl('checkout/onepage/success'),
						Mage::getUrl('laybuy/express/cancel'),
						Mage::getUrl('checkout/onepage/success')
				);
				$token = $this->_checkout->start(Mage::getUrl('*/*/return'), Mage::getUrl('*/*/cancel'));
				
	}
	/**
	 * when laybuy returns
	 * The order information at this point is in POST
	 */
	public function success($order_id)
	{
		//var_dump($order_id);echo "<br>";
		$respmodel=Mage::getModel("laybuy/laybuyresp")->getCollection();
		$laybuyresponse="";
		foreach($respmodel as $model)
		{
				if($order_id==$model->getData("order_id")){
						$resp="";
						$resp=$model->getData("response");
						$laybuyresponse=json_decode($resp,true);
						break;
				}
		}
		//var_dump($laybuyresponse);echo "<br>";
		$format = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
		$status = array_change_key_case($laybuyresponse,CASE_LOWER);
		//var_dump($status);echo "<br>";
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
			//$session->setQuoteId($session->getLayBuyStandardQuoteId(true));
			//Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
			$order = Mage::getModel('sales/order')->loadByIncrementId($status['custom']);
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
				$order->setData('state', "new");
   				$order->setStatus("pending");
   				//$order->addStatusToHistory("pending", 'Put your comment here', true);
   				/* $history = Mage::getModel('sales/order_status_history')
   				->setStatus("pending")
   				->setComment('My Comment!')
   				->setEntityName(Mage_Sales_Model_Order::HISTORY_ENTITY_NAME)
   				->setIsCustomerNotified(false)
   				->setCreatedAt(date('Y-m-d H:i:s', time() - 60*60*24));
   				
   				$order->addStatusHistory($history); */
   				$comments = $order->getStatusHistoryCollection(true);
   				$com="Authorized amount of ".Mage::app()->getLocale()->currency($status['currency'])->toCurrency($status['downpayment_amount']).". Transaction ID: \"".$status['dp_paypal_txn_id']."\"."; 
   				foreach ($comments as $c) {
   					
   						$c->setStatus("complete");
   						$c->setComment($com)->save();
   					
   				}
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
		//$this->_redirect('checkout/onepage/success',array('_secure' => true));
	}
	public function returnAction()
	{
		try {
			$this->_initCheckout();
			$this->_checkout->returnFromPaypal($this->_initToken());
			$this->_redirect('*/*/review');
			return;
		}
		catch (Mage_Core_Exception $e) {
			Mage::getSingleton('checkout/session')->addError($e->getMessage());
		}
		catch (Exception $e) {
			Mage::getSingleton('checkout/session')->addError($this->__('Unable to process Express Checkout approval.'));
			Mage::logException($e);
		}
		$this->_redirect('checkout/cart');
	}
	public function _initToken(){
		return $this->_token;
	}
	/**
	 * Get checkout method
	 *
	 * @return string
	 */
	public function getCheckoutMethod()
	{
		if ($this->getCustomerSession()->isLoggedIn()) {
			return Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER;
		}
	
		if (!$this->_quote->getCheckoutMethod()) {
			if (Mage::helper('checkout')->isAllowedGuestCheckout($this->_quote)) {
				$this->_quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_GUEST);
			} else {
				$this->_quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER);
			}
		}
		return $this->_quote->getCheckoutMethod();
	}
	
	/**
	 * Set shipping method to quote, if needed
	 * @param string $methodCode
	 */
	public function updateShippingMethod($methodCode)
	{
		if (!$this->_quote->getIsVirtual() && $shippingAddress = $this->_quote->getShippingAddress()) {
			if ($methodCode != $shippingAddress->getShippingMethod()) {
				$this->_ignoreAddressValidation();
				$shippingAddress->setShippingMethod($methodCode)->setCollectShippingRates(true);
				$this->_quote->collectTotals();
			}
		}
	}
	/**
	 * Prepare quote for guest checkout order submit
	 *
	 * @return Mage_Paypal_Model_Express_Checkout
	 */
	protected function _prepareGuestQuote()
	{
		$quote = $this->_quote;
		$quote->setCustomerId(null)
		->setCustomerEmail($quote->getBillingAddress()->getEmail())
		->setCustomerIsGuest(true)
		->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
		return $this;
	}
	/**
	 * Checks if customer with email coming from Express checkout exists
	 *
	 * @return int
	 */
	protected function _lookupCustomerId()
	{
		return Mage::getModel('customer/customer')
		->setWebsiteId(Mage::app()->getWebsite()->getId())
		->loadByEmail($this->_quote->getCustomerEmail())
		->getId();
	}
	/**
	 * Get customer session object
	 *
	 * @return Mage_Customer_Model_Session
	 */
	public function getCustomerSession()
	{
		return Mage::getSingleton('customer/session');
	}
	/**
	 * Prepare quote for customer order submit
	 *
	 * @return Mage_Paypal_Model_Express_Checkout
	 */
	protected function _prepareCustomerQuote()
	{
		$quote      = $this->_quote;
		$billing    = $quote->getBillingAddress();
		$shipping   = $quote->isVirtual() ? null : $quote->getShippingAddress();
	
		$customer = $this->getCustomerSession()->getCustomer();
		if (!$billing->getCustomerId() || $billing->getSaveInAddressBook()) {
			$customerBilling = $billing->exportCustomerAddress();
			$customer->addAddress($customerBilling);
			$billing->setCustomerAddress($customerBilling);
		}
		if ($shipping && ((!$shipping->getCustomerId() && !$shipping->getSameAsBilling())
				|| (!$shipping->getSameAsBilling() && $shipping->getSaveInAddressBook()))) {
			$customerShipping = $shipping->exportCustomerAddress();
			$customer->addAddress($customerShipping);
			$shipping->setCustomerAddress($customerShipping);
		}
	
		if (isset($customerBilling) && !$customer->getDefaultBilling()) {
			$customerBilling->setIsDefaultBilling(true);
		}
		if ($shipping && isset($customerBilling) && !$customer->getDefaultShipping() && $shipping->getSameAsBilling()) {
			$customerBilling->setIsDefaultShipping(true);
		} elseif ($shipping && isset($customerShipping) && !$customer->getDefaultShipping()) {
			$customerShipping->setIsDefaultShipping(true);
		}
		$quote->setCustomer($customer);
	
		return $this;
	}
	
	/**
	 * Prepare quote for customer registration and customer order submit
	 * and restore magento customer data from quote
	 *
	 * @return Mage_Paypal_Model_Express_Checkout
	 */
	protected function _prepareNewCustomerQuote()
	{
		$quote      = $this->_quote;
		$billing    = $quote->getBillingAddress();
		$shipping   = $quote->isVirtual() ? null : $quote->getShippingAddress();
	
		$customerId = $this->_lookupCustomerId();
		if ($customerId) {
			$this->getCustomerSession()->loginById($customerId);
			return $this->_prepareCustomerQuote();
		}
	
		$customer = $quote->getCustomer();
		/** @var $customer Mage_Customer_Model_Customer */
		$customerBilling = $billing->exportCustomerAddress();
		$customer->addAddress($customerBilling);
		$billing->setCustomerAddress($customerBilling);
		$customerBilling->setIsDefaultBilling(true);
		if ($shipping && !$shipping->getSameAsBilling()) {
			$customerShipping = $shipping->exportCustomerAddress();
			$customer->addAddress($customerShipping);
			$shipping->setCustomerAddress($customerShipping);
			$customerShipping->setIsDefaultShipping(true);
		} elseif ($shipping) {
			$customerBilling->setIsDefaultShipping(true);
		}
		/**
		 * @todo integration with dynamica attributes customer_dob, customer_taxvat, customer_gender
		 */
		if ($quote->getCustomerDob() && !$billing->getCustomerDob()) {
			$billing->setCustomerDob($quote->getCustomerDob());
		}
	
		if ($quote->getCustomerTaxvat() && !$billing->getCustomerTaxvat()) {
			$billing->setCustomerTaxvat($quote->getCustomerTaxvat());
		}
	
		if ($quote->getCustomerGender() && !$billing->getCustomerGender()) {
			$billing->setCustomerGender($quote->getCustomerGender());
		}
	
		Mage::helper('core')->copyFieldset('checkout_onepage_billing', 'to_customer', $billing, $customer);
		$customer->setEmail($quote->getCustomerEmail());
		$customer->setPrefix($quote->getCustomerPrefix());
		$customer->setFirstname($quote->getCustomerFirstname());
		$customer->setMiddlename($quote->getCustomerMiddlename());
		$customer->setLastname($quote->getCustomerLastname());
		$customer->setSuffix($quote->getCustomerSuffix());
		$customer->setPassword($customer->decryptPassword($quote->getPasswordHash()));
		$customer->setPasswordHash($customer->hashPassword($customer->getPassword()));
		$customer->save();
		$quote->setCustomer($customer);
	
		return $this;
	}
	/**
	 * Involve new customer to system
	 *
	 * @return Mage_Paypal_Model_Express_Checkout
	 */
	protected function _involveNewCustomer()
	{
		$customer = $this->_quote->getCustomer();
		if ($customer->isConfirmationRequired()) {
			$customer->sendNewAccountEmail('confirmation');
			$url = Mage::helper('customer')->getEmailConfirmationUrl($customer->getEmail());
			$this->getCustomerSession()->addSuccess(
					Mage::helper('customer')->__('Account confirmation is required. Please, check your e-mail for confirmation link. To resend confirmation email please <a href="%s">click here</a>.', $url)
			);
		} else {
			$customer->sendNewAccountEmail();
			$this->getCustomerSession()->loginById($customer->getId());
		}
		return $this;
	}
	
	/**
	 * Place the order and recurring payment profiles when customer returned from paypal
	 * Until this moment all quote data must be valid
	 *
	 * @param string $token
	 * @param string $shippingMethodCode
	 */
	public function place($token, $shippingMethodCode = null)
	{
		if ($shippingMethodCode) {
			$this->updateShippingMethod($shippingMethodCode);
		}
	
		$isNewCustomer = false;
		switch ($this->getCheckoutMethod()) {
			case Mage_Checkout_Model_Type_Onepage::METHOD_GUEST:
				$this->_prepareGuestQuote();
				break;
			case Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER:
				$this->_prepareNewCustomerQuote();
				$isNewCustomer = true;
				break;
			default:
				$this->_prepareCustomerQuote();
				break;
		}
	
		$this->_ignoreAddressValidation();
		$this->_quote->collectTotals();
	
		$service = Mage::getModel('sales/service_quote', $this->_quote);
	
		$service->submitAll();
		
		
		$this->_quote->save();
	
		if ($isNewCustomer) {
			try {
				$this->_involveNewCustomer();
			} catch (Exception $e) {
				Mage::logException($e);
			}
		}
		
		$this->_recurringPaymentProfiles = $service->getRecurringPaymentProfiles();
		// TODO: send recurring profile emails
	
		$order = $service->getOrder();
		if (!$order) {
			return;
		}
		$this->_billingAgreement = $order->getPayment()->getBillingAgreement();
	
	
		// commence redirecting to finish payment, if paypal requires it
		 
		if ($order->getPayment()->getAdditionalInformation(
				'laybuy_express_checkout_redirect_required'
		)) {
			$this->_redirectUrl = $this->getExpressCheckoutCompleteUrl($token);
		}
		 
		switch ($order->getState()) {
			// even after placement paypal can disallow to authorize/capture, but will wait until bank transfers money
			case Mage_Sales_Model_Order::STATE_PENDING_PAYMENT:
				// TODO
				break;
				// regular placement, when everything is ok
			case Mage_Sales_Model_Order::STATE_PROCESSING:
			case Mage_Sales_Model_Order::STATE_COMPLETE:
			case Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW:
				$order->sendNewOrderEmail();
				break;
		}
		
		$this->_order = $order;
	}
	/**
	 * Get url for additional actions that PayPal may require customer to do after placing the order.
	 * For instance, redirecting customer to bank for payment confirmation.
	 *
	 * @param string $token
	 * @return string
	 */
	public function getExpressCheckoutCompleteUrl($token)
	{
		return $this->getLaybuyUrl(array(
				'cmd'   => '_complete-express-checkout',
				'token' => $token,
		));
	}

	/**
	 * PayPal web URL generic getter
	 *
	 * @param array $params
	 * @return string
	 */
	public function getLaybuyUrl(array $params = array())
	{
		/* return sprintf('https://www.%spaypal.com/cgi-bin/webscr%s',
				$this->sandboxFlag ? 'sandbox.' : '',
				$params ? '?' . http_build_query($params) : ''
		); */
		$url="http://lay-buys.com/expressgateway".'?' . http_build_query($params) ;
		return $url;
	}
	/**
	 * Submit the order
	 */
	public function placeOrderAction()
	{
	
		try {
			$requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds();
			if ($requiredAgreements) {
				$postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
				if (array_diff($requiredAgreements, $postedAgreements)) {
					Mage::throwException(Mage::helper('laybuy')->__('Please agree to all the terms and conditions before placing the order.'));
				}
			}
	
			$this->_initCheckout();
			$this->place($this->_initToken());
			// prepare session to success or cancellation page
			$session = $this->_getCheckoutSession();
			$session->clearHelperData();
	
			// "last successful quote"
			$quoteId = $this->_getQuote()->getId();
			$session->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);
	
			// an order may be created
			$order = $this->getOrder();
			if ($order) {
				$session->setLastOrderId($order->getId())
				->setLastRealOrderId($order->getIncrementId());
				// as well a billing agreement can be created
				$agreement = $this->getBillingAgreement();
				if ($agreement) {
					$session->setLastBillingAgreementId($agreement->getId());
				}
			}
	
			// recurring profiles may be created along with the order or without it
			$profiles = $this->getRecurringPaymentProfiles();
			if ($profiles) {
				$ids = array();
				foreach($profiles as $profile) {
					$ids[] = $profile->getId();
				}
				$session->setLastRecurringProfileIds($ids);
			}
	
			// redirect if PayPal specified some URL (for example, to Giropay bank)
			/* $url = $this->getRedirectUrl();
			if ($url) {
				$this->getResponse()->setRedirect($url);
				return;
			} */
			//$this->_initToken(false); // no need in token anymore
			$order_id=$order->getIncrementId();
			$this->success($order_id);
			$this->_redirect('checkout/onepage/success');
			return;
		}
		catch (Mage_Core_Exception $e) {
			$this->_getSession()->addError($e->getMessage());
		}
		catch (Exception $e) {
			$this->_getSession()->addError($this->__('Unable to place the order.'));
			Mage::logException($e);
		}
		$this->_redirect('*/*/review');
	}
	/**
	 * Determine whether redirect somewhere specifically is required
	 *
	 * @return string
	 */
	public function getRedirectUrl()
	{
		return $this->_redirectUrl;
	}
	/**
	 * PayPal session instance getter
	 *
	 * @return Mage_PayPal_Model_Session
	 */
	private function _getSession()
	{
		return Mage::getSingleton('core/session');
	}
	/**
	 * Return recurring payment profiles
	 *
	 * @return array
	 */
	public function getRecurringPaymentProfiles()
	{
		return $this->_recurringPaymentProfiles;
	}
	/**
	 * Get created billing agreement
	 *
	 * @return Mage_Sales_Model_Billing_Agreement|null
	 */
	public function getBillingAgreement()
	{
		return $this->_billingAgreement;
	}
	/**
	 * Return order
	 *
	 * @return Mage_Sales_Model_Order
	 */
	public function getOrder()
	{
		return $this->_order;
	}
	public function reviewAction()
	{
		try {
			$this->_initCheckout();
			$this->prepareOrderReview($this->_initToken());
			$this->loadLayout();
			$this->_initLayoutMessages('paypal/session');
			$reviewBlock = $this->getLayout()->getBlock('laybuy.express.review');
			$reviewBlock->setQuote($this->_getQuote());
			$reviewBlock->getChild('details')->setQuote($this->_getQuote());
			if ($reviewBlock->getChild('shipping_method')) {
				$reviewBlock->getChild('shipping_method')->setQuote($this->_getQuote());
			}
			$this->renderLayout();
			return;
		}
		catch (Mage_Core_Exception $e) {
			Mage::getSingleton('checkout/session')->addError($e->getMessage());
		}
		catch (Exception $e) {
			Mage::getSingleton('checkout/session')->addError(
			$this->__('Unable to initialize Express Checkout review.')
			);
			Mage::logException($e);
		}
		$this->_redirect('checkout/cart');
	}
	/**
	 * Update Order (combined action for ajax and regular request)
	 */
	public function updateOrderAction()
	{
		try {
			$isAjax = $this->getRequest()->getParam('isAjax');
			$this->_initCheckout();
			$this->updateOrders($this->getRequest()->getParams());
			if ($isAjax) {
				$this->loadLayout('laybuy_express_review_details');
				$this->getResponse()->setBody($this->getLayout()->getBlock('root')
						->setQuote($this->_getQuote())
						->toHtml());
				return;
			}
		} catch (Mage_Core_Exception $e) {
			$this->_getSession()->addError($e->getMessage());
		} catch (Exception $e) {
			$this->_getSession()->addError($this->__('Unable to update Order data.'));
			Mage::logException($e);
		}
		if ($isAjax) {
			$this->getResponse()->setBody('<script type="text/javascript">window.location.href = '
					. Mage::getUrl('*/*/review') . ';</script>');
		} else {
			$this->_redirect('*/*/review');
		}
	}
	/**
	 * Update order data
	 *
	 * @param array $data
	 */
	public function updateOrders($data)
	{
		/** @var $checkout Mage_Checkout_Model_Type_Onepage */
		$checkout = Mage::getModel('checkout/type_onepage');
	
		$this->_quote->setTotalsCollectedFlag(true);
		$checkout->setQuote($this->_quote);
		if (isset($data['billing'])) {
			if (isset($data['customer-email'])) {
				$data['billing']['email'] = $data['customer-email'];
			}
			$checkout->saveBilling($data['billing'], 0);
		}
		if (!$this->_quote->getIsVirtual() && isset($data['shipping'])) {
			$checkout->saveShipping($data['shipping'], 0);
		}
	
		if (isset($data['shipping_method'])) {
			$this->updateShippingMethod($data['shipping_method']);
		}
		$this->_quote->setTotalsCollectedFlag(false);
		$this->_quote->collectTotals();
		$this->_quote->setDataChanges(true);
		$this->_quote->save();
	}
	
	/**
	 * Update Order (combined action for ajax and regular request)
	 */
	public function updateShippingMethodsAction()
	{
		try {
			$this->_initCheckout();
			$this->prepareOrderReview($this->_initToken());
			$this->loadLayout('laybuy_express_review');
	
			$this->getResponse()->setBody($this->getLayout()->getBlock('express.review.shipping.method')
					->setQuote($this->_getQuote())
					->toHtml());
			return;
		} catch (Mage_Core_Exception $e) {
			$this->_getSession()->addError($e->getMessage());
		} catch (Exception $e) {
			$this->_getSession()->addError($this->__('Unable to update Order data.'));
			Mage::logException($e);
		}
		$this->getResponse()->setBody('<script type="text/javascript">window.location.href = '
				. Mage::getUrl('*/*/review') . ';</script>');
	}
	
	/**
	 * Check whether order review has enough data to initialize
	 *
	 * @param $token
	 * @throws Mage_Core_Exception
	 */
	public function prepareOrderReview($token = null)
	{
		$payment = $this->_quote->getPayment();
		if (!$payment || !$payment->getAdditionalInformation("laybuy_express_checkout_payer_id")) {
			Mage::throwException(Mage::helper('laybuy')->__('Payer is not identified.'));
		}
		$this->_quote->setMayEditShippingAddress(
				1 != $this->_quote->getPayment()->getAdditionalInformation('laybuy_express_checkout_shipping_overriden')
		);
		$this->_quote->setMayEditShippingMethod(
				'' == $this->_quote->getPayment()->getAdditionalInformation('laybuy_express_checkout_shipping_method')
		);
		$this->_ignoreAddressValidation();
		$this->_quote->collectTotals()->save();
	}
	
	private function _initCheckout()
	{
		$quote = $this->_getQuote();
		if (!$quote->hasItems() || $quote->getHasError()) {
			$this->getResponse()->setHeader('HTTP/1.1','403 Forbidden');
			Mage::throwException(Mage::helper('laybuy')->__('Unable to initialize Express Checkout.'));
		}
	
		$this->_checkout = Mage::getSingleton($this->_checkoutType, array(
				'quote'  => $quote,
		));
	}
	public function cancelAction()
	{
		try {
			//$this->_initToken(false);
			$this->_token=null;
			// TODO verify if this logic of order cancelation is deprecated
			// if there is an order - cancel it
			$orderId = $this->_getCheckoutSession()->getLastOrderId();
			$order = ($orderId) ? Mage::getModel('sales/order')->load($orderId) : false;
			if ($order && $order->getId() && $order->getQuoteId() == $this->_getCheckoutSession()->getQuoteId()) {
				$order->cancel()->save();
				$this->_getCheckoutSession()
				->unsLastQuoteId()
				->unsLastSuccessQuoteId()
				->unsLastOrderId()
				->unsLastRealOrderId()
				->addSuccess($this->__('Laybuy Express Checkout and Order have been canceled.'))
				;
			} else {
				$this->_getCheckoutSession()->addSuccess($this->__('Laybuy Express Checkout has been canceled.'));
			}
		} catch (Mage_Core_Exception $e) {
			$this->_getCheckoutSession()->addError($e->getMessage());
		} catch (Exception $e) {
			$this->_getCheckoutSession()->addError($this->__('Unable to cancel Laybuy Express Checkout.'));
			Mage::logException($e);
		}
	
		$this->_redirect('checkout/cart');
	}
	
}

?>
