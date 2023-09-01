<?php

namespace SF\schedules;

use Aws\Credentials\Credentials;
use SF\core\Constants;
use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;
use SF\helpers\SNSHelper;
use SF\helpers\SQSHelper;
use SF\models\OrderItem;

class OrderVoucherSchedule
{

  protected $SQS_HOOK = 'dv_sqs_order_voucher_cron';
  protected $SQS_NAME = '';

  public static function init()
  {
    $currentClass = new OrderVoucherSchedule();
    if (defined('WP_CLI') && \WP_CLI) {
      \WP_CLI::add_command('sf-voucher', [$currentClass, 'requestSQS']);
    }
  }

  public function requestSQS()
  {
    $queueUrl = Constants::getConfig(Constants::SQS_VOUCHER);
    $results = SQSHelper::readQueue($queueUrl);
    if ($results) {
      foreach ($results as $result) {
        try {
          $message       = $result['Message'];
          $attributes    = $result['MessageAttributes'];
          $orderItemModel = new OrderItem();
          $orderItemModel->updateOrCreateBySQS($message);
        } catch (\Exception $e) {
          error_log($e, 1, 'chesspamungkas@gmail.com');
        } finally {
          SQSHelper::deleteMsg($queueUrl, $result);
        }
      }
    }
  }
}
