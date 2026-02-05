<?php

if (!defined('PHPUNIT_RUN')) {
	define('PHPUNIT_RUN', 1);
}

require_once __DIR__ . '/../../../lib/base.php';
require_once __DIR__ . '/Helper/DatabaseTransaction.php';
require_once __DIR__ . '/Helper/Writer/TestWriter.php';

// Fix for "Autoload path not allowed: .../tests/lib/testcase.php"
// Note: OC::$loader was removed in Nextcloud 30+, only exists in older versions
if (property_exists(OC::class, 'loader') && isset(OC::$loader)) {
	OC::$loader->addValidRoot(OC::$SERVERROOT . '/tests');
}

// Fix for "Autoload path not allowed: .../nextpod/tests/testcase.php"
OC_App::loadApp('nextpod');

OC_Hook::clear();
