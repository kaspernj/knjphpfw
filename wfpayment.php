<?

class wfpayment{
	private $payments = array();

	function __construct($args){
		$this->args = $args;

		if (!$this->args["username"]){
			throw new exception("No username was given.");
		}

		if (!$this->args["password"]){
			throw new exception("No password was given.");
		}

		require_once "knj/http.php";
		$this->http = new knj_httpbrowser();
		$this->http->connect("betaling.wannafind.dk", 443);

		$html = $this->http->getaddr("index.php");

		$html = $this->http->post("pg.loginauth.php", array(
			"username" => $this->args["username"],
			"password" => $this->args["password"]
		));

		if (strpos($html, "Brugernavn eller password, blev ikke godkendt.") !== false){
			throw new exception("Could not log in.");
		}
	}

	function http(){
		return $this->http;
	}

	function listPayments($args = array()){
		if ($args["awaiting"]){
			$html = $this->http->getaddr("pg.frontend/pg.transactions.php");
		}else{
			$sargs = array(
				"searchtype" => "",
				"fromday" => "01",
				"frommonth" => "01",
				"fromyear" => date("Y"),
				"today" => "31",
				"tomonth" => "12",
				"toyear" => date("Y"),
				"transacknum" => ""
			);

			if ($args["order_id"]){
				$sargs["orderid"] = $args["order_id"];
			}

			$html = $this->http->post("pg.frontend/pg.search.php?search=doit", $sargs);
		}

		if (!preg_match_all("/<tr([^>]+)>\s*([\s\S]+)<\/tr>/U", $html, $matches_tr)){
			return array();
		}

		if (count($matches_tr[2]) == 1 or strlen(trim($matches_tr[2][0])) <= 0){
			return array();
		}

		$payments = array();
		foreach($matches_tr[0] AS $key => $value){
			if (!preg_match_all("/<td([^>]+)>(.*)<\/td>/U", $value, $matches_td)){
				throw new exception("Could not match TDs.");
			}

			if (!preg_match("/id=(\d+)/", $matches_td[2][0], $match_id)){
				throw new exception("Could not match ID.");
			}

			$id = $match_id[1];

			$amount = str_replace(" DKK", "", $matches_td[2][3]);
			$amount = strtr($amount, array(
				"." => "",
				"," => "."
			));

			$card_type = $matches_td[2][6];

			if (strpos($card_type, "dk.png") !== false){
				$card_type = "dk";
			}elseif(strpos($card_type, "visa-elec.png") !== false){
				$card_type = "visa_electron";
			}elseif(strpos($card_type, "mc.png") !== false){
				$card_type = "mastercard";
			}elseif(strpos($card_type, "visa.png") !== false){
				$card_type = "visa";
			}else{
				throw new exception("Unknown card-type image: " . $card_type);
			}

			$date = strtr($matches_td[2][2], array(
				"januar" => 1,
				"februar" => 2,
				"marts" => 3,
				"april" => 4,
				"maj" => 5,
				"juni" => 6,
				"juli" => 7,
				"august" => 8,
				"september" => 9,
				"oktober" => 10,
				"november" => 11,
				"december" => 12
			));

			if (preg_match("/(\d+) (\d+) (\d+) (\d+):(\d+):(\d+)/", $date, $match)){
				$unixt = mktime($match[4], $match[5], $match[6], $match[2], $match[1], $match[3]);
			}elseif(preg_match("/(\d+) ([a-z]{3}) (\d{4}) (\d{2}):(\d{2}):(\d{2})/", $date, $match)){
				$month = $match[2];
				if ($month == "jan"){
					$month_no = 1;
				}elseif($month == "feb"){
					$month_no = 2;
				}elseif($month == "mar"){
					$month_no = 3;
				}elseif($month == "apr"){
					$month_no = 4;
				}elseif($month == "maj"){
					$month_no = 5;
				}elseif($month == "jun"){
					$month_no = 6;
				}elseif($month == "jul"){
					$month_no = 7;
				}elseif($month == "aug"){
					$month_no = 8;
				}elseif($month == "sep"){
					$month_no = 9;
				}elseif($month == "okt"){
					$month_no = 10;
				}elseif($month == "nov"){
					$month_no = 11;
				}elseif($month == "dec"){
					$month_no = 12;
				}else{
					throw new exception("Unknown month string: " . $month);
				}

				$unixt = mktime($match[4], $match[5], $match[6], $month_no, $match[1], $match[3]);
			}else{
				throw new exception("Could not parse date: " . $date);
			}

			$state = $matches_td[2][7];
			if (strpos($state, "Gennemført") !== false){
				$state = "done";
			}elseif(strpos($state, "Gennemfør") !== false){
				$state = "waiting";
			}elseif(strpos($state, "Annulleret") !== false){
				$state = "canceled";
			}elseif(strpos($state, "Refunderet") !== false){
				$state = "returned";
			}else{
				throw new exception("Unknown state: " . $state);
			}

			if ($this->payments[$id]){
				$payment = $this->payments[$id];
				$payment->set("state", $state);
			}else{
				$payment = new wfpayment_payment($this, array(
					"id" => $id,
					"order_id" => substr($matches_td[2][1], 4),
					"customer_id" => $matches_td[2][1],
					"customer_string" => $matches_td[2][1],
					"date" => $unixt,
					"amount" => $amount,
					"card_type" => $card_type,
					"state" => $state
				));
			}

			$payments[] = $payment;
		}

		return $payments;
	}
}

class wfpayment_payment{
	function __construct($wfpayment, $args){
		$this->wfpayment = $wfpayment;
		$this->http = $this->wfpayment->http();
		$this->args = $args;
	}

	function set($key, $value){
		$this->args[$key] = $value;
	}

	function get($key){
		if (!array_key_exists($key, $this->args)){
			throw new exception("No such key: " . $key);
		}

		return $this->args[$key];
	}

	function args(){
		return $this->args;
	}

	function accept(){
		if ($this->state() == "done"){
			throw new exception("This payment is already accepted.");
		}

		$html = $this->http->getaddr("pg.frontend/pg.transactions.php?capture=singlecapture&transid=" . $this->get("id") . "&page=1&orderby=&direction=");

		if ($this->state() != "done"){
			throw new exception("Could not accept the payment. State: " . $this->state());
		}
	}

	function cancel(){
		if ($this->state() != "waiting"){
			throw new exception("This is not waiting and cannot be canceled.");
		}

		$html = $this->http->getaddr("pg.frontend/pg.transactionview.php?action=cancel&id=" . $this->get("id") . "&page=1");

		if ($this->state() != "canceled"){
			throw new exception("Could not cancel the payment.");
		}
	}

	function state(){
		$payments = $this->wfpayment->listPayments(array("order_id" => $this->get("order_id")));
		if (!$payments or !$payments[0]){
			return false;
		}

		return $payments[0]->get("state");
	}
}

