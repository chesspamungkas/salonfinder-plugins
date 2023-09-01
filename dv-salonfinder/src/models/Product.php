<?php 
namespace SF\models;

use SF\core\Constants;
use WP_Query;

class Product extends \WC_Product_Variable {
  const PRODUCT_CAT = 'product_cat';
  const MERCHANT_TERM = 'merchant';
  const MERCHANT_ATTR = 'pa_advertiser';
  const OUTLET_ATTR = 'pa_branch';
  const COLLECTION_TERM = 'collection';
  const COLLECTION_ATTR = 'pa_collection';

  const PRODUCT_CURRENT_PRICE = '_price';
  const PRODUCT_FOREIGN_KEY = 'sf_product_id';

  const PROMOTEXT = '__promotionText';

  private $_outletIDs = [];
  private $_attributes = [];
  private $_merchants = [];
  private $_collections = [];
  private $_searchText = "";

  private $searchMerchantID = 0;
  private $searchCollectionID = 0;

  public function set_category_names($terms) {
    $returnTermIDs = [];
    foreach($terms as $term) {
      if(is_numeric($term)) {
        $returnTermIDs[] = $term;
      } if(is_array($term)) {
        $foundTerm = get_term_by('name', $term['name'], self::PRODUCT_CAT);
        if($foundTerm) {
          $returnTermIDs[] = $foundTerm->term_id;
        }
      }
      else {
        $foundTerm = get_term_by('name', $term, self::PRODUCT_CAT);
        if($foundTerm) {
          $returnTermIDs[] = $foundTerm->term_id;
        }
      }
    }
    $this->set_category_ids($returnTermIDs);
  }

  public function getMerchant() {
    $merchants = wp_get_object_terms($this->get_id(), Merchant::$TERM_NAME);
    foreach($merchants as $merchant) {
      if(!$merchant->parent_id || $merchant->parent_id == 0) {
        return $merchant;
      }
    }
  }

  public function getCollection() {
    $collections = wp_get_object_terms($this->get_id(), Collection::$TERM_NAME);
    foreach($collections as $collection) {
      if(!$collection->parent_id || $collection->parent_id == 0) {
        return $collection;
      }
    }
  }

  public function save() {
    parent::save();
  }

  public function addTitleWhere($where) {
    global $wpdb;
    $searchString = esc_sql($this->_searchText);
    $where .= " and (TRIM(UPPER({$wpdb->posts}.post_title)) = TRIM(UPPER('{$searchString}')))";
    return $where;
  }

  public function findProductByNameAndMerchant($name, $merchantID, $promoName = "") {
    $args = array(
      'post_type' => 'product',
      'post_status'=>['draft', 'pending', 'private', 'publish', 'preview'],
      'tax_query' => array(
        'relation' => 'AND',
        array(
          'taxonomy' => 'merchant',
          'field' => 'term_id',
          'terms' => $merchantID
        )
      ),
    );
    $this->_searchText = $name;
    $this->searchMerchantID = $merchantID;
    add_filter('posts_join', [$this, 'joinMerchant'] );
    add_filter('posts_where', [$this, 'addTitleWhere']);
    $this->_searchText = $name;
    $query = new WP_Query( $args );
    remove_filter('posts_join', [$this, 'joinMerchant'] );
    remove_filter('posts_where', [$this, 'addTitleWhere']);
    if($query->have_posts()) {
      global $post;
      $query->the_post();
      return new Product($post->ID);
    } else if($promoName) {
      return $this->findProductByNameAndMerchant($promoName, $merchantID, null);
    } 
    return new Product();
  }

