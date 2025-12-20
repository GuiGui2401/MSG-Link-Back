<?php

use App\Models\Setting;

if (!function_exists('setting')) {
    /**
     * Get a setting value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }
}

if (!function_exists('settings_by_group')) {
    /**
     * Get all settings by group
     *
     * @param string $group
     * @return array
     */
    function settings_by_group(string $group)
    {
        return Setting::getByGroup($group);
    }
}

if (!function_exists('is_premium_enabled')) {
    /**
     * Check if premium mode is enabled
     *
     * @return bool
     */
    function is_premium_enabled()
    {
        return (bool) Setting::get('premium_enabled', true);
    }
}

if (!function_exists('reveal_anonymous_price')) {
    /**
     * Get the price to reveal anonymous sender
     *
     * @return float
     */
    function reveal_anonymous_price()
    {
        return (float) Setting::get('reveal_anonymous_price', 500);
    }
}
