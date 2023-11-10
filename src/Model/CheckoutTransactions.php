<?php

namespace ForumPay\PaymentGateway\PrestaShopModule\Model;

class CheckoutTransactions
{
    private \DbPDO $database;

    public function __construct()
    {
        $this->database = \Db::getInstance();
    }

    /**
     * @param int|string $orderId
     * @param string $paymentId
     * @param array $data
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function insert($orderId, string $paymentId, array $data): bool
    {
        return (new Transaction())
            ->setOrderId($orderId)
            ->setPaymentId($paymentId)
            ->setData($data)
            ->save();
    }

    /**
     * @param int|string $orderId
     * @param string $paymentId
     * @param array $data
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function update($orderId, string $paymentId, array $data): bool
    {
        $transaction = Transaction::findByPaymentId($paymentId, $orderId);
        return $transaction->setData($data)->update();
    }

    /**
     * @param string $paymentId
     *
     * @return array|null
     * @throws \Exception
     */
    public function getData(string $paymentId): ?array
    {
        try {
            $transaction = Transaction::findByPaymentId($paymentId);
        } catch (\Exception $e) {
            return null;
        }

        if (null === $transaction) {
            return null;
        }

        return $transaction->getTransaction()['data'];
    }

    /**
     * @param string $paymentId
     *
     * @return int|false
     */
    public function getOrderId(string $paymentId)
    {
        try {
            $transaction = Transaction::findByPaymentId($paymentId);
        } catch (\Exception $e) {
            return false;
        }

        return (int) $transaction->getTransaction()['order_id'];
    }
}