  public function findProductByNameAndCollection($name, $collectionID, $promoName = "") {
    $args = array(
      'post_type' => 'product',
      'post_status'=>['draft', 'pending', 'private', 'publish', 'preview'],
      'tax_query' => array(
        'relation' => 'AND',
        array(
          'taxonomy' => 'collection',
          'field' => 'term_id',
          'terms' => $collectionID
        )
      ),
    );
    $this->_searchText = $name;
    $this->searchCollectionID = $collectionID;
    add_filter('posts_join', [$this, 'joinCollection'] );
    add_filter('posts_where', [$this, 'addTitleWhere']);
    $this->_searchText = $name;
    $query = new WP_Query( $args );
    remove_filter('posts_join', [$this, 'joinCollection'] );
    remove_filter('posts_where', [$this, 'addTitleWhere']);
    if($query->have_posts()) {
      global $post;
      $query->the_post();
      return new Product($post->ID);
    } else if($promoName) {
      return $this->findProductByNameAndCollection($promoName, $collectionID, null);
    } 
    return new Product();
  }

  public function joinMerchant($join) {
    global $wpdb;
    $join .= " 
    left join {$wpdb->term_relationships} r on r.object_id = {$wpdb->posts}.ID
    inner JOIN {$wpdb->term_taxonomy} merchantTerm ON (merchantTerm.term_id = {$this->searchMerchantID} and r.term_taxonomy_id = merchantTerm.term_taxonomy_id and merchantTerm.taxonomy = 'merchant')";
    return $join;
  }

  public function joinCollection($join) {
    global $wpdb;
    $join .= " 
    left join {$wpdb->term_relationships} r on r.object_id = {$wpdb->posts}.ID
    inner JOIN {$wpdb->term_taxonomy} collectionTerm ON (collectionTerm.term_id = {$this->searchCollectionID} and r.term_taxonomy_id = collectionTerm.term_taxonomy_id and collectionTerm.taxonomy = 'collection')";
    return $join;
  }

  public function saveMerchantAndOutlets() {
    wp_set_post_terms($this->get_id(), $this->_merchants, Merchant::$TERM_NAME, false);
  }

  public function saveCollectionAndOutlets() {
    wp_set_post_terms($this->get_id(), $this->_collections, Collection::$TERM_NAME, false);
  }

  // To be remove
  public function refreshAttributes() {
    $terms = wp_get_object_terms($this->get_id(), Merchant::$TERM_NAME);
    if($terms instanceof \WP_Error || !$terms) {
      throw new \WP_Error(400, 'Unable to get product merchant term', $this);
    }
   
    $advAttr = new \WC_Product_Attribute;
    $advAttr->set_name('Advertiser');
    $advAttr->set_position(2);
    $advAttr->set_visible(true);
    $advAttr->set_variation(true);
    $branchAttr = new \WC_Product_Attribute;
    $branchAttr->set_name('Branch');
    $branchAttr->set_position(1);
    $branchAttr->set_visible(true);
    $branchAttr->set_variation(true);
    $Advs = [];
    $outlet = [];
    foreach($terms as $term) {
      if(!$term->parent) {
        $Advs[] = $term->term_id;
      } else {
        $outlet[] = $term->term_id;
      }
    }
    $branchAttr->set_options($outlet);
    $advAttr->set_options($Advs);
    $this->set_attributes([$branchAttr, $advAttr]);
    $productVariant = new ProductVariant;
    $productVariant->createVariation($this);
    return $this;
  }

