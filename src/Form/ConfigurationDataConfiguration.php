<?php

declare(strict_types=1);

namespace ForumPay\PaymentGateway\PrestaShopModule\Form;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;

final class ConfigurationDataConfiguration implements DataConfigurationInterface
{
    public const FORUMPAY_TITLE = 'FORUMPAY_TITLE';
    public const FORUMPAY_DESCRIPTION = 'FORUMPAY_DESCRIPTION';
    public const FORUMPAY_API_URL = 'FORUMPAY_API_URL';
    public const FORUMPAY_API_USER = 'FORUMPAY_API_USER';
    public const FORUMPAY_API_KEY = 'FORUMPAY_API_KEY';
    public const FORUMPAY_POS_ID = 'FORUMPAY_POS_ID';
    public const FORUMPAY_API_URL_OVERRIDE = 'FORUMPAY_API_URL_OVERRIDE';
    public const FORUMPAY_INITIAL_ORDER_STATUS = 'FORUMPAY_INITIAL_ORDER_STATUS';
    public const FORUMPAY_CANCELLED_ORDER_STATUS = 'FORUMPAY_CANCELLED_ORDER_STATUS';
    public const FORUMPAY_SUCCESS_ORDER_STATUS = 'FORUMPAY_SUCCESS_ORDER_STATUS';
    public const FORUMPAY_LOG_DEBUG = 'FORUMPAY_LOG_DEBUG';
    public const FORUMPAY_ACCEPT_ZERO_CONFIRMATIONS = 'FORUMPAY_ACCEPT_ZERO_CONFIRMATIONS';
    public const FORUMPAY_CONFIG_ENABLED = 'FORUMPAY_CONFIG_ENABLED';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration(): array
    {
        $return = [];

        $return['title'] = $this->configuration->get(static::FORUMPAY_TITLE);
        $return['description'] = $this->configuration->get(static::FORUMPAY_DESCRIPTION);
        $return['api_url'] = $this->configuration->get(static::FORUMPAY_API_URL);
        $return['api_user'] = $this->configuration->get(static::FORUMPAY_API_USER);
        $return['api_key'] = $this->configuration->get(static::FORUMPAY_API_KEY);
        $return['pos_id'] = $this->configuration->get(static::FORUMPAY_POS_ID);
        $return['initial_order_status'] = $this->configuration->get(static::FORUMPAY_INITIAL_ORDER_STATUS);
        $return['cancelled_order_status'] = $this->configuration->get(static::FORUMPAY_CANCELLED_ORDER_STATUS);
        $return['success_order_status'] = $this->configuration->get(static::FORUMPAY_SUCCESS_ORDER_STATUS);
        $return['api_url_override'] = $this->configuration->get(static::FORUMPAY_API_URL_OVERRIDE);
        $return['forumpay_log_debug'] = (bool) $this->configuration->get(static::FORUMPAY_LOG_DEBUG);
        $return['accept_zero_confirmations'] = (bool) $this->configuration->get(
            static::FORUMPAY_ACCEPT_ZERO_CONFIRMATIONS
        );
        $return['enabled'] = (bool) $this->configuration->get(static::FORUMPAY_CONFIG_ENABLED);

        return $return;
    }

    public function updateConfiguration(array $configuration): array
    {
        $errors = [];

        if (!$this->validateConfiguration($configuration)) {
            $errors[] = 'Invalid configuration';

            return $errors;
        }

        $this->configuration->set(static::FORUMPAY_TITLE, $configuration['title']);
        $this->configuration->set(static::FORUMPAY_DESCRIPTION, $configuration['description']);
        $this->configuration->set(static::FORUMPAY_API_URL, $configuration['api_url']);
        $this->configuration->set(static::FORUMPAY_API_USER, $configuration['api_user']);

        $this->setApiKey($configuration);
        $this->setPosIdField($errors, $configuration);
        $this->setApiUrlOverrideField($errors, $configuration);

        $this->configuration->set(static::FORUMPAY_INITIAL_ORDER_STATUS, $configuration['initial_order_status']);
        $this->configuration->set(static::FORUMPAY_CANCELLED_ORDER_STATUS, $configuration['cancelled_order_status']);
        $this->configuration->set(static::FORUMPAY_SUCCESS_ORDER_STATUS, $configuration['success_order_status']);

        $this->configuration->set(static::FORUMPAY_LOG_DEBUG, $configuration['forumpay_log_debug']);
        $this->configuration->set(
            static::FORUMPAY_ACCEPT_ZERO_CONFIRMATIONS,
            $configuration['accept_zero_confirmations']
        );
        $this->configuration->set(static::FORUMPAY_CONFIG_ENABLED, $configuration['enabled']);

        return $errors;
    }

    /**
     * Ensure the parameters passed are valid.
     *
     * @return bool Returns true if no exception are thrown
     */
    public function validateConfiguration(array $configuration): bool
    {
        return isset(
            $configuration['title'],
            $configuration['description'],
            $configuration['api_url'],
            $configuration['api_user'],
            $configuration['pos_id'],
            $configuration['initial_order_status'],
            $configuration['cancelled_order_status'],
            $configuration['success_order_status'],
            $configuration['forumpay_log_debug'],
            $configuration['accept_zero_confirmations'],
            $configuration['enabled']
        );
    }

    /**
     * Validate and set POS ID
     *
     * @param array $errors
     * @param array $configuration
     *
     * @return void
     */
    private function setPosIdField(array &$errors, array &$configuration): void
    {
        $posId = $configuration['pos_id'];

        if (1 === preg_match('/^[A-Za-z0-9._-]+$/', $posId)) {
            $this->configuration->set(static::FORUMPAY_POS_ID, $posId);
        } else {
            $errors[] = sprintf(
                '%s %s',
                static::FORUMPAY_POS_ID,
                'POS ID field includes invalid characters. Allowed are: A-Za-z0-9._-'
            );
        }
    }

    /**
     * Validate and set API url override
     *
     * @param array $errors
     * @param array $configuration
     *
     * @return void
     */
    public function setApiUrlOverrideField(array &$errors, array &$configuration): void
    {
        $apiUrlOverride = $configuration['api_url_override'];
        if (empty($apiUrlOverride)) {
            return;
        }
        if (false === filter_var($apiUrlOverride, FILTER_VALIDATE_URL)) {
            $errors[] = sprintf(
                '%s %s',
                static::FORUMPAY_API_URL_OVERRIDE,
                'Custom environment URL must be valid URL.'
            );
        } else {
            $this->configuration->set(static::FORUMPAY_API_URL_OVERRIDE, $apiUrlOverride);
        }
    }

    /**
     * Sets Api Key if the value is not set or the existing value differs from new Api Key value,
     * otherwise does nothing.
     *
     * @param array $configuration
     * @return void
     */
    private function setApiKey(array &$configuration): void
    {
        $apiKey = $configuration['api_key'];
        $existingApiKey = $this->configuration->get(static::FORUMPAY_API_KEY);
        if ($apiKey === null && $existingApiKey !== null) {
            return;
        }
        if ($apiKey !== null && ($existingApiKey === null || $apiKey !== $existingApiKey)) {
            $this->configuration->set(static::FORUMPAY_API_KEY, $apiKey);
        }
    }
}
