<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    $tabTitle = $config['text']['captcha_wall']['tab_title'] ?? '';
    include __DIR__ . '/walls-partial/head.php';
    ?>
    <script>
        function newImageForCrowdSec() {
            document.getElementById('refresh').value = "1";
            document.getElementById('captcha').submit();
        }
    </script>
</head>
<body>
<div class="container">
    <div class="main">
        <h1>
            <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="exclamation-triangle" role="img"
                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" class="warning">
                <path fill="#f39b2f"
                      d="M569.517 440.013C587.975 472.007 564.806 512 527.94 512H48.054c-36.937 0-59.999-40.055-41.577-71.987L246.423 23.985c18.467-32.009 64.72-31.951 83.154 0l239.94 416.028zM288 354c-25.405 0-46 20.595-46 46s20.595 46 46 46 46-20.595 46-46-20.595-46-46-46zm-43.673-165.346l7.418 136c.347 6.364 5.609 11.346 11.982 11.346h48.546c6.373 0 11.635-4.982 11.982-11.346l7.418-136c.375-6.874-5.098-12.654-11.982-12.654h-63.383c-6.884 0-12.356 5.78-11.981 12.654z"></path>
            </svg>
            <?php
                echo htmlspecialchars($config['text']['captcha_wall']['title'] ?? '', ENT_QUOTES, 'UTF-8');
            ?>
        </h1>
        <p class="desc"><?php
            echo htmlspecialchars($config['text']['captcha_wall']['subtitle'] ?? '', ENT_QUOTES, 'UTF-8');
            ?></p>

        <img src="<?php
        echo htmlspecialchars($config['captcha_img'] ?? '', ENT_QUOTES, 'UTF-8');
        ?>" alt="captcha"/>
        <p><small><a id="refresh_link" href="#"
                     onclick="newImageForCrowdSec()"><?php
                    echo htmlspecialchars($config['text']['captcha_wall']['refresh_image_link'] ?? '', ENT_QUOTES, 'UTF-8');
                    ?></a></small></p>

        <form method="post" id="captcha" action="#">
            <script>document.querySelector("#captcha").setAttribute("action", "<?php
                    echo htmlspecialchars($config['captcha_resolution_url'] ?? '', ENT_QUOTES, 'UTF-8');
                    ?>")</script>
            <input type="text" name="phrase" placeholder="<?php
            echo htmlspecialchars($config['text']['captcha_wall']['captcha_placeholder'] ?? '', ENT_QUOTES, 'UTF-8');
            ?>" autofocus
                   autocomplete="off"/>
            <input type="hidden" name="crowdsec_captcha" value="1">
            <input type="hidden" name="refresh" value="0" id="refresh">
            <?php if (!empty($config['error'])): ?>
            <p class="error"><?php
                echo htmlspecialchars($config['text']['captcha_wall']['error_message'] ?? '', ENT_QUOTES, 'UTF-8');
                ?></p>
            <?php endif; ?>
            <button type="submit"><?php
                echo htmlspecialchars($config['text']['captcha_wall']['send_button'] ?? '', ENT_QUOTES, 'UTF-8');
                ?></button>
        </form>
        <?php if (!empty($config['text']['ban_wall']['footer'])): ?>
            <p class="footer"><?php echo htmlspecialchars($config['text']['ban_wall']['footer'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <?php if (empty($config['hide_mentions'])): ?>
            <?php include __DIR__ . '/walls-partial/mentions.php'; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
