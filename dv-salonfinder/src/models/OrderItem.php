<?php
/**
 * Created by PhpStorm.
 * User: monkeymon
 * Date: 2/3/2019
 * Time: 5:12 PM
 */

namespace SF\models;

use SF\core\IShortCode;
use SF\core\TermModels;
use SF\core\WPRegister;

class OrderItem {
  const ORDER_FOREIGN_KEY = 'sf_order_id';

  const SF_FULLY_REDEEMED = 'sf-fully-redeemed';

  const STATUS_REDEEMED = 2;
  const STATUS_INACTIVE = 0;
  const STATUS_ACTIVE = 1;

  const VOUCHER_CODE = 'voucherCode';
  const VOUCHER_REDEEMED = 'voucherRedeemed';
  const VOUCHER_HAS_REDEEMED = 'voucherHasRedeemed';

  public static function init() {
		$currentClass = new OrderItem();
    add_action( 'init', [$currentClass, 'fullyRedeemedStatus']);
    add_action( 'wc_order_statuses', [$currentClass, 'addFullyRedeemedStatusToOrder']);
    add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', [$currentClass, 'searchBySFOrderID'], 10, 2 );
  }

  function searchBySFOrderID($query, $query_vars ) {  
    if ( ! empty( $query_vars[self::ORDER_FOREIGN_KEY] ) ) {
      $query['meta_query'][] = array(
        'key' => self::ORDER_FOREIGN_KEY,
        'value' => esc_attr( $query_vars[self::ORDER_FOREIGN_KEY] ),
      );
    }
  
    return $query;
  }

  function fullyRedeemedStatus() {
    register_post_status( self::SF_FULLY_REDEEMED, array(
        'label'                     => 'Fully Redeemed',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Fully Redeemed order <span class="count">(%s)</span>', 'Fully Redeemed order <span class="count">(%s)</span>' )
    ) );
  }
  
  function addFullyRedeemedStatusToOrder( $order_statuses ) {
      $new_order_statuses = array();
      // add new order status after processing
      foreach ( $order_statuses as $key => $status ) {
          $new_order_statuses[ $key ] = $status;
          if ( 'wc-completed' === $key ) {
              $new_order_statuses[self::SF_FULLY_REDEEMED] = 'Fully Redeemed';
          }
      }
      return $new_order_statuses;
  }

  public function isAllRedeemed($vouchers) {
    $allRedeemed = 'YES';
    foreach($vouchers as $voucher) {
      if($voucher['status'] == self::STATUS_ACTIVE) {
        $allRedeemed = 'NO';
        break;
      }
    }
    return $allRedeemed;
  }

  public function hasRedeemed($vouchers) {
    $hasRedeemed = 'NO';
    foreach($vouchers as $voucher) {
      if($voucher['status'] == self::STATUS_INACTIVE || $voucher['status'] == self::STATUS_REDEEMED) {
        $hasRedeemed = 'YES';
        break;
      }
    }
    return $hasRedeemed;
  }

  private function makeVoucherArray($msgBody, $originalMsg = []) {
    if(!$msgBody['cancelDate'] && $msgBody['redeemed']==0) {
      $status = self::STATUS_ACTIVE;
    }
    if($msgBody['redeemed']==1) {
      $status = self::STATUS_REDEEMED;
    }
    if($msgBody['cancelDate']) {
      $status = self::STATUS_INACTIVE;
    }
    $expiryDate = new \DateTime($msgBody['expiryDate']);
    $today = new \DateTime();
    if($expiryDate < $today) {
      $status = self::STATUS_INACTIVE;
    }
    return array_merge($originalMsg, [
      'voucher'=>$msgBody['voucher'],
      'qrCode'=>$msgBody['qrUrl'],
      'emailTitle'=>$msgBody['emailTitle'],
      'emailContent'=>$msgBody['emailContent'],
      'redeemedDate'=>$msgBody['redeemedDate'],
      'redeemed'=>$msgBody['redeemed'],
      'expiryDate'=>$msgBody['expiryDate'],
      'cancelDate'=>$msgBody['cancelDate'],
      'status'=>$status
    ]);
  }

