<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        $tabTitle = $config['text']['ban_wall']['tab_title'] ?? '';
        include __DIR__ . '/walls-partial/head.php';
    ?>
</head>
<body>
<div class="container">
    <div class="main">
        <h1>
            <?php echo htmlspecialchars($config['text']['ban_wall']['title'] ?? '', ENT_QUOTES, 'UTF-8');?>
        </h1>
        <p class="desc"><?php
            echo htmlspecialchars($config['text']['ban_wall']['subtitle'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </p>
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
