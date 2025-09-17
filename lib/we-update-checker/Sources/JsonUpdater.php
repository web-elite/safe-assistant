<?php

if (!class_exists('JsonUpdater')) {

class JsonUpdater {

    protected $plugin_file;
    protected $config;

    public function __construct($plugin_file, array $config) {
        $this->plugin_file = $plugin_file;
        $this->config      = $config;
    }

    protected function get_local_version() {
        $plugin_data = get_plugin_data($this->plugin_file);
        return $plugin_data['Version'] ?? '0.0.0';
    }

    protected function get_remote_info() {
        $transient_key = $this->config['slug'] . '_json_release';
        $cached = get_transient($transient_key);
        if ($cached) {
            return $cached;
        }

        $url = $this->config['json_url'];
        if (!$url) return null;

        $response = wp_remote_get($url, [
            'headers' => ['User-Agent' => 'WordPress-Json-Updater']
        ]);

        if (is_wp_error($response)) return null;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!$body || empty($body['version']) || empty($body['zip_url'])) return null;

        $remote_info = [
            'version'     => $body['version'],
            'zip_url'     => $body['zip_url'],
            'icon'        => $body['icon'] ?? '',
            'changelog'   => $body['changelog'] ?? '',
            'homepage'    => $body['homepage'] ?? '',
        ];

        set_transient($transient_key, $remote_info, 12 * HOUR_IN_SECONDS);
        return $remote_info;
    }

    public function check_for_update($transient, $plugin_basename) {
        if (empty($transient->checked)) return $transient;

        $local_version = $this->get_local_version();
        $remote = $this->get_remote_info();
        if (!$remote) return $transient;

        if (version_compare($remote['version'], $local_version, '>')) {
            $obj = new stdClass();
            $obj->slug        = $this->config['slug'];
            $obj->new_version = $remote['version'];
            $obj->url         = $remote['homepage'];
            $obj->package     = $remote['zip_url'];
            $transient->response[$plugin_basename] = $obj;
        }

        return $transient;
    }

    public function plugin_info($result, $action, $args) {
        if ($args->slug !== $this->config['slug']) return $result;

        $remote = $this->get_remote_info();
        if (!$remote) return $result;

        return (object)[
            'name'          => ucfirst($this->config['slug']),
            'slug'          => $this->config['slug'],
            'version'       => $remote['version'],
            'author'        => '<a href="https://webelitee.ir">AlirezaYaghouti</a>',
            'homepage'      => $remote['homepage'],
            'sections'      => [
                'description' => "Auto-updated plugin from custom JSON.",
                'changelog'   => nl2br($remote['changelog']),
            ],
            'banners'       => [
                'low'  => $remote['icon'] ?? '',
                'high' => $remote['icon'] ?? '',
            ],
            'download_link' => $remote['zip_url'],
            'last_updated'  => current_time('mysql'),
        ];
    }
}

}
