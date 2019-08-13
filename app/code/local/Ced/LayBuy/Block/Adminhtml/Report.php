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
 * Block for LayBuy report
 */
class Ced_LayBuy_Block_Adminhtml_Report extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_report';
    $this->_blockGroup = 'laybuy';
    $this->_headerText = Mage::helper('laybuy')->__('Lay-Buy Instalment Reports');
    parent::__construct();
	$this->_removeButton('add');
        $message = Mage::helper('laybuy')->__('Connecting to Lay-Buy server to fetch transaction updates. Are you sure you want to proceed?');
        $this->_addButton('fetch', array(
            'label'   => Mage::helper('laybuy')->__('Fetch Updates'),
            'onclick' => "confirmSetLocation('{$message}', '{$this->getUrl('*/*/fetch',array('_secure' => true))}')",
            'class'   => 'task'
        ));
  }
}