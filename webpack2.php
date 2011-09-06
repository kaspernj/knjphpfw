<?

class webpack2{
	private $paras;
	private $soap;

	function __construct($paras){
		$this->paras = $paras;

		if ($this->paras["test"]){
			$this->soap_url = "http://www.postdanmark.dk/webpack2demo/ParcelLabelWsService?wsdl";
		}else{
			$this->soap_url = "http://www.postdanmark.dk/webpack2/ParcelLabelWsService?wsdl";
		}

		$this->soap = new SoapClient($this->soap_url);
	}

	function generateParcelLabel($data){
		$status = $this->soap->generateParcelLabel(array(
			"authentication" => array(
				"customerNo" => $this->paras["customer_no"],
				"password" => $this->paras["password"]
			),
			"parcels" => $data
		));

		if ($status->parcelLabel->parcels->parcel->errorMsg){
			throw new exception($status->parcelLabel->parcels->parcel->errorMsg);
		}

		if (!$status->parcelLabel->label){
			throw new exception("No label was returned from Webpack2.");
		}

		return $status;
	}
}

