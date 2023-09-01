<?php
/**
 * Created by PhpStorm.
 * User: monkeymon
 * Date: 2/3/2019
 * Time: 5:13 PM
 */

namespace SF\models;


use SF\core\TermModels;

class OutletPA extends TermModels {
  const OUTLET_ID = '___product_outlet_id';
  const OUTLET_SLUG = '___product_outlet_slug';
  const TERM_NAME = 'pa_branch';
  public static $TERM_NAME = 'pa_branch';
  

  public function addOutlet($id) {
    $this->metaData[self::OUTLET_ID] = $id;
  }

  public static function findByOutletID($id) {
    $args = [
      'taxonomy'=>OutLetPA::$TERM_NAME,
      'hide_empty'=>false,
      'meta_key'=>OutletPA::OUTLET_ID,
      'meta_value'=>$id
    ];
    $terms = get_terms($args);
    if($terms instanceof \WP_Error) {
      return false;
    }
    if(count($terms)>0) {      
      $term = get_term($terms[0], OutLetPA::$TERM_NAME, ARRAY_A);
		  $class=get_called_class();
		  $model = new $class;
      $model->setData($term);
		  return $model;
    }
    return false;
  }

  public function getOutletID() {
    return get_term_meta($this->term_id, self::OUTLET_ID, true);
  }

  public function getOutlet() {
    $outletID = $this->metaData[self::OUTLET_ID];
    $model = new Outlet();
    if($outletID) {
      return $model->find($outletID);
    } else {
      return $model->findByOldWay($this->slug);
    }
  }

  public function addOutletSlug($slug) {
    $this->metaData[self::OUTLET_SLUG] = $slug;
  }
}