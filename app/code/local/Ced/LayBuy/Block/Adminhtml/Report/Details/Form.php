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
class Ced_LayBuy_Block_Adminhtml_Report_Details_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Prepare read-only data and group it by fieldsets
     * @return Mage_Paypal_Block_Adminhtml_Settlement_Details_Form
     */
    protected function _prepareForm()
    {
        $model = Mage::registry('current_laybuy_transaction');
		/* print_r($model->getData());die; */
        /* @var $model Mage_Paypal_Model_Report_Settlement_Row */
        $settlement = Mage::getSingleton('laybuy/report');
        /* @var $settlement Mage_Paypal_Model_Report_Settlement */
		$statuses = Mage::helper('laybuy')->getStatuses();
		$status = $statuses[$model->getData('status')];
        $fieldsets = array(
            'reference_fieldset' => array(
                'fields' => array( 
					'paypal_profile_id' => array('label' => $settlement->getFieldLabel('paypal_profile_id'),'type'	=> 'label',),
					'laybuy_ref_no' => array('label' => $settlement->getFieldLabel('laybuy_ref_no'),'type'	=> 'label',),
                    'order_id' => array('label' => $settlement->getFieldLabel('order_id'),'type'	=> 'label',),
					
                ),
                'legend' => Mage::helper('laybuy')->__('Reference Information')
            ),

            'transaction_fieldset' => array(
                'fields' => array(
					'status' => array(
                        'label' => $settlement->getFieldLabel('status'),
                        'value' => $status,
						'type'	=> 'label',
                    ),
                    'amount' => array(
                        'label' => $settlement->getFieldLabel('amount'),
                        'value' => Mage::app()->getLocale()
                                       ->currency($model->getData('currency'))
									   ->toCurrency($model->getData('amount')),
						'type'	=> 'label',
                    ),
					'downpayment' => array(
                        'label' => $settlement->getFieldLabel('downpayment'),
                        'value' =>$model->getData('downpayment'),
						'type'	=> 'label',
                    ),
					'months' => array(
                        'label' => $settlement->getFieldLabel('months'),
                        'value' => $model->getData('months'),
						'type'	=> 'label',
                    ),
					'downpayment_amount' => array(
                        'label' => $settlement->getFieldLabel('downpayment_amount'),
                        'value' => Mage::app()->getLocale()
                                       ->currency($model->getData('currency'))
									   ->toCurrency($model->getData('downpayment_amount')),
						'type'	=> 'label',
                    ),
					'payment_amounts' => array(
                        'label' => $settlement->getFieldLabel('payment_amounts'),
                        'value' => Mage::app()->getLocale()
                                       ->currency($model->getData('currency'))
									   ->toCurrency($model->getData('payment_amounts')),
						'type'	=> 'label',
                    ),
					'first_payment_due' => array(
                        'label' => $settlement->getFieldLabel('first_payment_due'),
                        'value' => $this->helper('core')->formatDate($model->getData('first_payment_due'), Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true),
						'type'	=> 'label',
					),
					'last_payment_due' => array(
                        'label' => $settlement->getFieldLabel('last_payment_due'),
                        'value' => $this->helper('core')->formatDate($model->getData('last_payment_due'), Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true),
						'type'	=> 'label',
					),
					'report' => array(
                        'label' => $settlement->getFieldLabel('report'),
                        'value' => '',
						'type'	=> 'label',
						'after_element_html' => $model->getData('report'),
					),
					
                    
                ),
                'legend' => Mage::helper('laybuy')->__('Payment Plan')
            ),

			'customer_fieldset' => array(
                'fields' => array(
                    'firstname' => array(
                        'label' => $settlement->getFieldLabel('firstname'),
                        'value' => $model->getData('firstname'),
						'type'	=> 'label',
                    ),
					'lastname' => array(
                        'label' => $settlement->getFieldLabel('lastname'),
                        'value' => $model->getData('lastname'),
						'type'	=> 'label',
                    ),
					'email' => array(
                        'label' => $settlement->getFieldLabel('email'),
                        'value' => $model->getData('email'),
						'type'	=> 'link',
						'href' => 'mailto:'.$model->getData('email'),
					),
					'address' => array(
                        'label' => $settlement->getFieldLabel('address'),
                        'value' => $model->getData('address'),
						'type'	=> 'label',
                    ),
					'suburb' => array(
                        'label' => $settlement->getFieldLabel('suburb'),
                        'value' => $model->getData('suburb'),
						'type'	=> 'label',
                    ),
					'state' => array(
                        'label' => $settlement->getFieldLabel('state'),
                        'value' => $model->getData('state'),
						'type'	=> 'label',
                    ),
					'country' => array(
                        'label' => $settlement->getFieldLabel('country'),
                        'value' => $model->getData('country'),
						'type'	=> 'label',
                    ),
					'postcode' => array(
                        'label' => $settlement->getFieldLabel('postcode'),
                        'value' => $model->getData('postcode'),
						'type'	=> 'label',
                    ),
                ),
                'legend' => Mage::helper('laybuy')->__('Customer Information')
            ),
            /* 'fee_fieldset' => array(
                'fields' => array(
                    'fee_debit_or_credit' => array(
                        'label' => $settlement->getFieldLabel('fee_debit_or_credit'),
                        'value' => $model->getDebitCreditText($model->getData('fee_debit_or_credit'))
                    ),
                    'fee_amount' => array(
                        'label' => $settlement->getFieldLabel('fee_amount'),
                        'value' => Mage::app()->getLocale()
                                       ->currency($model->getData('fee_currency'))
                                       ->toCurrency($model->getData('fee_amount'))
                    ),
                ),
                'legend' => Mage::helper('laybuy')->__('Lay-Buy Fee Information')
            ), */
        );

        $form = new Varien_Data_Form();
        foreach ($fieldsets as $key => $data) {
            $fieldset = $form->addFieldset($key, array('legend' => $data['legend']));
            foreach ($data['fields'] as $id => $info) {
				if($info['type']=='link'){
					$fieldset->addField($id, $info['type'], array(
						'name'  => $id,
						'label' => $info['label'],
						'title' => $info['label'],
						'href' => $info['href'],
						'value' => isset($info['value']) ? $info['value'] : $model->getData($id),
						'after_element_html' => isset($info['after_element_html']) ? $info['after_element_html'] : '',
					));
				}else{
					$fieldset->addField($id, $info['type'], array(
						'name'  => $id,
						'label' => $info['label'],
						'title' => $info['label'],
						'value' => isset($info['value']) ? $info['value'] : $model->getData($id),
						'after_element_html' => isset($info['after_element_html']) ? $info['after_element_html'] : '',
					));
				}
            }
        }
        $this->setForm($form);
        return parent::_prepareForm();
    }
}