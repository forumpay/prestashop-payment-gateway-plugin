<?php

namespace ForumPay\PaymentGateway\PrestaShopModule\Model;

class Transaction extends \ObjectModel
{
    /**
     * @var
     */
    public $id;
    /**
     * @var
     */
    public $order_id;
    /**
     * @var
     */
    public $payment_id;
    /**
     * @var
     */
    public $data;
    /**
     * @var string
     */
    public $created_at;

    /**
     * @var array
     */
    public static $definition = [
        'table' => 'forumpay_checkout_transactions',
        'primary' => 'id',
        'fields' => [
            'id' => ['type' => self::TYPE_INT],
            'order_id' => ['type' => self::TYPE_INT],
            'payment_id' => [
                'type' => self::TYPE_STRING,
                'size' => 255,
            ],
            'data' => ['type' => self::TYPE_HTML],
            'created_at' => [
                'TYPE_DATE' => self::TYPE_DATE,
                'validate' => 'isDate',
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ],
    ];

    /**
     * @param int|null $id
     */
    public function __construct(int $id = null)
    {
        if (is_null($id)) {
            $this->created_at = date('Y-m-d H:i:s', time());
        }

        parent::__construct($id);
    }

    /**
     * Find a transaction by payment ID
     *
     * @param string $paymentId
     * @param string|null $orderId
     *
     * @return Transaction|null
     *
     * @throws \Exception
     */
    public static function findByPaymentId(string $paymentId, string $orderId = null): ?self
    {
        $where = null !== $orderId
            ? sprintf("payment_id = '%s' and order_id = '%s'", pSQL($paymentId), pSQL($orderId))
            : sprintf("payment_id = '%s'", pSQL($paymentId));

        $sql = new \DbQuery();
        $sql->select('*')
            ->from('forumpay_checkout_transactions')
            ->where($where)
            ->orderBy('created_at DESC');

        $result = \Db::getInstance()->getRow($sql);

        if ($result) {
            return (new Transaction())
                ->setId($result['id'])
                ->setOrderId($result['order_id'])
                ->setPaymentId($result['payment_id'])
                ->setData($result['data'])
                ->setCreatedAt($result['created_at']);
        }

        return null;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param int|string $orderId
     *
     * @return $this
     */
    public function setOrderId($orderId): self
    {
        $this->order_id = (int) trim($orderId);

        return $this;
    }

    /**
     * @param string $paymentId
     *
     * @return $this
     */
    public function setPaymentId(string $paymentId): self
    {
        $this->payment_id = trim($paymentId);

        return $this;
    }

    /**
     * @param string|array $data
     * @return Transaction
     * @throws \Exception
     */
    public function setData($data): self
    {
        if (!is_string($data)) {
            $data = json_encode($data);
            if ($data === false) {
                throw new \Exception('Encoding an array failed.');
            }
        }

        $this->data = $data;

        return $this;
    }

    /**
     * @param string $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * @return array
     */
    public function getTransaction(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'payment_id' => $this->payment_id,
            'data' => json_decode($this->data, true),
            'created_at' => $this->created_at,
        ];
    }
}
