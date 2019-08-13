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
 * LayBuy Report model
 */
 
class Ced_LayBuy_Model_Report extends Mage_Core_Model_Abstract
{
	
	/**
     * Default LayBuy Api host
     * @var string
     */
    const REPORTS_HOSTNAME = "https://lay-buys.com/report/";

	/**
     * Assoc array event code => label
     *
     * @var array
     */
    protected static $_eventList = array();

	/**
     * Initialize resource model
     */
	protected function _construct()
	{
		$this->_init('laybuy/report');
	}

    /**
     * Goes to specified host/path and fetches reports from there.
     * Save reports to database.
     *
     * @param array $config Api credentials
     * @return int Number of report rows that were fetched and saved successfully
     */
    public function fetchAndSave($config)
    {
        $fetched = 0;
		$helper = Mage::helper('laybuy');	
		
		$listing = $helper->fetchFromLaybuy($config);
		
		foreach($listing as $orderId=>$reports){
			$status = $reports->status;
			$report = $reports->report;
			/* print_r($status);
			echo "<br/><br/><br/><br/>";
			print_r($report);die; */
			$model = array();
			$model = Mage::getModel('laybuy/report')->loadByLayBuyRefId($orderId);
			$orderId = $model->getOrderId();
			$profileId = $model->getData('paypal_profile_id');
			/* echo $orderId;die; */
			$newStr = '<div class="grid"><div class="hor-scroll"><table cellspacing=0 class="data"><thead><tr class="headings"><th colspan=2 class=" no-link" style="text-align: center;"><span class="nobr">Instalment</span></th><th class=" no-link" style="text-align: center;"><span class="nobr">Date</span></th><th class=" no-link" style="text-align: center;"><span class="nobr">PayPal Transaction ID</span></th><th class=" no-link" style="text-align: center;"><span class="nobr">Status</span></th></tr></thead>';
			$newStr .= '<colgroup>
							<col width="100">
							<col width="75">
							<col width="183">
							<col width="183">
							<col width="98">
						</colgroup>';	
			$months = (int)$model->getData('months');
			$report_log = print_r($report,true);
			$pending_flag = false;
			Mage::log('Fetched Report{{'.$model->getId().'}}Report{{'.$report_log.'}}',null,'laybuy_success.log');
			$nextPaymentStatus = 'Pending';
			foreach($report as $month=>$transaction){
				$transaction->paymentDate = date('Y-m-d h:i:s', strtotime(str_replace('/','-',$transaction->paymentDate)));
				$date = Mage::helper('core')->formatDate($transaction->paymentDate, Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true);
				$nextPaymentDate = $transaction->paymentDate;
				if($transaction->type == 'd'){
					$newStr .= '<tbody><tr class="even" ><td style="text-align: center;"> DP: </td><td style="text-align: center;"> '.Mage::app()->getLocale()
								   ->currency($model->getData('currency'))
								   ->toCurrency($transaction->amount).' </td>'.
					  '<td style="text-align: center;"> '.$date.' </td>'.
					  '<td style="text-align: center;"> '.$transaction->txnID.' </td>'.
					  '<td style="text-align: center;"> '.$transaction->paymentStatus.' </td></tr>';
					continue;
				}elseif($transaction->type == 'p'){
					$pending_flag = true;
					$newStr .= '<tr ';
					if($month%2==0)
						$newStr .= 'class="even"';
					$newStr .= '>';
					$newStr .= '<td style="text-align: center;"> Month '.$month.': </td><td style="text-align: center;"> '.Mage::app()->getLocale()
									   ->currency($model->getData('currency'))
									   ->toCurrency($transaction->amount).' </td>';
									   
					
					$newStr .= '<td style="text-align: center;"> '.$date.' </td>';

					$txnID = $transaction->txnID;
					
					$newStr .= '<td style="text-align: center;"> '.$txnID.' </td>';
						
					$newStr .= 	'<td style="text-align: center;"> '.$transaction->paymentStatus.' </td></tr>';
					
					
				}
				
			}
			//if($pending_flag)
				$startIndex = $month+1;
			// else
				// $startIndex = $month+2;
			if($month<$months){
				for($month=$startIndex;$month<=$months;$month++){
					$newStr .= '<tr ';
					if($month%2==0)
						$newStr .= 'class="even"';
					$newStr .= '>';
					$newStr .= '<td style="text-align: center;"> Month '.$month.': </td><td style="text-align: center;"> '.Mage::app()->getLocale()
                                       ->currency($model->getData('currency'))
									   ->toCurrency($model->getData('payment_amounts')).' </td>';
									   
					$nextPaymentDate = date("Y-m-d h:i:s", strtotime($nextPaymentDate . " +1 month"));
					$date = Mage::helper('core')->formatDate($nextPaymentDate, Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true);
					$newStr .= '<td style="text-align: center;"> '.$date.' </td>';
					$newStr .= '<td style="text-align: center;">&nbsp;</td>';
					$newStr .= 	'<td style="text-align: center;"> '.$nextPaymentStatus.' </td></tr>';
				}
			}
			$newStr .= '</tbody></table></div></div>';
			
			switch($status){
				case -1: if($helper->processOrder($orderId,0)){
							$model->setStatus(-1)->setReport($newStr)->setTransaction($startIndex)->save();	/* Cancel */
							$fetched++;
						}
						break;
				case 0: if($helper->processOrder($orderId,2,$report,$profileId)){
							$model->setStatus(0)->setReport($newStr)->setTransaction($startIndex)->save(); /* Processing */
							$fetched++;
						}
						break;
				case 1: if($helper->processOrder($orderId,1)){
							$model->setStatus(1)->setReport($newStr)->setTransaction($startIndex)->save();	/* Paid*/
							$fetched++;
						}
						break;  
			}
			
		}
        
        return $fetched;
    }

