<?php
/**
 * @author jonathan@madepeople.se
 */
class Made_Dibs_PaymentWindowController extends Mage_Core_Controller_Front_Action
{
    protected $_order;

    /**
     * When Magento claims the order has been successfully placed
     */
    public function redirectAction()
    {
        $redirectBlock = $this->getLayout()
                ->createBlock('made_dibs/paymentwindow_redirect');
        $this->getResponse()->setBody($redirectBlock->toHtml());
    }

    /**
     * When a customer cancels payment in the DIBS gateway
     */
    public function cancelAction()
    {
        $session = Mage::getSingleton('checkout/session');
        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->save();
            }
        }
        $this->_redirect('checkout/cart', array('_secure' => true));
    }

    /**
     * We have returned from the DIBS gateway and they claim everything is
     * epic. Since there is a callback functionality and we need to handle
     * it the same way as this, we just use the callbackAction to process
     * the order information
     */
    public function returnAction()
    {
        try {
            $this->callbackAction();
            $this->_redirect('checkout/onepage/success', array('_secure' => true));
        } catch (Exception $e) {
            $order = $this->_initOrder();
            $order->addStatusHistoryComment('CAUTION! This order could have been paid, please inspect the DIBS administration panel. Error when returning from gateway: ' . $e->getMessage());
            $order->cancel()
                    ->save();

            Mage::getSingleton('core/session')->addError($e->getMessage());
            Mage::logException($e);

            $this->_redirect('checkout/onepage/failure');
        }
    }

    /**
     * Initialize the order object for the current transaction
     *
     * @throws Mage_Payment_Exception
     */
    protected function _initOrder()
    {
        if (!$this->_order) {
            $fields = $this->getRequest()->getPost();

            if (!isset($fields['orderId'])) {
                throw new Mage_Payment_Exception('Required field orderId is missing');
            }

            // Lock the order row to prevent double processing from the
            // customer + callback
            $resource = Mage::getModel('sales/order')->getResource();
            $resource->getReadConnection()
                    ->select()
                    ->forUpdate()
                    ->from($resource->getTable('sales/order'))
                    ->where('increment_id = ?', $fields['orderId'])
                    ->query();

            $order = Mage::getModel('sales/order')
                    ->loadByIncrementId($fields['orderId']);

            if (!$order->getId()) {
                throw new Mage_Payment_Exception('Order with ID "' . $fields['orderID'] . '" could not be found');
            }

            $this->_order = $order;
        }

        return $this->_order;
    }

    /**
     * Handle the callback information from DIBS, needs to be synchronous in
     * case the gateway sends the user to the success page the same time as
     * the DIBS callback calls us.
     *
     * We have everything within a transaction with row-locking to prevent
     * race conditions.
     *
     * @return void
     */
    public function callbackAction()
    {
        $write = Mage::getSingleton('core/resource')
                    ->getConnection('core_write');

        try {
            $write->beginTransaction();
            $order = $this->_initOrder();
            if ($order->getState() !== Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
                // Order is not in review state. It's possible that the payment
                // has already been registered via the callback.
                $write->rollback();
                return;
            }

            $methodInstance = $order->getPayment()
                    ->getMethodInstance();

            if (!($methodInstance instanceof Made_Dibs_Model_Payment_Paymentwindow)) {
                throw new Mage_Payment_Exception('Order isn\'t a DIBS order');
            }

            $fields = $this->getRequest()->getPost();
            $mac = $methodInstance->calculateMac($fields);
            if ($mac != $fields['MAC']) {
                throw new Mage_Payment_Exception('MAC verification failed for order #' . $fields['orderId']);
            }

            $payment = $order->getPayment();
            $payment->setTransactionId($fields['transaction'])
                    ->setIsTransactionApproved(true)
                    ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $fields);

            if (empty($fields['capturenow'])) {
                // Leave the transaction open for captures/refunds/etc
                $payment->setPreparedMessage('DIBS - Payment Authorized.');
                $payment->setIsTransactionClosed(0);
                $payment->authorize(false, $order->getGrandTotal());
            } else {
                // The order has been fully paid
                $payment->setPreparedMessage('DIBS - Payment Successful.');
                $payment->registerCaptureNotification($order->getGrandTotal());
            }

            $newOrderStatus = $methodInstance->getConfigData('order_status');
            if (!empty($newOrderStatus)) {
                $order->setStatus($newOrderStatus);
            }

            $order->sendNewOrderEmail();
            $order->save();
            $write->commit();
        } catch (Exception $e) {
            $write->rollback();
            throw $e;
        }
    }
}
