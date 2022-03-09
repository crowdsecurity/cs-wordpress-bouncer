<?php
require_once __DIR__.'/_base.php';
function displayCaptchaTemplate(bool $error, string $captchaImageSrc, string $captchaResolutionFormUrl, array $config): void
{
    crowdSecBaseTemplatePart1($config, $config['text']['captcha_wall']['tab_title']); ?><style>
        input {
            margin-top: 10px;
            padding: 10px;
            font-size: 1.1em;
            width: 150px;
        }

        button {
            margin: 30px 0 30px;
            padding: 10px 0;
            font-size: 1.1em;
            background-color: <?php echo htmlentities($config['color']['background']['button'], \ENT_QUOTES); ?>;
            color: <?php echo htmlentities($config['color']['text']['button'], \ENT_QUOTES); ?>;
            border: none;
            width: 150px;
            border-radius: 5px;
        }

        button:hover {
            background-color: <?php echo htmlentities($config['color']['background']['button_hover'], \ENT_QUOTES); ?>;
            cursor: pointer;
        }

        img {
            padding: 10px;
        }

        .error {
            color: <?php echo htmlentities($config['color']['text']['error_message'], \ENT_QUOTES); ?>;
            padding: 5px;
        }
    </style>
    <?php crowdSecBaseTemplatePart2(); ?>
    <h1>
        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="exclamation-triangle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" class="warning">
            <path fill="#f39b2f" d="M569.517 440.013C587.975 472.007 564.806 512 527.94 512H48.054c-36.937 0-59.999-40.055-41.577-71.987L246.423 23.985c18.467-32.009 64.72-31.951 83.154 0l239.94 416.028zM288 354c-25.405 0-46 20.595-46 46s20.595 46 46 46 46-20.595 46-46-20.595-46-46-46zm-43.673-165.346l7.418 136c.347 6.364 5.609 11.346 11.982 11.346h48.546c6.373 0 11.635-4.982 11.982-11.346l7.418-136c.375-6.874-5.098-12.654-11.982-12.654h-63.383c-6.884 0-12.356 5.78-11.981 12.654z"></path>
        </svg>
        <?php echo htmlentities($config['text']['captcha_wall']['title'], \ENT_QUOTES); ?>
    </h1>
    <p class="desc"><?php echo htmlentities($config['text']['captcha_wall']['subtitle'], \ENT_QUOTES); ?></p>

    <img src="<?php echo htmlentities($captchaImageSrc, \ENT_QUOTES); ?>" alt="captcha" />
    <p><small><a id="refresh_link" href="#" onclick="newImage()"><?php echo htmlentities($config['text']['captcha_wall']['refresh_image_link'], \ENT_QUOTES); ?></a></small></p>

    <form method="post" id="captcha" action="<?php echo htmlentities($captchaResolutionFormUrl, \ENT_QUOTES); ?>">
        <input type="text" name="phrase" placeholder="<?php echo htmlentities($config['text']['captcha_wall']['captcha_placeholder'], \ENT_QUOTES); ?>" autofocus autocomplete="off" />
        <input type="hidden" name="crowdsec_captcha" value="1">
        <input type="hidden" name="refresh" value="0" id="refresh">
        <?php if ($error) { ?><p class="error"><?php echo htmlentities($config['text']['captcha_wall']['error_message'], \ENT_QUOTES); ?></p><?php } ?>

        <button type="submit" /><?php echo htmlentities($config['text']['captcha_wall']['send_button'], \ENT_QUOTES); ?></button>
    </form>
<?php crowdSecBaseTemplatePart3($config, $config['text']['captcha_wall']['footer']);
} ?>