    /**
     * Return name for row column
     *
     * @param string $field Field name in row model
     * @return string
     */
    public function getFieldLabel($field)
    {
        switch ($field) {
            case 'report_date':
                return Mage::helper('laybuy')->__('Report Date');
            case 'account_id':
                return Mage::helper('laybuy')->__('Merchant Account');
            case 'transaction_id':
                return Mage::helper('laybuy')->__('Transaction ID');
            case 'order_id':
                return Mage::helper('laybuy')->__('Order ID');
			case 'mid':
                return Mage::helper('laybuy')->__('Lay-Buy Member ID');
            case 'paypal_profile_id':
                return Mage::helper('laybuy')->__('PayPal Profile ID');
            case 'laybuy_ref_no':
                return Mage::helper('laybuy')->__('Lay-Buy Reference ID');
			 case 'status':
                return Mage::helper('laybuy')->__('Status');
            case 'amount':
                return Mage::helper('laybuy')->__('Amount');
			case 'total_amount':
                return Mage::helper('laybuy')->__('Total Amount :');
            case 'downpayment':
                return Mage::helper('laybuy')->__('Down Payment %');
            case 'months':
                return Mage::helper('laybuy')->__('Months');
			case 'months_to_pay':
                return Mage::helper('laybuy')->__('Months to Pay : ');		
            case 'downpayment_amount':
                return Mage::helper('laybuy')->__('Down Payment Amount');
			case 'dp_amount':
                return Mage::helper('laybuy')->__('Initial Payment : ');
            case 'payment_amounts':
                return Mage::helper('laybuy')->__('Payment Amounts');
            case 'first_payment_due':
                return Mage::helper('laybuy')->__('First Payment Due');
			case 'last_payment_due':
                return Mage::helper('laybuy')->__('Last Payment Due');
			case 'report':
                return Mage::helper('laybuy')->__('Payment Record');
            case 'firstname':
                return Mage::helper('laybuy')->__('First Name');
            case 'lastname':
                return Mage::helper('laybuy')->__('Last Name');
			case 'email':
                return Mage::helper('laybuy')->__('Email');
            case 'address':
                return Mage::helper('laybuy')->__('Address');
			case 'suburb':
                return Mage::helper('laybuy')->__('Suburb');
            case 'state':
                return Mage::helper('laybuy')->__('State');
			case 'country':
                return Mage::helper('laybuy')->__('Country');
            case 'postcode':
                return Mage::helper('laybuy')->__('Postcode');
            case 'custom_field':
                return Mage::helper('laybuy')->__('Custom');
			case 'preview':
                return Mage::helper('laybuy')->__('Preview');
            default:
                return Mage::helper('laybuy')->__('%s',$field);
        }
    }

