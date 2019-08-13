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
        $row = Mage::getModel('laybuy/report')->load($rowId);
        if (!$row->getId()) {
            $this->_redirect('*/*/');
            return;
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
		/* $tomorrow  = mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"));
		echo date('Y-m-d h:i:s',$tomorrow);
		echo "<br/>";
		$nextmonth = mktime(0, 0, 0, date("m")+1, date("d"),   date("Y"));
		echo date('Y-m-d h:i:s',$nextmonth);
		echo "<br/>";
		$nextyear  = mktime(0, 0, 0, date("m"),   date("d"),   date("Y")+1);
		echo date('Y-m-d h:i:s',$nextyear);
		die; */
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
        $this->_redirect('*/*/index');
    }
	
	/**
     * Edit transaction details action
     */
    public function editAction()
    {
        $rowId = $this->getRequest()->getParam('id');
        $row = Mage::getModel('laybuy/report')->load($rowId);
        if (!$row->getId()) {
            $this->_redirect('*/*/');
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
				Mage::helper('laybuy')->__("Failed to modify Plan")
			);
			Mage::logException($e);
		}
		
		$this->_redirect('*/*/details',array('id'=>$rowId));
		
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
		$this->_redirect('*/*/details',array('id'=>$id));
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
			echo $header.$html.$footer;
		}else{
			echo $dep.'~'.$rest.'~'.$first_payment_due.'~'.$date.'~'.$html;
		}
	}
	
}
