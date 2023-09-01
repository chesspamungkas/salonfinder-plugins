<?php
namespace SF\controllers\v1;

use SF\core\Constants;
use SF\core\base\Controller;
use SF\core\Ulti;
use SF\models\Merchant;
use SF\models\MerchantPA;
use SF\models\Product;

class MerchantController extends Controller {

  protected $rest_base = '/merchant';
  protected $rest_version = '/v1';

  protected $customFields = [
    'logoSrc'=>[
      'logo',
      'advertiser_logo'
    ],
    'logoAlt'=>[
      'logoAlt',
      'advertiser_logo_alt'
    ],
    'description'=>[
      'description',
      'advertiser_description'
    ],
    'commission'=>[
      'commission',
      'advertiser_commission'
    ],
    'email'=>[
      'email',
      'advertiser_email'
    ],
    'url'=>[
      'websiteURL',
      'advertiser_website_url'
    ],
    'terms'=>[
      'companyTerm',
      'advertiser_company t&c'
    ],
    'status'=>[
      'active',
      'advertiser_active'
    ],
    'catalogueVisibility'=>'catalogueVisibility',
	  'password'=>'password'
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
  }

  public function updateOrCreateBySQS($body) {

  }

  public function updateOrCreate($req) {
	  $foreignID = 0;
	  if($req instanceof \WP_REST_Request) {
		  $id = $req['id'];
		  $jsonBody = $req->get_json_params();
	  } else {
			$merchant = Merchant::findByForeignKey($req['id']);
      $foreignID = $req['id'];
		  if($merchant instanceof Merchant) {
			  $id = $merchant->term_id;
		  } else if($merchant instanceof \WP_Term) {
			  $id = $merchant->term_id;
		  } else {
        $merchant = get_term_by('name', $req['name'], Merchant::$TERM_NAME, ARRAY_A);
        if(!($merchant instanceof \WP_Error ))
          $id = $merchant['term_id'];
      }
		  $jsonBody = $req;
	  }

    $merchantTerm = null;
    

    $merchant = new Merchant([
      'term_id'=>$id?:0,
      'name'=>$jsonBody['name'],
      'description'=>$jsonBody['description']
    ]);
      
    $merchantTerm = $merchant->save();
    if ($merchantTerm) {
      $termID = Merchant::$TERM_NAME.'_'.$merchantTerm['term_id'];
      
      $this->saveCustomFields($termID, $jsonBody);
      $merchantDetails = get_term($merchantTerm['term_id'], Merchant::$TERM_NAME, ARRAY_A);   
       
      $merchantPA = MerchantPA::findBySlugNew($merchantDetails['slug']);
      if(!$merchantPA) {
        $merchantPA = new MerchantPA([
          'name'=>$jsonBody['name'],
          'description'=>$jsonBody['description']
        ]);
        $merchantPA->addMerchantID($merchantDetails['term_id']);
        $merchantPA->addMerchantSlug($merchantDetails['slug']);
        $attributeTerm = $merchantPA->save();        
      }

      $merchant->addAttrID($merchantPA->term_id);
      $merchant->addAttrSlug($merchantPA->slug);
      // $merchant->addAttrID($merchantTerm['term_id']);
      // $merchant->addAttrSlug($merchantTerm['slug']);
	    $merchant->addForeignKeyID($foreignID);
      $merchant->saveMetaData($merchantDetails['term_id']);
      if($jsonBody['logoSrc']) {
        $attachmentID = Ulti::insert_image_from_url($jsonBody['logoSrc']);
        update_field('image', $attachmentID, $termID);
      }
	    if($req instanceof \WP_REST_Request) {
		    $response = new \WP_REST_Response();
		    $response->set_data([
			    'result'=>'success',
			    'data'=> [
				    'id'=>$merchantTerm['term_id'],
            'slug'=>$merchantDetails['slug'],
            'term_id'=>$merchantTerm['term_id'],
			    ]
		    ]);
	    } else {
		    $response = [
			    'result'=>'success',
			    'data'=> [
            'term_id'=>$merchantTerm['term_id'],
				    'id'=>$merchantTerm['term_id'],
				    'slug'=>$merchantDetails['slug'],
			    ]
		    ];
	    }
    } else {
      print_r($jsonBody);
      die();
      $response->set_status(403);
      $response->set_data($jsonBody);
      return $response;
    }
    return $response;
  }
}
