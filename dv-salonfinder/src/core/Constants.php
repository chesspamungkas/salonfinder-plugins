<?php 
namespace SF\core;
use Aws\Credentials\Credentials;

class Constants {
  const REST_BASE='dvsalon';
  const OPTION_NAME='dv-salon-finder-plugins';
  const SNS_AWS_KEY='SNS_AWS_KEY';
  const SNS_AWS_SECRET='SNS_AWS_SECRET';
  const SNS_AWS_REGION='SNS_AWS_REGION';
  const SNS_ORDER_STATUS_NAME='SNS_ORDER_STATUS_NAME';
  const SNS_ADVERTISER_CALLBACK='SNS_ADVERTISER_CALLBACK';
  const SNS_OUTLET_CALLBACK='SNS_OUTLET_CALLBACK';
  const SNS_SERVICE_CALLBACK='SNS_SERVICE_CALLBACK';
  const SNS_PROMO_CALLBACK='SNS_PROMO_CALLBACK';
  const SNS_COLLECTION_CALLBACK='SNS_COLLECTION_CALLBACK';
  
  const SF_API_URL='SF_API_URL';

	const SQS_ADVERTISER='SQS_ADVERTISER';
  const SQS_OUTLET='SQS_OUTLET';
  const SQS_SERVICE='SQS_SERVICE';
  const SQS_PROMO='SQS_PROMO';
  const SQS_VOUCHER='SQS_VOUCHER';
  const SQS_COLLECTION='SQS_COLLECTION';

  const AWS_CREDENTIALS='AWS_CREDENTIALS';
  
  const ATTACHEMENT_FK='sf_image_url';
  
  public static $_config = null;

  public static function initConfig() {
    if(self::$_config===null) {
      self::$_config = json_decode(get_option(self::OPTION_NAME, []), true);
	    self::$_config[self::SQS_ADVERTISER]=SF_SQS_ADVERTISER;
      self::$_config[self::SQS_OUTLET]=SF_SQS_OUTLET;
      self::$_config[self::SQS_SERVICE]=SF_SQS_SERVICE;
      self::$_config[self::SQS_PROMO]=SF_SQS_PROMO;
      self::$_config[self::SQS_VOUCHER]=SF_SQS_ORDER_VOUCHER;
      self::$_config[self::SQS_COLLECTION]=SF_SQS_COLLECTION;
      self::$_config[self::SNS_ADVERTISER_CALLBACK]=SF_SNS_ADVERTISER_CALLBACK;
      self::$_config[self::SNS_OUTLET_CALLBACK]=SF_SNS_OUTLET_CALLBACK;
      self::$_config[self::SNS_SERVICE_CALLBACK]=SF_SNS_SERVICE_CALLBACK;
      self::$_config[self::SNS_PROMO_CALLBACK]=SF_SNS_PROMO_CALLBACK;
      self::$_config[self::SNS_ORDER_STATUS_NAME]=SF_SNS_ORDER_STATUS_NAME;
      self::$_config[self::SNS_COLLECTION_CALLBACK]=SF_SNS_COLLECTION_CALLBACK;
	    self::$_config[self::AWS_CREDENTIALS] = new Credentials(
        SF_AWS_ACCESS_KEY_ID,
        SF_AWS_SECRET_ACCESS_KEY
	    );
    }
  }

  public static function setConfig($config){
    self::$_config = $config;
  }

  public static function getConfig($key){
    return isset(self::$_config[$key])?self::$_config[$key]:null;
  }
}
