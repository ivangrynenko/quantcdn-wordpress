<?php

use Quant\Client;

if (!empty($_SERVER['HTTP_QUANT_TOKEN'])) {
    // This is ensures content renders locally without redirection (e.g via localhost webserver with a Hostname set).
    // @todo: Validate the Quant-Token (e.g for Draft content and prevent external abuse).
    remove_action('template_redirect', 'redirect_canonical');
}


if (!function_exists('quant_get_options')) {
    /**
     * Return the plugin settings/options
     *
     * @return array
     */
    function quant_get_options()
    {
        return get_option(QUANT_SETTINGS_KEY);
    }
}


if (!function_exists('quant_is_enabled')) {
    /**
     * Return if Quant is activated or not
     *
     * @return bool
     */
    function quant_is_enabled()
    {
        $options = quant_get_options();
        return boolval($options['enabled']);
    }
    quant_is_enabled();
}



if (!function_exists('quant_fire_webhook_save_post')) {
    /**
     * Fire a request to the webhook when a post is saved.
     *
     * @param int $id
     * @return void
     */
    function quant_fire_webhook_save_post($id)
    {
        // @todo: Support draft posts
        if (get_post_status($id) !== 'publish' || !quant_is_enabled()) {
            return;
        }

        $client = new Client();
        $client->sendPost($id);
    }
    add_action('save_post', 'quant_fire_webhook_save_post');
}

if (!function_exists('quant_fire_webhook_unpublish_post')) {
    /**
     * Unpublish the route via Quant API on deletion.
     *
     * @return void
     */
    function quant_fire_webhook_unpublish_post($id)
    {
        if (!quant_is_enabled()) {
            return;
        }

        $client = new Client();
        $permalink = wp_make_link_relative(get_permalink($id));

        // Remove __trashed from permalinks if present.
        $permalink = preg_replace('/__trashed.*$/', '', $permalink);
        $client->unpublish($permalink);

    }
    add_action('trashed_post', 'quant_fire_webhook_unpublish_post');
}