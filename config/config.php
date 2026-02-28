<?php 

define('DB_HOST', 'localhost');
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','study_scheduler');

// For file upload
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('UPLOAD_URL','uploads/');
define('ALLOWED_EXT', ['pdf']);
define('MAX_FILE_SIZE', 10 * 1024 * 1024);

// App info
define('APP_NAME','StudyDesk');
define('APP_TAGLINE','Reviewer system ng baby ko');