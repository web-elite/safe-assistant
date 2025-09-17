<?php

if (!class_exists('GithubUpdater')) {

class GithubUpdater {

    protected $plugin_file;
    protected $config;
    protected $api_url;
    protected $branch_url;

    public function __construct($plugin_file, array $config) {
        $this->plugin_file = $plugin_file;
        $this->config      = $config;

        $repo   = $config['repo'];
        $branch = $config['branch'] ?? 'main';

        $this->api_url    = "https://api.github.com/repos/{$repo}/releases/latest";
        $this->branch_url = "https://api.github.com/repos/{$repo}/branches/{$branch}";
    }

    protected function get_local_version() {
        $plugin_data = get_plugin_data($this->plugin_file);
        return $plugin_data['Version'] ?? '0.0.0';
    }

    protected function get_remote_info() {

        $transient_key = $this->config['slug'] . '_github_release';
        $cached        = get_transient($transient_key);
        if ($cached) {
            return $cached;
        }

        $headers = [
            'User-Agent' => 'WordPress-Github-Updater'
        ];

        // اگر توکن داده شده بود
        if (!empty($this->config['token'])) {
            $headers['Authorization'] = 'token ' . $this->config['token'];
        }

        $response = wp_remote_get($this->api_url, ['headers' => $headers]);

        if (is_wp_error($response)) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['tag_name'])) {
            return null;
        }

        $version = ltrim($body['tag_name'], 'v');
        $slug    = $this->config['slug'];

        $zip_url = '';
        if (!empty($body['assets'])) {
            foreach ($body['assets'] as $asset) {
                if ($asset['name'] === "{$slug}.zip") {
                    $zip_url = $asset['browser_download_url'];
                    break;
                }
            }
        }

        // fallback به branch zip اگر asset پیدا نشد
        if (!$zip_url) {
            $zip_url = "https://github.com/{$this->config['repo']}/archive/refs/heads/{$this->config['branch']}.zip";
        }

        $remote_info = [
            'version'      => $version,
            'zip_url'      => $zip_url,
            'changelog'    => $body['body'] ?? '',
            'published_at' => $body['published_at'] ?? '',
        ];

        set_transient($transient_key, $remote_info, 12 * HOUR_IN_SECONDS);

        return $remote_info;
    }

    public function check_for_update($transient, $plugin_basename) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $local_version = $this->get_local_version();
        $remote        = $this->get_remote_info();

        if (!$remote) return $transient;

        if (version_compare($remote['version'], $local_version, '>')) {
            $obj = new stdClass();
            $obj->slug        = $this->config['slug'];
            $obj->new_version = $remote['version'];
            $obj->url         = "https://github.com/{$this->config['repo']}";
            $obj->package     = $remote['zip_url'];
            $transient->response[$plugin_basename] = $obj;
        }

        return $transient;
    }

    public function plugin_info($result, $action, $args) {
        if ($args->slug !== $this->config['slug']) {
            return $result;
        }

        $remote = $this->get_remote_info();
        if (!$remote) return $result;

        return (object)[
            'name'          => ucfirst($this->config['slug']),
            'slug'          => $this->config['slug'],
            'version'       => $remote['version'],
            'author'        => '<a href="https://webelitee.ir">AlirezaYaghouti</a>',
            'homepage'      => "https://github.com/{$this->config['repo']}",
            'sections'      => [
                'description' => "Auto-updated plugin from GitHub repository <code>{$this->config['repo']}</code>.",
                'changelog'   => nl2br($remote['changelog']),
            ],
            'banners'       => [
                'low'  => $this->config['icon'] ?? '',
                'high' => $this->config['icon'] ?? '',
            ],
            'download_link' => $remote['zip_url'],
            'last_updated'  => $remote['published_at'],
        ];
    }

}
}
