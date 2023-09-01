<?php
/**
 * Created by PhpStorm.
 * User: monkeymon
 * Date: 2/3/2019
 * Time: 5:13 PM
 */

namespace SF\models;


use SF\core\TermModels;
use SF\core\Ulti;

use Aws\Credentials\Credentials;
use SF\core\Constants;
use Aws\Sqs\SNSClient;
use Aws\Exception\AwsException;

class Outlet extends TermModels {
  const ATTR_ID = '___product_outlet_attr_id';
  const ATTR_SLUG = '___product_outlet_attr_slug';

  const PRODUCT_COUNT = '___product_count';

  public static $TERM_NAME = 'merchant';
  public static $FOREIGN_KEY = 'sf_outlet_id';
  

  private $customFields = [
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
    'shopImagesV2'=>'outlet_shopimages_v2',
    'imageV2'=>'outlet_featured_image_v2',
  ];

  public function addAttrID($id) {
    $this->metaData[self::ATTR_ID] = $id;
  }

  public function addAttrSlug($slug) {
    $this->metaData[self::ATTR_SLUG] = $slug;
  }

  public function addProductCount($count) {
    $this->metaData[self::PRODUCT_COUNT] = $count;
  }

	public function addForeignKeyID($id) {
		$this->metaData[self::$FOREIGN_KEY] = $id;
  }

  public function getAllProducts() {
    $queryAry['tax_query'] = array(
      'limit'=>-1,
      array(
        'taxonomy' => Outlet::$TERM_NAME,
        'field'    => 'id',
        'terms'    => $this->term_id,
      ),
    );
    return wc_get_products($queryAry);
  }

  public static function updateOrCreateByJSON($jsonBody) {
    $model = new Outlet();
    return $model->updateOrCreateBySQS($jsonBody);
  }
  
  public function updateOrCreateBySQS($jsonBody) {
    $foreignID = $jsonBody['id'];
    $outlet = null;
    $outlet = $this->findByForeignKey($foreignID);
    $merchant = Merchant::findByForeignKey($jsonBody['merchantID']);
    $id = 0;

    if($merchant instanceof \WP_Error || !$merchant) {
      throw new \WP_Error(404, 'Merchant not found', $jsonBody);
    }
    if($outlet instanceof \WP_Error || !$outlet) {
      $outletName = $merchant->name .' - '.$jsonBody['name'];    
      $outlet = get_term_by('name', $outletName, Outlet::$TERM_NAME, ARRAY_A);
      if(!($outlet instanceof \WP_Error ))
        $id = $outlet['term_id'];
    } else {
      $id = $outlet->term_id;
      $outletName = $outlet->name;
    }
		$outletModel = new Outlet([
      'term_id'=>$id,
      'name'=>$outletName,
      'description'=>$jsonBody['description'],
      'parent'=>$merchant->term_id
    ]);
    $outletModelTerm = $outletModel->save();
    $outlet = get_term($outletModelTerm['term_id'], Outlet::$TERM_NAME, ARRAY_A);
    $jsonBody['name'] = $outletName;
    $this->__commonUpdateOrCreate($outlet, $jsonBody, $foreignID);
    return [
      'result'=>'success',
      'data'=> $outlet
    ];
  }


  public static function delete($id) {
    $outletModel = self::findByForeignKey($id);
    if(!$outletModel) {
      return [
        'result'=>'failed',
        'message'=>'Outlet not found'
      ];
    }
    $args = array(
      'merchant' => array( $outletModel->slug ),
      'limit'=>-1
    );
    $productModels = wc_get_products($args);
    foreach($productModels as $productModel) {
      $childrenIDs = $productModel->get_visible_children();
      foreach($childrenIDs as $childrenID) {
        $productVariant = new ProductVariant($childrenID);
        $attr = $productVariant->get_variation_attributes();
        if(isset($attr['attribute_branch']) && $attr['attribute_branch'] == $outletModel->term_id) {
          wp_update_post(array(
            'ID'    =>  $childrenID,
            'post_status'   =>  'private'
          ));
        }
      }
      $addingMerchantID = [];
      $existingMerchants = wp_get_post_terms($productModel->get_id(), Merchant::$TERM_NAME);
      foreach($existingMerchants as $existingMerchant) {
        if($outletModel->term_id != $existingMerchant->term_id) {
          $addingMerchantID[] = $existingMerchant->term_id;
        }
      }
      wp_set_post_terms($productModel->get_id(), $addingMerchantID, Merchant::$TERM_NAME, false);
    }
    $outletModel->inactivate($outletModel->term_id);
    $outlet = get_term($outletModel->term_id, Outlet::$TERM_NAME, ARRAY_A);
    return [
      'result'=>'success',
      'data'=> $outlet
    ];
  }

