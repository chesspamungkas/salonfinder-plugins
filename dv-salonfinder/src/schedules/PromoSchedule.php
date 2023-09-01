<?php

namespace SF\schedules;

use Aws\Credentials\Credentials;
use SF\models\Product;
use SF\core\Constants;
use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;
use SF\helpers\SNSHelper;

class PromoSchedule
{

  protected $SQS_HOOK = 'dv_sqs_promo_cron';
  protected $SQS_NAME = '';

  public static function init()
  {
    $currentClass = new PromoSchedule();
    if (defined('WP_CLI') && \WP_CLI) {
      \WP_CLI::add_command('sf-promo', [$currentClass, 'requestSQS']);
    }
  }

  public function requestSQS()
  {
    $queueUrl = Constants::getConfig(Constants::SQS_PROMO);

    $credentials = Constants::getConfig(Constants::AWS_CREDENTIALS);

    $client = new SqsClient([
      'region' => 'ap-southeast-1',
      'version' => 'latest',
      'credentials' => $credentials
    ]);
    try {
      $result = $client->receiveMessage(array(
        'QueueUrl' => $queueUrl,
        'WaitTimeSeconds' => 20,
        'MaxNumberOfMessages' => 10
      ));
      if (is_array($result->get('Messages')) && count($result->get('Messages')) > 0) {
        foreach ($result->get('Messages') as $sqsMessage) {
          try {
            $_messageBody  = json_decode($sqsMessage['Body'], true);
            $message       = json_decode($_messageBody['Message'], true);
            $productModel = new Product();
            $response      = $productModel->setPromo($message['body']);
            $response['key'] = $message['key'];
            if ($response['result'] == 'success') {
              SNSHelper::publish(Constants::getConfig(Constants::SNS_PROMO_CALLBACK), $response);
              $deleteResult = $client->deleteMessage([
                'QueueUrl' => $queueUrl, // REQUIRED
                'ReceiptHandle' => $sqsMessage['ReceiptHandle'] // REQUIRED
              ]);
            }
          } catch (\Exception $e) {
            error_log($e, 1, 'chesspamungkas@gmail.com');
          }
        }
      }
    } catch (AwsException $e) {
      // output error message if fails
      error_log($e->getMessage());
    }
  }
}
