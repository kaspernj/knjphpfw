<?php
/**
 * This file contains the webpack2 class
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
 * Class for requesting lables from Post Danmark webpack2
 *
 * @category Framework
 * @package  Knjphpfw
 * @author   Kasper Johansen <kaspernj@gmail.com>
 * @license  Public domain http://en.wikipedia.org/wiki/Public_domain
 * @link     https://github.com/kaspernj/knjphpfw
 */
class webpack2
{
    private $_customerNo;
    private $_password;
    private $_soap;

    /**
     * TODO
     *
     * @param array $paras TODO
     */
    function __construct($paras)
    {
        $this->_customerNo = $paras["customer_no"];
        $this->_password = $paras["password"];

        $soapUrl = "http://www2.postdanmark.dk/webpack2/ParcelLabelWsService?wsdl";
        if ($paras["test"]) {
            $soapUrl = "http://www2.postdanmark.dk/webpack2demo/ParcelLabelWsService?wsdl";
        }

        $this->_soap = new SoapClient($soapUrl);
    }

    /**
     * TODO
     *
     * @param array $data TODO
     *
     * @return TODO
     */
    function generateParcelLabel($data)
    {
        $authentication = array(
            "customerNo" => $this->_customerNo,
            "password" => $this->_password
        );
        $args = array(
            "authentication" => $authentication,
            "parcels" => $data
        );
        $status = $this->_soap->generateParcelLabel($args);

        if ($status->parcelLabel->parcels->parcel->errorMsg) {
            throw new exception($status->parcelLabel->parcels->parcel->errorMsg);
        }

        if (!$status->parcelLabel->label) {
            throw new exception(_("No label was returned from Webpack2."));
        }

        return $status;
    }
}

