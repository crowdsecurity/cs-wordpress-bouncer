<?php

namespace CrowdSecWordPressBouncer;

class AdminNotice
{
    const NOTICE_FIELD = 'crowdsec_admin_notice_message';

    public function displayAdminNotice()
    {
        $option = is_multisite() ? get_site_option(self::NOTICE_FIELD) : get_option(self::NOTICE_FIELD);
        $message = $option['message'] ?? false;
        $noticeLevel = !empty($option['notice-level']) ? $option['notice-level'] : 'notice-error';

        if ($message) {
            echo "<div class='notice {$noticeLevel} is-dismissible'><p>{$message}</p></div>";
            if (is_multisite()) {
                delete_site_option(self::NOTICE_FIELD);
            } else {
                delete_option(self::NOTICE_FIELD);
            }
        } elseif( is_multisite() && isset( $_GET[ 'page' ] ) && in_array($_GET[ 'page' ], ['crowdsec_plugin',
                'crowdsec_theme_settings', 'crowdsec_advanced_settings'])
                 && isset( $_GET[ 'updated'] )  ) {
            ?><div class="notice"><p><b><?php echo __('Settings saved.') ?></b></p></div><?php
        }
    }

    public static function displayError($message)
    {
        self::updateOption($message, 'notice-error');
    }

    public static function displayWarning($message)
    {
        self::updateOption($message, 'notice-warning');
    }

    public static function displayInfo($message)
    {
        self::updateOption($message, 'notice-info');
    }

    public static function displaySuccess($message)
    {
        self::updateOption($message, 'notice-success');
    }

    protected static function updateOption($message, $noticeLevel)
    {
        if (is_multisite()) {
            update_site_option(self::NOTICE_FIELD, [
                'message' => $message,
                'notice-level' => $noticeLevel,
            ]);
        } else {
            update_option(self::NOTICE_FIELD, [
                'message' => $message,
                'notice-level' => $noticeLevel,
            ]);
        }
    }
}
