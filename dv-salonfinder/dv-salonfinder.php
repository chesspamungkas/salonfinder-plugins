<?php

/**
 * Plugin Name: DailyVanity Salon Finder
 * Plugin Slug: dv-salonfinder
 * Plugin URI: https://yourdomain.com
 * Version: 1.0.0
 * Author: Catur Pamungkas
 */

require_once(__DIR__ . '/vendor/autoload.php');

if (!defined('ABSPATH')) exit;
define('SF_BASEPATH', plugin_dir_path(__FILE__) . DIRECTORY_SEPARATOR . 'src');
define('SF_BASEURL', plugin_dir_url(__FILE__) . DIRECTORY_SEPARATOR . 'src');
$builder = new \DI\ContainerBuilder();
$builder->addDefinitions([
  SF\core\SalonFinder::class => DI\autowire(SF\core\SalonFinder::class)
]);
$container = $builder->build();
$salonFinder = $container->get(SF\core\SalonFinder::class);
add_action('init', [$salonFinder, 'init']);
