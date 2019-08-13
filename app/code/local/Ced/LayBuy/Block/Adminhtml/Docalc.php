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
class Ced_LayBuy_Block_Adminhtml_Docalc extends Mage_Adminhtml_Block_Abstract
{
    protected function _toHtml()
    {
        $header = '<html>';
		$header .= '<head>';
		$header .= '<link media="all" href="'.Mage::getBaseUrl('skin').'adminhtml/default/default/reset.css" type="text/css" rel="stylesheet">';
		$header .= '<link media="all" href="'.Mage::getBaseUrl('skin').'adminhtml/default/default/boxes.css" type="text/css" rel="stylesheet">';
		$header .= '</head>';
		$header .= '<body style="font: 12px/1.5em; background: none repeat scroll 0 0 rgba(0, 0, 0, 0);">';
		$header .= '<div class="grid">';
		$header .= '<div class="hor-scroll">';
		$html = '<table cellspacing="0" class="data">';
		$html .= '<colgroup>
				<col width="175">
				<col width="183">
				<col width="98">
			  </colgroup>';
		 	
		 $tod=time();
		 
		 $isLeap = 0;
		 $isLeap = Date('L',$tod);
		 if($isLeap)
			$dim=array(31,31,29,31,30,31,30,31,31,30,31,30,31);
		 else
			$dim=array(31,31,28,31,30,31,30,31,31,30,31,30,31);
		 
		 $day=Date('d',$tod);
		 $mth=Date('m',$tod);
		 $yr=Date('Y',$tod);
		 $mnth=$this->getRequest()->getParam('mnth');
		 $hght=150 / (2 + $mnth);
		 $html .= '<thead><tr class="headings"><th class=" no-link" style="text-align: center; font-size: 0.7em; padding-bottom: 4px; padding-top: 4px;"><span class="nobr">Payment</span></th><th class=" no-link" style="text-align: center; font-size: 0.7em; padding-bottom: 4px; padding-top: 4px;"><span class="nobr">Due Date</span></th><th class=" no-link" style="text-align: center; font-size: 0.7em; padding-bottom: 4px; padding-top: 4px;"><span class="nobr">Amount</span></th></tr></thead>';
		 $init=$this->getRequest()->getParam('init');
		 $amt=$this->getRequest()->getParam('amt');
		 $currency = $this->getRequest()->getParam('currency');
		 $dep=$amt*$init/100;
		 $rest=number_format(($amt-$dep)/$mnth,2,'.','');
		 $dep=number_format($amt - $rest * $mnth,2,'.','');
		 $html .= '<tbody><tr class="even" ><td style="text-align: center;">DownPayment</td><td style="text-align: center;">Today</td><td style="text-align:right">'.Mage::app()->getLocale()->currency($currency)->toCurrency($dep).'</td></tr>';
		 for ($e=1; $e<=$mnth; $e++) {
			if (++$mth>12) {
			  $mth='01';
			  $yr++;
			}
			$m=1+$mth-1;
			$d=min($day,$dim[$m]);
			$even = '';
			if($e%2==0)
				$even = ' class="even"';
			$date = '';
			$date = $d.'-'.$mth.'-'.$yr;
			$date = Mage::helper('core')->formatDate($date, Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true);
			if($e==1){
				$first_payment_due = $date;
			}
			$html .= '<tr'.$even.' ><td style="text-align: center;">'.$e.'</td><td style="text-align: center;">'.$date.'</td><td style="text-align:right">'.Mage::app()->getLocale()->currency($currency)->toCurrency($rest).'</td></tr>';
		 }
		$html .= '</tbody>';
		$html .= '</table>';
		$footer = '</div>';
		$footer .= '</div>';
		$footer .= '</body>';
		$footer .= '</html>';
		if($this->getRequest()->getParam('html')){
			return $header.$html.$footer;
		}else{
			return $dep.'~'.$rest.'~'.$first_payment_due.'~'.$date.'~'.$html;
		}
    }
}
