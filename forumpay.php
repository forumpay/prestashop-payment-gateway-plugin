<?php

declare(strict_types=1);

define('FORUMPAY_VERSION', '2.0.1');

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

/**
 * ForumPay payment module class
 */
class ForumPay extends PaymentModule
{
    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->name = 'forumpay';
        $this->author = 'ForumPay';
        $this->tab = 'payments_gateways';
        $this->version = FORUMPAY_VERSION;
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('ForumPay', [], 'Modules.ForumPay.Admin');
        $this->description = $this->trans(
            'Pay with crypto.',
            [],
            'Modules.ForumPay.Admin'
        );

        $this->ps_versions_compliancy = ['min' => '1.7.8', 'max' => '8.99.99'];
    }

    /**
     * ForumPay installation method
     *
     * @return bool
     */
    public function install()
    {
        if (!parent::install() || !$this->registerHook('paymentOptions')) {
            return false;
        }

        $tableName = _DB_PREFIX_ . 'forumpay_checkout_transactions';

        $sql = "CREATE TABLE $tableName (
            `id` INT NOT NULL AUTO_INCREMENT,
            `order_id` INT NOT NULL,
            `payment_id` VARCHAR(255) NOT NULL,
            `data` TEXT NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        )";

        $indexOrderSql = "CREATE INDEX idx_order_id ON $tableName (`order_id`)";
        $indexPaymentSql = "CREATE INDEX idx_payment_id ON $tableName (`payment_id`)";

        $dbUpdated = Db::getInstance()->Execute($sql)
            && Db::getInstance()->execute($indexOrderSql)
            && Db::getInstance()->execute($indexPaymentSql);

        if (!$dbUpdated) {
            return false;
        }

        \Configuration::updateValue('FORUMPAY_ACCEPT_ZERO_CONFIRMATIONS', true);
        \Configuration::updateValue('FORUMPAY_TITLE', 'Pay with Crypto');
        \Configuration::updateValue('FORUMPAY_DESCRIPTION', 'Pay with Crypto (by ForumPay)');
        \Configuration::updateValue('FORUMPAY_INITIAL_ORDER_STATUS', 3);
        \Configuration::updateValue('FORUMPAY_CANCELLED_ORDER_STATUS', 6);
        \Configuration::updateValue('FORUMPAY_SUCCESS_ORDER_STATUS', 2);
        \Configuration::updateValue('FORUMPAY_CONFIG_ENABLED', 1);

        return true;
    }

    /**
     * Get configuration page
     *
     * @return void
     */
    public function getContent()
    {
        $route = $this->get('router')->generate('forumpay_configuration_form');
        Tools::redirectAdmin($route);
    }

    public function hookPaymentOptions($params)
    {
        if (!\Configuration::get('FORUMPAY_CONFIG_ENABLED')) {
            return;
        }
        $paymentOption = new PaymentOption();
        $template = $this->context->smarty->createTemplate(
            'module:forumpay/views/templates/hook/payment_option.twig'
        );
        $template->assign([
            'description' => \Configuration::get('FORUMPAY_DESCRIPTION'),
        ]);

        $paymentOption
            ->setCallToActionText($this->l(\Configuration::get('FORUMPAY_TITLE')))
            ->setAction($this->context->link->getModuleLink($this->name, 'redirect', [], true))
            ->setAdditionalInformation($template->fetch());

        return [$paymentOption];
    }

    public function uninstall(): bool
    {
        if (!parent::uninstall()) {
            return false;
        }
        $tableName = _DB_PREFIX_ . 'forumpay_checkout_transactions';
        $sql = "DROP TABLE $tableName";
        $dbUpdated = Db::getInstance()->execute($sql);
        if (!$dbUpdated) {
            return false;
        }

        return true;
    }
}
