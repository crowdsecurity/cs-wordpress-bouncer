<?php

/*
We MUST load the WP CORE because boucing require these function, constants or variables :
- wp_specialchars_decode
- get_option
- esc_attr
- is_admin
- $GLOBALS['pagenow']
- WP_DEBUG
*/
require __DIR__.'/../../../../wp-load.php';

$bounce = new Bounce();
$bounce->init();
$bounce->safelyBounce();
