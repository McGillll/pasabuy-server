<?php

namespace App\Repositories;

use App\Interfaces\InvoiceSetting\InvoiceSettingRepositoryInterface;
use App\Models\PasabuySellerInvoiceSetting;

class InvoiceSettingRepository implements InvoiceSettingRepositoryInterface
{
    // Per-request memoization — avoids re-querying the same seller's settings
    // when computing invoice totals across a list of many orders.
    protected static array $cache = [];

    public function getForUser(int $userId)
    {
        if (array_key_exists($userId, static::$cache)) {
            return static::$cache[$userId];
        }

        $setting = PasabuySellerInvoiceSetting::where('user_id', $userId)->first();

        if (! $setting) {
            $setting = new PasabuySellerInvoiceSetting;
            $setting->user_id = $userId;
            $setting->markup_threshold_amount = 1000.00;
            $setting->markup_percentage = 10.00;
            $setting->markup_fixed_amount = 100.00;
            $setting->handling_fee = 0.00;
            $setting->interest_rate = 1.00;
            $setting->interest_period = 'month';
            $setting->interest_base = 'outstanding_balance';
            $setting->is_active = true;
            $setting->save();
        }

        return static::$cache[$userId] = $setting;
    }

    public function update(object $payload, int $userId)
    {
        $setting = $this->getForUser($userId);

        $setting->markup_threshold_amount = $payload->markup_threshold_amount;
        $setting->markup_percentage = $payload->markup_percentage;
        $setting->markup_fixed_amount = $payload->markup_fixed_amount;
        $setting->handling_fee = $payload->handling_fee;
        $setting->interest_rate = $payload->interest_rate;
        $setting->interest_period = $payload->interest_period;
        $setting->interest_base = $payload->interest_base;
        $setting->is_active = $payload->is_active ?? $setting->is_active;
        $setting->save();

        static::$cache[$userId] = $setting;

        return $setting;
    }
}
