<?php

/*
 * mailgun-wordpress-plugin - Sending mail from Wordpress using Mailgun
 * Copyright (C) 2016 Mailgun, et al.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

?>
        <div class="wrap">
            <h2><?php _e('Mailgun', 'mailgun'); ?></h2>
            <p>A <a target="_blank" href="http://www.mailgun.com/">Mailgun</a> account is required to use this plugin and the Mailgun service.</p>
            <p>If you need to register for an account, you can do so at <a target="_blank" href="http://www.mailgun.com/">http://www.mailgun.com/</a>.</p>
            <form id="mailgun-form" action="options.php" method="post">
                <?php settings_fields('mailgun'); ?>
                <h3><?php _e('Configuration', 'mailgun'); ?></h3>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <?php _e('Use HTTP API', 'mailgun'); ?>
                        </th>
                        <td>
                            <select id="mailgun-api" name="mailgun[useAPI]">
                                <option value="1"<?php selected('1', $this->get_option('useAPI')); ?>><?php _e('Yes', 'mailgun'); ?></option>
                                <option value="0"<?php selected('0', $this->get_option('useAPI')); ?>><?php _e('No', 'mailgun'); ?></option>
                            </select>
                            <p class="description"><?php _e('Set this to "No" if your server cannot make outbound HTTP connections or if emails are not being delivered. "No" will cause this plugin to use SMTP. Default "Yes".', 'mailgun'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php _e('Mailgun Domain Name', 'mailgun'); ?>
                        </th>
                        <td>
                            <input type="text" class="regular-text" name="mailgun[domain]" value="<?php esc_attr_e($this->get_option('domain')); ?>" placeholder="samples.mailgun.org" />
                            <p class="description"><?php _e('Your Mailgun Domain Name.', 'mailgun'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top" class="mailgun-api">
                        <th scope="row">
                            <?php _e('API Key', 'mailgun'); ?>
                        </th>
                        <td>
                            <input type="text" class="regular-text" name="mailgun[apiKey]" value="<?php esc_attr_e($this->get_option('apiKey')); ?>" placeholder="key-3ax6xnjp29jd6fds4gc373sgvjxteol0" />
                            <p class="description"><?php _e('Your Mailgun API key. Only valid for use with the API.', 'mailgun'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php _e('Click Tracking', 'mailgun'); ?>
                        </th>
                        <td>
                            <select name="mailgun[track-clicks]">
                                <option value="htmlonly"<?php selected('htmlonly', $this->get_option('track-clicks')); ?>><?php _e('HTML Only', 'mailgun'); ?></option>
                                <option value="yes"<?php selected('yes', $this->get_option('track-clicks')); ?>><?php _e('Yes', 'mailgun'); ?></option>
                                <option value="no"<?php selected('no', $this->get_option('track-clicks')); ?>><?php _e('No', 'mailgun'); ?></option>
                            </select>
                            <p class="description"><?php _e('If enabled, Mailgun will  and track links.', 'mailgun'); ?> <a href="http://documentation.mailgun.com/user_manual.html#tracking-clicks" target="_blank">Click Tracking Documentation</a></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php _e('Open Tracking', 'mailgun'); ?>
                        </th>
                        <td>
                            <select name="mailgun[track-opens]">
                                <option value="1"<?php selected('1', $this->get_option('track-opens')); ?>><?php _e('Yes', 'mailgun'); ?></option>
                                <option value="0"<?php selected('0', $this->get_option('track-opens')); ?>><?php _e('No', 'mailgun'); ?></option>
                            </select>
                            <p class="description"><?php _e('If enabled, HTML messages will include an open tracking beacon.', 'mailgun'); ?> <a href="http://documentation.mailgun.com/user_manual.html#tracking-opens" target="_blank">Open Tracking Documentation</a></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php _e('From Address', 'mailgun'); ?>
                        </th>
                        <td>
                            <input type="text" class="regular-text" name="mailgun[from-address]" value="<?php esc_attr_e($this->get_option('from-address')); ?>" placeholder="wordpress@mydomain.com" />
                            <p class="description"><?php _e('The <address@mydomain.com> part of the sender information (<code>"Excited User &lt;user@samples.mailgun.org&gt;"</code>). This address will appear as the `From` address on sent mail. <strong>It is recommended that the @mydomain portion matches your Mailgun sending domain.</strong>', 'mailgun'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php _e('From Name', 'mailgun'); ?>
                        </th>
                        <td>
                            <input type="text" class="regular-text" name="mailgun[from-name]" value="<?php esc_attr_e($this->get_option('from-name')); ?>" placeholder="WordPress" />
                            <p class="description"><?php _e('The "User Name" part of the sender information (<code>"Excited User &lt;user@samples.mailgun.org&gt;"</code>).', 'mailgun'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php _e('Override "From" Details', 'mailgun'); ?>
                        </th>
                        <td>
                            <select name="mailgun[override-from]">
                                <option value="1"<?php selected('1', $this->get_option('override-from', null, '0')); ?>><?php _e('Yes', 'mailgun'); ?></option>
                                <option value="0"<?php selected('0', $this->get_option('override-from', null, '0')); ?>><?php _e('No', 'mailgun'); ?></option>
                            </select>
                            <p class="description"><?php _e('If enabled, all emails will be sent with the above "From Name" and "From Address", regardless of values set by other plugins. Useful for cases where other plugins don\'t play nice with our "From Name" / "From Address" setting.', 'mailgun'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php _e('Tag', 'mailgun'); ?>
                        </th>
                        <td>
                            <input type="text" class="regular-text" name="mailgun[campaign-id]" value="<?php esc_attr_e($this->get_option('campaign-id')); ?>" placeholder="tag" />
                            <p class="description"><?php _e('If added, this tag will exist on every outbound message. Statistics will be populated in the Mailgun Control Panel. Use a comma to define multiple tags.', 'mailgun'); ?> <?php _e('Learn more about', 'mailgun'); ?> <a href="https://documentation.mailgun.com/user_manual.html#tracking-messages" target="_blank">Tracking</a> <?php _e('and', 'mailgun'); ?> <a href="https://documentation.mailgun.com/user_manual.html#tagging" target="_blank">Tagging</a>.</p>
                        </td>
                    </tr>
                </table>

                <p><?php _e('Before attempting to test the configuration, please click "Save Changes".', 'mailgun'); ?></p>
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Save Changes', 'mailgun'); ?>" />
                    <input type="button" id="mailgun-test" class="button-secondary" value="<?php _e('Test Configuration', 'mailgun'); ?>" />
                </p>
            </form>
        </div>
