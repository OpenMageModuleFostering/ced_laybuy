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
 *  Block for LayBuy report grid renderer
 */
class Ced_LayBuy_Block_Adminhtml_Report_Renderer_Order extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
	public function render(Varien_Object $row)
    {
       	$orderId=$row->getOrderId();
		$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
		if($order && $order->getId()){
			$orderUrl = Mage::helper('adminhtml')->getUrl('adminhtml/sales_order/view',array('order_id'=>$order->getId()));
			return '<a title="'.$orderUrl.'" href="'.$orderUrl.'">'.$orderId.'</a>';
		}else{
			return $orderId;
		}
        
    }
}