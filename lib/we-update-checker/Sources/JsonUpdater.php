<?php

if (!class_exists('JsonUpdater')) {

    class JsonUpdater
    {
        protected $plugin_file;
        protected $config;

        public function __construct($plugin_file, array $config)
        {
            $this->plugin_file = $plugin_file;
            $this->config      = $config;

            WE_Update_Checker_Logger::log("JsonUpdater initialized", ['slug' => $this->config['slug']]);
        }

        protected function get_local_version()
        {
            $plugin_data = get_plugin_data($this->plugin_file);
            $version = $plugin_data['Version'] ?? '0.0.0';
            WE_Update_Checker_Logger::log("Local version detected", ['version' => $version]);
            return $version;
        }

        protected function get_remote_info($force_refresh = false)
        {
            $transient_key = $this->config['slug'] . '_json_release';

            if ($force_refresh) {
                delete_transient($transient_key);
                WE_Update_Checker_Logger::log("Force refresh transient for {$this->config['slug']}");
            }

            $cached = get_transient($transient_key);
            if ($cached) {
                WE_Update_Checker_Logger::log("Using cached remote info", $cached);
                return $cached;
            }

            $url = $this->config['json_url'];
            if (!$url) {
                WE_Update_Checker_Logger::log("No JSON URL configured for {$this->config['slug']}");
                return null;
            }

            $response = wp_remote_get($url, [
                'headers' => ['User-Agent' => 'WordPress-Json-Updater']
            ]);

            if (is_wp_error($response)) {
                WE_Update_Checker_Logger::log("WP_Error fetching JSON", ['error' => $response->get_error_message()]);
                return null;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!$body || empty($body['version']) || empty($body['zip_url'])) {
                WE_Update_Checker_Logger::log("Invalid JSON structure", $body);
                return null;
            }

            $remote_info = [
                'version'     => $body['version'],
                'zip_url'     => $body['zip_url'],
                'icon'        => $body['icon'] ?? '',
                'changelog'   => $body['changelog'] ?? '',
                'homepage'    => $body['homepage'] ?? '',
            ];

            set_transient($transient_key, $remote_info, 12 * HOUR_IN_SECONDS);
            WE_Update_Checker_Logger::log("Fetched and cached remote info", $remote_info);

            return $remote_info;
        }

        public function check_for_update($transient, $plugin_basename)
        {
            WE_Update_Checker_Logger::log("check_for_update triggered", ['slug' => $this->config['slug']]);

            if (empty($transient->checked)) return $transient;

            $force = is_admin() && isset($_SERVER['PHP_SELF']) && strpos($_SERVER['PHP_SELF'], 'update-core.php') !== false;

            $local_version = $this->get_local_version();
            WE_Update_Checker_Logger::log("Local version detected", ['version' => $local_version]);

            $remote = $this->get_remote_info($force);
            if (!$remote) {
                WE_Update_Checker_Logger::log("No remote info available, skipping update check");
                return $transient;
            }

            if (version_compare($remote['version'], $local_version, '>')) {
                $obj = new stdClass();
                $obj->slug        = $this->config['slug'];
                $obj->new_version = $remote['version'];
                $obj->url         = $remote['homepage'];
                $obj->package     = $remote['zip_url'];
                $transient->response[$plugin_basename] = $obj;

                WE_Update_Checker_Logger::log("Update available", [
                    'local' => $local_version,
                    'remote' => $remote['version'],
                    'url'   => $remote['zip_url']
                ]);
            } else {
                WE_Update_Checker_Logger::log("No update available", [
                    'local' => $local_version,
                    'remote' => $remote['version']
                ]);
            }

            return $transient;
        }

        public function plugin_info($result, $action, $args)
        {
            WE_Update_Checker_Logger::log("plugin_info requested", ['slug' => $args->slug, 'action' => $action]);

            if ($args->slug !== $this->config['slug']) {
                WE_Update_Checker_Logger::log("plugin_info ignored: slug mismatch");
                return $result;
            }

            $remote = $this->get_remote_info();
            if (!$remote) {
                WE_Update_Checker_Logger::log("plugin_info failed: no remote info");
                return $result;
            }

            $info = (object)[
                'name'          => ucfirst($this->config['slug']),
                'slug'          => $this->config['slug'],
                'version'       => $remote['version'],
                'author'        => "<a href='" . $remote['author_url'] . "'>" . $remote['author'] . "</a>",
                'homepage'      => $remote['homepage'],
                'sections'      => [
                    'description' => $remote['desc'] ?? $remote['description'] ?? '',
                    'changelog'   => nl2br($remote['changelog']),
                ],
                'banners'       => [
                    'low'  => $remote['icon'] ?? '',
                    'high' => $remote['icon'] ?? '',
                ],
                'icons' => [
                    '1x' => $remote['icon'],
                    '2x' => $remote['icon'],
                ],
                'download_link' => $remote['zip_url'],
                'last_updated'  => current_time('mysql'),
            ];

            WE_Update_Checker_Logger::log("plugin_info returned", (array)$info);
            return $info;
        }
    }
}
