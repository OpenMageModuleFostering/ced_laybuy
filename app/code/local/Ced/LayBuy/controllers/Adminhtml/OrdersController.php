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
 * LayBuy Instalment Reports Controller
 *
 * @category    Ced
 * @package     Ced_LayBuy
 * @author 		Asheesh Singh<asheeshsingh@cedcoss.com>
 */
class Ced_LayBuy_Adminhtml_OrdersController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Grid action
     */
    public function indexAction() {
        $this->_title($this->__('Sales'))->_title(Mage::helper('laybuy')->__('Lay-Buy Orders'));
        $this->loadLayout()
            ->_setActiveMenu('sales/laybuyorders')
            ->_addBreadcrumb($this->__('Sales'), Mage::helper('laybuy')->__('Sales'))
            ->_addBreadcrumb(Mage::helper('laybuy')->__('Lay-Buy Orders'), Mage::helper('laybuy')->__('Lay-Buy Orders'))
			->_addContent($this->getLayout()->createBlock('laybuy/adminhtml_orders'))
            ->renderLayout();
    }

    /**
     * Ajax callback for grid actions
     */
    public function gridAction() {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('laybuy/adminhtml_orders_grid')->toHtml()
        );
    }
	
}
