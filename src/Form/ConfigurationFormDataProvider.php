<?php

declare(strict_types=1);

namespace ForumPay\PaymentGateway\PrestaShopModule\Form;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

class ConfigurationFormDataProvider implements FormDataProviderInterface
{
    /**
     * @var DataConfigurationInterface
     */
    private $configurationDataConfiguration;

    public function __construct(DataConfigurationInterface $configurationDataConfiguration)
    {
        $this->configurationDataConfiguration = $configurationDataConfiguration;
    }

    public function getData(): array
    {
        return $this->configurationDataConfiguration->getConfiguration();
    }

    public function setData(array $data): array
    {
        return $this->configurationDataConfiguration->updateConfiguration($data);
    }
}
