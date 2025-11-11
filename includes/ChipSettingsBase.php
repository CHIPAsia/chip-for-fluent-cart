<?php

namespace FluentCart\App\Modules\PaymentMethods\Chip;
use FluentCart\Api\StoreSettings;
use FluentCart\App\Modules\PaymentMethods\Core\BaseGatewaySettings;

class ChipSettingsBase extends BaseGatewaySettings
{

    public $settings;
    public $methodHandler = 'fluent_cart_payment_settings_chip';


    public static function getDefaults(): array
    {
        return [
            'is_active'            => 'no',
            'payment_mode'         => 'live',
            'brand_id'             => '',
            'secret_key'           => '',
            'public_key'           => '',
            'payment_method_whitelist' => [],
            'email_fallback'       => '',
            'debug'                => 'no',
        ];
    }

    public function isActive(): bool
    {
        return $this->settings['is_active'] == 'yes';
    }

    public function getMode()
    {
        return $this->settings['payment_mode'] ?? 'live';
    }

    public function getPublicKey()
    {
        return $this->settings['public_key'] ?? '';
    }

    public function getApiKey()
    {
        return $this->settings['secret_key'] ?? '';
    }

    public function getBrandId()
    {
        return $this->settings['brand_id'] ?? '';
    }

    public function getPaymentMethodWhitelist()
    {
        return $this->settings['payment_method_whitelist'] ?? [];
    }

    public function getEmailFallback()
    {
        return $this->settings['email_fallback'] ?? '';
    }

    public function isDebugEnabled()
    {
        return ($this->settings['debug'] ?? 'no') === 'yes';
    }

    public function get($key = '')
    {
        $settings = $this->settings;

        if ($key && isset($this->settings[$key])) {
            return $this->settings[$key];
        }
        return $settings;
    }
}

