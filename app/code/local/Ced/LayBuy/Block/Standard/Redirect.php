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
        $dcount = 0;
		$helper = Mage::helper('laybuy/config');
		$submitUrl = $helper->getSubmitUrl();

        $form = new Varien_Data_Form();
        $form->setAction($submitUrl)
            ->setId('laybuy_standard_checkout')
            ->setName('laybuy_standard_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);
		
		$session = Mage::getSingleton('checkout/session');
		$data =  $helper->extractAndPrepareRequiredValueForFormFields($session);
		
        foreach ($helper->getStandardCheckoutFormFields($data) as $field=>$value) {
            if(is_array($value)){
				foreach($value as $description){
					$form->addField($field.$dcount, 'hidden', array('name'=>$field, 'value'=>$description));
					$dcount++;
				}
			}else{
				$form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value));
			}
        }
        $submitButton = new Varien_Data_Form_Element_Submit(array(
            'value'    => $this->__('Click here if you are not redirected within 10 seconds...'),
        ));
        $submitButton->setId('laybuy_standard_payment');
        $form->addElement($submitButton);
        $html = '<html><body>';
        $html.= $this->__('You will be redirected to the PayPal website in a few seconds.');
        $html.= $form->toHtml();
        $html.= '<script type="text/javascript">document.getElementById("laybuy_standard_checkout").submit();</script>';
        $html.= '</body></html>';

        return $html;
    }
}
