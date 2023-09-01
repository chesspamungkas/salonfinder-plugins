<?php
namespace SF\models;

use SF\core\IShortCode;
use SF\core\TermModels;
use SF\core\WPRegister;

class Collection extends TermModels implements WPRegister {
  const MERCHANT_TERM = 'collection';
  const TERM_NAME = 'collection';
  const ATTR_ID = '___product_collection_attr_id';
  const ATTR_SLUG = '___product_collection_attr_slug';

  public static $TERM_NAME = 'collection';
	public static $FOREIGN_KEY = 'sf_advertiser_id';

  public function getServices($collectionID) {

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
  
  public function addProducts($productIDs) {
    foreach($productIDs as $productID)
      wp_set_post_terms($productID, $this->term_id, self::$TERM_NAME, true);
  }

  public static function delete($id) {
    $collectionModel = self::findByForeignKey($id);
    if(!$collectionModel) {
      return [
        'result'=>'failed',
        'message'=>'Collection not found'
      ];
    }
    $args = array(
      'category' => array( $collectionModel->slug ),
    );
  
    $productModels = wc_get_products( $args );
    foreach($productModels as $productModel) {
      $productModel->set_catalog_visibility('hidden');
      $productModel->save();
    }
    $collectionModel->inactivate($collectionModel->term_id);
    $termObjects = get_term_children($id, Collection::$TERM_NAME);
    if (!($termObjects instanceof \WP_Error)) {
      foreach ($termObjects as $term) {
        $collectionModel->inactivate($term['term_id']);
      }
    }
    return $collectionModel;
  }

  public static function register()
  {
    $model = new Collection();
    add_filter( 'terms_clauses', [$model, 'addNewTermQuery'], 10, 3);
    add_filter( 'terms_clauses', [$model, 'randomOutlet'], 10, 3);
  }

  public static function unregister()
  {
    $model = new Collection();
    remove_filter( 'terms_clauses', [$model, 'addNewTermQuery'], 10);
    remove_filter( 'terms_clauses', [$model, 'randomOutlet'], 10);
  }
}