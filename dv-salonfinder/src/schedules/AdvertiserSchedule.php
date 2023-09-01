<?php

namespace SF\schedules;

use Aws\Credentials\Credentials;
use SF\controllers\v1\MerchantController;
use SF\core\Constants;
use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;
use SF\helpers\SNSHelper;
use SF\models\Merchant;

class AdvertiserSchedule
{

  protected $SQS_HOOK = 'dv_sqs_merchant_create_cron';
  protected $SQS_NAME = '';

  public static function init()
  {
    $currentClass = new AdvertiserSchedule();
    if (defined('WP_CLI') && \WP_CLI) {
      \WP_CLI::add_command('sf-advertiser', [$currentClass, 'requestSQS']);
    }
  }

  public function requestSQS()
  {
    $queueUrl = Constants::getConfig(Constants::SQS_ADVERTISER);

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
              $merchantModel = new MerchantController();
              $response      = $merchantModel->updateOrCreate($message['body']);
              $response['key'] = $message['key'];
              if ($response['result'] == 'success') {
                $response['data']['sf_id'] = $message['body']['id'];
              }
            } else {
              $merchant = Merchant::delete($message['body']['id']);
              $response = [
                'result' => 'success',
                'data' => [
                  'sf_id' => $message['body']['id'],
                  'id' => $merchant->term_id,
                  'status' => 'Inactive'
                ]
              ];
            }
            SNSHelper::publish(Constants::getConfig(Constants::SNS_ADVERTISER_CALLBACK), $response);
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
      error_log($e->getMessage());
    }
  }
}
