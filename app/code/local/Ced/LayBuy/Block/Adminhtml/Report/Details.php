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
 * Settlement reports transaction details
 *
 * @category    Ced
 * @package     Ced_LayBuy
 * @author 		Asheesh Singh<asheeshsingh@cedcoss.com>
 */
class Ced_LayBuy_Block_Adminhtml_Report_Details extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * Block construction
     * Initialize titles, buttons
     */
    public function __construct()
    {
        parent::__construct();
        $this->_controller = '';
        $this->_headerText = Mage::helper('laybuy')->__('View Transaction Details');
        $this->_removeButton('reset')
            ->_removeButton('delete')
            ->_removeButton('save');
		$message = Mage::helper('laybuy')->__("Are you sure to cancel this transaction? Because it will cancel the buyer order and recurring profile");
		$model = Mage::registry('current_laybuy_transaction');
		if($model->getStatus()!=-1 && $model->getStatus()!=-2 && $model->getStatus()!=2){
			$this->_addButton('delete', array(

				'label'   => Mage::helper('laybuy')->__('Cancel Transaction'),

				'onclick' => "confirmSetLocation('{$message}', '{$this->getUrl('*/*/cancel',array('id'=>$this->getRequest()->getParam('id')))}')",

				'class'	  => 'delete',
			));
			
			$this->_addButton('edit', array(
			
                'label'    => Mage::helper('laybuy')->__('Revise Instalment Plan'),
				
				'onclick'  => "setLocation('{$this->getUrl('*/*/edit',array('id'=>$this->getRequest()->getParam('id')))}')",
				
				'class'	   => 'add',
            ));
		}
    }

    /**
     * Initialize form
     * @return Mage_LayBuy_Block_Adminhtml_Settlement_Details
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setChild('form', $this->getLayout()->createBlock('laybuy/adminhtml_report_details_form'));
        return $this;
    }
}
