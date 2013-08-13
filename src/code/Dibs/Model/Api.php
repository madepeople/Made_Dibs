<?php
/**
 * General operations with the Dibs capture/cancel API
 *
 * @author jonathan@madepeople.se
 */
class Made_Dibs_Model_Api
{
    protected $_messages = array(
        '0' => 'Accepted',
        '1' => 'No response from acquirer.',
        '2' => 'Timeout',
        '3' => 'Credit card expired.',
        '4' => 'Rejected by acquirer.',
        '5' => 'Authorisation older than 7 days.',
        '6' => 'Transaction status on the DIBS server does not allow capture.',
        '7' => 'Amount too high.',
        '8' => 'Error in the parameters sent to the DIBS server. An additional parameter called "message" is returned, with a value that may help identifying the error.',
        '9' => 'Order number (orderid) does not correspond to the authorisation order number.',
        '10' => 'Re-authorisation of the transaction was rejected.',
        '11' => 'Not able to communicate with the acquier.',
        '12' => 'Confirm request error',
        '14' => 'Capture is called for a transaction which is pending for batch - i.e. capture was already called',
        '15' => 'Capture was blocked by DIBS.',
    );
    
    protected $_payment;
    
    /**
     * Payment method instance setter
     * 
     * @param Made_Dibs_Model_Paymentwindow $payment
     */
    public function setPayment(Made_Dibs_Model_Paymentwindow $payment)
    {
        $this->_payment = $payment;
    }
    
    /**
     * Payment method instance getter
     * 
     * @return Made_Dibs_Model_Paymentwindow
     */
    public function getPayment()
    {
        return $this->_payment;
    }

    /**
     * Determine if order is within the auto-capture time span
     *
     * @param Mage_Sales_Model_Order $order
     * @return boolean
     */
    protected function _shouldAutoCapture(Mage_Sales_Model_Order $order)
    {
        $daysAgo = $this->getPayment()->getConfigData('auto_capture_days_ago');
        
        for ($i = 1; $i <= $daysAgo; $i++) {
            $date = strtotime("$i days ago");
            $dayName = strtolower(strftime("%u", $date));
            if (in_array($dayName, array(6, 7))) {
                // Weekends aren't bank days
                $daysAgo++;
            }
        }
        $date = strtotime("$daysAgo days ago");
            
        // Räkna från 00:00:00 oavsett tid då order lagts
        $date = date('Y-m-d', $date);
        $orderDate = date('Y-m-d', strtotime($order->getCreatedAt()));

        return $date === $orderDate;
    }
    
    /**
     * Determine if order should be auto-cancelled
     *
     * @param Mage_Sales_Model_Order $order
     * @return boolean
     */
    protected function _shouldAutoCancel(Mage_Sales_Model_Order $order)
    {
        // TODO: implement me
        return false;
    }
    
    /**
     * Determine if order should be processed depending on status
     *
     * @return boolean
     */
    protected function _shouldProcessOrder(Mage_Sales_Model_Order $order)
    {
        throw new Exception('Re-implement me!');
        
//        if (!($info = $this->_getDibsInfo($order))) {
//            Mage::log('! Information not present in dibs_orderdata, or order already handled');
//            return false;
//        }
//        
//        if ($info['status'] > 1) {
//            Mage::log('! Order has already been processed, skipping (status ' . $info['status'] . ')');
//            return false;
//        }
//
//        return true;
    }
       
    /**
     * Fetch Dibs custom order information
     *
     * @param Mage_Sales_Model_Order $order
     * @return array|boolean
     */
    protected function _getDibsInfo(Mage_Sales_Model_Order $order)
    {
        throw new Exception('Re-implement me!');
        
//        $read = Mage::getSingleton('core/resource')
//            ->getConnection('core_read');
//
//        $select = $read->select()
//            ->from('dibs_orderdata')
//            ->where('orderid = ?', $order->getRealOrderId())
//        ;
//        
//        return $read->fetchRow($select);
    }
    
    protected function _setDibsInfo(Mage_Sales_Model_Order $order, $fields)
    {
        throw new Exception('Re-implement me!');
        
//        $write = Mage::getSingleton('core/resource')
//            ->getConnection('core_write');
//
//        $write->update('dibs_orderdata', $fields,
//              'orderid=' . $order->getRealOrderId());
    }
     
    /**
     * Build array of required Dibs fields
     *
     * @param Mage_Sales_Model_Order $order
     * @param boolean $force
     * @return array
     */
    protected function _getDibsApiFields(Mage_Sales_Model_Order $order, $force = false)
    {
        throw new Exception('Re-implement me!');
        
//        $dibs = Mage::getModel('dibs/dibs');
//        $info = $this->_getDibsInfo($order);
//        
//        if (empty($info)) {
//            throw new Phosworks_Dibs_Model_Exception('No order #' . $order->getIncrementId() . ' in dibs_orderdata');
//        }
//
//        if (empty($info['transact'])) {
//            throw new Phosworks_Dibs_Model_Exception('Order #' . $order->getIncrementId() . ' has no Dibs transaction ID - is it really authorized?');
//        }
//        
//        $fields = array(
//            'merchant' => $dibs->getConfigData('merchantid'),
//            'amount' => $order->getTotalDue() * 100,
//            'transact' => $info['transact'],
//            'orderid' => $order->getRealOrderId(),
//            'textreply' => 'yes',
//        );
//        
//        if ($force === true) {
//            $fields['force'] = 'yes';
//        }
//
//        $md5key1 = $dibs->getConfigData('md5key1');
//        $md5key2 = $dibs->getConfigData('md5key2');
//        if (($md5key1 != '') && ($md5key2 != '')) {
//            $md5key = md5($md5key2 . md5($md5key1 . 
//                'merchant=' . $fields['merchant'] . 
//                '&orderid=' . $fields['orderid'] . 
//                '&transact=' . $fields['transact'] .
//                '&amount=' . $fields['amount']));
//            $fields['md5key'] = $md5key;
//        }       
//        
//        return $fields;
    }
    