  public function setMerchantIDs($outletIDs, $advertiserID=1) {
    $outletMapFn = function($value): int {
      if(is_numeric($value))
        return $value;
      if(isset($value['id']))
        return $value['id'];
      return 0;
    };
    $this->_outletIDs = array_unique( array_map( $outletMapFn, $outletIDs ) );
    $advAttr = new \WC_Product_Attribute;
    $advAttr->set_name('Advertiser');
    $advAttr->set_position(2);
    $advAttr->set_visible(true);
    $advAttr->set_variation(true);
    $branchAttr = new \WC_Product_Attribute;
    $branchAttr->set_name('Branch');
    $branchAttr->set_position(1);
    $branchAttr->set_visible(true);
    $branchAttr->set_variation(true);
    $Advs = [];
    $merchantModel = Merchant::findByForeignKey($advertiserID);
    if($merchantModel) {
      $Advs[] = $merchantModel->term_id; //get_term_meta($merchantModel->term_id, Merchant::ATTR_ID, true);
      $this->_merchants[] = $merchantModel->term_id;
      foreach($this->_outletIDs as $_outletID) {
        $outletModel = Outlet::findByForeignKey($_outletID);
        if($outletModel) {
          $this->_merchants[] = $outletModel->term_id;
          //$id = get_term_meta($outletModel->term_id, Outlet::ATTR_ID, true);
          $this->_attributes[] = $outletModel->term_id;  
        }
      }
      $branchAttr->set_options($this->_attributes);
      $advAttr->set_options($Advs);
      $this->set_attributes([$branchAttr, $advAttr]);
      return $this->_merchants;
    }
    return false;
  }

  public function setCollectionIDs($collectionIDs, $append = false) {
    return wp_set_post_terms($this->get_id(), $collectionIDs, Collection::$TERM_NAME, $append);
  }

  private function randomPassword($length) {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < $length; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
  }

  public static function updateOrCreateViaJSON($body) {
    $product = new Product();
    return $product->updateOrCreateViaSQS($body);
  }

  public function updateOrCreateViaSQS($body) {
    $id = $body['id']?:0;
    $jsonBody = $body;
    $attributeTerm = null;
    $product = Product::findByForeignKey($id);
    $merchantModel = Merchant::findByForeignKey($jsonBody['advertiser_id']);
    if (!$product) {
      $product = new Product();
    }

    if(!is_array($jsonBody['subType'])) {
      $jsonBody['subType'] = [];
    }
    $ulti = new \SF\core\Ulti();

    
    $product->set_name($jsonBody['name']);
    $product->set_short_description($jsonBody['terms']);
    $product->set_description($jsonBody['description']);
    $product->set_regular_price($jsonBody['price']);

    if($jsonBody['sales'] && !empty($jsonBody['sales'])) {
      $product->set_sale_price($jsonBody['sales']['price']);
      $product->set_date_on_sale_from(new \WC_DateTime($jsonBody['sales']['from']));
      $product->set_date_on_sale_to(new \WC_DateTime($jsonBody['sales']['to']));
      $product->add_meta_data('promoName', $jsonBody['sales']['name'], true);
      $product->add_meta_data('promoTerms', $jsonBody['sales']['terms'], true);
    }
    $product_categories = $jsonBody['categories'];
    if($product_categories && is_array($product_categories) && count($product_categories)>0) {
      $product->set_category_names($product_categories);
    }
    else {
      $product_cats = $jsonBody['subType'];
      $product_cats[] = $jsonBody['type'];
      $product->set_category_names($product_cats);
    }

    $featuredImageID = '';
    if($jsonBody['uploadImage']) {
      $featuredImageID = $ulti->insert_image_from_url($jsonBody['uploadImage']['url'], $jsonBody['name']);  
    } else if($jsonBody['image']) {
      $featuredImageID = $ulti->insert_image_from_url($jsonBody['image'], $jsonBody['name']);      
    }
    if($featuredImageID)
      $product->set_image_id($featuredImageID);
    $status = 'publish';
    if(isset($jsonBody['status']) && strtoupper($jsonBody['status']) != 'ACTIVE') {
      $status = 'draft';
    }
    $product->set_status($status);
    if($jsonBody['preview'] && $jsonBody['preview'] == 1) {
      $product->set_catalog_visibility('hidden');
    } else {
      $product->set_catalog_visibility('visible');
    }
    if($product->setMerchantIDs($jsonBody['outlets'], $jsonBody['advertiser_id'])) {
      $product->save();
      wp_set_post_terms($product->get_id(), $product->_merchants, Merchant::$TERM_NAME, false);
      $productVariant = new ProductVariant;
      $productVariant->createVariation($product, $jsonBody);
      update_field('duration', $jsonBody['duration'], $product->get_id());

      $product->add_meta_data('duration', $jsonBody['duration'], true);      

      $product->addForeignKey($id);
      if($product->get_catalog_visibility() == 'hidden' && empty($product->get_prop('post_password'))) {
        $post = array( 
          'ID' => $product->get_id(),
          'post_password' => $merchantModel->getCustomField('password')
        );
        $result = wp_update_post($post);
      } else if($product->get_catalog_visibility() != 'hidden' && !empty($product->get_prop('post_password'))) {
        $post = array( 
          'ID' => $product->get_id(),
          'post_password' => ""
        );
        $result = wp_update_post($post);
      }
      $product->save();
      $returnProduct = new Product($product->get_id());
      return [
        'result'=>"success",
        'data'=> $returnProduct->get_extended_data()
      ];
    } else {
      throw new \WP_Error(404, 'Merchant not found', $jsonBody);
    }
  }

