<?php

namespace SF\schedules;

use Aws\Credentials\Credentials;
use SF\models\Product;
use SF\core\Constants;
use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;
use SF\helpers\SNSHelper;

class ServicesSchedule
{

  protected $SQS_HOOK = 'dv_sqs_services_cron';
  protected $SQS_NAME = '';

  public static function init()
  {
    $currentClass = new ServicesSchedule();
    if (defined('WP_CLI') && \WP_CLI) {
      \WP_CLI::add_command('sf-services', [$currentClass, 'requestSQS']);
    }
  }

  public function requestSQS()
  {
    $queueUrl = Constants::getConfig(Constants::SQS_SERVICE);

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
            if ($message['key'] != 'delete') {
              $productModel = new Product();
              $response      = $productModel->updateOrCreateViaSQS($message['body']);
              $response['key'] = $message['key'];
              if ($response['result'] == 'success') {
                $response['data']['sf_id'] = $message['body']['id'];
              }
            } else {
              if (isset($message['body']['fields']['is_active']) && $message['body']['fields']['is_active'] == 'Inactive') {
                $model = Product::removeProduct($message['body']['id']);
                $response = [
                  'result' => 'success',
                  'data' => [
                    'sf_id' => $message['body']['id'],
                    'id' => $model->id,
                    'status' => 'Inactive'
                  ]
                ];
              } else if ($message['body']['fields']['is_active'] == 'Active') {
                $model = Product::activateProduct($message['body']['id']);
                $response = [
                  'result' => 'success',
                  'data' => [
                    'sf_id' => $message['body']['id'],
                    'id' => $model->id,
                    'status' => 'Active'
                  ]
                ];
              }
            }
            SNSHelper::publish(Constants::getConfig(Constants::SNS_SERVICE_CALLBACK), $response);
          } catch (\Exception $e) {
            error_log($e, 1, 'chesspamungkas@gmail.com');
            continue;
          } finally {
            $result = $client->deleteMessage([
              'QueueUrl' => $queueUrl, // REQUIRED
              'ReceiptHandle' => $sqsMessage['ReceiptHandle'] // REQUIRED
            ]);
          }
        }
      }
    } catch (AwsException $e) {
      // output error message if fails
      print_r($e->getMessage());
      error_log($e->getMessage());
    }
  }
}
