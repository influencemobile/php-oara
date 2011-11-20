<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Ebay
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Ebay extends Oara_Network{
    /**
     * Export Merchants Parameters
     * @var array
     */
	private $_exportMerchantParameters = null;
    /**
     * Export Transaction Parameters
     * @var array
     */
	private $_exportTransactionParameters = null;
    /**
     * Export Overview Parameters
     * @var array
     */
	private $_exportOverviewParameters = null;
	/**
     * Export Payment Parameters
     * @var array
     */
	private $_exportPaymentParameters = null;
	
	private $_idBox = null;
    /**
     * Client 
     * @var unknown_type
     */
	private $_client = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Daisycon
	 */
	public function __construct($credentials)
	{
		$user = $credentials['user'];
        $password = $credentials['password'];
        
		
		$valuesLogin = array(
							 new Oara_Curl_Parameter('login_username', $user),
							 new Oara_Curl_Parameter('login_password', $password),
							 new Oara_Curl_Parameter('submit_btn', 'GO'),
							 new Oara_Curl_Parameter('hubpage', 'y')
							 );
							 
		$loginUrl = 'https://ebaypartnernetwork.com/PublisherLogin?hubpage=y&lang=en-US?';		 
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);
		

        $this->_exportTransactionParameters = array(new Oara_Curl_Parameter('pt', '1'),
	                                                new Oara_Curl_Parameter('advIdProgIdCombo', ''),
	                                                new Oara_Curl_Parameter('submit_excel', 'Download Excel File')
                                                   );
                                                            
       $this->_exportOverviewParameters =  array(new Oara_Curl_Parameter('advIdProgIdCombo', '1'),
                                                 new Oara_Curl_Parameter('submit_excel_epc', 'Download Excel File')
                                                );
                                               
       $this->_exportPaymentParameters = array(); 
       
                                               
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		//If not login properly the construct launch an exception
 		$connection = true;
		$urls = array();
        $urls[] = new Oara_Curl_Request('https://publisher.ebaypartnernetwork.com/PublisherHome', array());
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('#login_username');
		if (count($results) > 0){
			$connection = false;
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getMerchantList()
	 */
	public function getMerchantList($merchantMap = array()){
		$merchants = array();
		
        $obj = array();
        $obj['cid'] = "1";
        $obj['name'] = "Ebay";
        $obj['url'] = "https://publisher.ebaypartnernetwork.com";
        $merchants[] = $obj;
        
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){
		
		$totalTransactions = array();

		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
		$valuesFromExport[] = new Oara_Curl_Parameter('start_date', $dStartDate->toString("MM/dd/yy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('start_date_month', $dStartDate->toString("MM"));
		$valuesFromExport[] = new Oara_Curl_Parameter('start_date_day', $dStartDate->toString("dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('start_date_year', $dStartDate->toString("yyyy"));

		$valuesFromExport[] = new Oara_Curl_Parameter('end_date', $dEndDate->toString("MM/dd/yy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('end_date_month', $dEndDate->toString("MM"));
		$valuesFromExport[] = new Oara_Curl_Parameter('end_date_day', $dEndDate->toString("dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('end_date_year', $dEndDate->toString("yyyy"));
	   
		$urls = array();
        $urls[] = new Oara_Curl_Request('https://publisher.ebaypartnernetwork.com/PublisherReportsTx?', $valuesFromExport);
		$exportReport = $this->_client->get($urls);
			
        $exportData = str_getcsv($exportReport[0],"\n");
        $num = count($exportData);
        for ($i = 1; $i < $num; $i++) {
            $transactionExportArray = str_getcsv($exportData[$i],"\t");
            $transaction = Array();
            $transaction['merchantId'] = 1;
            $transactionDate = new Zend_Date($transactionExportArray[0], 'yyyy-MM-dd', 'en');
            $transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
            if ($transactionExportArray[10] != null){
            	$transaction['custom_id'] = $transactionExportArray[10];
            }
            
            $transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
            $transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[3]);
            $transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[3]);
            $totalTransactions[] = $transaction;
        }
        
        $valuesFromExport = Oara_Utilities::cloneArray($this->_exportOverviewParameters);
		$valuesFromExport[] = new Oara_Curl_Parameter('epc_start_date', $dStartDate->toString("MM/dd/yy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('epc_start_date_month', $dStartDate->toString("MM"));
		$valuesFromExport[] = new Oara_Curl_Parameter('epc_start_date_day', $dStartDate->toString("dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('epc_start_date_year', $dStartDate->toString("yyyy"));

		$valuesFromExport[] = new Oara_Curl_Parameter('epc_end_date', $dEndDate->toString("MM/dd/yy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('epc_end_date_month', $dEndDate->toString("MM"));
		$valuesFromExport[] = new Oara_Curl_Parameter('epc_end_date_day', $dEndDate->toString("dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('epc_end_date_year', $dEndDate->toString("yyyy"));
		
	    
		$urls = array();
        $urls[] = new Oara_Curl_Request('https://publisher.ebaypartnernetwork.com/PublisherReportsTx?', $valuesFromExport);
		$exportReport = $this->_client->get($urls);
		$exportData = str_getcsv($exportReport[0],"\n");
		$num = count($exportData);
        for ($i = 1; $i < $num; $i++) {
            $transactionExportArray = str_getcsv($exportData[$i],"\t");
            $transaction = Array();
            $transaction['merchantId'] = 1;
            $transactionDate = new Zend_Date($transactionExportArray[0], 'yyyy-MM-dd', 'en');
            $transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
            
            $transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
            $transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[7]);
            $transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[7]);
            $totalTransactions[] = $transaction;
        }
        
        return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){
		$overviewArray = Array();
		$transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);
		
		foreach ($merchantList as $merchantId){

			$overviewExport = Oara_Utilities::cloneArray($this->_exportOverviewParameters);
			$overviewExport[] = new Oara_Curl_Parameter('epc_start_date', $dStartDate->toString("MM/dd/yy"));
			$overviewExport[] = new Oara_Curl_Parameter('epc_start_date_month', $dStartDate->toString("MM"));
			$overviewExport[] = new Oara_Curl_Parameter('epc_start_date_day', $dStartDate->toString("dd"));
			$overviewExport[] = new Oara_Curl_Parameter('epc_start_date_year', $dStartDate->toString("yyyy"));
	
			$overviewExport[] = new Oara_Curl_Parameter('epc_end_date', $dEndDate->toString("MM/dd/yy"));
			$overviewExport[] = new Oara_Curl_Parameter('epc_end_date_month', $dEndDate->toString("MM"));
			$overviewExport[] = new Oara_Curl_Parameter('epc_end_date_day', $dEndDate->toString("dd"));
			$overviewExport[] = new Oara_Curl_Parameter('epc_end_date_year', $dEndDate->toString("yyyy"));
			
			$urls = array();
	        $urls[] = new Oara_Curl_Request('https://publisher.ebaypartnernetwork.com/PublisherReportsTx?', $overviewExport);
			$exportReport = $this->_client->get($urls);
			$exportData = str_getcsv($exportReport[0],"\n");
            $num = count($exportData);
            $overviewByDateArray = array();
            for ($j = 1; $j < $num; $j++) {
            	
                $overviewExportArray = str_getcsv($exportData[$j],"\t");
                if (isset($overviewByDateArray[$overviewExportArray[0]])){
                	$obj = $overviewByDateArray[$overviewExportArray[0]];
                	$obj['impression_number'] += $overviewExportArray[4];
	                $obj['click_number'] += $overviewExportArray[5];
                } else {
                	$obj = array();
	                $obj['merchantId'] = 1;
	                
	                $overviewDate = new Zend_Date($overviewExportArray[0], "yyyy/MM/dd");
	                $obj['date'] = $overviewDate->toString("yyyy-MM-dd");
	                            
	                $obj['impression_number'] = $overviewExportArray[4];
	                $obj['click_number'] = $overviewExportArray[5];
	                $obj['transaction_number'] = 0;
	                            
	                $obj['transaction_confirmed_commission'] = 0;
	                $obj['transaction_confirmed_value'] = 0;
	                $obj['transaction_pending_commission'] = 0;
	                $obj['transaction_pending_value'] = 0;
	                $obj['transaction_declined_commission'] = 0;
	                $obj['transaction_declined_value'] = 0;
                }
                $overviewByDateArray[$overviewExportArray[0]] = $obj;
            }
		}
		foreach ($overviewByDateArray as $date => $obj){
			$overviewDate = new Zend_Date($date, "yyyy/MM/dd");
			$transactionDateArray = Oara_Utilities::getDayFromArray($obj['merchantId'], $transactionArray, $overviewDate);
            foreach ($transactionDateArray as $transaction){
                $obj['transaction_number']++;
                if ($transaction['status'] == Oara_Utilities::STATUS_CONFIRMED){
                	$obj['transaction_confirmed_value'] += $transaction['amount'];
                	$obj['transaction_confirmed_commission'] += $transaction['commission'];
                } else if ($transaction['status'] == Oara_Utilities::STATUS_PENDING){
                	$obj['transaction_pending_value'] += $transaction['amount'];
                	$obj['transaction_pending_commission'] += $transaction['commission'];
                } else if ($transaction['status'] == Oara_Utilities::STATUS_DECLINED){
                	$obj['transaction_declined_value'] += $transaction['amount'];
                	$obj['transaction_declined_commission'] += $transaction['commission'];
            	}
            }
            if (Oara_Utilities::checkRegister($obj)){
            	$overviewArray[] = $obj;
            }
			
		}
	 			
		return $overviewArray;
	}
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Base#getPaymentHistory()
	 */
	public function getPaymentHistory(){
    	$paymentHistory = array();
    	
    	return $paymentHistory;
    }

}