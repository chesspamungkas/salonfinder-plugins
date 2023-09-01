<?php
/**
 * Plugin Name:  DV Custom Mialgun Plguin
 * Description:  Mailgun integration for Dv WordPress websites
 * Version:      1.0.0
 * License:      GPLv2 or later
 * Text Domain:  mailgun
 * Domain Path:  /languages/.
 * Author: gowtham
 */

/**
 * Entrypoint for the Send Mail In Mailgun plugin.
 *
 * Registers handlers for later actions and sets up config variables with
 * Wordpress.
 */
class Send_In_Mailgun
{
    /**
     * Send_In_Mailgun constructor.
     */
    public function __construct()
    {
        $this->options = get_option('mailgun');
        $this->plugin_file = __FILE__;
        $this->plugin_basename = plugin_basename($this->plugin_file);
        $this->api_endpoint = 'https://api.mailgun.net/v3/';

        if ($this->get_option('useAPI') || (defined('MAILGUN_USEAPI') && MAILGUN_USEAPI)) {
            if (!function_exists('wp_mail')) {
                if (!include dirname(__FILE__).'/includes/wp-mail-api.php') {
                    self::deactivate_and_die(dirname(__FILE__).'/includes/wp-mail-api.php');
                }
            }
        }
    }

    /**
     * Get specific option from the options table.
     *
     * @param string $option Name of option to be used as array key for retrieving the specific value
     *
     * @return mixed
     *
     * @since 0.1
     */
    public function get_option($option, $options = null, $default = false)
    {
        if (is_null($options)) {
            $options = &$this->options;
        }
        if (isset($options[$option])) {
            return $options[$option];
        } else {
            return $default;
        }
    }

    /**
     * Deactivate this plugin and die.
     *
     * Used to deactivate the plugin when files critical to it's operation can not be loaded
     *
     * @since 0.1
     *
     * @return none
     */
    public static function deactivate_and_die($file)
    {
        load_plugin_textdomain('mailgun', false, 'mailgun/languages');
        $message = sprintf(__('Mailgun has been automatically deactivated because the file <strong>%s</strong> is missing. Please reinstall the plugin and reactivate.'), $file);
        if (!function_exists('deactivate_plugins')) {
            include ABSPATH.'wp-admin/includes/plugin.php';
        }
        deactivate_plugins(__FILE__);
        wp_die($message);
    }

    /**
     * Make a Mailgun api call.
     *
     * @param string $endpoint The Mailgun endpoint uri
     *
     * @return array
     *
     * @since 0.1
     */
    public function api_call($uri, $params = array(), $method = 'POST')
    {
        $options = get_option('mailgun');
        $apiKey = (defined('MAILGUN_APIKEY') && MAILGUN_APIKEY) ? MAILGUN_APIKEY : $options['apiKey'];

        $time = time();
        $url = $this->api_endpoint.$uri;
        $headers = array(
            'Authorization' => 'Basic '.base64_encode("api:{$apiKey}"),
        );

        switch ($method) {
        case 'GET':
            $params['sess'] = '';
            $querystring = http_build_query($params);
            $url = $url.'?'.$querystring;
            $params = '';
            break;
        case 'POST':
        case 'PUT':
        case 'DELETE':
            $params['sess'] = '';
            $params['time'] = $time;
            $params['hash'] = sha1(date('U'));
            break;
        }

        // make the request
        $args = array(
            'method'    => $method,
            'body'      => $params,
            'headers'   => $headers,
            'sslverify' => true,
        );

        // make the remote request
        $result = wp_remote_request($url, $args);
        if (!is_wp_error($result)) {
            return $result['body'];
        } else {
            return $result->get_error_message();
        }
    }

}

$mailgun = new Send_In_Mailgun();

if (is_admin()) {
    if (@include dirname(__FILE__).'/includes/admin.php') {
        $mailgunAdmin = new MailgunAdmin();
    } else {
        Mailgun::deactivate_and_die(dirname(__FILE__).'/includes/admin.php');
    }
}
