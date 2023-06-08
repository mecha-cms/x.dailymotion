<?php

$content = file_get_contents(__DIR__ . D . 'test.txt');

echo '<pre style="background:#ccc;border:1px solid rgba(0,0,0,.25);color:#000;font:normal normal 100%/1.25 monospace;padding:.5em .75em;white-space:pre-wrap;word-wrap:break-word;">' . htmlspecialchars($content) . '</pre>';
echo '<pre style="background:#cfc;border:1px solid rgba(0,0,0,.25);color:#000;font:normal normal 100%/1.25 monospace;padding:.5em .75em;white-space:pre-wrap;word-wrap:break-word;">' . htmlspecialchars(x\dailymotion\page__content($content)) . '</pre>';
echo '<hr>';
echo '<div style="margin: 0 auto; max-width: 1024px;">';
echo x\dailymotion\page__content($content);
echo '</div>';

exit;