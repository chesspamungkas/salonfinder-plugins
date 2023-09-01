<?php
namespace SF\hooks;

use SF\core\interfaces\IStaticHook;
use SF\models\Merchant;

class ProductOptionName implements IStaticHook {

  public static function registerHooks() {
    $model = new ProductOptionName;
    add_filter( 'woocommerce_variation_option_name', [$model, 'getTermName'], 10, 1 ); 
  }

  public function getTermName($termID) {
    $merchant = Merchant::findByID($termID);
    if($merchant) {
      return $merchant->name;
    }
    return $termID;
  }
}