<?php

namespace SF\helpers;
use SF\core\Constants;
use Aws\Sns\SnsClient; 
use Aws\Exception\AwsException;

class SNSHelper {

  public static $_model;

  private $SNSClient;

  public function __construct() {
    Constants::initConfig();
    $credentials = Constants::getConfig(Constants::AWS_CREDENTIALS);
    $this->SNSClient = new SnsClient([
      'region' => Constants::getConfig(Constants::SNS_AWS_REGION),
      'version' => '2010-03-31',
      'credentials'=>$credentials
    ]);
  }

  public function publishToSNS($topicName, $body) {
    try {
      $result = $this->SNSClient->publish([
        'Message'=>json_encode($body),
        'TopicArn'=>$topicName
      ]);
      return $result;
    } catch(AwsException $e) {
      return false;
    }
  }

  public static function publish($topicName, $body) {
    if(self::$_model==null) {
      self::$_model = new SNSHelper();
    }
    return self::$_model->publishToSNS($topicName, $body);
  }
}