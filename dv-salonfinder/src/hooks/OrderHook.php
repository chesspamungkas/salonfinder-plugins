<?php
namespace SF\hooks;

use SF\core\interfaces\IHooks;
use SF\core\Constants;
use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;
use SF\models\Merchant;
use SF\models\Outlet;
use SF\models\Product;

class OrderHook implements IHooks {

	private $SNSClient;

  public function __construct() {
    Constants::initConfig();
    $credentials = Constants::getConfig(Constants::AWS_CREDENTIALS);
    $this->SNSClient = new SnsClient([
      'region' => Constants::getConfig(Constants::SNS_AWS_REGION),
      'version' => '2010-03-31',
      'credentials'=> $credentials
    ]);
  }

  public function registerHooks() {
    // TODO: Implement register() method.
    // add_action( 'woocommerce_update_product', [$this, 'updatePrice'], 10, 1 );
    // add_action( 'woocommerce_new_product', [$this, 'updatePrice'], 10, 1 );
    // add_action('woocommerce_update_product_variation', [$this, 'updateMeta', 10, 1]);
    add_filter( 'woocommerce_order_status_processing', [$this,'sendCoupon'], 10, 2 );
  }

  public function sendCoupon($orderID) {
    $order = wc_get_order( $orderID );
    $customer = new \WC_Customer($order->get_customer_id());
    //if( $order->get_used_coupons() && count($order->get_used_coupons()) ) {
      $coupons = $order->get_items( 'coupon' );
      $orderData = $order->get_data();
      $orderData['couponDetails'] = [];
      
      $orderData['lineDetails'] = [];
      $orderData['customer'] = $customer->get_data();
      $orderData['customer']['dob'] = $customer->get_meta('dob', true);
      $orderData['customer']['gender'] = $customer->get_meta('gender', true);
      $orderData['customer']['customer_id'] = $order->get_customer_id();
      
      foreach($coupons as $coupon) {
        $orderData['couponDetails'][] = $coupon->get_data();
      }

      foreach ($order->get_items() as $item_id => $item_data) {
        $item = $item_data->get_data();
        $product = $item_data->get_product();
        $productData = $product->get_data();
        $productData['product_id'] = $item_data->get_product_id();
        $productData['variation_id'] = $product->get_variation_id();
        $productData['product_type'] = $product->get_type();
        $productData['attributeDetails'] = [];
        $productData['qty'] = $item['quantity'];
        $productData['total'] = $item['total'];
        $productData['subtotal'] = $item_data['subtotal'];
        $productData['total_tax'] = $item_data['total_tax'];
        $productData['subtotal_tax'] = $item_data['subtotal_tax'];
        $productData['tax_class'] = $item_data['tax_class'];
        $productData['order_item_id'] = $item['id'];

        $wcProduct = wc_get_product($item_data->get_product_id());
        if($wcProduct) {
          $productData['sfID'] = $wcProduct->get_meta(Product::PRODUCT_FOREIGN_KEY, true);
        }
        
        foreach($productData['attributes'] as $key=>$id) {
          $merchant = null;
          if(is_numeric($id)) {
            $merchant = Merchant::findByID($id);
          } else {
            $merchant = Merchant::findByID($product->get_attribute($key));
          }
          if($merchant) {
            if($key == 'advertiser') {
              $productData['merchantDetail'] = $merchant->get_data();
              $productData['merchantDetail']['sfID'] = $merchant->getMeta(Merchant::$FOREIGN_KEY);
            }
            if($key == 'branch') {
              $productData['outletDetail']         = $merchant->get_data();
              $productData['outletDetail']['sfID'] = $merchant->getMeta(Outlet::$FOREIGN_KEY);
            }
							$productData['attributeDetails'][$key] = $merchant->get_data();
          }
        }
        $orderData['lineDetails'][] = $productData;
      }
      try {
        $result = $this->SNSClient->publish([
          'Message'=>json_encode($orderData),
          'TopicArn'=>Constants::getConfig(Constants::SNS_ORDER_STATUS_NAME)
        ]);
      } catch(AwsException $e) {
      }
    //}
  }
}
