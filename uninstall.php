<?php

/**
 * Trigger this file on Plugin uninstall
 */

 if ( ! defined ( 'WP_UNINSTALL_PLUGIN' )) {
     die;
 }

 // TODO P3 if we don't deactivate before deleting, are the wp_option delete ? test this case.