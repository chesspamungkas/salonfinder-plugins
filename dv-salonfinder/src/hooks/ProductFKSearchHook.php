<?php 
namespace SF\hooks;

use SF\core\interfaces\IHooks;
use SF\core\Constants;
use Aws\Sns\SnsClient; 
use Aws\Exception\AwsException;
use SF\models\Product;

class ProductFKSearchHook implements IHooks {

  public function addingForeignKeyToSearch( $query, $query_vars ) {
    if ( ! empty( $query_vars['fk'] ) ) {
      $query['meta_query'][] = array(
        'key' => Product::PRODUCT_FOREIGN_KEY,
        'value' => esc_attr( $query_vars['fk'] ),
      );
    }
    return $query;
  }

  public function registerHooks() {
    add_filter( 'woocommerce_product_data_store_cpt_get_products_query', [$this, 'addingForeignKeyToSearch'], 10, 2 );
  }
}
  