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
 * Revise Instalment Plan 
 *
 * @category    Ced
 * @package     Ced_LayBuy
 * @author 		Asheesh Singh<asheeshsingh@cedcoss.com>
 */
class Ced_LayBuy_Block_Adminhtml_Report_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * Block construction
     * Initialize titles, buttons
     */
    public function __construct()
    {
        parent::__construct();
        $this->_controller = '';
        $this->_headerText = Mage::helper('laybuy')->__('Edit Transaction Details');
        $this->_removeButton('delete')
			 ->_removeButton('back')
			 ->_removeButton('save');
		$message = Mage::helper('laybuy')->__('Are you sure to cancel this transaction?');
		$model = Mage::registry('current_laybuy_transaction_edit');
		$this->_addButton('back', array(
		
			'label'    => Mage::helper('laybuy')->__('Back'),
			
			'onclick'  => "setLocation('{$this->getUrl('*/*/details',array('_secure' => true,'id'=>$this->getRequest()->getParam('id')))}')",
			
			'class'	   => 'back',
		));
		$buttonLabel = Mage::helper('laybuy')->__('Save and Send Email to Buyer');
		if($model->getStatus() == -2) {
			$revised = Mage::getModel('laybuy/revise')->getCollection()
							->addFieldToFilter('transaction_id',array('eq'=>$model->getId()))
							->addFieldToFilter('type',array('eq'=>'new'))->getLastItem()->load();
			if($revised && $revised->getId()){
				$model = $revised;
				if(Mage::app()->getRequest()->getParam('reviseagain')) {
					
				} else {
					$buttonLabel = Mage::helper('laybuy')->__('Resend Email to Buyer');
				}
			}
		}
		$this->_addButton('save', array(
			
				'label'    => $buttonLabel,
				
				'onclick'  => "editForm.submit()",
				
				'class'	   => 'save',
			));
    }

    /**
     * Initialize form
     * @return Mage_LayBuy_Block_Adminhtml_Settlement_Details
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setChild('form', $this->getLayout()->createBlock('laybuy/adminhtml_report_edit_form'));
        return $this;
    }
}