  public function get_extended_data() {
    $output = $this->get_data();
    $output['variations'] = [];
    $productVariant = new Product($output['id']);
    $variants = $productVariant->get_available_variations();
    $merchant = $this->getMerchant();
    if($merchant) {
      $output['advertiser_attr'] = $merchant->slug;
      $output['advertiser_slug'] = $merchant->slug;
      $output['advertiser_id'] = $merchant->term_id;
      $foreignMeta = get_post_meta($merchant->term_id, Merchant::$FOREIGN_KEY, true);
      if($foreignMeta) {
        $output['advertiser_sf_id'] = $foreignMeta;
      }
    }
    foreach($variants as $variant) {
      $variant['id'] = $variant['variation_id'];
      $variant['active'] = $variant['variation_is_active'];
      $variant['visible'] = $variant['variation_is_visible'];
      $variant['description'] = $variant['variation_description']; 
      if(isset($variant['attributes']['attribute_branch'])) {
        $outletModel = new Outlet($variant['attributes']['attribute_branch']);
        if($outletModel) {
          $variant['outlet_attr'] = $outletModel->slug;
          $variant['outlet_slug'] = $outletModel->slug;
        }
      }
      $output['variations'][] = $variant;
    }
    return $output;
  }

  public static function removeProduct($id) {
    $product = Product::findByForeignKey($id);
    if (!$product) {
      return [
        "result"=> "failed",
        "message"=> "Product not found."
      ];
    }
    $product->set_catalog_visibility('hidden');
    $product->set_status('draft');
    $product->save();
    $variants = $product->get_children();
    foreach($variants as $id) {
      wp_update_post([
        'ID'=>$id,
        'post_status'=>'private'
      ]);
    }
    return $product;
  }

  public static function activateProduct($id) {
    $product = Product::findByForeignKey($id);
    if (!$product) {
      return [
        "result"=> "failed",
        "message"=> "Product not found."
      ];
    }
    $product->set_catalog_visibility('visible');
    $product->set_status('publish');
    $product->save();
    $variants = $product->get_children();
    foreach($variants as $id) {
      wp_update_post([
        'ID'=>$id,
        'post_status'=>'publish'
      ]);
    }
    return $product;
  }

  public static function deletePromo($id, $disable = false) {
    $productVariant = new ProductVariant($id);
    $productVariant->set_sale_price("");
    $productVariant->set_date_on_sale_from(null);
    $productVariant->set_date_on_sale_to(null);
    $productVariant->addPromoText("");
    if($disable) {
      $productVariant->set_status('private');
    }
    $productVariant->save();
    return $productVariant;
  }

