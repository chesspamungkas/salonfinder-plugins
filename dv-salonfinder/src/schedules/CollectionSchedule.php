<?php

namespace SF\schedules;

use Aws\Credentials\Credentials;
use SF\controllers\v1\CollectionController;
use SF\core\Constants;
use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;
use SF\helpers\SNSHelper;
use SF\helpers\SQSHelper;
use SF\models\Collection;

class CollectionSchedule
{

  protected $SQS_HOOK = 'dv_sqs_collection_create_cron';
  protected $SQS_NAME = '';

  public static function init()
  {
    $currentClass = new CollectionSchedule();
    if (defined('WP_CLI') && \WP_CLI) {
      \WP_CLI::add_command('sf-collection', [$currentClass, 'requestSQS']);
    }
  }

  public function requestSQS()
  {
    $queueUrl = Constants::getConfig(Constants::SQS_COLLECTION);
    $results = SQSHelper::readQueue($queueUrl);
    if ($results) {
      foreach ($results as $result) {
        try {
          $message       = $result['Message'];
          $attributes    = $result['MessageAttributes'];
          $actions       = json_decode($attributes['action']['Value'], true);
          if (!in_array('delete', $actions)) {
            $collectionModel = new CollectionController();
            $response      = $collectionModel->updateOrCreate($message, $attributes['messageVersion']['Value']);
            $response['key'] = $actions;
            if ($response['result'] == 'success') {
              $response['data']['sf_id'] = $message['id'];
            }
          } else {
            $collection = Collection::delete($message['id']);
            $response = [
              'result' => 'success',
              'data' => [
                'sf_id' => $message['id'],
                'id' => $collection->term_id,
                'status' => 'Inactive'
              ]
            ];
          }
          SNSHelper::publish(Constants::getConfig(Constants::SNS_COLLECTION_CALLBACK), $response);
        } catch (\Exception $e) {
          error_log($e, 1, 'chesspamungkas@gmail.com');
        } finally {
          SQSHelper::deleteMsg($queueUrl, $result);
        }
      }
    }
  }
}
