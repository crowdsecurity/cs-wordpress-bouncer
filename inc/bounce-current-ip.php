<?php

require_once __DIR__.'/Bounce.php';

function safelyBounceCurrentIp()
{
    $bounce = new Bounce();
    $bounce->init();
    $bounce->safelyBounce();
}
