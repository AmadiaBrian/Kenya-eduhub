<?php
// Prevent directory listing
header('HTTP/1.0 403 Forbidden');
echo '<h1>403 Forbidden</h1><p>Access to this directory is not allowed.</p>';
exit;
