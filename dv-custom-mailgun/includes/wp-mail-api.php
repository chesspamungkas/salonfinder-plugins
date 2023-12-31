<?php
// Include MG filter functions
if (!include dirname(__FILE__).'/mg-filter.php') {
    Send_In_Mailgun::deactivate_and_die(dirname(__FILE__).'/mg-filter.php');
}

function mg_api_last_error($error = null)
{
    static $last_error;

    if (null === $error) {
        return $last_error;
    } else {
        $tmp = $last_error;
        $last_error = $error;

        return $tmp;
    }
}

add_filter('mg_mutate_to_rcpt_vars', 'mg_mutate_to_rcpt_vars_cb');
function mg_mutate_to_rcpt_vars_cb($to_addrs)
{
    if (is_string($to_addrs)) {
        $to_addrs = explode(',', $to_addrs);
    }

    if (has_filter('mg_use_recipient_vars_syntax')) {
        $use_rcpt_vars = apply_filters('mg_use_recipient_vars_syntax', null);
        if ($use_rcpt_vars) {
            $vars = array();

            $idx = 0;
            foreach ($to_addrs as $addr) {
                $rcpt_vars[$addr] = array('batch_msg_id' => $idx);
                $idx++;
            }

            return array(
                'to'        => '%recipient%',
                'rcpt_vars' => json_encode($rcpt_vars),
            );
        }
    }

    return array(
        'to'        => $to_addrs,
        'rcpt_vars' => null,
    );
}