  public function refreshOutletProducts($outletTerm, $newProducts) {
    $excludeIDs = [];
    foreach($newProducts as $newProduct) {
      $excludeIDs[] = $newProduct->get_id();
    }
    $args = array(
      'merchant' => array( $outletTerm['slug'] ),
      'limit'=>-1,
      'exclude'=> $excludeIDs
    );  
    $existingProductModels = wc_get_products( $args );
    foreach($existingProductModels as $existingProductModel) {
      wp_remove_object_terms($existingProductModel->get_id(),$outletTerm['id'], Merchant::$TERM_NAME);
    }

    $args = array(
      'merchant' => array( $outletTerm['slug'] ),
      'limit'=>-1,
      'include'=> $excludeIDs
    );  
    $existingProductModels = wc_get_products( $args );
    $addingProductIDs = $excludeIDs;

    foreach($existingProductModels as $existingProductModel) {
      $removeKey = array_search($existingProductModel->get_id(), $addingProductIDs);
      if($removeKey !== false) {
        unset($addingProductIDs[$removeKey]);
      }
    }
    foreach($addingProductIDs as $addingProductID) {
      wp_set_post_terms($addingProductID,[$outletTerm['id']], Merchant::$TERM_NAME, true);
    }
  }

  public static function addBackOutlet($id) {
    $outletModel = self::findByForeignKey($id);
    if(!$outletModel) {
      return [
        'result'=>'failed',
        'message'=>'Outlet not found'
      ];
    }
    $parentModel = get_term( $outletModel->parent, Outlet::$TERM_NAME );
    $args = array(
      'merchant' => array( $parentModel->slug ),
      'limit'=>-1
    );  
    $productModels = wc_get_products( $args );
    foreach($productModels as $productModel) {
      $childrenIDs = $productModel->get_children();
      $found = false;
      foreach($childrenIDs as $childrenID) {
        $productVariant = new ProductVariant($childrenID);
        $attr = $productVariant->get_variation_attributes();
        if(isset($attr['attribute_branch']) && $attr['attribute_branch'] == $outletModel->term_id) {
          wp_update_post(array(
            'ID'    =>  $childrenID,
            'post_status'   =>  'publish'
          ));
          $found = true;
        }
      }
      if($found) {
        $addingMerchantID = [$outletModel->term_id];
        $existingMerchants = wp_get_post_terms($productModel->get_id(), Merchant::$TERM_NAME);
        foreach($existingMerchants as $existingMerchant) {
          $addingMerchantID[] = $existingMerchant->term_id;
        }
        wp_set_post_terms($productModel->get_id(), $addingMerchantID, Merchant::$TERM_NAME, false);
      }
      
    }
    $outletModel->activate($outletModel->term_id);
    $outlet = get_term($outletModel->term_id, Outlet::$TERM_NAME, ARRAY_A);
    return [
      'result'=>'success',
      'data'=> $outlet
    ];;
  }

  public function __commonUpdateOrCreate($outlet, $jsonBody, $foreignID=0) {
    $attachmentID = null;
    if($jsonBody['image'])
      $attachmentID = Ulti::insert_image_from_url($jsonBody['image'], $jsonBody['imageAlt']);
    $outletID = self::$TERM_NAME.'_'.$outlet['term_id'];
    $this->saveCustomFields($outletID, $jsonBody, $this->customFields, [
      'shopImages'=> function($value, $body) {
        $attachmentIDs = [];
        foreach ($value as $imageURL) {
          if($imageURL)
            $attachmentIDs[] = Ulti::insert_image_from_url($imageURL, $body['imageAlt']);
        }
        return $attachmentIDs;
      }, 
      'opsHours'=> function($value, $body) use($outletID) {
        delete_field($this->customFields['opsHours'],  $outletID);
        foreach ($value as $dayInfo) {
          $hour = [
            "day"=>$dayInfo['key'],
            "open_time"=>$dayInfo['starthr'],
            "close_time"=>$dayInfo['endhr'],
            "is_outlet_closed"=>$dayInfo['closed']
          ];
          add_row($this->customFields['opsHours'], $hour, $outletID);
        }

        return $value;
    }]);
    
    update_field('image', $attachmentID, $outletID);    
    $outletModel = new Outlet();
    $outletModel->setData($outlet);
    $outletModel->addForeignKeyID($foreignID);
    $newOutlet = $outletModel->save();
    if($jsonBody['services'] && is_array($jsonBody['services']) && $newOutlet) {
      $products = [];
      foreach($jsonBody['services'] as $apiService) {
        $product = Product::findByForeignKey($apiService['id']);
        if($product) {
          $products[] = $product;
        }
      }
      
      $outletModel->refreshOutletProducts($newOutlet, $products);
      foreach($products as $product) {
        $product->refreshAttributes();
      }
    }
    
    return $outletModel;
  }
}