    /**
     * Iterate through website configurations and collect all SFTP configurations
     * Filter config values if necessary
     *
     * @param bool $automaticMode Whether to skip settings with disabled Automatic Fetching or not
     * @return array
     */
    public function getApiCredentials($automaticMode = false)
    {
        $configs = array();
        $uniques = array();
        foreach(Mage::app()->getStores() as $store) {
            /*@var $store Mage_Core_Model_Store */
            $active = (bool)$store->getConfig('laybuy/fetch_reports/active');
            if (!$active && $automaticMode) {
                continue;
            }
            $cfg = array(
                'hostname'  	=> $store->getConfig('laybuy/fetch_reports/ftp_ip'),
                'username' => $store->getConfig('payment/laybuy/membership_number'),
            );
            if (empty($cfg['username'])) {
                continue;
            }
            if (empty($cfg['hostname'])) {
                $cfg['hostname'] = self::REPORTS_HOSTNAME;
            }
            // avoid duplicates
            if (in_array(serialize($cfg), $uniques)) {
                continue;
            }
            $uniques[] = serialize($cfg);
            $configs[] = $cfg;
        }
        return $configs;
    }
	
	/**
     * Load report by Order Id
     *
     * @return Ced_LayBuy_Model_Report
     */
    public function loadByOrderId($orderId)
    {
        $this->getResource()->loadByOrderId($this, $orderId);
        return $this;
    }
	
	/**
     * Load report by Lay-Buy Reference Id
     *
     * @return Ced_LayBuy_Model_Report
     */
    public function loadByLayBuyRefId($laybuyId){
		$this->getResource()->loadByLayBuyRefId($this, $laybuyId);
        return $this;
	}

