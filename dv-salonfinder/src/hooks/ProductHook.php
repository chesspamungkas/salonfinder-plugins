<?php
namespace SF\hooks;

use SF\core\interfaces\IHooks;
use SF\models\Merchant;
use SF\models\ProductVariant;
use SF\models\Product;

class ProductHook implements IHooks {

  public function registerHooks() {
    // TODO: Implement register() method.
    //add_action( 'woocommerce_update_product', [$this, 'updatePrice'], 10, 1 );
    //add_action( 'woocommerce_new_product', [$this, 'updatePrice'], 10, 1 );
    // add_action('woocommerce_update_product_variation', [$this, 'updateMeta', 10, 1]);
    add_filter( 'woocommerce_product_data_store_cpt_get_products_query', [$this,'handleProductSearchByMerchant'], 10, 2 );
  }

  public function updatePrice($productID) {
    $product = new Product($productID);
    $vProducts = $product->get_available_variations();
    print_r($vProducts);
    $productPrice = [];
    $price = $product->get_regular_price();
    if($product->is_on_sale()) {
      $price = $product->get_sale_price();
    }
    $productPrice[] = [
      'id'=>$product->ID,
      'price'=>$price
    ];
    foreach($vProducts as $vProduct) {
      $productPrice[] = [
        'id'=>$vProduct->ID,
        'price'=>$price
      ];
    }
    print_r($productPrice);
    die();

    foreach($productPrice as $_productPrice) {
      $this->addMeta($_productPrice['id'], Product::PRODUCT_CURRENT_PRICE, $_productPrice['price']);
    }
  }

  function addMeta($id, $key, $value) {
    $currentValue = get_post_meta($id, $key, true);
    if($currentValue) {
      update_post_meta($id, $key, $value, $currentValue);
    } else {
      add_post_meta($id, $key, $value, true);
    }
  }

  public function handleProductSearchByMerchant($query, $query_vars) {
    switch($query_vars) {
      case "merchant" : $query['tax_query'][] = array(
        'taxonomy' => Merchant::MERCHANT_TERM,
        'field'=>'slug',
        'terms' => esc_attr( $query_vars['merchant'] )
      );
    }
    return $query;
  }
}