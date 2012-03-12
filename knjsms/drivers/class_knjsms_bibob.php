<?php
class knjsms_bibob implements knjsms_driver
{
    function __construct($args)
    {
        require_once "knj/functions_knj_extensions.php";
        knj_dl(array("soap", "openssl", "xml"));

        $this->opts = $args;
        $this->soap_client = new SoapClient("https://www.bibob.dk/SmsSender.asmx?WSDL", array(
            "verify_peer" => false,
            "allow_self_signed" => true
        ));
    }

    function sendSMS($number, $text)
    {
        if (!$this->soap_client) {
            $this->connect();
        }

        $status_ob = $this->soap_client->__soapCall("SendMessage", array("parameters" => array(
            "cellphone" => $this->opts["mobilenumber"],
            "password" => md5($this->opts["password"]),
            "smsTo" => array("string" => $number),
            "smscontents" => $text,
            "sendDate" => date("Y-m-d"),
            "deliveryReport" => "0",
            "fromNumber" => $this->opts["mobilenumber"]
        )));
        if ($status_ob->SendMessageResult->ErrorString != "Ingen fejl.") {
            throw new Exception("Could not send SMS (" . $status_ob->SendMessageResult->ErrorString . ").");
        }
    }
}

