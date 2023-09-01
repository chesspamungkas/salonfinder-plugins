<?php

namespace SF\schedules;

// use Aws\Credentials\Credentials;
use SF\models\Outlet;
use SF\core\Constants;
use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;
use SF\helpers\SNSHelper;

class OutletSchedule
{

  protected $SQS_HOOK = 'dv_sqs_outlet_cron';
  protected $SQS_NAME = '';

  public static function init()
  {
    $currentClass = new OutletSchedule();
    if (defined('WP_CLI') && \WP_CLI) {
      \WP_CLI::add_command('sf-outlet', [$currentClass, 'requestSQS']);
    }
  }

  public function requestSQS()
  {
    $queueUrl = Constants::getConfig(Constants::SQS_OUTLET);

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
              $merchantModel = new Outlet();
              if ($message['body']['active'] == 'Inactive') {
                $response = Outlet::delete($message['body']['id']);
                // die();
              } else {
                $response = $merchantModel->updateOrCreateBySQS($message['body']);
              }
              $response['key'] = $message['key'];
              if ($response['result'] == 'success') {
                $response['data']['id'] = $response['data']['term_id'];
                $response['data']['sf_id'] = $message['body']['id'];
              }
            } else {
              if ($message['body']['fields']['is_active'] == 'Inactive') {
                $outlet = Outlet::delete($message['body']['id']);
              } else {
                $outlet = Outlet::addBackOutlet($message['body']['id']);
              }

              $response = [
                'result' => 'success',
                'key' => 'activate',
                'data' => [
                  'sf_id' => $message['body']['id'],
                  'id' => $outlet->term_id,
                  'status' => 'Inactive'
                ]
              ];
            }
            SNSHelper::publish(Constants::getConfig(Constants::SNS_OUTLET_CALLBACK), $response);
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
