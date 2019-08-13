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
 * Block for LayBuy report grid
 */
class Ced_LayBuy_Block_Adminhtml_Report_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct()
	{
		
		parent::__construct();
		$this->setId('laybuyReportGrid');
		$this->setDefaultSort('created_at');
		$this->setDefaultDir('DESC');
		$this->setSaveParametersInSession(true);
	}

	protected function _prepareCollection()
	{
		$collection = Mage::getModel('laybuy/report')->getCollection();
		
		$this->setCollection($collection);
		return parent::_prepareCollection();
	}
	
	protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

	protected function _prepareColumns()
	{
		$this->addColumn('created_at', array(
			'header'        => Mage::helper('laybuy')->__('Created At'),
			'align'         => 'left',
			'filter_index'  => 'created_at',
			'index'         => 'created_at',
			'type'          => 'datetime',
		));
		
		$this->addColumn('order_id', array(
			'header'        => Mage::helper('laybuy')->__('Order#'),
			'align'         => 'right',
			'filter_index'  => 'order_id',
			'index'         => 'order_id',
			'type'          => 'number',
			'renderer'  => 'Ced_LayBuy_Block_Adminhtml_Report_Renderer_Order',
		));
		
		$store = $this->_getStore();
		$this->addColumn('amount', array(
			'header'        => Mage::helper('laybuy')->__('Amount'),
			'align'         => 'left',
			'filter_index'  => 'amount',
			'index'         => 'amount',
			'type'          => 'price',
			'currency_code' => $store->getBaseCurrency()->getCode(),
		));
		
		$this->addColumn('downpayment', array(
			'header'        => Mage::helper('laybuy')->__('Down Payment %'),
			'align'         => 'right',
			'filter_index'  => 'downpayment',
			'index'         => 'downpayment',
			'type'          => 'range',
		));
		
		$this->addColumn('months', array(
			'header'        => Mage::helper('laybuy')->__('Months'),
			'align'         => 'left',
			'filter_index'  => 'months',
			'index'         => 'months',
			'type'          => 'range',
		));
		
		$this->addColumn('downpayment_amount', array(
			'header'        => Mage::helper('laybuy')->__('Down Payment Amount'),
			'align'         => 'right',
			'filter_index'  => 'downpayment_amount',
			'index'         => 'downpayment_amount',
			'type'          => 'price',
			'currency_code' => $store->getBaseCurrency()->getCode(),
		));
		
		$this->addColumn('payment_amounts', array(
			'header'        => Mage::helper('laybuy')->__('Payment Amounts'),
			'align'         => 'left',
			'filter_index'  => 'payment_amounts',
			'index'         => 'payment_amounts',
			'type'          => 'price',
			'currency_code' => $store->getBaseCurrency()->getCode(),
		));
		
		$this->addColumn('first_payment_due', array(
			'header'        => Mage::helper('laybuy')->__('First Payment Due'),
			'align'         => 'right',
			'filter_index'  => 'first_payment_due',
			'index'         => 'first_payment_due',
			'type'          => 'datetime',
		));
		
		$this->addColumn('last_payment_due', array(
			'header'        => Mage::helper('laybuy')->__('Last Payment Due'),
			'align'         => 'left',
			'filter_index'  => 'last_payment_due',
			'index'         => 'last_payment_due',
			'type'          => 'datetime',
		));
		
		/* $this->addColumn('email', array(
			'header'        => Mage::helper('laybuy')->__('Email'),
			'align'         => 'right',
			'width'			=> '75',
			'filter_index'  => 'email',
			'index'         => 'email',
			'type'          => 'text',
			'renderer'  => 'Ced_LayBuy_Block_Adminhtml_Report_Renderer_Email',
		)); */
		/* $this->addColumn('report', array(
		$this->addColumn('status', array(
			'header'        => Mage::helper('laybuy')->__('Status'),
			'align'         => 'right',
			'filter_index'  => 'status',
			'index'         => 'status',
			'type'      => 'options',
			'options'   => Mage::helper('laybuy')->getStatuses(),
		));
			
		$this->addExportType('*/*/exportCsv', Mage::helper('laybuy')->__('CSV'));
		$this->addExportType('*/*/exportXml', Mage::helper('laybuy')->__('XML'));
		  
		return parent::_prepareColumns();
	}

  public function getRowUrl($row)
  {
      return $this->getUrl('*/*/details', array('id' => $row->getId()));
  }
}