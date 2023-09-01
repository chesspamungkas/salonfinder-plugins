<?php

namespace SF\helpers;
use SF\core\Constants;
use Aws\Sqs\SqsClient; 
use Aws\Exception\AwsException;

class SQSHelper {

  public static $_model;

  private $SQSClient;

  public function __construct() {
    Constants::initConfig();
    $credentials = Constants::getConfig(Constants::AWS_CREDENTIALS);
    $this->SQSClient = new SqsClient([
      'region' => 'ap-southeast-1',
      'version' => 'latest',
      'credentials' => $credentials
    ]);
  }

  public function read($queueURL) {
    try {
      $returnResult = [];
      $result = $this->SQSClient->receiveMessage(array(
        'QueueUrl' => $queueURL,
        'WaitTimeSeconds' => 20,
        'MaxNumberOfMessages'=> 10
      ));
      if (is_array($result->get('Messages')) && count($result->get('Messages')) > 0) {
        foreach($result->get('Messages') as $sqsMessage) {
          $_messageBody = json_decode( $sqsMessage['Body'], true );
          $_messageBody['Message'] = json_decode( $_messageBody['Message'], true );
          $_messageBody['ReceiptHandle'] = $sqsMessage['ReceiptHandle'];
          $returnResult[] = $_messageBody;
        }
      }


      return $returnResult;
    } catch(AwsException $e) {
      return false;
    }
  }

  public static function getClient() {
    if(self::$_model==null) {
      self::$_model = new SQSHelper();
    }
    return self::$_model;
  }

  public static function deleteMsg($queueURL, $messageArray) {
    return self::getClient()->SQSClient->deleteMessage([
      'QueueUrl' => $queueURL, // REQUIRED
      'ReceiptHandle' => $messageArray['ReceiptHandle'] // REQUIRED
    ]);
  }

  public static function readQueue($queueURL) {
    $client = self::getClient();
    return $client->read($queueURL);
  }
}