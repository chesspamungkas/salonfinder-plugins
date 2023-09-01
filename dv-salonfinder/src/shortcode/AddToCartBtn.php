<?php
/**
 * Created by PhpStorm.
 * User: monkeymon
 * Date: 5/3/2019
 * Time: 10:10 PM
 */

namespace SF\shortcode;


use SF\core\IShortCode;
use SF\core\SF_ShortCode;
use SF\models\Merchant;
use SF\models\Outlet;
use SF\models\ProductVariant;
use SF\models\Product;

class AddToCartBtn extends SF_ShortCode implements IShortCode
{

  public static function registerShortCode()
  {
    $addToCartBtn = new AddToCartBtn();
    add_shortcode('addToCartBtn', [$addToCartBtn, 'createBtn']);
    add_action( 'wp_enqueue_scripts', [$addToCartBtn, 'loadJS'] );
  }

  public function loadJS() {
    wp_enqueue_script('addToCart', SF_BASEURL.'/assets/shortcode/addToCart/addtoCart.js', array('jquery'), false, true);
  }

  public function createBtn($args, $content) {
    $page = 0;
    $limit = 10;
    $merchantid = null;
    $searchtext = null;
    $searchoutlet = null;
    extract($args, EXTR_OVERWRITE);
    $args = [
      'hide_empty'=>true,
      'taxonomy'=>Merchant::$TERM_NAME,
      'name__like'=>$searchoutlet?:null,
      'randomOutlet'=>true,
      'outletOnly'=>$merchantid?null:true
    ];
    if($merchantid) {
      $args['parent'] = $merchantid;
    }

    $branches = get_terms($args);
    $returnBranch = [];
    foreach($branches as $branch) {
      $outletModel = new Outlet($branch->to_array());
      $products = ProductVariant::listProductByOutlet($outletModel->getMeta(Outlet::ATTR_SLUG), 3);
      $newBranch = $branch->to_array();
      $newBranch['products'] = $products;
      $newBranch['acfID'] = Merchant::$TERM_NAME.'_'.$branch->term_id;
      $returnBranch[] = $newBranch;
    }
    echo $this->render('views/shortcode/listBranches/list', ['outlets'=>$returnBranch]);
  }
}