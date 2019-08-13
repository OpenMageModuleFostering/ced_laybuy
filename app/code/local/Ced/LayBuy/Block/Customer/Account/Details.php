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

class Ced_LayBuy_Block_Customer_Account_Details extends Mage_Core_Block_Template
{
    protected $_links = array();
	protected $_orderId = null;

    protected function _construct()
    {
        parent::_construct();
		$this->_orderId = $this->getRequest()->getParam('order_id');
		$order =  Mage::getModel('sales/order')->load($this->_orderId);
		$orderIncrementId = $order->getIncrementId();
        $this->setOrderIncrementId($orderIncrementId);
		$this->setOrderStatusLabel($order->getStatusLabel());
		$transaction = Mage::getModel('laybuy/report')->load($this->getRequest()->getParam('id'));

        $this->setTransaction($transaction);

        Mage::app()->getFrontController()->getAction()->getLayout()->getBlock('root')->setHeaderTitle(Mage::helper('laybuy')->__('View Transaction Details'));
    }
	
	public function getTransactionStatusLabel($statusId){
		$statuses = Mage::helper('laybuy')->getStatuses();
		return $statuses[$statusId];
	}

	public function getBackUrl()
    {
        return $this->getUrl('laybuy/report/grid/',array('_secure' => true,'order_id'=>$this->_orderId));
    }
	
	public function getBackTitle()
    {
        return Mage::helper('laybuy')->__('Back');
    }

}
