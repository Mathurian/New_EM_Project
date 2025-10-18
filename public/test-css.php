<?php
// Test CSS loading
echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head>\n";
echo "    <meta charset='utf-8'>\n";
echo "    <title>CSS Loading Test</title>\n";
echo "    <link rel='stylesheet' href='assets/css/style.css'>\n";
echo "    <style>\n";
echo "        body { background: red; }\n";
echo "        .test { color: blue; font-size: 24px; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";
echo "    <h1>CSS Loading Test</h1>\n";
echo "    <p class='test'>If you see this in blue and large text, inline CSS works.</p>\n";
echo "    <p>If the background is red, inline CSS works but external CSS might not.</p>\n";
echo "    <p>If you see styled buttons and proper layout, external CSS is working.</p>\n";
echo "    <button class='btn btn-primary'>Test Button</button>\n";
echo "    <p>Current working directory: " . getcwd() . "</p>\n";
echo "    <p>CSS file exists: " . (file_exists('assets/css/style.css') ? 'YES' : 'NO') . "</p>\n";
echo "    <p>CSS file path: " . realpath('assets/css/style.css') . "</p>\n";
echo "</body>\n";
echo "</html>\n";
?>
