<?php
/**
 * Define the EPay and EPayTransaction calsses
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
 * Class for fetching transations from ePay via the SOAP api
 *
 * @category Framework
 * @package  Knjphpfw
 * @author   Kasper Johansen <kaspernj@gmail.com>
 * @license  Public domain http://en.wikipedia.org/wiki/Public_domain
 * @link     https://github.com/kaspernj/knjphpfw
 */
class EPay
{
    private $_merchantNo = 0;
    private $_username = '';
    private $_password = '';
    private $_soap_client;

    /**
     * Set up variables
     *
     * @param int    $merchantNo Merchant number
     * @param string $username   Username
     * @param string $password   Password
     */
    function __construct($merchantNo, $username, $password)
    {
        if (!$merchantNo || !$username || !$password) {
            throw new exception(_('Missing argument'));
        }

        $this->_merchantNo = $merchantNo;
        $this->_username = $username;
        $this->_password = $password;

        $this->_soap_client = new SoapClient(
            'https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL',
            array(
                'verify_peer' => false,
                'allow_self_signed' => true
            )
        );
    }

    /**
     * Search for transation
     *
     * @param string $datestart Start date "yyyy-MM-dd HH:mm:ss"
     * @param string $dateend   End date "yyyy-MM-dd HH:mm:ss"
     * @param string $orderid   Search for transation with defined order number
     * @param string $group     Search for transation in a defined group.
     * @param int    $status    0 = PAYMENT_UNDEFINED
     *                          1 = PAYMENT_NEW
     *                          2 = PAYMENT_CAPTURED
     *                          3 = PAYMENT_DELETED
     *                          4 = PAYMENT_DELETED
     *                          5 = PAYMENT_SUBSCRIPTION_INI
     *
     * @return array Array of EPayTransaction with id as key
     */
    public function transactions(
        $datestart,
        $dateend,
        $orderid = '',
        $group = '',
        $status = null
    ) {
        $transactions = array();

        $search = array(
            'merchantnumber' => $this->_merchantNo,
            'Searchdatestart' => $datestart,
            'Searchdateend' => $dateend
        );
        if ($orderid) {
            $search['searchorderid'] = $orderid;
        }
        if ($group) {
            $search['Searchgroup'] = $group;
        }
        if ($status !== null) {
            $search['Status'] = $status;
        }

        $result = $this->_soap_client->__soapCall(
            'gettransactionlist',
            array('parameters' => $search)
        );
        $result = $result->transactionInformationAry->TransactionInformationType;

        if (is_array($result)) {
            foreach ($result as $transaction) {
                $transactions[$transaction->transactionid] = new EPayTransaction(
                    $transaction,
                    $this
                );
            }
        } elseif ($result) {
            $transactions[$result->transactionid] = new EPayTransaction(
                $result,
                $this
            );
        }

        return $transactions;
    }

    /**
     * Get a specific transation
     *
     * @param int $transactionid The ePay id for the transation
     *
     * @return object EPayTransaction
     */
    public function transaction($transactionid)
    {
        $response = $this->_soap_client->__soapCall(
            'gettransaction',
            array(
                'parameters' => array_merge(
                    array(
                        'transactionid' => $transactionid
                    ),
                    array(
                        'merchantnumber' => $this->_merchantNo,
                        'epayresponse' => true
                    )
                )
            )
        );

        return new EPayTransaction(
            $response->transactionInformation,
            $this
        );
    }
}

/**
 * Class to handle an ePay transation
 *
 * @category Framework
 * @package  Knjphpfw
 * @author   Kasper Johansen <kaspernj@gmail.com>
 * @license  Public domain http://en.wikipedia.org/wiki/Public_domain
 * @link     https://github.com/kaspernj/knjphpfw
 */
class EPayTransaction
{
    public $id = 0;
    public $status = '';
    public $orderid = '';
    public $amount = 0;

    private $_ePay;

    /**
     * Setup variables
     *
     * @param object $transaction A transation as returned by the ePay SOAP api
     * @param object &$ePay       The parent ePay object
     */
    function __construct($transaction, EPay &$ePay)
    {
        $this->id = $transaction->transactionid;
        $this->orderid = $transaction->orderid;
        $this->status = $transaction->status;
        if ($transaction->capturedamount) {
            $this->amount = (int) $transaction->capturedamount;
        } else {
            $this->amount = (int) $transaction->authamount;
        }

        $this->_ePay = $ePay;
    }

    /**
     * Transfer the amount between the accounts
     *
     * @param int $amount The amount in minor units
     *
     * @return bool Return true if payment is captured
     */
    public function capture($amount = 0)
    {
        if ($this->status == 'PAYMENT_CAPTURED') {
            return true;
        }

        if ($amount > $this->amount) {
            return false;
        }

        if (!$amount) {
            $amount = $this->amount;
        }

        $res = $this->_ePay->soap_client->__soapCall(
            'capture',
            array(
                'parameters' => array(
                    'merchantnumber' => $this->_ePay->_merchantNo,
                    'transactionid' => $this->id,
                    'amount' => $amount,
                    'epayresponse' => true,
                    'pbsResponse' => true
                )
            )
        );

        if (!$res->captureResult) {
            return false;
        }

        $this->status = 'PAYMENT_CAPTURED';

        return true;
    }

    /**
     * Cancle a payment
     *
     * @return bool Return true if payment is captured
     */
    public function delete()
    {
        if ($this->status == 'PAYMENT_DELETED') {
            return true;
        }

        $res = $this->_ePay->soap_client->__soapCall(
            'delete',
            array(
                'parameters' => array(
                    'merchantnumber' => $this->_ePay->_merchantNo,
                    'transactionid' => $this->id,
                    'epayresponse' => true
                )
            )
        );

        if (!$res->deleteResult) {
            return false;
        }

        $this->status = 'PAYMENT_DELETED';

        return true;
    }
}

