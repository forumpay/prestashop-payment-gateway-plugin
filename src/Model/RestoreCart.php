<?php

namespace ForumPay\PaymentGateway\PrestaShopModule\Model;

use ForumPay\PaymentGateway\PrestaShopModule\Logger\ForumPayLogger;
use ForumPay\PaymentGateway\PrestaShopModule\Model\Payment\ForumPay;

class RestoreCart
{
    /**
     * ForumPay payment model
     *
     * @var ForumPay
     */
    private ForumPay $forumPay;

    /**
     * @var ForumPayLogger
     */
    private ForumPayLogger $logger;

    /**
     * Constructor
     *
     * @param ForumPay $forumPay
     * @param ForumPayLogger $forumPayLogger
     */
    public function __construct(ForumPay $forumPay, ForumPayLogger $forumPayLogger)
    {
        $this->forumPay = $forumPay;
        $this->logger = $forumPayLogger;
    }

    /**
     * Restores cart.
     *
     * @param Request $request
     * @return null
     * @throws \Exception
     */
    public function execute(Request $request)
    {
        try {
            $this->forumPay->restoreCart();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), $e->getTrace());
            throw new \Exception($e->getMessage(), 7050, $e);
        }

        return null;
    }
}
