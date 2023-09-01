<?php
namespace SF\core\base;

abstract class Controller extends \WP_REST_Controller {

  abstract protected function routes();
  protected $customFields = [];

  const REST_BASE='dvsalon';

  public function update_meta_data($termID, $key, $value, $unique = false) {
    
  }

  public function saveCustomFields($termID, $body, $customFields = null, $overrideCustomFields = []) {
    if(!$body)
      return;
    if (!$customFields || count($customFields) <=0) {
      $customFields = $this->customFields;
    }
    
    foreach ($customFields as $key => $fieldNames) {
      if(isset($body[$key])) {
        $value = $body[$key];
        foreach($overrideCustomFields as $overrideCustomField=>$fn) {
          if ($key == $overrideCustomField) {
            $value = $fn($body[$key], $body);
          }
        }
        if($value && $value!="") {
          if(!is_array($fieldNames)) {
            $fieldNames = [$fieldNames];
          }
          foreach($fieldNames as $fieldName) {
            update_field($fieldName, $value, $termID);
          }
        }
      }
    }
  }

  public function register_routes() {
    $this->namespace = self::REST_BASE.$this->rest_version;
    add_action( 'rest_api_init', function () {
      $routes = $this->routes();
      foreach($routes as $routeConfig) {
        $regRoute = array_shift($routeConfig);
        $routeConfig['permission_callback'] = function() {
          return true;
        };
        register_rest_route( $this->namespace, $this->rest_base.$regRoute, $routeConfig);
      }
    });
  }
}