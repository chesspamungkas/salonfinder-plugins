<?php
namespace SF\controllers\v1;

use SF\core\Constants;
use SF\core\base\Controller;
use SF\core\Ulti;
use SF\models\Collection;
use SF\models\CollectionPA;
use SF\models\Product;

class CollectionController extends Controller {

  protected $rest_base = '/collections';
  protected $rest_version = '/v1';

  protected $customFields = [
    'startDate'=>'start_date',
    'endDate'=>'end_date',
    'featured'=>'featured',
    'uploadImage'=>'image',
    'pageTitle'=>'pageTitle',
    'metaDescription'=>'metaDescription'
  ];

  public function routes() {
    return [
      ['/(?P<id>\d+)', 'methods'=>'GET', 'callback'=> [$this, 'get']],
      ['/', 'methods'=>'POST', 'callback'=> [$this, 'updateOrCreate']],
      ['/(?P<id>\d+)', 'methods'=>'POST', 'callback'=> [$this, 'updateOrCreate']],
      ['/(?P<id>\d+)', 'methods'=>'DELETE', 'callback'=> [$this, 'delete']],
    ];
  }

  public function delete(\WP_REST_Request $req) {
    $id = $req['id'];
    if ($id) {
      $termObjects = get_term_children($id, Product::MERCHANT_TERM);
      if (!($termObjects instanceof \WP_Error)) {
        foreach ($termObjects as $term) {
          wp_delete_term($term['term_id'], Product::MERCHANT_TERM);
        }
      }
      $termID = Product::MERCHANT_TERM.'_'.$id;
      $attrID = get_field('advertiser_attr_id', $termID);
      wp_delete_term($id, Product::MERCHANT_TERM);
      wp_delete_term($attrID, Product::MERCHANT_ATTR);
    }
  }

  public function get(\WP_REST_Request $request) {
    $id = $request['id'];
    if($req instanceof \WP_REST_Request) {
      $response = new \WP_REST_Response();
      $response->set_data([
        'result'=>'success',
        'data'=> $collectionTerm
      ]);
    } else {
      
    }
  }

  public function updateOrCreateBySQS($body) {

  }

  public function post(\WP_REST_Request $request) {
    $jsonBody = $request->get_json_params();
    $jsonBody['term_id'] = $jsonBody['id'];
    $jsonBody['id'] = '';
    $result = $this->updateOrCreate($jsonBody);
    $res = new \WP_REST_Response();
    $res->set_data($result);
    if($result['result'] === 'failed'){
      $res->set_status(400);
    } else {
      $res->set_status(200);
    }
    return $res;
  }

  public function updateOrCreate($req, $changeVersion) {
	  $foreignID = 0;
    if($req['term_id']) {
      $id = $req['term_id'];
    } else {
      $collection = Collection::findByForeignKey($req['id']);
      $foreignID = $req['id'];
		  if(($collection instanceof \WP_Error) || $collection===null) {
        $collection = get_term_by('name', $req['name'], Collection::$TERM_NAME, ARRAY_A);
        if(!($collection instanceof \WP_Error ) && $collection !== false)
          $id = $collection['term_id'];
		  } else {
			  $id = $collection->term_id;
		  }
    }
    $jsonBody = $req;

    $collectionTerm = null;
    

    $collection = new Collection([
      'term_id'=>$id?:0,
      'name'=>$jsonBody['name'],
      'description'=>$jsonBody['description'],
      'slug'=>$jsonBody['slug']
    ]);
      
    $collectionTerm = $collection->save();
    $response = [
      'result'=>'failed',
      'requestBody'=>$jsonBody
    ];
    if ($collectionTerm) {
      $collectionModel = new Collection($collectionTerm);
      
      $collectionModel->addMetaDatas($jsonBody, $this->customFields, ['uploadImage'=>function($uploadImageObj, $jsonBody) {
        if($uploadImageObj && isset($uploadImageObj['url']) && $uploadImageObj['url'])
          return Ulti::insert_image_from_url($uploadImageObj['url']);
        return "";
      }]);

	    $collectionModel->addForeignKeyID($foreignID);
      $collectionModel->saveMetaData();

      if($jsonBody['services'] && is_array($jsonBody['services']) && count($jsonBody['services'])) {
        $productIDs = [];
        foreach($jsonBody['services'] as $service) {
          $product = Product::findByForeignKey($service['id']);
          if($product) {
            $productIDs[] = $product->get_id();
          }
        }
        $collectionModel->addProducts($productIDs);
      }

      $response = [
        'result'=>'success',
        'data'=> $collectionTerm
      ];
    }
    return $response;
  }
}
