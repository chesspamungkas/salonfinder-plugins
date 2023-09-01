<?php
/**
 * Created by PhpStorm.
 * User: monkeymon
 * Date: 2/3/2019
 * Time: 6:27 PM
 */

namespace SF\models;


use SF\core\TermModels;

class MerchantPA extends TermModels {
  const TERM_NAME = 'pa_advertiser';
  const MERCHANT_ID = '___product_merchant_id';
  const MERCHANT_SLUG = '___product_merchant_slug';

  public static $TERM_NAME = 'pa_advertiser';

  public function addMerchantID($id) {
    $this->metaData[self::MERCHANT_ID] = $id;
  }

  public function addMerchantSlug($slug) {
    $this->metaData[self::MERCHANT_SLUG] = $slug;
  }

  public function getMerchant() {
    $merchantID = $this->metaData[self::MERCHANT_ID];
    $model = new Merchant();
    if($merchantID) {
      return $model->find($merchantID);
    } else {
      return $model->findByOldWay($this->slug);
    }
  }

  function termName()
  {
    return 'pa_advertiser';
  }

}