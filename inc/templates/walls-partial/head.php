<title>
    <?php
        echo htmlspecialchars($tabTitle ?? '', ENT_QUOTES, 'UTF-8');
    ?>
</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
    * {
        margin: 0;
        padding: 0;
    }

    html {
        height: 100%;
        color: <?php echo htmlspecialchars($config['color']['text']['primary'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
    }

    body {
        background: <?php echo htmlspecialchars($config['color']['background']['page'] ?? '', ENT_QUOTES, 'UTF-8'); ?>;
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
        background: <?php echo htmlspecialchars($config['color']['background']['container'] ?? '', ENT_QUOTES, 'UTF-8'); ?>;
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
        color: <?php echo htmlspecialchars($config['color']['text']['secondary'] ?? '', ENT_QUOTES, 'UTF-8'); ?>;
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
        color: <?php echo htmlspecialchars($config['color']['text']['secondary'] ?? '', ENT_QUOTES, 'UTF-8'); ?>;
    }

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
        background-color: <?php echo htmlspecialchars($config['color']['background']['button'] ?? '', ENT_QUOTES, 'UTF-8'); ?>;
        color: <?php echo htmlspecialchars($config['color']['text']['button'] ?? '', ENT_QUOTES, 'UTF-8'); ?>;
        border: none;
        width: 150px;
        border-radius: 5px;
    }

    button:hover {
        background-color: <?php echo htmlspecialchars($config['color']['background']['button_hover'] ?? '', ENT_QUOTES, 'UTF-8'); ?>;
        cursor: pointer;
    }

    img {
        padding: 10px;
    }

    .error {
        color: <?php echo htmlspecialchars($config['color']['text']['error_message'] ?? '', ENT_QUOTES, 'UTF-8'); ?>;
        padding: 5px;
    }

    <?php
        if (!empty($config['custom_css'])) {
            echo $config['custom_css'];
        }
    ?>
</style>
