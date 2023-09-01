<?php
namespace SF\controllers\v2;

use SF\core\Constants;
use SF\core\base\Controller;
use SF\core\IRestAPI;
use SF\core\Ulti;
use SF\models\Merchant;
use SF\models\Outlet;
use SF\models\OutletPA;
use SF\models\Product;

class OutletController extends Controller {

  protected $rest_base = '/outlet';
  protected $rest_version = '/v2';

  protected $customFields = [
    'name'=>'outlet_brandname',
    'code'=>'outlet_code',
    'image'=>'outlet_featured_image',
    'imageAlt'=>'outlet_image_alt',
    'address'=>'outlet_address',
    'postalCode'=>'outlet_postalcode',
    'description'=>'outlet_description',
    'creditCard'=>'outlet_creditcard',
    'email'=>'outlet_email',
    'contact'=>'outlet_contact',
    'active'=>'outlet_active',
    'shopImages'=>'outlet_shopimages',
    'opsHours'=>'outlet_operating_hours',
    'status'=>'active'
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
      $attrID = get_field('outlet_attr_id', $termID);
      wp_delete_term($id, Product::MERCHANT_TERM);
      wp_delete_term($attrID, Product::OUTLET_ATTR);
    }
  }

  public function get(\WP_REST_Request $request) {
    $id = $request['id'];
  }

  public function updateOrCreate(\WP_REST_Request $req) {
    $jsonBody = $req->get_json_params();
    $merchantID = $jsonBody['merchantID'];
    $response = new \WP_REST_Response();
    if (!$merchantID) {
      $response->set_status(400);
      $response->set_data([
        'result'=>"failed",
        "message"=>"Merchant not found",
      ]);
      return $response;
    }
    $merchant = new Merchant();
    if($merchant->find($merchantID) instanceof \WP_Error) {
      $response->set_status(400);
      $response->set_data([
        'result'=>"failed",
        "message"=>"Merchant not found",
      ]);
    } else {
      if($jsonBody['id']) {
        $response->set_status(200);
      } else {
        $response->set_status(201);
      }
      $response->set_data(Outlet::updateOrCreateByJSON($jsonBody));
    }
    return $response;
  }



  
}
