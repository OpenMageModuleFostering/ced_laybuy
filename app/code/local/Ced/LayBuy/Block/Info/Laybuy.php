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
 * Block for LayBuy payment method info
 */
class Ced_LayBuy_Block_Info_Laybuy extends Mage_Payment_Block_Info
{
    /**
     * Payment rendered specific information
     *
     * @var Varien_Object
     */
    protected $_paymentSpecificInformation = null;
	
	protected $_currentTransaction = null;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('laybuy/info/default.phtml');
    }

    /**
     * Prepare information specific to current payment method
     *
     * @param Varien_Object|array $transport
     * @return Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null === $this->_paymentSpecificInformation) {
            if (null === $transport) {
                $transport = new Varien_Object;
            } elseif (is_array($transport)) {
                $transport = new Varien_Object($transport);
            }
            Mage::dispatchEvent('payment_info_block_prepare_specific_information', array(
                'transport' => $transport,
                'payment'   => $this->getInfo(),
				'transaction'=> $this->getTransaction(),
                'block'     => $this,
            ));
            $this->_paymentSpecificInformation = $transport;
        }
        return $this->_paymentSpecificInformation;
    }
	
	/**
     * Retrieve LayBuy transaction model
     *
     * @return Ced_LayBuy_Model_Report
     */
	public function getTransaction(){
		if(null === $this->_currentTransaction){
			$orderId = $this->getInfo()->getParentId();
			$orderIncrementId = Mage::getModel('sales/order')->load($orderId)->getIncrementId();
			$this->_currentTransaction = Mage::getModel('laybuy/report')->getCollection()->addFieldToFilter('order_id',array('eq'=>$orderIncrementId));
		}
		return $this->_currentTransaction;
	}
}