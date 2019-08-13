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
 * Revise Instalment Plan Form
 *
 * @category    Ced
 * @package     Ced_LayBuy
 * @author 		Asheesh Singh<asheeshsingh@cedcoss.com>
 */
class Ced_LayBuy_Block_Adminhtml_Report_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Prepare read-only data and group it by fieldsets
     * @return Mage_Paypal_Block_Adminhtml_Settlement_Details_Form
     */
    protected function _prepareForm()
    {
        $calcUrl = $this->getUrl('*/*/docalc');/*'http://lay-buys.com/gateway/docalc.php'*/;
		$model = Mage::registry('current_laybuy_transaction_edit');
		/* print_r($model->getData());die; */
        /* @var $model Mage_Paypal_Model_Report_Settlement_Row */
        $settlement = Mage::getSingleton('laybuy/report');
        /* @var $settlement Mage_Paypal_Model_Report_Settlement */
		$order_id = $model->getData('order_id');
		$order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
		$storeId = $order->getStoreId();
		$newAmount = $model->getData('amount') - ($model->getData('downpayment_amount') + (((int)$model->getTransaction() - 2) * $model->getData('payment_amounts')));
        $fieldsets = array(
            'reference_fieldset' => array(
                'fields' => array( 
					'mid' => array(
									'label' => $settlement->getFieldLabel('mid'),
									'type'	=> 'hidden',
									'value' => Mage::getStoreConfig('payment/laybuy/membership_number',$storeId),
									),
					'paypal_profile_id' => array(
											'label' => $settlement->getFieldLabel('paypal_profile_id'),
											'type'	=> 'text',
											'readonly' => true,
											'after_element_html' => '<p class="note"><span>Readonly attribute</span></p>',
										   ),
					'laybuy_ref_no' => array(
											'label' => $settlement->getFieldLabel('laybuy_ref_no'),
											'type'	=> 'text',
											'readonly' => true,
											'after_element_html' => '<p class="note"><span>Readonly attribute</span></p>',
										),
                    'order_id' => array(
											'label' => $settlement->getFieldLabel('order_id'),
											'type'	=> 'text',
											'readonly' => true,
											'after_element_html' => '<p class="note"><span>Readonly attribute</span></p>',
											),
					
                ),
                'legend' => Mage::helper('laybuy')->__('Reference Information')
            ),

            'transaction_fieldset' => array(
                'fields' => array(
                    'amount' => array(
                        'label' => $settlement->getFieldLabel('total_amount'),
                        'value' => number_format($newAmount,2,'.',','),
						'type'	=> 'hidden',
                    ),
					'pending_amount' => array(
                        'label' => $settlement->getFieldLabel('total_amount'),
                        'value' => Mage::app()->getLocale()
                                       ->currency($model->getData('currency'))
									   ->toCurrency($newAmount),
						'type'	=> 'label',
                    ),
					'lay-buy' => array(
                        'label' => $settlement->getFieldLabel('Payment Type:'),
                        'value' => 1,
						'type'	=> 'radio',
						'onclick'=> 'methodChange(1)',
						'checked' => 'checked',
						'after_element_html' => '<label for="lay-buy" class="inline">Lay-Buy</label>',
                    ),
					'buy-now' => array(
                        'label' => $settlement->getFieldLabel(''),
                        'value' => 0,
						'type'	=> 'radio',
						'onclick'=> 'methodChange(0)',
						'after_element_html' => '<label for="buy-now" class="inline">Buy-Now</label>',
                    ),
					/* 'pp1' => array(
                        'label' => $settlement->getFieldLabel(''),
                        'value' => 1,
						'type'	=> 'radios',
						'onchange'=> 'methodChange()',
						'values' => array(
										array('value'=>0,'label'=>'Buy-Now'),
										array('value'=>1,'label'=>'Lay-Buy'),
								   ),
                    ), */
					'pp' => array(
                        'label' => $settlement->getFieldLabel(''),
                        'value' => 1,
						'type'	=> 'hidden',
                    ),
					'pplan' => array(
                        'label' => $settlement->getFieldLabel(''),
                        'value' => 1,
						'type'	=> 'hidden',
                    ),
					'currency' => array(
                        'label' => $settlement->getFieldLabel('currency'),
                        'value' =>$model->getData('currency'),
						'type'	=> 'hidden',
                    ),
					'dp_amount' => array(
                        'label' => $settlement->getFieldLabel('dp_amount'),
                        'value' => $model->getData('downpayment'),
						'type'	=> 'select',
						'dy'	=> 1,
						'onchange' => 'rcalc()',
						'values' => $settlement->getArray('dp_amount',$newAmount,$storeId),
                    ),
					'months' => array(
                        'label' => $settlement->getFieldLabel('months_to_pay'),
                        'value' => $model->getData('months'),
						'type'	=> 'select',
						'onchange' => 'rcalc()',
						'values' => $settlement->getArray('months',$newAmount,$storeId),                                               
                        ),
					'preview' => array(
                        'label' => $settlement->getFieldLabel('preview'),
                        'value' => '',
						'type'	=> 'label',
						'after_element_html' => '<iframe name="preview-tbl" id="preview-tbl" style="width:171%; height:157px; border:0; margin:0; overflow:hidden" marginheight="0" marginwidth="0" noscroll></iframe>',
					),
					
                    'firstname' => array(
                        'label' => $settlement->getFieldLabel('firstname'),
                        'value' => $model->getData('firstname'),
						'type'	=> 'hidden',
                    ),
					'lastname' => array(
                        'label' => $settlement->getFieldLabel('lastname'),
                        'value' => $model->getData('lastname'),
						'type'	=> 'hidden',
                    ),
					'email' => array(
                        'label' => $settlement->getFieldLabel('email'),
                        'value' => $model->getData('email'),
						'type'	=> 'text',
						'readonly' => true,
						'after_element_html' => '<p class="note"><span>Readonly attribute.</span></p>',
					),
					'address' => array(
                        'label' => $settlement->getFieldLabel('address'),
                        'value' => $model->getData('address'),
						'type'	=> 'hidden',
                    ),
					'suburb' => array(
                        'label' => $settlement->getFieldLabel('suburb'),
                        'value' => $model->getData('suburb'),
						'type'	=> 'hidden',
                    ),
					'state' => array(
                        'label' => $settlement->getFieldLabel('state'),
                        'value' => $model->getData('state'),
						'type'	=> 'hidden',
                    ),
					'country' => array(
                        'label' => $settlement->getFieldLabel('country'),
                        'value' => $model->getData('country'),
						'type'	=> 'hidden',
                    ),
					'postcode' => array(
                        'label' => $settlement->getFieldLabel('postcode'),
                        'value' => $model->getData('postcode'),
						'type'	=> 'hidden',
                    ),
					'downpayment_amount' => array(
                        'label' => $settlement->getFieldLabel('downpayment_amount'),
                        'value' => Mage::app()->getLocale()
                                       ->currency($model->getData('currency'))
									   ->toCurrency($model->getData('downpayment_amount')),
						'type'	=> 'hidden',
                    ),
					'payment_amounts' => array(
                        'label' => $settlement->getFieldLabel('payment_amounts'),
                        'value' => Mage::app()->getLocale()
                                       ->currency($model->getData('currency'))
									   ->toCurrency($model->getData('payment_amounts')),
						'type'	=> 'hidden',
                    ),
					'first_payment_due' => array(
                        'label' => $settlement->getFieldLabel('first_payment_due'),
                        'value' => $this->helper('core')->formatDate($model->getData('first_payment_due'), Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true),
						'type'	=> 'hidden',
					),
					'last_payment_due' => array(
                        'label' => $settlement->getFieldLabel('last_payment_due'),
                        'value' => $this->helper('core')->formatDate($model->getData('last_payment_due'), Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true),
						'type'	=> 'hidden',
					),
					'report' => array(
                        'label' => $settlement->getFieldLabel('report'),
						'value' => 'New Description',
						'type'	=> 'hidden',
					),
					
                ),
                'legend' => Mage::helper('laybuy')->__("Please Choose Buyer's New Payment Plan"),
			),
        );

        $form = new Varien_Data_Form();
		$submitUrl = $this->getUrl('*/*/save',array('id'=>$this->getRequest()->getParam('id')));
		$form->setAction($submitUrl)
			 ->setId('edit_form')
			 ->setName('laybuy_revise_plan')
			 ->setMethod('POST')
			 ->setUseContainer(true);
        foreach ($fieldsets as $key => $data) {
            $fieldset = $form->addFieldset($key, array('legend' => $data['legend']));
            foreach ($data['fields'] as $id => $info) {
				if($info['type']=='link'){
					$id = $fieldset->addField($id, $info['type'], array(
						'name'  => $id,
						'label' => $info['label'],
						'title' => $info['label'],
						'href' => $info['href'],
						'value' => isset($info['value']) ? $info['value'] : $model->getData($id),
						'after_element_html' => isset($info['after_element_html']) ? $info['after_element_html'] : '',
						'readonly' => isset($info['readonly'])?$info['readonly']:false,
						isset($info['values'])?'values':'' => isset($info['values'])?$info['values']:'',
						isset($info['onchange'])?'onchange':'' => isset($info['onchange'])?$info['onchange']:'',
						isset($info['onclick'])?'onclick':'' => isset($info['onclick'])?$info['onclick']:'',
						isset($info['checked'])?'checked':'' => isset($info['checked'])?$info['checked']:'',
						
					));
				}else{
					$id = $fieldset->addField($id, $info['type'], array(
						'name'  => $id,
						'label' => $info['label'],
						'title' => $info['label'],
						'value' => isset($info['value']) ? $info['value'] : $model->getData($id),
						'after_element_html' => isset($info['after_element_html']) ? $info['after_element_html'] : '',
						isset($info['readonly'])?'readonly':'' => isset($info['readonly'])?$info['readonly']:false,
						isset($info['values'])?'values':'' => isset($info['values'])?$info['values']:'',
						isset($info['onchange'])?'onchange':'' => isset($info['onchange'])?$info['onchange']:'',
						isset($info['onclick'])?'onclick':'' => isset($info['onclick'])?$info['onclick']:'',
						isset($info['checked'])?'checked':'' => isset($info['checked'])?$info['checked']:'',
					));
					
				}
				if(isset($info['dy']) && isset($info['onchange']) && $function = $info['onchange']){
					$id->setAfterElementHtml(
					   '<script type="text/javascript">
							  function '.$function.'{
								document.getElementById("lay-buy").checked = true;
								document.getElementById("loading-mask").show();
								var f = document.getElementById("preview-tbl");
							    f.src = "'.$calcUrl.'?currency="+document.laybuy_revise_plan.currency.value+"&amt="+document.laybuy_revise_plan.amount.value+"&init="+document.laybuy_revise_plan.dp_amount.value+"&mnth="+document.laybuy_revise_plan.months.value+"&rnd="+Math.random()+"&html=1";
								
								data = "'.$calcUrl.'?currency="+document.laybuy_revise_plan.currency.value+"&amt="+document.laybuy_revise_plan.amount.value+"&init="+document.laybuy_revise_plan.dp_amount.value+"&mnth="+document.laybuy_revise_plan.months.value+"&rnd="+Math.random();
								loadXMLDoc(data);
							  } 
							  setTimeout("'.$function.';",200);
							  function loadXMLDoc(url)
								{
									var xmlhttp;
									if (window.XMLHttpRequest)
									  {// code for IE7+, Firefox, Chrome, Opera, Safari
									  xmlhttp=new XMLHttpRequest();
									  }
									else
									  {// code for IE6, IE5
									  xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
									  }
									xmlhttp.onreadystatechange=function()
									  {
									  if (xmlhttp.readyState==4 && xmlhttp.status==200)
										{
											data = xmlhttp.responseText;
											data = data.split("~");	
											document.getElementById("downpayment_amount").value= data[0];
											document.getElementById("payment_amounts").value = data[1];
											document.getElementById("first_payment_due").value = data[2];
											document.getElementById("last_payment_due").value = data[3];
											document.getElementById("report").value= data[4];
											document.getElementById("loading-mask").hide();
										}
									  }
									xmlhttp.open("GET",url,true);
									xmlhttp.send();
								}
								function methodChange(value){
									/* alert(value); */
									document.getElementById("pp").value = value;
									document.getElementById("pplan").value = value;
									if(value){
										document.getElementById("buy-now").checked = false;
										document.getElementById("preview-tbl").parentNode.parentNode.show();
										document.getElementById("dp_amount").parentNode.parentNode.show();
										document.getElementById("months").parentNode.parentNode.show();
										document.getElementById("preview-tbl").parentNode.show();
										document.getElementById("dp_amount").parentNode.show();
										document.getElementById("months").parentNode.show();
									}else{
										document.getElementById("lay-buy").checked = false;
										document.getElementById("preview-tbl").parentNode.parentNode.hide();
										document.getElementById("dp_amount").parentNode.parentNode.hide();
										document.getElementById("months").parentNode.parentNode.hide();	
										document.getElementById("preview-tbl").parentNode.hide();
										document.getElementById("dp_amount").parentNode.hide();
										document.getElementById("months").parentNode.hide();
									}
								} 
						</script>'
					);
				}
			}
        }
        $this->setForm($form);
        return parent::_prepareForm();
    }
}