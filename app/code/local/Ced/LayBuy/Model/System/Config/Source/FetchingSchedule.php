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
 * Source model for available instalment report fetching intervals
 */
class Ced_LayBuy_Model_System_Config_Source_FetchingSchedule
{
    public function toOptionArray()
    {
        return array (
            1 => Mage::helper('laybuy')->__("Daily"),
            3 => Mage::helper('laybuy')->__("Every 3 days"),
            7 => Mage::helper('laybuy')->__("Every 7 days"),
            10 => Mage::helper('laybuy')->__("Every 10 days"),
            14 => Mage::helper('laybuy')->__("Every 14 days"),
            30 => Mage::helper('laybuy')->__("Every 30 days"),
            40 => Mage::helper('laybuy')->__("Every 40 days"),
        );
    }
}