function wp_mail($to, $subject, $message, $headers = '', $attachments = array())
{
    // Compact the input, apply the filters, and extract them back out
    extract(apply_filters('wp_mail', compact('to', 'subject', 'message', 'headers', 'attachments')));

    $mailgun = get_option('mailgun');
    $apiKey = (defined('MAILGUN_APIKEY') && MAILGUN_APIKEY) ? MAILGUN_APIKEY : $mailgun['apiKey'];
    $domain = (defined('MAILGUN_DOMAIN') && MAILGUN_DOMAIN) ? MAILGUN_DOMAIN : $mailgun['domain'];

    if (empty($apiKey) || empty($domain)) {
        return false;
    }

    if (!is_array($attachments)) {
        $attachments = explode("\n", str_replace("\r\n", "\n", $attachments));
    }

    // Headers
    if (empty($headers)) {
        $headers = array();
    } else {
        if (!is_array($headers)) {
            // Explode the headers out, so this function can take both
            // string headers and an array of headers.
            $tempheaders = explode("\n", str_replace("\r\n", "\n", $headers));
        } else {
            $tempheaders = $headers;
        }
        $headers = array();
        $cc = array();
        $bcc = array();

        // If it's actually got contents
        if (!empty($tempheaders)) {
            // Iterate through the raw headers
            foreach ((array) $tempheaders as $header) {
                if (strpos($header, ':') === false) {
                    if (false !== stripos($header, 'boundary=')) {
                        $parts = preg_split('/boundary=/i', trim($header));
                        $boundary = trim(str_replace(array("'", '"'), '', $parts[1]));
                    }
                    continue;
                }
                // Explode them out
                list($name, $content) = explode(':', trim($header), 2);

                // Cleanup crew
                $name = trim($name);
                $content = trim($content);

                switch (strtolower($name)) {
                    // Mainly for legacy -- process a From: header if it's there
                case 'from':
                    if (strpos($content, '<') !== false) {
                        // So... making my life hard again?
                        $from_name = substr($content, 0, strpos($content, '<') - 1);
                        $from_name = str_replace('"', '', $from_name);
                        $from_name = trim($from_name);

                        $from_email = substr($content, strpos($content, '<') + 1);
                        $from_email = str_replace('>', '', $from_email);
                        $from_email = trim($from_email);
                    } else {
                        $from_email = trim($content);
                    }
                    break;
                case 'content-type':
                    if (strpos($content, ';') !== false) {
                        list($type, $charset) = explode(';', $content);
                        $content_type = trim($type);
                        if (false !== stripos($charset, 'charset=')) {
                            $charset = trim(str_replace(array('charset=', '"'), '', $charset));
                        } elseif (false !== stripos($charset, 'boundary=')) {
                            $boundary = trim(str_replace(array('BOUNDARY=', 'boundary=', '"'), '', $charset));
                            $charset = '';
                        }
                    } else {
                        $content_type = trim($content);
                    }
                    break;
                case 'cc':
                    $cc = array_merge((array) $cc, explode(',', $content));
                    break;
                case 'bcc':
                    $bcc = array_merge((array) $bcc, explode(',', $content));
                    break;
                default:
                    // Add it to our grand headers array
                    $headers[trim($name)] = trim($content);
                    break;
                }
            }
        }
    }

    if (!isset($from_name)) {
        $from_name = null;
    }

    if (!isset($from_email)) {
        $from_email = null;
    }

    $from_name = mg_detect_from_name($from_name);
    $from_email = mg_detect_from_address($from_email);

    $body = array(
        'from'    => "{$from_name} <{$from_email}>",
        'to'      => $to,
        'subject' => $subject,
    );

    $rcpt_data = apply_filters('mg_mutate_to_rcpt_vars', $to);
    if (!is_null($rcpt_data['rcpt_vars'])) {
        $body['recipient-variables'] = $rcpt_data['rcpt_vars'];
    }

    $body['o:tag'] = array();
    $body['o:tracking-clicks'] = !empty($mailgun['track-clicks']) ? $mailgun['track-clicks'] : 'no';
    $body['o:tracking-opens'] = empty($mailgun['track-opens']) ? 'no' : 'yes';

    // this is the wordpress site tag
    if (isset($mailgun['tag'])) {
        $tags = explode(',', str_replace(' ', '', $mailgun['tag']));
        $body['o:tag'] = $tags;
    }

    // campaign-id now refers to a list of tags which will be appended to the site tag
    if (!empty($mailgun['campaign-id'])) {
        $tags = explode(',', str_replace(' ', '', $mailgun['campaign-id']));
        if (empty($body['o:tag'])) {
            $body['o:tag'] = $tags;
        } elseif (is_array($body['o:tag'])) {
            $body['o:tag'] = array_merge($body['o:tag'], $tags);
        } else {
            $body['o:tag'] .= ','.$tags;
        }
    }

    if (!empty($cc) && is_array($cc)) {
        $body['cc'] = implode(', ', $cc);
    }

    if (!empty($bcc) && is_array($bcc)) {
        $body['bcc'] = implode(', ', $bcc);
    }

    // If we are not given a Content-Type in the supplied headers,
    // write the message body to a file and try to determine the mimetype
    // using get_mime_content_type.
    if (!isset($content_type)) {
        $tmppath = tempnam(sys_get_temp_dir(), 'mg');
        $tmp = fopen($tmppath, 'w+');

        fwrite($tmp, $message);
        fclose($tmp);

        $content_type = get_mime_content_type($tmppath, 'text/plain');

        unlink($tmppath);
    }

    // Allow external content type filter to function normally
    if (has_filter('wp_mail_content_type')) {
        $content_type = apply_filters(
            'wp_mail_content_type',
            $content_type
        );
    }

    if ('text/plain' === $content_type) {
        $body['text'] = $message;
    } else if ('text/html' === $content_type) {
        $body['html'] = $message;
    } else {
        // Unknown Content-Type??
        error_log('[mailgun] Got unknown Content-Type: ' . $content_type);
        $body['text'] = $message;
        $body['html'] = $message;
    }

    // If we don't have a charset from the input headers
    if (!isset($charset)) {
        $charset = get_bloginfo('charset');
    }

    // Set the content-type and charset
    $charset = apply_filters('wp_mail_charset', $charset);
    if (isset($headers['Content-Type'])) {
        if (!strstr($headers['Content-Type'], 'charset')) {
            $headers['Content-Type'] = rtrim($headers['Content-Type'], '; ')."; charset={$charset}";
        }
    }

    // Set custom headers
    if (!empty($headers)) {
        foreach ((array) $headers as $name => $content) {
            $body["h:{$name}"] = $content;
        }

    }

    $payload = '';

    // First, generate a boundary for the multipart message.
    $boundary = base_convert(uniqid('boundary', true), 10, 36);

    // Allow other plugins to apply body changes before creating the payload.
    $body = apply_filters('mg_mutate_message_body', $body);
    if ( ($body_payload = mg_build_payload_from_body($body, $boundary)) != null ) {
        $payload .= $body_payload;
    }

    // Allow other plugins to apply attachent changes before writing to the payload.
    $attachments = apply_filters('mg_mutate_attachments', $attachments);
    if ( ($attachment_payload = mg_build_attachments_payload($attachments, $boundary)) != null ) {
        $payload .= $attachment_payload;
    }

    $payload .= '--'.$boundary.'--';

    $data = array(
        'body'    => $payload,
        'headers' => array(
            'Authorization' => 'Basic '.base64_encode("api:{$apiKey}"),
            'Content-Type'  => 'multipart/form-data; boundary='.$boundary,
        ),
    );

    $url = "https://api.mailgun.net/v3/{$domain}/messages";

    // overriding this function, let's add looping here to handle that
    $response = wp_remote_post($url, $data);
    if (is_wp_error($response)) {
        // Store WP error in last error.
        mg_api_last_error($response->get_error_message());

        return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = json_decode(wp_remote_retrieve_body($response));

    if ((int) $response_code != 200 && !isset($response_body->message)) {
        // Store response code and HTTP response message in last error.
        $response_message = wp_remote_retrieve_response_message($response);
        $errmsg = "$response_code - $response_message";
        mg_api_last_error($errmsg);

        return false;
    }

    // Not sure there is any additional checking that needs to be done here, but why not?
    if ($response_body->message != 'Queued. Thank you.') {
        mg_api_last_error($response_body->message);

        return false;
    }

    return true;
}

function mg_build_payload_from_body($body, $boundary) {
    $payload = '';

    // Iterate through pre-built params and build payload:
    foreach ($body as $key => $value) {
        if (is_array($value)) {
            $parent_key = $key;
            foreach ($value as $key => $value) {
                $payload .= '--'.$boundary;
                $payload .= "\r\n";
                $payload .= 'Content-Disposition: form-data; name="'.$parent_key."\"\r\n\r\n";
                $payload .= $value;
                $payload .= "\r\n";
            }
        } else {
            $payload .= '--'.$boundary;
            $payload .= "\r\n";
            $payload .= 'Content-Disposition: form-data; name="'.$key.'"'."\r\n\r\n";
            $payload .= $value;
            $payload .= "\r\n";
        }
    }

    return $payload;
}

function mg_build_payload_from_mime($body, $boundary) {
}

function mg_build_attachments_payload($attachments, $boundary) {
    $payload = '';

    // If we have attachments, add them to the payload.
    if (!empty($attachments)) {
        $i = 0;
        foreach ($attachments as $attachment) {
            if (!empty($attachment)) {
                $payload .= '--'.$boundary;
                $payload .= "\r\n";
                $payload .= 'Content-Disposition: form-data; name="attachment['.$i.']"; filename="'.basename($attachment).'"'."\r\n\r\n";
                $payload .= file_get_contents($attachment);
                $payload .= "\r\n";
                $i++;
            }
        }
    } else {
        return null;
    }

    return $payload;
}
