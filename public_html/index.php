<?php

require_once __DIR__ . '/bootstrap_paths.php';

$appPath = ap_resolve_app_path();

require_once $appPath . '/bootstrap/app.php';

ap_bootstrap_http_application($appPath, __DIR__);