    /**
     * Fill/translate and sort all event codes/labels
     */
    protected function _generateEventLabels()
    {
        if (!self::$_eventList) {
            self::$_eventList = array(
            'T0000' => Mage::helper('laybuy')->__('General: received payment of a type not belonging to the other T00xx categories'),
            'T0001' => Mage::helper('laybuy')->__('Mass Pay Payment'),
            'T0002' => Mage::helper('laybuy')->__('Subscription Payment, either payment sent or payment received'),
            'T0003' => Mage::helper('laybuy')->__('Preapproved Payment (BillUser API), either sent or received'),
            'T0004' => Mage::helper('laybuy')->__('eBay Auction Payment'),
            'T0005' => Mage::helper('laybuy')->__('Direct Payment API'),
            'T0006' => Mage::helper('laybuy')->__('Express Checkout APIs'),
            'T0007' => Mage::helper('laybuy')->__('Website Payments Standard Payment'),
            'T0008' => Mage::helper('laybuy')->__('Postage Payment to either USPS or UPS'),
            'T0009' => Mage::helper('laybuy')->__('Gift Certificate Payment: purchase of Gift Certificate'),
            'T0010' => Mage::helper('laybuy')->__('Auction Payment other than through eBay'),
            'T0011' => Mage::helper('laybuy')->__('Mobile Payment (made via a mobile phone)'),
            'T0012' => Mage::helper('laybuy')->__('Virtual Terminal Payment'),
            'T0100' => Mage::helper('laybuy')->__('General: non-payment fee of a type not belonging to the other T01xx categories'),
            'T0101' => Mage::helper('laybuy')->__('Fee: Web Site Payments Pro Account Monthly'),
            'T0102' => Mage::helper('laybuy')->__('Fee: Foreign ACH Withdrawal'),
            'T0103' => Mage::helper('laybuy')->__('Fee: WorldLink Check Withdrawal'),
            'T0104' => Mage::helper('laybuy')->__('Fee: Mass Pay Request'),
            'T0200' => Mage::helper('laybuy')->__('General Currency Conversion'),
            'T0201' => Mage::helper('laybuy')->__('User-initiated Currency Conversion'),
            'T0202' => Mage::helper('laybuy')->__('Currency Conversion required to cover negative balance'),
            'T0300' => Mage::helper('laybuy')->__('General Funding of LayBuy Account '),
            'T0301' => Mage::helper('laybuy')->__('LayBuy Balance Manager function of LayBuy account'),
            'T0302' => Mage::helper('laybuy')->__('ACH Funding for Funds Recovery from Account Balance'),
            'T0303' => Mage::helper('laybuy')->__('EFT Funding (German banking)'),
            'T0400' => Mage::helper('laybuy')->__('General Withdrawal from LayBuy Account'),
            'T0401' => Mage::helper('laybuy')->__('AutoSweep'),
            'T0500' => Mage::helper('laybuy')->__('General: Use of LayBuy account for purchasing as well as receiving payments'),
            'T0501' => Mage::helper('laybuy')->__('Virtual LayBuy Debit Card Transaction'),
            'T0502' => Mage::helper('laybuy')->__('LayBuy Debit Card Withdrawal from ATM'),
            'T0503' => Mage::helper('laybuy')->__('Hidden Virtual LayBuy Debit Card Transaction'),
            'T0504' => Mage::helper('laybuy')->__('LayBuy Debit Card Cash Advance'),
            'T0600' => Mage::helper('laybuy')->__('General: Withdrawal from LayBuy Account'),
            'T0700' => Mage::helper('laybuy')->__('General (Purchase with a credit card)'),
            'T0701' => Mage::helper('laybuy')->__('Negative Balance'),
            'T0800' => Mage::helper('laybuy')->__('General: bonus of a type not belonging to the other T08xx categories'),
            'T0801' => Mage::helper('laybuy')->__('Debit Card Cash Back'),
            'T0802' => Mage::helper('laybuy')->__('Merchant Referral Bonus'),
            'T0803' => Mage::helper('laybuy')->__('Balance Manager Account Bonus'),
            'T0804' => Mage::helper('laybuy')->__('LayBuy Buyer Warranty Bonus'),
            'T0805' => Mage::helper('laybuy')->__('LayBuy Protection Bonus'),
            'T0806' => Mage::helper('laybuy')->__('Bonus for first ACH Use'),
            'T0900' => Mage::helper('laybuy')->__('General Redemption'),
            'T0901' => Mage::helper('laybuy')->__('Gift Certificate Redemption'),
            'T0902' => Mage::helper('laybuy')->__('Points Incentive Redemption'),
            'T0903' => Mage::helper('laybuy')->__('Coupon Redemption'),
            'T0904' => Mage::helper('laybuy')->__('Reward Voucher Redemption'),
            'T1000' => Mage::helper('laybuy')->__('General. Product no longer supported'),
            'T1100' => Mage::helper('laybuy')->__('General: reversal of a type not belonging to the other T11xx categories'),
            'T1101' => Mage::helper('laybuy')->__('ACH Withdrawal'),
            'T1102' => Mage::helper('laybuy')->__('Debit Card Transaction'),
            'T1103' => Mage::helper('laybuy')->__('Reversal of Points Usage'),
            'T1104' => Mage::helper('laybuy')->__('ACH Deposit (Reversal)'),
            'T1105' => Mage::helper('laybuy')->__('Reversal of General Account Hold'),
            'T1106' => Mage::helper('laybuy')->__('Account-to-Account Payment, initiated by LayBuy'),
            'T1107' => Mage::helper('laybuy')->__('Payment Refund initiated by merchant'),
            'T1108' => Mage::helper('laybuy')->__('Fee Reversal'),
            'T1110' => Mage::helper('laybuy')->__('Hold for Dispute Investigation'),
            'T1111' => Mage::helper('laybuy')->__('Reversal of hold for Dispute Investigation'),
            'T1200' => Mage::helper('laybuy')->__('General: adjustment of a type not belonging to the other T12xx categories'),
            'T1201' => Mage::helper('laybuy')->__('Chargeback'),
            'T1202' => Mage::helper('laybuy')->__('Reversal'),
            'T1203' => Mage::helper('laybuy')->__('Charge-off'),
            'T1204' => Mage::helper('laybuy')->__('Incentive'),
            'T1205' => Mage::helper('laybuy')->__('Reimbursement of Chargeback'),
            'T1300' => Mage::helper('laybuy')->__('General (Authorization)'),
            'T1301' => Mage::helper('laybuy')->__('Reauthorization'),
            'T1302' => Mage::helper('laybuy')->__('Void'),
            'T1400' => Mage::helper('laybuy')->__('General (Dividend)'),
            'T1500' => Mage::helper('laybuy')->__('General: temporary hold of a type not belonging to the other T15xx categories'),
            'T1501' => Mage::helper('laybuy')->__('Open Authorization'),
            'T1502' => Mage::helper('laybuy')->__('ACH Deposit (Hold for Dispute or Other Investigation)'),
            'T1503' => Mage::helper('laybuy')->__('Available Balance'),
            'T1600' => Mage::helper('laybuy')->__('Funding'),
            'T1700' => Mage::helper('laybuy')->__('General: Withdrawal to Non-Bank Entity'),
            'T1701' => Mage::helper('laybuy')->__('WorldLink Withdrawal'),
            'T1800' => Mage::helper('laybuy')->__('Buyer Credit Payment'),
            'T1900' => Mage::helper('laybuy')->__('General Adjustment without businessrelated event'),
            'T2000' => Mage::helper('laybuy')->__('General (Funds Transfer from LayBuy Account to Another)'),
            'T2001' => Mage::helper('laybuy')->__('Settlement Consolidation'),
            'T9900' => Mage::helper('laybuy')->__('General: event not yet categorized'),
            );
            asort(self::$_eventList);
        }
    }
	
	public function getArray($type,$value,$storeId){
		$options = array();
		if($type=='dp_amount'){
			$mind = Mage::getStoreConfig('payment/laybuy/mind',$storeId);
			if(!$mind || $mind<20 || $mind>50){
				$mind = 20;
			}
			$mind = floor($mind / 10) * 10;
			$maxd = Mage::getStoreConfig('payment/laybuy/maxd',$storeId);
			if(!$maxd || $maxd<20 || $maxd>50){
				$maxd = 50;
			}
			$maxd = round($maxd / 10) * 10;
			$vrg = 1;
			for ($e=$mind; $e<=$maxd; $e+=10) {
			  $options[] = array('value'=>$e,'label'=>$e.'%');
			  $vrg=0;
			}
		}
		if($type=='months'){
			$mmm = Mage::getStoreConfig('payment/laybuy/months',$storeId);
			if (!strlen($mmm)) $mmm = 3;
			if ($mmm < 1) $mmm = 1;
			if ($mmm > 6) $mmm = 6;
			for ($e=1; $e<=$mmm; $e++) {
			  $options[] = array('value'=>$e,'label'=>$e .' month'.($e > 1 ? 's' :''));
			}
		}
		return $options;
	}
}