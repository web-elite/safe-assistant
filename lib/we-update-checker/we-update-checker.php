<?php
/**
 * Universal Plugin Updater
 * 
 * Usage:
 * $updater = new Updater(__FILE__, [
 *     'slug'   => 'safe-assistant',
 *     'source' => 'github',
 *     'repo'   => 'web-elite/safe-assistant',
 *     'branch' => 'main',
 *     'icon'   => 'https://example.com/icon.png',
 * ]);
 * $updater->init();
 */

if (!class_exists('WE_Updater')) {
    class WE_Updater {
        protected $plugin_file;
        protected $plugin_slug;
        protected $plugin_basename;
        protected $config;
        protected $source;

        public function __construct($plugin_file, array $config) {
            $this->plugin_file     = $plugin_file;
            $this->plugin_slug     = $config['slug'];
            $this->plugin_basename = plugin_basename($plugin_file);
            $this->config          = $config;

            $this->load_source();
        }

        protected function load_source() {
            $source = strtolower($this->config['source'] ?? 'github');
            $class  = ucfirst($source) . 'Updater';
            $file   = __DIR__ . "/Sources/{$class}.php";

            if (file_exists($file)) {
                require_once $file;
                $this->source = new $class($this->plugin_file, $this->config);
            }
        }

        public function init() {
            if (!$this->source) {
                return;
            }
            add_filter('site_transient_update_plugins', [$this, 'check_for_update']);
            add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
        }

        public function check_for_update($transient) {
            return $this->source->check_for_update($transient, $this->plugin_basename);
        }

        public function plugin_info($result, $action, $args) {
            if ($args->slug !== $this->plugin_slug) {
                return $result;
            }
            return $this->source->plugin_info($result, $action, $args);
        }
    }
}
