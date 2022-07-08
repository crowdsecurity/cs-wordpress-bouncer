<?php
require_once __DIR__ . '/_base.php';
function displayAccessForbiddenTemplate(array $config): void
{
    crowdSecBaseTemplatePart1($config, $config['text']['ban_wall']['tab_title']);
    crowdSecBaseTemplatePart2(); ?>
    <h1><?php echo htmlentities($config['text']['ban_wall']['title'], \ENT_QUOTES); ?></h1>
    <p class="desc"><?php echo htmlentities($config['text']['ban_wall']['subtitle'], \ENT_QUOTES); ?></p>
    <?php crowdSecBaseTemplatePart3($config, $config['text']['ban_wall']['footer']);
} ?>