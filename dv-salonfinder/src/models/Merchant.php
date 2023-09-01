<?php
/**
 * Created by PhpStorm.
 * User: monkeymon
 * Date: 2/3/2019
 * Time: 5:12 PM
 */

namespace SF\models;

use SF\core\IShortCode;
use SF\core\TermModels;
use SF\core\WPRegister;

class Merchant extends TermModels implements WPRegister {
  const MERCHANT_TERM = 'merchant';
  const TERM_NAME = 'merchant';
  const ATTR_ID = '___product_merchant_attr_id';
  const ATTR_SLUG = '___product_merchant_attr_slug';

  public static $TERM_NAME = 'merchant';
	public static $FOREIGN_KEY = 'sf_advertiser_id';

  public function getServices($merchantID) {

  }

  public function listBranches($args) {

  }

  public function addAttrID($id) {
    $this->metaData[self::ATTR_ID] = $id;
  }

  public function addAttrSlug($slug) {
    $this->metaData[self::ATTR_SLUG] = $slug;
  }

	public function addForeignKeyID($id) {
		$this->metaData[self::$FOREIGN_KEY] = $id;
	}

  public function addNewTermQuery( $pieces, $taxonomies, $args) {
    if (!isset( $args['outletOnly'] ) || $args['outletOnly'] == null) {
      return $pieces;
    }
    $pieces['where'] .= ' AND tt.parent > 0';
    return $pieces;
  }

  public function randomOutlet( $pieces, $taxonomies, $args) {
    if(isset($args['randomOutlet'])) {
      $pieces['orderby'] = 'ORDER BY RAND()';
      $pieces['order'] = '';
      return $pieces;
    }
    return $pieces;
  }

  public static function delete($id) {
    $merchantModel = self::findByForeignKey($id);
    if(!$merchantModel) {
      return [
        'result'=>'failed',
        'message'=>'Merchant not found'
      ];
    }
    $args = array(
      'category' => array( $merchantModel->slug ),
    );
  
    $productModels = wc_get_products( $args );
    foreach($productModels as $productModel) {
      $productModel->set_catalog_visibility('hidden');
      $productModel->save();
    }
    $merchantModel->inactivate($merchantModel->term_id);
    $termObjects = get_term_children($id, Merchart::$TERM_NAME);
    if (!($termObjects instanceof \WP_Error)) {
      foreach ($termObjects as $term) {
        $merchantModel->inactivate($term['term_id']);
      }
    }
    return $merchantModel;
  }

  public static function register()
  {
    $model = new Merchant();
    add_filter( 'terms_clauses', [$model, 'addNewTermQuery'], 10, 3);
    add_filter( 'terms_clauses', [$model, 'randomOutlet'], 10, 3);
  }

  public static function unregister()
  {
    $model = new Merchant();
    remove_filter( 'terms_clauses', [$model, 'addNewTermQuery'], 10);
    remove_filter( 'terms_clauses', [$model, 'randomOutlet'], 10);
  }
}