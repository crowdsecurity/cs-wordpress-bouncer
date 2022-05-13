<?php
function crowdSecBaseTemplatePart1($config, $tabTitle): void
{ ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <title><?php echo htmlentities($tabTitle, \ENT_QUOTES); ?></title>
        <meta content="text/html; charset=utf-8" />
        <style>
            * {
                margin: 0;
                padding: 0;
            }

            html {
                height: 100%;
                color:<?php echo htmlentities($config['color']['text']['primary'], \ENT_QUOTES); ?>;
            }

            body {
                background: <?php echo htmlentities($config['color']['background']['page'], \ENT_QUOTES); ?>;
                font-family: Arial, Helvetica, sans-serif;
                height: 100%;
            }

            .container {
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100%;
            }

            .main {
                background: <?php echo htmlentities($config['color']['background']['container'], \ENT_QUOTES); ?>;
                padding: 50px 50px 30px 50px;
                box-shadow: rgba(0, 0, 0, 0.2) 0px 3px 3px -2px, rgba(0, 0, 0, 0.14) 0px 3px 4px 0px, rgba(0, 0, 0, 0.12) 0px 1px 8px 0px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-direction: column;
                z-index: 1;
                position: relative;
            }

            h1 {
                padding: 10px;
            }

            .desc {
                font-size: 1.2em;
                margin-bottom: 30px;
            }

            .powered {
                margin-top: 30px;
                font-size: small;
                color: <?php echo htmlentities($config['color']['text']['secondary'], \ENT_QUOTES); ?>;
            }

            .warning {
                width: 40px;
                display: inline-block;
                vertical-align: -4px;
            }

            .logo {
                width: 17px;
                display: inline-block;
                vertical-align: -12px;
                margin-left: 5px;
            }

            a {
                color: <?php echo htmlentities($config['color']['text']['secondary'], \ENT_QUOTES); ?>;
            }
            <?php echo htmlentities($config['custom_css'], \ENT_QUOTES); ?>
        </style>
    </head>
<?php }
function crowdSecBaseTemplatePart2(): void
{ ?>
    <script>
        function newImage() {
            document.getElementById('refresh').value = "1";
            document.getElementById('captcha').submit();
        }
    </script>

    <body>
        <div class="container">
            <div class="main">
                <?php }
            function crowdSecBaseTemplatePart3(array $config, $footer): void
            {
                if (strlen($footer)) {?><p class="footer"><?php echo htmlentities($footer, \ENT_QUOTES); ?></p><?php }
                if (!$config['hide_crowdsec_mentions']) { ?>
                    <p class="powered">This security check has been powered by
                        <svg class="logo" width="33.92" height="33.76" viewBox="0 0 254.4 253.2">
                            <defs>
                                <clipPath id="a">
                                    <path d="M0 52h84v201.2H0zm0 0" />
                                </clipPath>
                                <clipPath id="b">
                                    <path d="M170 52h84.4v201.2H170zm0 0" />
                                </clipPath>
                            </defs>
                            <path d="M59.3 128.4c1.4 2.3 2.5 4.6 3.4 7-1-4.1-2.3-8.1-4.3-12-3.1-6-7.8-5.8-10.7 0-2 4-3.2 8-4.3 12.1 1-2.4 2-4.8 3.4-7.1 3.4-5.8 8.8-6 12.5 0M207.8 128.4a42.9 42.9 0 013.4 7c-1-4.1-2.3-8.1-4.3-12-3.2-6-7.8-5.8-10.7 0-2 4-3.3 8-4.3 12.1.9-2.4 2-4.8 3.4-7.1 3.4-5.8 8.8-6 12.5 0M134.6 92.9c2 3.5 3.6 7 4.8 10.7-1.3-5.4-3-10.6-5.6-15.7-4-7.5-9.7-7.2-13.3 0a75.4 75.4 0 00-5.6 16c1.2-3.8 2.7-7.4 4.7-11 4.1-7.2 10.6-7.5 15 0M43.8 136.8c.9 4.6 3.7 8.3 7.3 9.2 0 2.7 0 5.5.2 8.2.3 3.3.4 6.6 1 9.6.3 2.3 1 2.2 1.3 0 .5-3 .6-6.3 1-9.6l.2-8.2c3.5-1 6.4-4.6 7.2-9.2a17.8 17.8 0 01-9 2.4c-3.5 0-6.6-1-9.2-2.4M192.4 136.8c.8 4.6 3.7 8.3 7.2 9.2 0 2.7 0 5.5.3 8.2.3 3.3.4 6.6 1 9.6.3 2.3.9 2.2 1.2 0 .6-3 .7-6.3 1-9.6.2-2.7.3-5.5.2-8.2 3.6-1 6.4-4.6 7.3-9.2a17.8 17.8 0 01-9.1 2.4c-3.4 0-6.6-1-9.1-2.4M138.3 104.6c-3.1 1.9-7 3-11.3 3-4.3 0-8.2-1.1-11.3-3 1 5.8 4.5 10.3 9 11.5 0 3.4 0 6.8.3 10.2.4 4.1.5 8.2 1.2 12 .4 2.9 1.2 2.7 1.6 0 .7-3.8.8-7.9 1.2-12 .3-3.4.3-6.8.3-10.2 4.5-1.2 8-5.7 9-11.5" fill="<?php echo htmlentities($config['color']['text']['secondary'], \ENT_QUOTES); ?>" />
                            <path d="M51 146c0 2.7.1 5.5.3 8.2.3 3.3.4 6.6 1 9.6.3 2.3 1 2.2 1.3 0 .5-3 .6-6.3 1-9.6l.2-8.2c3.5-1 6.4-4.6 7.2-9.2a17.8 17.8 0 01-9 2.4c-3.5 0-6.6-1-9.2-2.4.9 4.6 3.7 8.3 7.3 9.2M143.9 105c-1.9-.4-3.5-1.2-4.9-2.3 1.4 5.6 2.5 11.3 4 17 1.2 5 2 10 2.4 15 .6 7.8-4.5 14.5-10.9 14.5h-15c-6.4 0-11.5-6.7-11-14.5.5-5 1.3-10 2.6-15 1.3-5.3 2.3-10.5 3.6-15.7-2.2 1.2-4.8 1.9-7.7 2-4.7.1-9.4-.3-14-1-4-.4-6.7-3-8-6.7-1.3-3.4-2-7-3.3-10.4-.5-1.5-1.6-2.8-2.4-4.2-.4-.6-.8-1.2-.9-1.8v-7.8a77 77 0 0124.5-3c6.1 0 12 1 17.8 3.2 4.7 1.7 9.7 1.8 14.4 0 9-3.4 18.2-3.8 27.5-3 4.9.5 9.8 1.6 14.8 2.4v8.2c0 .6-.3 1.5-.7 1.7-2 .9-2.2 2.7-2.7 4.5-.9 3.2-1.8 6.4-2.9 9.5a11 11 0 01-8.8 7.7 40.6 40.6 0 01-18.4-.2m29.4 80.6c-3.2-26.8-6.4-50-8.9-60.7a14.3 14.3 0 0014.1-14h.4a9 9 0 005.6-16.5 14.3 14.3 0 00-3.7-27.2 9 9 0 00-6.9-14.6c2.4-1.1 4.5-3 5.8-5 3.4-5.3 4-29-8-44.4-5-6.3-9.8-2.5-10 1.8-1 13.2-1.1 23-4.5 34.3a9 9 0 00-16-4.1 14.3 14.3 0 00-28.4 0 9 9 0 00-16 4.1c-3.4-11.2-3.5-21.1-4.4-34.3-.3-4.3-5.2-8-10-1.8-12 15.3-11.5 39-8.1 44.4 1.3 2 3.4 3.9 5.8 5a9 9 0 00-7 14.6 14.3 14.3 0 00-3.6 27.2A9 9 0 0075 111h.5a14.5 14.5 0 0014.3 14c-4 17.2-10 66.3-15 111.3l-1.3 13.4a1656.4 1656.4 0 01106.6 0l-1.4-12.7-5.4-51.3" fill="<?php echo htmlentities($config['color']['text']['secondary'], \ENT_QUOTES); ?>" />
                            <g clip-path="url(#a)">
                                <path d="M83.5 136.6l-2.3.7c-5 1-9.8 1-14.8-.2-1.4-.3-2.7-1-3.8-1.9l3.1 13.7c1 4 1.7 8 2 12 .5 6.3-3.6 11.6-8.7 11.6H46.9c-5.1 0-9.2-5.3-8.7-11.6.3-4 1-8 2-12 1-4.2 1.8-8.5 2.9-12.6-1.8 1-3.9 1.5-6.3 1.6a71 71 0 01-11.1-.7 7.7 7.7 0 01-6.5-5.5c-1-2.7-1.6-5.6-2.6-8.3-.4-1.2-1.3-2.3-2-3.4-.2-.4-.6-1-.6-1.4v-6.3c6.4-2 13-2.6 19.6-2.5 4.9.1 9.6 1 14.2 2.6 3.9 1.4 7.9 1.5 11.7 0 1.8-.7 3.6-1.2 5.5-1.6a13 13 0 01-1.6-15.5A18.3 18.3 0 0159 73.1a11.5 11.5 0 00-17.4 8.1 7.2 7.2 0 00-12.9 3.3c-2.7-9-2.8-17-3.6-27.5-.2-3.4-4-6.5-8-1.4C7.5 67.8 7.9 86.9 10.6 91c1.1 1.7 2.8 3.1 4.7 4a7.2 7.2 0 00-5.6 11.7 11.5 11.5 0 00-2.9 21.9 7.2 7.2 0 004.5 13.2h.3c0 .6 0 1.1.2 1.7.9 5.4 5.6 9.5 11.3 9.5A1177.2 1177.2 0 0010 253.2c18.1-1.5 38.1-2.6 59.5-3.4.4-4.6.8-9.3 1.4-14 1.2-11.6 3.3-30.5 5.7-49.7 2.2-18 4.7-36.3 7-49.5" fill="<?php echo htmlentities($config['color']['text']['secondary'], \ENT_QUOTES); ?>" />
                            </g>
                            <g clip-path="url(#b)">
                                <path d="M254.4 118.2c0-5.8-4.2-10.5-9.7-11.4a7.2 7.2 0 00-5.6-11.7c2-.9 3.6-2.3 4.7-4 2.7-4.2 3.1-23.3-6.5-35.5-4-5.1-7.8-2-8 1.4-.8 10.5-.9 18.5-3.6 27.5a7.2 7.2 0 00-12.8-3.3 11.5 11.5 0 00-17.8-7.9 18.4 18.4 0 01-4.5 22 13 13 0 01-1.3 15.2c2.4.5 4.8 1 7.1 2 3.8 1.3 7.8 1.4 11.6 0 7.2-2.8 14.6-3 22-2.4 4 .4 7.9 1.2 12 1.9l-.1 6.6c0 .5-.2 1.2-.5 1.3-1.7.7-1.8 2.2-2.2 3.7l-2.3 7.6a8.8 8.8 0 01-7 6.1c-5 1-10 1-14.9-.2-1.5-.3-2.8-1-3.9-1.9 1.2 4.5 2 9.1 3.2 13.7 1 4 1.6 8 2 12 .4 6.3-3.6 11.6-8.8 11.6h-12c-5.2 0-9.3-5.3-8.8-11.6.4-4 1-8 2-12 1-4.2 1.9-8.5 3-12.6-1.8 1-4 1.5-6.3 1.6-3.7 0-7.5-.3-11.2-.7a7.7 7.7 0 01-3.7-1.5c3.1 18.4 7.1 51.2 12.5 100.9l.6 5.3.8 7.9c21.4.7 41.5 1.9 59.7 3.4L243 243l-4.4-41.2a606 606 0 00-7-48.7 11.5 11.5 0 0011.2-11.2h.4a7.2 7.2 0 004.4-13.2c4-1.8 6.8-5.8 6.8-10.5" fill="<?php echo htmlentities($config['color']['text']['secondary'], \ENT_QUOTES); ?>" />
                            </g>
                            <path d="M180 249.6h.4a6946 6946 0 00-7.1-63.9l5.4 51.3 1.4 12.6M164.4 125c2.5 10.7 5.7 33.9 8.9 60.7a570.9 570.9 0 00-8.9-60.7M74.8 236.3l-1.4 13.4 1.4-13.4" fill="<?php echo htmlentities($config['color']['text']['secondary'], \ENT_QUOTES); ?>" />
                        </svg>
                        <a href="https://crowdsec.net/" target="_blank" rel="noopener">CrowdSec</a>
                    </p>
                <?php } ?>
            </div>
        </div>
    </body>

    </html>
<?php
            } ?>