<?

class epay{
	private $payments = array();

	function __construct($args){
		$this->args = $args;

		if (!$this->args["username"]){
			throw new exception("No username was given.");
		}

		if (!$this->args["password"]){
			throw new exception("No password was given.");
		}

		$this->soap_client = new SoapClient("https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL", array(
			"verify_peer" => false,
			"allow_self_signed" => true
		));
	}

	function transactions($args = array()){
		$res = $this->soap_client->__soapCall("gettransactionlist", array("parameters" => array_merge($args, array(
			"merchantnumber" => $this->args["merchant_no"]
		))));
		$ret = array();

		if (is_array($res->transactionInformationAry->TransactionInformationType)){
			foreach($res->transactionInformationAry->TransactionInformationType as $trans_obj){
				$ret[] = new epay_payment(array(
					"epay" => $this,
					"obj" => $trans_obj,
					"soap_client" => $this->soap_client
				));
			}
		}elseif($res->transactionInformationAry->TransactionInformationType){
			$ret[] = new epay_payment(array(
				"epay" => $this,
				"obj" => $res->transactionInformationAry->TransactionInformationType,
				"soap_client" => $this->soap_client
			));
		}

		return $ret;
	}

	function transaction($args = array()){
		$res = $this->soap_client->__soapCall("gettransaction", array("parameters" => array_merge($args, array(
			"merchantnumber" => $this->args["merchant_no"],
			"epayresponse" => true
		))));

		return new epay_payment(array(
			"epay" => $this,
			"obj" => $res->transactionInformation,
			"soap_client" => $this->soap_client
		));
	}
}

class epay_payment{
	function __construct($args){
		$this->args = $args;
		$this->soap_client = $args["soap_client"];

		if ($args["obj"]->capturedamount){
			$amount = floatval($args["obj"]->capturedamount);
		}else{
			$amount = floatval($args["obj"]->authamount);
		}

		$this->data = array(
			"amount" => $amount,
			"orderid" => intval($args["obj"]->orderid),
			"status" => $args["obj"]->status,
			"transactionid" => $args["obj"]->transactionid
		);
	}

	function get($key){
		if ($key == "id"){
			$key = "transactionid";
		}

		if (!array_key_exists($key, $this->data)){
			throw new exception("No such key: " . $key);
		}

		return $this->data[$key];
	}

	function args(){
		return $this->args;
	}

	function accept(){
		if ($this->data["status"] == "PAYMENT_CAPTURED"){
			return false;
		}

		$res = $this->soap_client->__soapCall("capture", array("parameters" => array(
			"merchantnumber" => $this->args["epay"]->args["merchant_no"],
			"transactionid" => $this->data["transactionid"],
			"amount" => $this->data["amount"],
			"epayresponse" => true,
			"pbsResponse" => true
		)));

		if (!$res->captureResult){
			throw new exception("Could not accept payment.\n\n" . print_r($res, true));
		}
	}

	function delete(){
		$res = $this->soap_client->__soapCall("delete", array("parameters" => array(
			"merchantnumber" => $this->args["epay"]->args["merchant_no"],
			"transactionid" => $this->data["transactionid"],
			"epayresponse" => true
		)));

		if (!$res->deleteResult){
			throw new exception("Could not delete payment.\n\n" . print_r($res, true));
		}
	}

	function state(){
		throw new exception("stub!");
	}
}

