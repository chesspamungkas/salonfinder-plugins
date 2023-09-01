<?php

namespace SF\core;

use SF\controllers\v1\MerchantController;
use SF\controllers\v1\OutletController;
use SF\controllers\v1\ServicesController;
use SF\controllers\v1\CollectionController;
use SF\controllers\v2\MerchantController as MerchantV2Controller;
use SF\controllers\v2\OutletController as OutletV2Controller;
use SF\controllers\v2\ServicesController as ServicesV2Controller;
use SF\controllers\v2\CollectionController as CollectionV2Controller;
use SF\hooks\ProductHook;
use SF\models\Merchant;
use SF\models\Product;
use SF\models\Collection;
use SF\schedules\AdvertiserSchedule;
use SF\schedules\OutletSchedule;
use SF\schedules\ServicesSchedule;
use SF\schedules\PromoSchedule;
use SF\schedules\CollectionSchedule;
use SF\shortcode\AddToCartBtn;
use SF\shortcode\ListBranches;
use SF\hooks\OrderHook;
use SF\hooks\ProductFKSearchHook;
use SF\hooks\ProductOptionName;
use SF\models\OrderItem;
use SF\schedules\OrderVoucherSchedule;

class SalonFinder {

  private $merchantController;
  private $servicesController;
  private $outletController;
  private $collectionController;
  private $productHooks;
  private $orderHooks;
  private $fkHooks;

  public function __construct(
    MerchantController $merchantController,
    ServicesController $servicesController,
    OutletController $outletController,
    CollectionController $collectionController,
    MerchantV2Controller $merchantV2Controller,
    ServicesV2Controller $servicesV2Controller,
    OutletV2Controller $outletV2Controller,
    CollectionV2Controller $collectionV2Controller,
    ProductHook $productHooks,
    OrderHook $orderHooks,
    ProductFKSearchHook $fkHooks
  ) {
    $this->merchantController = $merchantController;
    $this->servicesController = $servicesController;
    $this->outletController = $outletController;
    $this->collectionController = $collectionController;

    $this->merchantV2Controller = $merchantV2Controller;
    $this->servicesV2Controller = $servicesV2Controller;
    $this->outletV2Controller = $outletV2Controller;
    $this->collectionV2Controller = $collectionV2Controller;

    $this->productHooks = $productHooks;
    $this->orderHooks = $orderHooks;
    $this->fkHooks = $fkHooks;
    Constants::initConfig();
    add_filter('cron_schedules',[$this, 'dv_custom_schedule_timing']);
  }

  public function init() {
    $this->registerHooks();
    $this->registerCSSandJS();
    $this->registerCron();
    $this->registerMedia();
    $this->registerAPI();
    $this->registerShortCode();
    OrderItem::init();
  }

  private function registerCron(){
	  AdvertiserSchedule::init();
    OutletSchedule::init();
    ServicesSchedule::init();
    PromoSchedule::init();
    OrderVoucherSchedule::init();
    CollectionSchedule::init();
  }


  private function registerShortCode() {
    ListBranches::registerShortCode();
    AddToCartBtn::registerShortCode();
  }

  private function registerAPI() {
    $this->merchantController->register_routes();
    $this->servicesController->register_routes();
    $this->outletController->register_routes();
    $this->collectionController->register_routes();

    $this->merchantV2Controller->register_routes();
    $this->servicesV2Controller->register_routes();
    $this->outletV2Controller->register_routes();
    $this->collectionV2Controller->register_routes();
  }

  private function registerHooks() {
    $this->productHooks->registerHooks();
    $this->orderHooks->registerHooks();
    $this->fkHooks->registerHooks();
    Merchant::register();
    ProductOptionName::registerHooks();
    $this->collectionV2Controller->registerHooks();
  }

  private function registerCSSandJS() {
    add_action( 'wp_enqueue_scripts', [$this, 'loadJS'] );
    add_action( 'wp_enqueue_scripts', [$this, 'loadCSS'] );
  }

  public function loadJS() {
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script( 'ds-theme-script', get_stylesheet_directory_uri() . '/ds-script.js',
      array( 'jquery' )
    );
    // Mobile menu js
    wp_enqueue_script('ds-menu-script', get_stylesheet_directory_uri() . '/js/mobile-menu.js', array('jquery'));
  }

  public function loadCSS() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_register_style( 'fontawesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css' );
    // wp_register_style( 'custom-css', get_stylesheet_directory_uri().'/css/custom.css' );
    // wp_enqueue_style( 'custom-css');
  }

  private function registerMedia() {
  }

	function dv_custom_schedule_timing($schedules){
		if(!isset($schedules["5min"])){
			$schedules["5min"] = array(
				'interval' => 5*60,
				'display' => __('Once every 5 minutes'));
		}
		if(!isset($schedules["30min"])){
			$schedules["30min"] = array(
				'interval' => 30*60,
				'display' => __('Once every 30 minutes'));
    }
    if(!isset($schedules["2min"])){
			$schedules["2min"] = array(
				'interval' => 2*60,
				'display' => __('Once every 2 minutes'));
		}
		return $schedules;
	}

}
