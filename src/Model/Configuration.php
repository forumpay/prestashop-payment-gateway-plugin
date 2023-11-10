<?php

namespace ForumPay\PaymentGateway\PrestaShopModule\Model;

use ForumPay\PaymentGateway\PrestaShopModule\Form\ConfigurationDataConfiguration;
use PrestaShop\PrestaShop\Adapter\Configuration as ConfigurationAdapter;

class Configuration
{
    private ConfigurationAdapter $configuration;
    private \Context $context;

    public function __construct(
        ConfigurationAdapter $configuration,
        \Context $context
    ) {
        $this->configuration = $configuration;
        $this->context = $context;
    }

    /**
     * Get api url from settings
     *
     * @return mixed|string
     */
    public function getApiUrl()
    {
        $apiUrlOverride = $this->configuration->get(ConfigurationDataConfiguration::FORUMPAY_API_URL_OVERRIDE, null);

        return $apiUrlOverride ?? $this->configuration->get(ConfigurationDataConfiguration::FORUMPAY_API_URL, null);
    }

    /**
     * Get Api key from settings
     *
     * @return mixed|string
     */
    public function getMerchantApiUser()
    {
        return $this->configuration->get(ConfigurationDataConfiguration::FORUMPAY_API_USER, null);
    }

    /**
     * Get Api secret from settings
     *
     * @return mixed|string
     */
    public function getMerchantApiSecret()
    {
        return $this->configuration->get(ConfigurationDataConfiguration::FORUMPAY_API_KEY, null);
    }

    /**
     * Get default store locale
     *
     * @return mixed
     */
    public function getStoreLocale()
    {
        return $this->context->currentLocale->getCode();
    }

    /**
     * Get WordPress installation version if possible
     *
     * @return string
     */
    public function getPrestaShopVersion(): string
    {
        return defined('_PS_VERSION_') ? _PS_VERSION_ : 'PS - no version';
    }

    /**
     * Get current ForumPay gateway installation version
     *
     * @return string
     */
    public function getPluginVersion(): string
    {
        $module = \Module::getInstanceByName('forumpay');
        return $module->version;
    }

    /**
     * Return POS ID from settings
     *
     * @return mixed|string
     */
    public function getPosId()
    {
        return $this->configuration->get(ConfigurationDataConfiguration::FORUMPAY_POS_ID, null);
    }

    /**
     * Return weather or not zero confirmation is checked in settings
     *
     * @return bool
     */
    public function isAcceptZeroConfirmations(): bool
    {
        return $this->configuration->get(ConfigurationDataConfiguration::FORUMPAY_ACCEPT_ZERO_CONFIRMATIONS, false);
    }

}
