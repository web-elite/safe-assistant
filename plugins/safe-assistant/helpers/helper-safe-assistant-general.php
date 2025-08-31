<?php

if (! function_exists('sa_get_option')) {
    /**
     * Get an option from Codestar options array.
     *
     * @param string $option   Option key
     * @param mixed  $default  Default value
     * @return mixed
     */
    function sa_get_option($option = '', $default = null)
    {
        $options = get_option(SAFE_ASSISTANT_SLUG . '-settings');
        return (isset($options[$option])) ? $options[$option] : $default;
    }
}