    /**
     * Make the actual API request
     *
     * @param string $url
     * @param array $fields
     * @throws Mage_Core_Exception
     */
    protected function _makeRequest($url, $fields)
    {
        throw new Exception('Re-implement me!');
        
//        $client = new Zend_Http_Client($url);
//        $client->setEncType(Zend_Http_Client::ENC_FORMDATA);
//        $client->setParameterPost($fields);
//        
//        try {
//            $response = $client->request(Zend_Http_Client::POST);
//        } catch (Exception $e) {
//            throw new Phosworks_Dibs_Model_Exception('Internal error (' . get_class($e) . '): ' . $e->getMessage());
//        }
//        
//        parse_str($response->getBody(), $responseData);
//
//        if ($responseData['result'] > 0) {
//            $message = 'An error occured with an operation at Dibs: ' . $this->_messages[$responseData['result']];
//            throw new Phosworks_Dibs_Model_Exception($message, 100);
//        }
    }
    
    protected function _sendEmail($addresses, $message)
    {
        throw new Exception('Re-implement me!');
        
//        foreach ($addresses as $address) {
//            Mage::getModel('core/email')
//                ->setFromEmail('www-data@loplabbet.se')
//                ->setToEmail($address)
//                ->setBody($message)
//                ->setSubject('Fel vid automatisk Dibs-operation')
//                ->send();
//        }
    }
    
    /**
     * Capture the amount reserved at Dibs
     *
     * @param Mage_Sales_Model_Order $order
     */
    public function capture(Mage_Sales_Model_Order $order)
    {
        throw new Exception('Re-implement me!');
        
//        if (Mage::getModel('dibs/dibs')->getConfigData('capture_now') == '1') {
//            return false;
//        }
//
//        if (!$this->_shouldProcessOrder($order)) {
//            return false;
//        }
//
//        $status = true;
//        try {
//            $fields = $this->_getDibsApiFields($order);
//            $this->_makeRequest('https://payment.architrade.com/cgi-bin/capture.cgi', $fields);
//            $this->_setDibsInfo($order, array(
//                'status' => 2,
//                'successaction' => 1,
//            ));
//            $commentMessage = 'Successfully captured order via Dibs API';
//            Mage::log("- $commentMessage");
//        } catch (Exception $e) {
//            $status = false;
//            if ($e->getCode() === 100) {
//                // Internal dibs error, cancel order
//                $order->cancel();
//            }
//            $commentMessage = 'Could not capture order #' . $order->getIncrementId() . ', caught ' . get_class($e) . ': ' . $e->getMessage();
//            Mage::log("! $commentMessage");
//            $this->_sendEmail(array(
//                //'mottagare@loplabbet.se',
//            ), $commentMessage);
//        }
//        
//        $order->addStatusToHistory($order->getStatus(), $commentMessage, false);
//        $order->save();
//        
//        return $status;
    }
    
    /**
     * Cancel the amount reserved at Dibs
     *
     * @param Mage_Sales_Model_Order $order
     */
    public function cancel(Mage_Sales_Model_Order $order)
    {
        throw new Exception('Re-implement me!');
        
//        if (Mage::getModel('dibs/dibs')->getConfigData('capturenow') == '1') {
//            return;
//        }
//                
//        if (!$this->_shouldProcessOrder($order)) {
//            return;
//        }
//
//        $status = true;
//        try {
//            $fields = $this->_getDibsApiFields($order);
//            unset($fields['amount']);
//            $this->_makeRequest('https://loplabb2012:xeeC8eeg@payment.architrade.com/cgi-bin/cancel.cgi', $fields);
//            $commentMessage = 'Successfully cancelled order via Dibs API';
//            $this->_setDibsInfo($order, array(
//                'status' => 2,
//                'ordercancellation' => 1,
//            ));
//            Mage::log("- $commentMessage");
//        } catch (Exception $e) {
//            $status = false;
//            $commentMessage = 'Could not cancel order, caught ' . get_class($e) . ': ' . $e->getMessage();
//            Mage::log("! $commentMessage");
//        }
//        
//        $order->addStatusToHistory($order->getStatus(), $commentMessage, false);
//        $order->save();
//        
//        return $status;
    }
    
    /**
     * Determine if order was placed using Dibs
     *
     * @param Mage_Sales_Model_Order $order
     */
    public static function isDibs(Mage_Sales_Model_Order $order)
    {
        return strtolower($order->getPayment()->getMethod()) === 'made_dibs';
    }
    
    /**
     * Automatically capture orders on their third business day
     */
    public function autoProcessOrders()
    {
        throw new Exception('Re-implement me!');
        
//        $status = Mage::getStoreConfig('payment/Dibs/order_status');
//        $orderCollection = Mage::getModel('sales/order')
//            ->getCollection()
//            ->addFieldToFilter('status', array('eq' => $status))
//            ->load()
//        ;
//        
//        foreach ($orderCollection as $order) {
//            if (!$this->isDibs($order)) {
//                continue;
//            }
//
//            if ($this->_shouldAutoCapture($order)) {
//                Mage::log('Automatically capturing order #' . $order->getRealOrderId());
//                $this->capture($order);
//            }
//            
//            if ($this->_shouldAutoCancel($order)) {
//                Mage::log('Automatically cancelling order #' . $order->getRealOrderId());
//                $this->cancel($order);
//            }
//        }
    }
}
