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
 * LayBuy Instalment Reports Controller
 *
 * @category    Ced
 * @package     Ced_LayBuy
 * @author 		Asheesh Singh<asheeshsingh@cedcoss.com>
 */
class Ced_LayBuy_Adminhtml_ReportController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Grid action
     */
    public function indexAction()
    {
        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('laybuy/adminhtml_report'))
            ->renderLayout();
    }

    /**
     * Ajax callback for grid actions
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('laybuy/adminhtml_report_grid')->toHtml()
        );
    }

    /**
     * View transaction details action
     */
    public function detailsAction()
    {
        $rowId = $this->getRequest()->getParam('id');
		$fbr = $this->getRequest()->getParam('fbr',0);
        $row = Mage::getModel('laybuy/report')->load($rowId);
        if (!$row->getId()) {
            $this->_redirect('*/*/',array('_secure' => true));
            return;
        }
		$row->setReviseConfirmMessage('');
		if($fbr && Mage::helper('laybuy')->fetchBeforeRevise($row->getData('paypal_profile_id'))) {
			$row = Mage::getModel('laybuy/report')->load($row->getId());
		} elseif($fbr) {
			$row->setReviseConfirmMessage(Mage::helper('laybuy')->__("Are you sure to Revise Instalment Plan because instalment reports are not up-to-date."));
		}
		Mage::register('current_laybuy_transaction', $row);
		$this->_initAction()
			->_title($this->__('View Transaction'))
			->_addContent($this->getLayout()->createBlock('laybuy/adminhtml_report_details', 'laybuyInstalmentDetails'))
			->renderLayout();
    }

    /**
     * Forced fetch reports action
     */
    public function fetchAction()
    {
		try {
            $reports = Mage::getModel('laybuy/report');
            /* @var $reports Mage_laybuy_Model_Report_Instalment */
            $credentials = $reports->getApiCredentials();
            if (empty($credentials)) {
                Mage::throwException(Mage::helper('laybuy')->__('Nothing to fetch because of an empty configuration.'));
            }
            foreach ($credentials as $config) {
                try {
                    $fetched = $reports->fetchAndSave($config);
					if($fetched){
						$this->_getSession()->addSuccess(
							Mage::helper('laybuy')->__("Fetched %s report rows from '%s'.", $fetched, $config['hostname'])
						);
					}else{
						$this->_getSession()->addSuccess(
							Mage::helper('laybuy')->__("There is no new Transaction.")
						);
					}
                } catch (Exception $e) {
                    $this->_getSession()->addError(
                        Mage::helper('laybuy')->__("Failed to fetch reports from '%s'.%s", $config['hostname'],$e->getMessage())
                    );
                    Mage::logException($e);
                }
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
        }
        $this->_redirect('*/*/index',array('_secure' => true));
    }
	
	/**
     * Edit transaction details action
     */
    public function editAction()
    {
        $rowId = $this->getRequest()->getParam('id');
        $row = Mage::getModel('laybuy/report')->load($rowId);
        if (!$row->getId()) {
            $this->_redirect('*/*/',array('_secure' => true));
            return;
        }
        Mage::register('current_laybuy_transaction_edit', $row);
        $this->_initAction()
            ->_title($this->__('Edit Transaction'))
            ->_addContent($this->getLayout()->createBlock('laybuy/adminhtml_report_edit', 'laybuyInstalmentEdit'))
            ->renderLayout();
    }
	
	/**
     * Save transaction details action
     */
    public function saveAction()
    {
		if($this->getRequest()->getParam('is_revised')){
			$this->_forward('resend');
			return;
		}
        $rowId = $this->getRequest()->getParam('id');
        $data = $this->getRequest()->getParams();
		/* print_r($data);die; */
		$reportModel = Mage::getModel('laybuy/report')->load($rowId);
		
		$temp = array();
		$temp['original']= $temp['new']= $reportModel->getData();
		$temp['original']['transaction_id'] = $temp['new']['transaction_id'] = $reportModel->getId();
		$temp['original']['type'] = 'original';
		$temp['new']['type'] = 'new';
		$temp['new']['amount'] = $data['amount'];
		$temp['new']['months'] = $data['months'];
		$temp['new']['first_payment_due'] = $data['first_payment_due'];
		$temp['new']['last_payment_due'] = $data['last_payment_due'];
		$temp['new']['months'] = $data['months'];
		$temp['new']['email'] = $data['email'];
		$temp['new']['downpayment'] = $data['dp_amount'];
		$temp['new']['downpayment_amount'] = $data['downpayment_amount'];
		$temp['new']['payment_amounts'] = $data['payment_amounts'];
		$temp['new']['created_at']	= date('Y-m-d h:i:s',time());
		$temp['new']['report'] = $data['report'];
		$temp['new']['payment_type'] = $data['pp'];/* if($data['pp']){ Lay-Buy Payment }else{ Buy-Now Payment } */
		$temp['new']['status'] = '-2';
		try{
			/* print_r($temp['new']);die; */
			$collection = Mage::getModel('laybuy/revise')->getCollection()->addFieldToFilter('transaction_id',array('eq'=>$temp['new']['id']));
			unset($temp['original']['id']);
			unset($temp['new']['id']);
			if(count($collection)==2){
				foreach($collection as $request){
					if($request->getType()=='original'){
						$request->addData($temp['original'])->setId($request->getId())->save();
					}
					if($request->getType()=='new'){
						$revise = $request->addData($temp['new'])->setId($request->getId())->save();
					}
				}
			}else{
				$reviseModelbkp = Mage::getModel('laybuy/revise')->setData($temp['original'])->save();
				$revise = $reviseModel = Mage::getModel('laybuy/revise')->setData($temp['new'])->save();
			}
			
			if(Mage::helper('laybuy')->revisePlan($revise)){

				$reportModel->setStatus(-2)->save();
				$this->_getSession()->addSuccess(
					Mage::helper('laybuy')->__("Request was saved and email sent to %s for order#%s",$revise->getEmail(),$revise->getOrderId())
				);
			}else{
				$this->_getSession()->addError(
					Mage::helper('laybuy')->__("Failed to modify Plan")
				);
			}
		}catch(Exception $e){
			$this->_getSession()->addError(
				Mage::helper('laybuy')->__($e->getMessage())
			);
			Mage::logException($e);
		}
		
		$this->_redirect('*/*/details',array('_secure' => true,'id'=>$rowId));
		
    }
	
	public function resendAction() {
		$revise = Mage::getModel('laybuy/revise')->load($this->getRequest()->getParam('is_revised',0));
		$rowId = $this->getRequest()->getParam('id');
		if($revise && $revise->getId()) {
			try {
				if(Mage::helper('laybuy')->revisePlan($revise)){

					$this->_getSession()->addSuccess(
						Mage::helper('laybuy')->__("Email re-sent to %s for order#%s",$revise->getEmail(),$revise->getOrderId())
					);
				} else {
					$this->_getSession()->addError(
						Mage::helper('laybuy')->__("Failed to re-send email")
					);
				}
			} catch (Exception $e) {
				$this->_getSession()->addError(
						Mage::helper('laybuy')->__($e->getMessage())
					);
				Mage::logException($e);
			}
		}
		$this->_redirect('*/*/details',array('_secure' => true,'id'=>$rowId));
	}
	
	/**
     * Forced to cancel transaction action
     */
	public function cancelAction(){
		$id = $this->getRequest()->getParam('id');
		$model = Mage::getModel('laybuy/report')->load($id);
		Mage::log('Action cancel transaction called',null,'laybuy_success.log');
		try{
			/* $model->setReport($newStr)->setStatus(-1)->save(); */
			if(Mage::helper('laybuy')->cancelTransaction($model)){
				$this->_getSession()->addSuccess(
					Mage::helper('laybuy')->__("Transaction was cancelled successfully.")
				);
			}else{
				$this->_getSession()->addError(
					'Cancel request was unsuccessful.Please try again!!'
				);
			}
		}catch (Exception $e) {
			$this->_getSession()->addError(
				$e->getMessage()
			);
			Mage::logException($e);
		}
		$this->_redirect('*/*/details',array('_secure' => true,'id'=>$id));
	}

    /**
     * Initialize titles, navigation
     * @return Mage_laybuy_Adminhtml_laybuy_ReportsController
     */
    protected function _initAction()
    {
        $this->_title($this->__('Reports'))->_title($this->__('Sales'))->_title(Mage::helper('laybuy')->__('Lay-Buy Instalment Reports'));
        $this->loadLayout()
            ->_setActiveMenu('report/sales')
            ->_addBreadcrumb($this->__('Reports'), Mage::helper('laybuy')->__('Reports'))
            ->_addBreadcrumb($this->__('Sales'), Mage::helper('laybuy')->__('Sales'))
            ->_addBreadcrumb(Mage::helper('laybuy')->__('Lay-Buy Instalment Reports'), Mage::helper('laybuy')->__('Lay-Buy Instalment Reports'));
        return $this;
    }

    /**
     * ACL check
     * @return bool
     */
    protected function _isAllowed()
    {
        switch ($this->getRequest()->getActionName()) {
            case 'index':
            case 'details':
                return Mage::getSingleton('admin/session')->isAllowed('report/salesroot/laybuyreport/view');
                break;
            case 'fetch':
                return Mage::getSingleton('admin/session')->isAllowed('report/salesroot/laybuyreport/fetch');
                break;
			case 'edit':
			case 'save':
                return Mage::getSingleton('admin/session')->isAllowed('report/salesroot/laybuyreport/update');
                break;
			case 'cancel':
                return Mage::getSingleton('admin/session')->isAllowed('report/salesroot/laybuyreport/cancel');
                break;
            default:
                return Mage::getSingleton('admin/session')->isAllowed('report/salesroot/laybuyreport');
                break;
        }
    }
	
	 public function exportCsvAction()
    {
        $fileName   = 'Lay-BuyTransaction'.time().'.csv';
        $content    = $this->getLayout()->createBlock('laybuy/adminhtml_report_grid')
            ->getCsv();

        $this->_sendUploadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName   = 'Lay-BuyTransaction'.time().'.xml';
        $content    = $this->getLayout()->createBlock('laybuy/adminhtml_report_grid')
            ->getXml();

        $this->_sendUploadResponse($fileName, $content);
    }

    protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')
    {
        $response = $this->getResponse();
        $response->setHeader('HTTP/1.1 200 OK','');
        $response->setHeader('Pragma', 'public', true);
        $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
        $response->setHeader('Content-Disposition', 'attachment; filename='.$fileName);
        $response->setHeader('Last-Modified', date('r'));
        $response->setHeader('Accept-Ranges', 'bytes');
        $response->setHeader('Content-Length', strlen($content));
        $response->setHeader('Content-type', $contentType);
        $response->setBody($content);
        $response->sendResponse();
        die;
    }
	
	public function docalcAction(){
		$this->getResponse()->setBody(
			$this->getLayout()->createBlock('laybuy/adminhtml_docalc')->toHtml()
		);
	}
	
	public function ordersAction() {
		$this->_title($this->__('Sales'))->_title(Mage::helper('laybuy')->__('Lay-Buy Orders'));
        $this->loadLayout()
            ->_setActiveMenu('sales/laybuyorders')
            ->_addBreadcrumb($this->__('Sales'), Mage::helper('laybuy')->__('Sales'))
            ->_addBreadcrumb(Mage::helper('laybuy')->__('Lay-Buy Orders'), Mage::helper('laybuy')->__('Lay-Buy Orders'))
			->_addContent($this->getLayout()->createBlock('laybuy/adminhtml_orders'))
            ->renderLayout();
	}
	
}
