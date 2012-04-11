<?php
/**
 * TODO
 *
 * PHP version 5
 *
 * @category Framework
 * @package  Knjphpfw
 * @author   Kasper Johansen <kaspernj@gmail.com>
 * @license  Public domain http://en.wikipedia.org/wiki/Public_domain
 * @link     https://github.com/kaspernj/knjphpfw
 */

/**
 * TODO
 *
 * @category Framework
 * @package  Knjphpfw
 * @author   Kasper Johansen <kaspernj@gmail.com>
 * @license  Public domain http://en.wikipedia.org/wiki/Public_domain
 * @link     https://github.com/kaspernj/knjphpfw
 */
class epay
{
    /**
     * TODO
     *
     * @param array $args TODO
     */
    function __construct($args)
    {
        $this->args = $args;

        if (!$this->args["username"]) {
            throw new exception(_("No username was given."));
        }

        if (!$this->args["password"]) {
            throw new exception(_("No password was given."));
        }

        $data = array(
            "verify_peer" => false,
            "allow_self_signed" => true
        );
        $this->soap_client = new SoapClient(
            "https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL",
            $data
        );
    }

    /**
     * TODO
     *
     * @param array $args TODO
     *
     * @return array TODO
     */
    function transactions($args = array())
    {
        $args2 = array(
            "merchantnumber" => $this->args["merchant_no"]
        );
        $res = $this->soap_client->__soapCall(
            "gettransactionlist",
            array("parameters" => array_merge($args, $args2))
        );
        $ret = array();

        if (is_array($res->transactionInformationAry->TransactionInformationType)) {
            foreach ($res->transactionInformationAry->TransactionInformationType as $trans_obj) {
                $data = array(
                    "epay" => $this,
                    "obj" => $trans_obj,
                    "soap_client" => $this->soap_client
                );
                $ret[] = new epay_payment($data);
            }
        } elseif ($res->transactionInformationAry->TransactionInformationType) {
                $data = array(
                "epay" => $this,
                "obj" => $res->transactionInformationAry->TransactionInformationType,
                "soap_client" => $this->soap_client
            );
            $ret[] = new epay_payment($data);
        }

        return $ret;
    }

    /**
     * Get a specific transation
     *
     * @param int $transactionid The epay id for the transation
     *
     * @return object TODO
     */
    function transaction($transactionid)
    {
        $response = $this->soap_client->__soapCall(
            'gettransaction',
            array(
                'parameters' => array_merge(
                    array(
                        'transactionid' => $transactionid
                    ),
                    array(
                        'merchantnumber' => $this->args['merchant_no'],
                        'epayresponse' => true
                    )
                )
            )
        );

        $data = array(
            "epay" => $this,
            "obj" => $response->transactionInformation,
            "soap_client" => $this->soap_client
        );
        return new epay_payment($data);
    }
}

/**
 * TODO
 *
 * @category Framework
 * @package  Knjphpfw
 * @author   Kasper Johansen <kaspernj@gmail.com>
 * @license  Public domain http://en.wikipedia.org/wiki/Public_domain
 * @link     https://github.com/kaspernj/knjphpfw
 */
class epay_payment
{
    /**
     * TODO
     *
     * @param array $args TODO
     */
    function __construct($args)
    {
        $this->args = $args;
        $this->soap_client = $args["soap_client"];

        if ($args["obj"]->capturedamount) {
            $amount = floatval($args["obj"]->capturedamount);
        } else {
            $amount = floatval($args["obj"]->authamount);
        }

        $this->data = array(
            "amount" => $amount,
            "orderid" => intval($args["obj"]->orderid),
            "status" => $args["obj"]->status,
            "transactionid" => $args["obj"]->transactionid
        );
    }

    /**
     * TODO
     *
     * @param string $key TODO
     *
     * @return TODO
     */
    function get($key)
    {
        if ($key == "id") {
            $key = "transactionid";
        }

        if (!array_key_exists($key, $this->data)) {
            throw new exception(sprintf(_("No such key: %s"), $key));
        }

        return $this->data[$key];
    }

    /**
     * TODO
     *
     * @return array TODO
     */
    function args()
    {
        return $this->args;
    }

    /**
     * TODO
     *
     * @return bool TODO
     */
    function accept()
    {
        if ($this->data["status"] == "PAYMENT_CAPTURED") {
            return false;
        }

        $parameters = array(
            "merchantnumber" => $this->args["epay"]->args["merchant_no"],
            "transactionid" => $this->data["transactionid"],
            "amount" => $this->data["amount"],
            "epayresponse" => true,
            "pbsResponse" => true
        );
        $res = $this->soap_client->__soapCall(
            "capture",
            array("parameters" => $parameters)
        );

        if (!$res->captureResult) {
            $msg = _("Could not accept payment.");
            throw new exception($msg ."\n\n" .print_r($res, true));
        }
    }

    /**
     * TODO
     *
     * @return null
     */
    function delete()
    {
        $parameters = array(
            "merchantnumber" => $this->args["epay"]->args["merchant_no"],
            "transactionid" => $this->data["transactionid"],
            "epayresponse" => true
        );
        $res = $this->soap_client->__soapCall(
            "delete",
            array("parameters" => $parameters)
        );

        if (!$res->deleteResult) {
            $msg = _("Could not delete payment.");
            throw new exception($msg ."\n\n" .print_r($res, true));
        }
    }

    /**
     * TODO
     *
     * @return null
     */
    function state()
    {
        throw new exception(_("stub!"));
    }
}

