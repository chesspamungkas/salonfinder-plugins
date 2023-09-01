<?php
/**
 * Created by PhpStorm.
 * User: monkeymon
 * Date: 28/2/2019
 * Time: 7:45 PM
 */

namespace SF\models;

use WC_DateTime;

class ProductVariant extends \WC_Product_Variation {

  const TYPE='product_variation';
  const PROMOTEXT = '__promotionText';

  const ATTR_BRANCH_KEY='__pa_branch';
  const ATTR_MERCHANT_KEY='__pa_advertiser';

  public function deleteVariant() {
    return $this->delete();
    // Below code seems not used.
    $outletSlug = $this->get_meta(self::ATTR_BRANCH_KEY);
    $this->delete_meta_data(self::ATTR_BRANCH_KEY);
    $this->delete_meta_data(self::ATTR_MERCHANT_KEY);
    $this->delete();
    $count = $this->countProductByOutlet($outletSlug);
    $outletPATerm = new OutletPA(get_term_by('slug', $outletSlug, OutletPA::$TERM_NAME, ARRAY_A));
    $outletID = $outletPATerm->getOutletID();
    $outletModel = new Outlet($outletID);
    $outletModel->addProductCount($count);
    $outletModel->saveMetaData();
  }

  public function addPromo($params) {
    $this->set_sale_price($params['sale_price']);
    $this->set_date_on_sale_from($params['date_on_sale_from']);
    $this->set_date_on_sale_to($params['date_on_sale_to']);
    $this->addPromoText($params['description']);
    $this->save_meta_data();
    return $this;
  }

  public function deletePromo($disable = false) {
    $this->set_sale_price("");
    $this->set_date_on_sale_from(null);
    $this->set_date_on_sale_to(null);
    $this->addPromoText("");
    $this->save_meta_data();
    if($disable) {
      $this->set_status('private');
    }
    return $this;
  }

  public function addPromoText($text) {
    $this->add_meta_data(ProductVariant::PROMOTEXT, $text, true);
  }

  function addMeta($id, $key, $value) {
    $currentValue = get_post_meta($id, $key, true);
    if($currentValue) {
      update_post_meta($id, $key, $value, $currentValue);
    } else {
      add_post_meta($id, $key, $value, true);
    }
  }

  public function createVariant(\WC_Product $parentProduct, $variant, $pricingBody) {

    if($parentProduct->get_parent_id())
      return 0;
    $this->set_parent_id($parentProduct->get_id());
    $this->set_parent_data($parentProduct->get_data());
    $this->set_attributes($variant);    
    $this->set_regular_price($pricingBody['price']);
    if($pricingBody['sales'] && !empty($pricingBody['sales'])) {
      $this->set_sale_price($pricingBody['sales']['price']);
      $this->set_date_on_sale_from(new \WC_DateTime($pricingBody['sales']['from']));
      $this->set_date_on_sale_to(new \WC_DateTime($pricingBody['sales']['to']));
      $this->add_meta_data('promoName', $pricingBody['sales']['name'], true);
      $this->add_meta_data('promoTerms', $pricingBody['sales']['terms'], true);
    } else {
      $this->set_sale_price('');
      $this->set_date_on_sale_from();
      $this->set_date_on_sale_to();
      $this->add_meta_data('promoName', '', true);
      $this->add_meta_data('promoTerms', '', true);
    }
    $savedID = $this->save();
    return $savedID;
  }

  public function countProductByOutlet($outletSlug) {
    $arg = [
      'post_type'=>self::TYPE,
      'fields'=>'ids',
      'meta_query' => array(
        array(
          'key'     => self::ATTR_BRANCH_KEY,
          'value'   => $outletSlug
        ),
      ),
    ];
    $query = new \WP_Query($arg);
    return $query->found_posts;
  }

  public static function listProductByOutlet($outletSlug, $limit=null) {
    $arg = [
      'post_type'=>self::TYPE,
      'limit'=>$limit,
      'meta_key'=>Product::PRODUCT_CURRENT_PRICE,
      'meta_value'   => 0,
      'meta_compare' => '>',
      'orderby'=> 'meta_value_num',
      'order'=>'ASC',
      'fields'=>'ids',
      'status'=>'published',
      'meta_query' => array(
        array(
          'key'     => self::ATTR_BRANCH_KEY,
          'value'   => $outletSlug
        ),
      ),
    ];
    $query = new \WP_Query($arg);
    $found = $query->get_posts();
    $returnProduct = [];
    if(count($found)) {
      foreach($found as $productID) {
        $returnProduct[] = new \WC_Product_Variation($productID);
      }
    }
    return $returnProduct;
  }

  public function createVariation($product_id, $pricingBody) {
    $product = new Product($product_id);
    $attributes = $product->get_attributes();
    $variantIDs = $product->get_children();
    $variationIDs = [];
    foreach($attributes['advertiser']->get_options() as $merchant) {
      foreach($attributes['branch']->get_options() as $branch) {
        $foundVariations = 0;
        foreach($variantIDs as $index=>$variantID) {
          $vProduct = new ProductVariant($variantID);
          $attr = $vProduct->get_variation_attributes();
          if($attr['attribute_branch'] == $branch && $attr['attribute_advertiser'] == $merchant) {
            $foundVariations = $variantID;
            unset($variantIDs[$index]);
            break;
          }
        }
        $productVariant = new ProductVariant();
        if($foundVariations) {
          $productVariant->set_id($foundVariations);
        }
        $variationIDs[] = $productVariant->createVariant($product, ['advertiser' => $merchant, 'branch' => $branch], $pricingBody);
      }
    }
    foreach($variantIDs as $variantID) {
      $productVariant = new ProductVariant();
      $productVariant->set_id($variantID);
      $productVariant->deleteVariant();
    }
    return $variationIDs;
  }
}