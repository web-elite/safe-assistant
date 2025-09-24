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

include_once 'we-update-checker-debugbar.php';

if (!class_exists('WE_Updater')) {
    class WE_Updater
    {
        protected $plugin_file;
        protected $plugin_slug;
        protected $plugin_basename;
        protected $config;
        protected $source;

        public function __construct($plugin_file, array $config)
        {
            $this->plugin_file     = $plugin_file;
            $this->plugin_slug     = $config['slug'];
            $this->plugin_basename = plugin_basename($plugin_file);
            $this->config          = $config;

            WE_Update_Checker_Logger::log("Init WE_Updater for {$this->plugin_slug}");
            $this->load_source();
        }

        protected function load_source()
        {
            $source = strtolower($this->config['source'] ?? 'github');
            $class  = ucfirst($source) . 'Updater';
            $file   = __DIR__ . "/Sources/{$class}.php";

            WE_Update_Checker_Logger::log("Loading source: {$class}");

            if (file_exists($file)) {
                require_once $file;
                $this->source = new $class($this->plugin_file, $this->config);
                WE_Update_Checker_Logger::log("Source {$class} loaded successfully");
            } else {
                WE_Update_Checker_Logger::log("Source file not found: {$file}");
            }
        }

        public function init()
        {
            if (!$this->source) {
                WE_Update_Checker_Logger::log("No source available for {$this->plugin_slug}");
                return;
            }
            add_filter('site_transient_update_plugins', [$this, 'check_for_update']);
            add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);

            WE_Update_Checker_Logger::log("Updater hooks initialized for {$this->plugin_slug}");
        }

        public function check_for_update($transient)
        {
            WE_Update_Checker_Logger::log("check_for_update triggered", ['slug' => $this->plugin_slug]);
            return $this->source->check_for_update($transient, $this->plugin_basename);
        }

        public function plugin_info($result, $action, $args)
        {
            WE_Update_Checker_Logger::log("plugin_info triggered", ['slug' => $this->plugin_slug, 'action' => $action]);
            if ($args->slug !== $this->plugin_slug) {
                WE_Update_Checker_Logger::log("plugin_info ignored: slug mismatch", ['expected' => $this->plugin_slug, 'got' => $args->slug]);
                return $result;
            }
            return $this->source->plugin_info($result, $action, $args);
        }
    }
}