  public function updateVoucherAction($msgBody, $key) {
    $args = array(
		  self::ORDER_FOREIGN_KEY => $msgBody['orderID']
    );
    $orders = wc_get_orders($args);
    
    foreach($orders as $order) {      
      $orderItems = $order->get_items();
      $fullyRedeemed = true;
      foreach($orderItems as $orderItem) {
        if($orderItem) {
          $metaArray = [];
          $itemRedeemed = true;
          if($orderItem->meta_exists(self::VOUCHER_CODE)) {
            $metaArray = $orderItem->get_meta(self::VOUCHER_CODE, true);
          }
          if(isset($metaArray[$msgBody['voucherCode']])) {
            $metaArray[$msgBody['voucher']] = $this->makeVoucherArray($msgBody, $metaArray[$msgBody['voucher']]);
            if(in_array('REDEEM', $key)) {
              $metaArray[$msgBody['voucherCode']]['status'] == self::STATUS_REDEEMED;
              $metaArray[$msgBody['voucherCode']]['redeemed'] = 1;
              $metaArray[$msgBody['voucherCode']]['redeemedDate'] = $msgBody['redeemedDate'];
              $orderItem->add_meta_data(self::VOUCHER_HAS_REDEEMED, 'YES', true);
            }
            if(in_array('EXTEND', $key)) {
              $metaArray[$msgBody['voucherCode']]['status'] == self::STATUS_ACTIVE;
              $metaArray[$msgBody['voucherCode']]['expiryDate'] = $msgBody['expiryDate'];
            }
            if(in_array('CANCEL', $key)) {
              $metaArray[$msgBody['voucherCode']]['status'] = self::STATUS_INACTIVE;
              $metaArray[$msgBody['voucherCode']]['expiryDate'] = $msgBody['cancelDate'];
            }
            $orderItem->add_meta_data(self::VOUCHER_CODE, $metaArray, true);
            $orderItem->save_meta_data();
          } else {
            $metaArray[$msgBody['voucher']] = $this->makeVoucherArray($msgBody);
          }
          if($this->isAllRedeemed($metaArray) == 'NO') {
            $fullyRedeemed = false;
            $itemRedeemed = false;
          }
          if($itemRedeemed) {
            $orderItem->add_meta_data(self::VOUCHER_REDEEMED, 'YES', true);
            $orderItem->save_meta_data();
          }
          
        }
      }
      if($fullyRedeemed) {
        $order->set_status(self::SF_FULLY_REDEEMED);
        $order->save();
      }
    }
  }

  public function updateOrCreateBySQS($msgBody) {
    if(isset($msgBody['order'])) {
      $order = new \WC_Order( $msgBody['order']['wpOrderID'] );
      if($order) {
        $orderItem = $order->get_item($msgBody['wpOrderItemID']);
        if($orderItem) {
          $metaArray = [];
          if($orderItem->meta_exists(self::VOUCHER_CODE)) {
            $metaArray = $orderItem->get_meta(self::VOUCHER_CODE, true);
          }
          if(!isset($metaArray[$msgBody['voucher']]) || !is_array($metaArray)) {
            $metaArray[$msgBody['voucher']] = [];
          }
          
          if(!$msgBody['cancelDate'] && $msgBody['redeemed']==0) {
            $status = self::STATUS_ACTIVE;
          }
          if($msgBody['redeemed']==1) {
            $status = self::STATUS_REDEEMED;
          }
          if($msgBody['cancelDate']) {
            $status = self::STATUS_INACTIVE;
          }
          $expiryDate = new \DateTime($msgBody['expiryDate']);
          $today = new \DateTime();
          if($expiryDate < $today) {
            $status = self::STATUS_INACTIVE;
          }
          $metaArray[$msgBody['voucher']] = [
            'voucher'=>$msgBody['voucher'],
            'qrCode'=>$msgBody['qrUrl'],
            'emailTitle'=>$msgBody['emailTitle'],
            'emailContent'=>$msgBody['emailContent'],
            'redeemedDate'=>$msgBody['redeemedDate'],
            'redeemed'=>$msgBody['redeemed'],
            'expiryDate'=>$msgBody['expiryDate'],
            'status'=>$status
          ];
          $orderItem->add_meta_data(self::VOUCHER_CODE, $metaArray, true);
          $orderItem->add_meta_data(self::VOUCHER_REDEEMED, $this->isAllRedeemed($metaArray), true);
          $orderItem->add_meta_data(self::VOUCHER_HAS_REDEEMED, $this->hasRedeemed($metaArray), true);
          $orderItem->save_meta_data();
          $order->delete_meta_data(self::ORDER_FOREIGN_KEY);
          $order->add_meta_data(self::ORDER_FOREIGN_KEY, $msgBody['orderID']);
          $order->save_meta_data();
        }
      }
    }
  }
}