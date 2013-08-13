<?php
/**
 * @author jonathan@madepeople.se
 */
abstract class Made_Dibs_Model_Payment_Abstract
    extends Mage_Payment_Model_Method_Abstract
{
    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_canVoid = true;
    protected $_canManageRecurringProfiles = false;
    protected $_canOrder = true;

    /**
     * Currency codes mapped by code and float precision
     *
     * @var array
     */
    private $_currencyCodeMap = array(
        'AFA' => array('004', 2), 'ALL' => array('008', 2), 'AMD' => array('051', 2), 'ANG' => array('532', 2),
        'AOA' => array('973', 2), 'ARS' => array('032', 2), 'AUD' => array('036', 2), 'AWG' => array('533', 2),
        'BAM' => array('977', 2), 'BBD' => array('052', 2), 'BDT' => array('050', 2), 'BGN' => array('975', 2),
        'BHD' => array('048', 3), 'BIF' => array('108', 0), 'BMD' => array('060', 2), 'BND' => array('096', 2),
        'BOB' => array('068', 2), 'BRL' => array('986', 2), 'BSD' => array('044', 2), 'BTN' => array('064', 2),
        'BWP' => array('072', 2), 'BYR' => array('974', 0), 'BZD' => array('084', 2), 'CAD' => array('124', 2),
        'CDF' => array('976', 2), 'CHF' => array('756', 2), 'CLP' => array('152', 0), 'CNY' => array('156', 2),
        'COP' => array('170', 2), 'CRC' => array('188', 2), 'CUP' => array('192', 2), 'CVE' => array('132', 2),
        'CZK' => array('203', 2), 'DJF' => array('262', 0), 'DKK' => array('208', 2), 'DOP' => array('214', 2),
        'DZD' => array('012', 2), 'EGP' => array('818', 2), 'ERN' => array('232', 2), 'ETB' => array('230', 2),
        'EUR' => array('978', 2), 'FJD' => array('242', 2), 'FKP' => array('238', 2), 'GBP' => array('826', 2),
        'GEL' => array('981', 2), 'GIP' => array('292', 2), 'GMD' => array('270', 2), 'GNF' => array('324', 0),
        'GTQ' => array('320', 2), 'GYD' => array('328', 2), 'HKD' => array('344', 2), 'HNL' => array('340', 2),
        'HRK' => array('191', 2), 'HTG' => array('332', 2), 'HUF' => array('348', 2), 'IDR' => array('360', 2),
        'ILS' => array('376', 2), 'INR' => array('356', 2), 'IQD' => array('368', 3), 'IRR' => array('364', 2),
        'ISK' => array('352', 0), 'JMD' => array('388', 2), 'JOD' => array('400', 3), 'JPY' => array('392', 0),
        'KES' => array('404', 2), 'KGS' => array('417', 2), 'KHR' => array('116', 2), 'KMF' => array('174', 0),
        'KPW' => array('408', 2), 'KRW' => array('410', 0), 'KWD' => array('414', 3), 'KYD' => array('136', 2),
        'KZT' => array('398', 2), 'LAK' => array('418', 2), 'LBP' => array('422', 2), 'LKR' => array('144', 2),
        'LRD' => array('430', 2), 'LSL' => array('426', 2), 'LTL' => array('440', 2), 'LVL' => array('428', 2),
        'LYD' => array('434', 3), 'MAD' => array('504', 2), 'MDL' => array('498', 2), 'MKD' => array('807', 2),
        'MMK' => array('104', 2), 'MNT' => array('496', 2), 'MOP' => array('446', 2), 'MRO' => array('478', 0),
        'MUR' => array('480', 2), 'MVR' => array('462', 2), 'MWK' => array('454', 2), 'MXN' => array('484', 2),
        'MYR' => array('458', 2), 'NAD' => array('516', 2), 'NGN' => array('566', 2), 'NIO' => array('558', 2),
        'NOK' => array('578', 2), 'NPR' => array('524', 2), 'NZD' => array('554', 2), 'OMR' => array('512', 3),
        'PAB' => array('590', 2), 'PEN' => array('604', 2), 'PGK' => array('598', 2), 'PHP' => array('608', 2),
        'PKR' => array('586', 2), 'PLN' => array('985', 2), 'PYG' => array('600', 0), 'QAR' => array('634', 2),
        'RUB' => array('643', 2), 'RWF' => array('646', 0), 'SAR' => array('682', 2), 'SBD' => array('090', 2),
        'SCR' => array('690', 2), 'SEK' => array('752', 2), 'SGD' => array('702', 2), 'SHP' => array('654', 2),
        'SLL' => array('694', 2), 'SOS' => array('706', 2), 'STD' => array('678', 2), 'SVC' => array('222', 2),
        'SYP' => array('760', 2), 'SZL' => array('748', 2), 'THB' => array('764', 2), 'TJS' => array('972', 2),
        'TND' => array('788', 3), 'TOP' => array('776', 2), 'TRY' => array('949', 2), 'TTD' => array('780', 2),
        'TWD' => array('901', 2), 'TZS' => array('834', 2), 'UAH' => array('980', 2), 'UGX' => array('800', 2),
        'USD' => array('840', 2), 'UYU' => array('858', 2), 'UZS' => array('860', 2), 'VND' => array('704', 0),
        'VUV' => array('548', 0), 'XAF' => array('950', 0), 'XCD' => array('951', 2), 'XOF' => array('952', 0),
        'XPF' => array('953', 0), 'YER' => array('886', 2), 'ZAR' => array('710', 2), 'ZMK' => array('894', 2),
        'ADP' => array('020', 0), 'AZM' => array('031', 0), 'BGL' => array('100', 2), 'BOV' => array('984', 2),
        'CLF' => array('990', 0), 'CYP' => array('196', 2), 'ECS' => array('218', 0), 'ECV' => array('983', 0),
        'EEK' => array('233', 2), 'GHC' => array('288', 0), 'GWP' => array('624', 2), 'MGF' => array('450', 0),
        'MTL' => array('470', 2), 'MXV' => array('979', 2), 'MZM' => array('508', 0), 'ROL' => array('642', 2),
        'RUR' => array('810', 2), 'SDD' => array('736', 0), 'SIT' => array('705', 1), 'SKK' => array('703', 1),
        'SRG' => array('740', 2), 'TMM' => array('795', 0), 'TPE' => array('626', 2), 'TRL' => array('792', 0),
        'VEB' => array('862', 2), 'YUM' => array('891', 2), 'ZWD' => array('716', 2));

    /**
     * Get the DIBS currency information
     *
     * @param string $currencyCode
     * @return array  (Code, Precision)
     * @throws Mage_Payment_Exception
     */
    protected function _getCurrencyInfo($currencyCode)
    {
        $currencyCode = strtoupper($currencyCode);
        if (!isset($this->_currencyCodeMap[$currencyCode])) {
            throw new Mage_Payment_Exception('Incorrect currency code "' . $currencyCode . '"');
        }

        return $this->_currencyCodeMap[$currencyCode];
    }

    /**
     * Get the dibs version of an ISO currency code
     *
     * @return string
     */
    public function getDibsCurrencyCode($currencyCode)
    {
        $currencyInfo = $this->_getCurrencyInfo($currencyCode);
        return $currencyInfo[0];
    }

    /**
     * Return the amount rounding precision per currency
     *
     * @param string $currencyCode
     * @return int
     */
    public function getCurrencyPrecision($currencyCode)
    {
        $currencyInfo = $this->_getCurrencyInfo($currencyCode);
        return $currencyInfo[1];
    }

    /**
     * Format an amount in a way that DIBS likes it, depending on currency
     *
     * @param int|float $amount
     * @param string $currencyCode
     * @return int
     */
    public function formatAmount($amount, $currencyCode)
    {
        $precision = $this->getCurrencyPrecision($currencyCode);
        $amount = round($amount, $precision) * pow(10, $precision);
        return (int)$amount;
    }

    /**
     * Convert a hexadecimal representation to a string representation
     *
     * @param type $hex
     * @return type
     */
    protected final function _hexToString($hex)
    {
        $string = '';

        foreach (explode("\n", trim(chunk_split($hex, 2))) as $h) {
            $string .= chr(hexdec($h));
        }

        return $string;
    }

    /**
     * The MAC is used to verify that a payment request we receive has actually
     * been paid, and that it reflects the correct gateway order fields
     *
     * @see http://tech.dibspayment.com/dibs_api/dibs_payment_window/other_features/mac_calculation/
     * @param Varien_Object $fields
     * @return string
     */
    public final function calculateMac($fields)
    {
        if (!is_array($fields)) {
            throw new Mage_Payment_Exception('The DIBS HMAC fields has to be an array');
        }

        $hmacKey = str_replace(' ', '', $this->getConfigData('hmac_key'));
        if (empty($hmacKey)) {
            throw new Mage_Payment_Exception('Please set up the DIBS HMAC key in System / Config / Payment Methods');
        }

        ksort($fields);

        $message = '';
        foreach ($fields as $key => $value) {
            if (strtolower(trim($key)) === 'mac') {
                continue;
            }
            $message .= "$key=$value&";
        }
        $message = preg_replace('/\&$/', '', $message);
        return hash_hmac('sha256', $message, $this->_hexToString($hmacKey));
    }

    /**
     * We always just place a simple order, waiting for gateway action.
     *
     * Also, we shouldn't know/guess if it's an authorization or capture
     * that's going to happen at the gateway.
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return Mage_Payment_Model_Method_Abstract::ACTION_ORDER;
    }

    /**
     * Capture an authorized payment. This should only be available if there
     * is an open authorized transaction already.
     *
     * Requires API details entered in the admin interface
     *
     * @param Varien_Object $payment
     * @param int|float $amount
     */
    public function capture(Varien_Object $payment, $amount)
    {
        die('capture');
        return $this;
    }

    /**
     * Void previously *authorized* payment.
     *
     * Requires API details entered in the admin interface
     *
     * @param Varien_Object $payment
     * @return Mage_Payment_Model_Abstract
     */
    public function void(Varien_Object $payment)
    {
        die('void');
        return $this;
    }
}