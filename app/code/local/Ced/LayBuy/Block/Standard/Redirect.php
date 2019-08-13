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
class Ced_LayBuy_Block_Standard_Redirect extends Mage_Core_Block_Abstract
{

	protected function _toHtml()
    {
        $helper = Mage::helper('laybuy');
		$chelper = Mage::helper('laybuy/config');
		$redirectURL = $chelper->getSubmitUrl();
		$session = Mage::getSingleton('checkout/session');
		$data =  $chelper->extractAndPrepareRequiredValueForFormFields($session);
		
		if ($token = $helper->postToLaybuy($redirectURL,$chelper->getStandardCheckoutFormFields($data))) {
			$session->setLayBuyToken($token);
			$redirectURL .= '?TOKEN='.$token;
			$html = '<html><body>';
			$html.= $this->__('You will be redirected to the PayPal website in a few seconds.');
			$html.= '<br><input type="button" onClick="window.location=\''.$redirectURL.'\' " value="'.$this->__('Click here if you are not redirected within 10 seconds...').'" />';
			$html.= '<script type="text/javascript">setTimeout(\'window.location="'.$redirectURL.'"\',1000);</script>';
			$html.= '</body></html>';
		}else{
			$session->addError($helper->__('Order # - '.$data ["CUSTOM"].' successfull but payment not completed. Please inform store owner.'));
			$html = Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getBaseUrl().'checkout/cart/index');
			/*$html = '<html><body>';
			$html.= $this->__('You will be redirected to the PayPal website in a few seconds.');
			$html.= '<br><input type="button" onClick="window.location=window.location;" value="'.$this->__('Click here if you are not redirected within 10 seconds...').'" />';
			$html.= '<script type="text/javascript">setTimeout("window.location=window.location;",1000);</script>';
			$html.= '</body></html>';*/
		}
		return $html;
    }
}
