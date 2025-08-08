<?php

    defined('ABSPATH') || exit;

    function igm_render_settings_page()
    {
        echo '<div class="wrap"><h1>Integration Settings</h1>';
        igm_render_schedule_settings_form();
        igm_render_teachable_circle_settings_form();
        igm_render_community_settings_form();
        echo '</div>';
    }

    function igm_render_schedule_settings_form()
    {
        if (isset($_POST['save_schedule']) && check_admin_referer('igm_save_settings', 'igm_settings_nonce')) {
            update_option('igm_delete_cron_interval', sanitize_text_field($_POST['igm_delete_cron_interval']));
            update_option('igm_update_cron_interval', sanitize_text_field($_POST['igm_update_cron_interval']));
            echo '<div class="updated"><p>Schedule settings saved.</p></div>';

            wp_clear_scheduled_hook('igm_cron_update_course_names');
            wp_clear_scheduled_hook('igm_cron_delete_removed_courses');

            $delete_interval = get_option('igm_delete_cron_interval', '');
            $update_interval = get_option('igm_update_cron_interval', '');

            if ($delete_interval !== 'disabled') {
                wp_schedule_event(time(), $delete_interval, 'igm_cron_delete_removed_courses');
            }
            if ($update_interval !== 'disabled') {
                wp_schedule_event(time(), $update_interval, 'igm_cron_update_course_names');
            }
        }

        $update_interval = get_option('igm_update_cron_interval', '');
        $delete_interval = get_option('igm_delete_cron_interval', '');

    ?>
    <form method="post">
        <?php wp_nonce_field('igm_save_settings', 'igm_settings_nonce'); ?>
        <h2>Schedule Settings</h2>
        <table class="form-table">
            <tr>
                <th>Update Interval</th>
                <td>
                    <select name="igm_update_cron_interval">
                        <?php foreach (igm_get_cron_options(true) as $val => $label): ?>
                            <option value="<?php echo esc_attr($val); ?>"<?php selected($update_interval, $val); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>Delete Interval</th>
                <td>
                    <select name="igm_delete_cron_interval">
                        <?php foreach (igm_get_cron_options(true) as $val => $label): ?>
                            <option value="<?php echo esc_attr($val); ?>"<?php selected($delete_interval, $val); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php submit_button('Save Schedule', 'primary', 'save_schedule'); ?>
    </form>
    <?php
        }

        function igm_render_teachable_circle_settings_form()
        {
            $circle_v1     = get_option('igm_circle_api_token_v1', '');
            $circle_v2     = get_option('igm_circle_api_token_v2', '');
            $teachable_api = get_option('igm_teachable_api_key', '');

            if (isset($_POST['save_api_tokens']) && check_admin_referer('igm_save_settings', 'igm_settings_nonce')) {
                $new_circle_v1     = sanitize_text_field($_POST['igm_circle_api_v1']);
                $new_circle_v2     = sanitize_text_field($_POST['igm_circle_api_v2']);
                $new_teachable_api = sanitize_text_field($_POST['igm_teachable_api_token']);

                if ($new_circle_v1 !== mask_token($circle_v1)) {
                    update_option('igm_circle_api_token_v1', $new_circle_v1);
                    $circle_v1 = $new_circle_v1;
                }
                if ($new_circle_v2 !== mask_token($circle_v2)) {
                    update_option('igm_circle_api_token_v2', $new_circle_v2);
                    $circle_v2 = $new_circle_v2;
                }
                if ($new_teachable_api !== mask_token($teachable_api)) {
                    update_option('igm_teachable_api_key', $new_teachable_api);
                    $teachable_api = $new_teachable_api;
                }
                echo '<div class="updated"><p>API tokens saved.</p></div>';
            }

        ?>
    <form method="post">
        <?php wp_nonce_field('igm_save_settings', 'igm_settings_nonce'); ?>
        <h2>API Keys</h2>
        <table class="form-table">
            <tr>
                <th>Circle API V1</th>
                <td><input type="text" name="igm_circle_api_v1"
                           value="<?php echo esc_attr(mask_token($circle_v1)); ?>"
                           class="regular-text" onfocus="clearIfMasked(this)" /></td>
            </tr>
            <tr>
                <th>Circle API V2</th>
                <td><input type="text" name="igm_circle_api_v2"
                           value="<?php echo esc_attr(mask_token($circle_v2)); ?>"
                           class="regular-text" onfocus="clearIfMasked(this)" /></td>
            </tr>
            <tr>
                <th>Teachable API Token</th>
                <td><input type="text" name="igm_teachable_api_token"
                           value="<?php echo esc_attr(mask_token($teachable_api)); ?>"
                           class="regular-text" onfocus="clearIfMasked(this)" /></td>
            </tr>
        </table>
        <?php submit_button('Save Tokens', 'primary', 'save_api_tokens'); ?>
    </form>

    <script>
        function clearIfMasked(input) {
            if (input.value.includes('*')) {
                input.value = '';
            }
        }
    </script>
    <?php
    }
    function igm_render_community_settings_form()
    {
        if (isset($_POST['save_community']) && check_admin_referer('igm_save_settings', 'igm_settings_nonce')) {
            update_option('igm_circle_community_id', sanitize_text_field($_POST['igm_circle_community_id']));
            echo '<div class="updated"><p>Community saved.</p></div>';
        }
        $selected    = get_option('igm_circle_community_id', '');
        $communities = igm_fetch_circle_communities();
    ?>
    <form method="post">
        <?php wp_nonce_field('igm_save_settings', 'igm_settings_nonce'); ?>
        <h2>Circle Community</h2>
        <table class="form-table">
            <tr>
                <th>Select Community</th>
                <td>
                    <select name="igm_circle_community_id">
                        <?php if (is_array($communities) && ! empty($communities)): ?>
                            <option value="">— Select a Community —</option>
                            <?php foreach ($communities as $id => $name): ?>
                                <option value="<?php echo esc_attr($id); ?>"<?php selected($selected, $id); ?>>
                                    <?php echo esc_html($name); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <option value="" disabled>⚠️ No communities found – check Circle API key</option>
                        <?php endif; ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php submit_button('Save Community', 'primary', 'save_community'); ?>
    </form>
    <?php
    }
    function igm_get_cron_options($include_disabled = false)
    {
        $options = [
            'every_minute' => 'Every Minute',
            'hourly'       => 'Hourly',
            'twicedaily'   => 'Twice Daily',
            'daily'        => 'Daily',
        ];
        return $include_disabled ? ['disabled' => 'Disabled'] + $options : $options;
    }
    function mask_token($token)
    {
        $len = strlen($token);
        return ($len > 4) ? str_repeat('*', $len - 4) . substr($token, -4) : $token;
    }