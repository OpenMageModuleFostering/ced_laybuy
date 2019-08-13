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
 * LayBuy Standard Checkout Module
 */
 
class Ced_LayBuy_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
	public $_code = 'laybuy';
	protected $_formBlockType = 'laybuy/form_laybuy';
	protected $_infoBlockType = 'laybuy/info_laybuy';
	protected $_isInitializeNeeded      = true;
	protected $_canUseInternal          = false;
	protected $_canUseForMultishipping  = false;
	
	/**
	* Return Order place redirect url
	* @return string
	*/
	public function getOrderPlaceRedirectUrl()
	{
		//when you click on place order you will be redirected on this url, if you don't want this action remove this method
		return Mage::getUrl('laybuy/standard/redirect', array('_secure' => true));
	}
	/**
     * Check whether payment method can be used
     * @param Mage_Sales_Model_Quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if ($status = parent::isAvailable($quote)) {
	
			$storeId = $quote->getStoreId();
			
			/* Condition for minimum checkout amount for method availability */
			$configTotal = Mage::getStoreConfig('laybuy/conditional_criteria/total',$storeId);
			$total = $quote->getData('grand_total');
			if($status && $configTotal){
				if($configTotal<$total){
					$status = true;
				}else{
					$status = false;
				}
			}
			
			/* Condition for customer groups for method availability */
			if($status){
				$configCustomerGroupId = explode(',',Mage::getStoreConfig('laybuy/conditional_criteria/customergroup',$storeId ));
				$customerGroupId = $quote->getData('customer_group_id');
				if($configCustomerGroupId && in_array($customerGroupId,$configCustomerGroupId)){
					$status = true;
				}else{
					$status = false;
				}
			}	
			
			return $status;
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
		if($configCategories){
			foreach($cartItems as $_product){
				$_product = Mage::getModel('catalog/product')->load($_product->getProductId());
				if($xproducts && in_array($_product->getId(),$xproducts)){
					$status = false;
					$productName .= $_product->getName().',';
				}elseif(count(array_diff($_product->getCategoryIds(),$configCategories))>0){
					$status = false;
					$productName .= $_product->getName().',';
				}
			}
			$productName = rtrim($productName,',');
		}
        return array($status,$productName);
    }
}