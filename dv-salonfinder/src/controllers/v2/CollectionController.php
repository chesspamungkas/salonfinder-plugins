<?php
namespace SF\controllers\v2;


use SF\controllers\v1\CollectionController as CollectionV1Controller;
use SF\models\Collection;

class CollectionController extends CollectionV1Controller{
  protected $rest_version = '/v2';

  public function registerHooks() {
    add_filter( 'wpseo_title', [$this, 'SEOTitle'], 10, 1 ); 
    add_filter( 'wpseo_opengraph_title', [$this, 'SEOTitle'], 10, 1 );
    add_filter( 'wpseo_twitter_title', [$this, 'SEOTitle'], 10, 1 );

    add_filter( 'wpseo_metadesc', [$this, 'SEODescription'], 10, 1 ); 
    add_filter( 'wpseo_twitter_description', [$this, 'SEODescription'], 10, 1 ); 
    add_filter( 'wpseo_opengraph_desc', [$this, 'SEODescription'], 10, 1 );

    add_filter( 'wpseo_schema_collectionpage', [$this, 'SEOSchema'] );
    

  }

  public function SEOSchema($data) {
    if(is_tax(Collection::MERCHANT_TERM)) {
      $term_id = get_queried_object()->term_id;
      $model = Collection::findByID($term_id);
      if($returnValue = $model->getMeta('pageTitle')) {
        $data['name'] = $returnValue;
      }
    }
    
    return $data;
  }

  public function SEOTitle($title) {
    if(is_tax(Collection::MERCHANT_TERM)) {
      $term_id = get_queried_object()->term_id;
      $model = Collection::findByID($term_id);
      if($returnValue = $model->getMeta('pageTitle')) {
        $title = $returnValue;
      }
    }
    return $title;
  }

  public function SEODescription($description) {
    if(is_tax(Collection::MERCHANT_TERM)) {
      $term_id = get_queried_object()->term_id;
      $model = Collection::findByID($term_id);
      if($returnValue = $model->getMeta('metaDescription')) {
        $description = $returnValue;
      }
    }
    return $description;
  }
}
