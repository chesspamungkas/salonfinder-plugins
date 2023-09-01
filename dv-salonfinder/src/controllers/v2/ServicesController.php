<?php
namespace SF\controllers\v2;

use SF\core\base\Controller;
use SF\core\Ulti;
use SF\models\ProductVariant;
use SF\models\Product;

class ServicesController extends Controller {

  protected $rest_base = '/services';
  protected $rest_version = '/v2';

  protected $customFields = [
    'name',
    'image',
    'description',
    'type',
    'subType',
    'terms',
    'duration',
    'outletIDs',
    'status',
    'advertiserID',
    'price',
    'salesPrice',
    'preview'
  ];

  private $ulti; 

  public function __construct(Ulti $ulti) {
    //parent::__construct();
    $this->ulti = $ulti;
  }

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
  }

  public function setSale(\WP_REST_Request $req) {
    $id = $req['id']?:0;
    $jsonBody = $req->get_json_params();
    $product = new Product($id);
    if($product) {
      $product->set_sale_price($jsonBody['price']);
      $product->set_date_on_sale_from($jsonBody['from']);
      $product->set_date_on_sale_to($jsonBody['to']);
      $product->save();

    }
  }

  public function updateOrCreate(\WP_REST_Request $req) {
    $id = $req['id']?:0;
    $jsonBody = $req->get_json_params();
    $response = new \WP_REST_Response();
    $response->set_data(Product::updateOrCreateViaJSON($jsonBody));
    return $response;
  }
}
