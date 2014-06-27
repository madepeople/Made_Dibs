<?php
/**
 * @author jonathan@madepeople.se
 */
class Made_Dibs_Model_Observer
{
    /**
     * We need to call the authorize function separately in order to maintain
     * correct transaction hierarchy when using the authorize+capture action,
     * and the only way to do that seems to be from within the
     * "sales_order_payment_capture" event
     *
     * @param Varien_Event_Observer $observer
     */
    public function authorizeBeforeCapture(Varien_Event_Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();
        $method = $payment->getMethodInstance();
        if (!($method instanceof Made_Dibs_Model_Payment_Api)) {
            return;
        }

        if ($payment->hasData('last_trans_id')
                || $method->getConfigPaymentAction() !== Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE) {
            // If we have last_trans_id it isn't a fresh transaction and we
            // might actually be capturing a previous authorization
            return;
        }

        $invoice = $observer->getEvent()->getInvoice();

        // @see Mage_Sales_Model_Order_Payment line 379
        $amount = Mage::app()->getStore()->roundPrice($invoice->getBaseGrandTotal());
        $payment->authorize(true, $amount);

        // Our capture method requires a parent transaction, and last_trans_id
        // might actually be something else in other cases, but here we choose
        // that the parent one for capture is the last one, from authorization
        $payment->setAuthorizeTransactionId($payment->getLastTransId());
    }

    /**
     * This method cleans up old pending_gateway orders as they are probably
     * left over from customers who closed their browsers, lost internet
     * connectivity, etc.
     *
     * @param Varien_Object $observer
     */
    public function cancelOldPendingGatewayOrders(Varien_Event_Observer $observer)
    {
        $date = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $orderCollection = Mage::getModel('sales/order')
                ->getCollection()
                ->addFieldToFilter('state', Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)
                ->addAttributeToFilter('created_at', array('lt' => $date));

        foreach ($orderCollection as $order) {
            if (!$order->canCancel()) {
                continue;
            }

            $method = $order->getPayment()
                    ->getMethod();

            if (!strstr($method, 'made_dibs')) {
                continue;
            }

            $order->cancel();
            $order->addStatusHistoryComment('The order was automatically cancelled due to more than 1 hour of gateway inactivity.');
            $order->save();
        }
    }

    /**
     * We have some information from the payment which we add here so the order
     * emails etc have credit card information.
     *
     * @param Varien_Event_Observer $observer
     */
    public function addDibsPaymentInfo(Varien_Event_Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();
        if (!preg_match('/dibs/', $payment->getMethod())) {
            return;
        }
        $transport = $observer->getEvent()->getTransport();
        $order = $payment->getOrder();

        if (null === $order || !$order->getId()) {
            return;
        }

        $transaction = Mage::getModel('sales/order_payment_transaction')
            ->getCollection()
            ->setOrderFilter($order)
            ->addPaymentIdFilter($payment->getId())
            ->addTxnTypeFilter(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH)
            ->getIterator()
            ->current();

        if (null === $transaction || !$transaction->getId()) {
            return;
        }

        $helper = Mage::helper('made_dibs');
        $additionalData = $transaction->getAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS);
        if (!empty($additionalData['cardTypeName'])) {
            $transport->setData($helper->__('Credit Card'), $additionalData['cardTypeName']);
            $transport->setData($helper->__('Credit Card Number'), $additionalData['cardNumberMasked']);
            $transport->setData($helper->__('Expiration Date'), $additionalData['expMonth'] . '/' . $additionalData['expYear']);
        }
    }
}