  public function setPromo($promoBody) {
    $returnData = [];
    $returnVariants = [];
    $product = Product::findByForeignKey($promoBody['id']);
    if($product) {
      foreach($promoBody['outletID'] as $outletID) {
        $outletModel = Outlet::findByForeignKey($outletID);
        if($outletModel) {
          if(count($returnData)<=0) {
            $returnData = $product->get_data();
            $returnData['sf_id'] = $promoBody['id'];
          }
          $variantIDs = $product->get_children();
          foreach($variantIDs as $variantID) {
            $vProduct = new ProductVariant($variantID);
            $variant = $vProduct->get_variation_attributes();
            
            if(isset($variant['attribute_branch']) && $variant['attribute_branch'] == $outletModel->term_id) {
              $status = 0;
              if(strtoupper($promoBody['status']) == 'ACTIVE') {
                $status = 1;
                $vProduct->addPromo($promoBody);
                $vProduct->save();
                if($promoBody['promoName']) {
                  $product->add_meta_data('promoName', $promoBody['promoName'], true);
                  $product->save_meta_data();  
                }
                // if($product->get_status() != 'publish') {
                //   $product->set_status('publish');
                //   if(empty($product->get_prop('post_password'))) {
                //     $product->set_catalog_visibility('visible');
                //   } else {
                //     $product->set_catalog_visibility('hidden');
                //   }
                //   $product->save();
                // }
              } else {
                $disable = ($promoBody['has_service'])?false:true;
                $vProduct->deletePromo($disable);
                $vProduct->save();
                if(!$promoBody['has_service'] || count($product->get_available_variations())<=0) {
                  Product::removeProduct($promoBody['id']);
                }
              }
              $_returnData = $vProduct->get_data();
              $_returnData['promo_status'] = $status;
              $_returnData['id'] = $vProduct->get_id();
              $_returnData['branchID'] = $outletID;
              $returnVariants[] = $_returnData;
            }
          }
        }
      }
    } else  {
      return [
        'message'=>"Product not found",
        'result'=>'failure',
        'code'=>404
      ];
    }
    
    $returnData['variations'] = $returnVariants;
    return [
      'data'=>$returnData,
      'result'=>'success',
      'code'=>200
    ];
  }

  public function updatePrice($productID) {
    $product = new Product($productID);
    $vProducts = $product->get_available_variations();
    $productPrice = [];
    $price = $product->get_regular_price();
    if($product->is_on_sale()) {
      $price = $product->get_sale_price();
    }
    $productPrice[] = [
      'id'=>$product->ID,
      'price'=>$price
    ];
    foreach($vProducts as $vProduct) {
      $productPrice[] = [
        'id'=>$vProduct->ID,
        'price'=>$price
      ];
    }

    foreach($productPrice as $_productPrice) {
      $this->addMeta($_productPrice['id'], Product::PRODUCT_CURRENT_PRICE, $_productPrice['price']);
    }
  }

  public function addForeignKey($id) {
    $this->addMeta($this->get_id(), Product::PRODUCT_FOREIGN_KEY, $id);
  }

  public static function findByForeignKey($id) {
    $args = array(
      'fk' => $id,
    );
    global $wpdb;
    $products = wc_get_products( $args );
    if(count($products)>0) {
      return new Product($products[0]);  
    }
    return false;
  }

  function getForeignKey() {
    return get_post_meta($this->get_id(), Product::PRODUCT_FOREIGN_KEY, true);
  }

  function addMeta($id, $key, $value) {
    $currentValue = get_post_meta($id, $key, true);
    if($currentValue) {
      update_post_meta($id, $key, $value, $currentValue);
    } else {
      add_post_meta($id, $key, $value, true);
    }
  }

  function addingForeignKeyToSearch( $query, $query_vars ) {
    if ( ! empty( $query_vars['fk'] ) ) {
      $query['meta_query'][] = array(
        'key' => Product::PRODUCT_FOREIGN_KEY,
        'value' => esc_attr( $query_vars['fk'] ),
      );
    }
    return $query;
  }
}
