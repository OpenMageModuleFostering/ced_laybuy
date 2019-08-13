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
 * LayBuy Resource Report model
 */
 
class Ced_LayBuy_Model_Mysql4_Report extends Mage_Core_Model_Mysql4_Abstract{
	protected function _construct()
	{
		$this->_init('laybuy/report', 'id');
	}
	
	/**
     * Check if report with same account and report date already fetched
     *
     * @param Ced_LayBuy_Model_Report $report
     * @param string $orderId
     * @param string $reportDate
     * @return Mage_Paypal_Model_Resource_Report_Settlement
     */
    public function loadByOrderId(Ced_LayBuy_Model_Report $report, $order_id)
    {
        $adapter = $this->_getReadAdapter();
        $select  = $adapter->select()
            ->from($this->getMainTable())
            ->where("order_id = :order_id AND status!='-2' AND status!='2'");

        $data = $adapter->fetchRow($select, array(':order_id' => $order_id));
        if ($data) {
            $report->addData($data);
        }

        return $this;
    }		public function loadByLayBuyRefId(Ced_LayBuy_Model_Report $report, $laybuy_ref_no)    {        $adapter = $this->_getReadAdapter();        $select  = $adapter->select()            ->from($this->getMainTable())            ->where('laybuy_ref_no = :laybuy_ref_no');        $data = $adapter->fetchRow($select, array(':laybuy_ref_no' => $laybuy_ref_no));        if ($data) {            $report->addData($data);        }        return $this;    }
} 