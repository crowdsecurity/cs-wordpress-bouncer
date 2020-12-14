<html>
<style>
    * {
        margin: 0;
        padding: 0;
    }

    body {
        background: #eee;
        font-family: Arial, Helvetica, sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .main {
        background: white;
        padding: 50px 50px 30px 50px;
        box-shadow: rgba(0, 0, 0, 0.2) 0px 3px 3px -2px, rgba(0, 0, 0, 0.14) 0px 3px 4px 0px, rgba(0, 0, 0, 0.12) 0px 1px 8px 0px;
        border-radius: 10px;
    }

    form {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        z-index: 1;
        position: relative;
    }

    input {
        padding: 10px;
        font-size: 1.1em;
        width: 150px;
    }

    button {
        margin: 30px 0 30px;
        padding: 10px 0;
        font-size: 1.1em;
        background-color: #626365;
        color: white;
        border: none;
        width: 150px;
        border-radius: 5px;
    }

    button:hover {
        background-color: #333;
        cursor: pointer;
    }

    h1 {
        padding: 10px;
        padding: 10px;
    }

    img {
        padding: 10px;
    }

    .desc {
        font-size: 1.2em;
        margin-bottom: 30px;
    }

    .error {
        color: #b90000;
        padding: 5px;
    }

    .powered {
        margin-top: 30px;
        font-size: small;
        color:  #AAA;
    }

    svg {
        width: 40px;
        display: inline-block;
        vertical-align: -4px;
    }
    a {
        color:#AAA;
    }
</style>

<body>
    <div class="main">
        <form method="post">

            <h1><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="exclamation-triangle" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" class="svg-inline--fa fa-exclamation-triangle fa-w-18 fa-7x">
                    <path fill="#f39b2f" d="M569.517 440.013C587.975 472.007 564.806 512 527.94 512H48.054c-36.937 0-59.999-40.055-41.577-71.987L246.423 23.985c18.467-32.009 64.72-31.951 83.154 0l239.94 416.028zM288 354c-25.405 0-46 20.595-46 46s20.595 46 46 46 46-20.595 46-46-20.595-46-46-46zm-43.673-165.346l7.418 136c.347 6.364 5.609 11.346 11.982 11.346h48.546c6.373 0 11.635-4.982 11.982-11.346l7.418-136c.375-6.874-5.098-12.654-11.982-12.654h-63.383c-6.884 0-12.356 5.78-11.981 12.654z"></path>
                </svg> One moment...</h1>
            <p class="desc">Please complete the security check.</p>

            <img src="<?php echo $img ?>" />

            <input type="text" name="phrase" placeholder="Type here..." autofocus autocomplete="off" />
            <?php if ($error) : ?><p class="error">Please try again.</p><?php endif; ?>

            <button type="submit" />CONTINUE</button>

            <p>We detected a problem with the IP address <?php echo $ip ?>.</p>
            <p class="powered">This security check has been powerer by <a href="https://crowdsec.net/" target="_blank">CrowdSec</a></p>

        </form>
    </div>
    <script>

    </script>
</body>

</html>