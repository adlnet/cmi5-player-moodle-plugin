<?php
 $CFG->phpunit_prefix = 'phpu_';
 $CFG->phpunit_dataroot = "$CFG->dirroot/mod/cmi5launch/cmi5PHP/src/tests/phpunitdata";

$LRSs = [
    [
        'endpoint' => 'https://cloud.scorm.com/tc/0CKX3A0SF2/sandbox/',
        'username' => 'PjRb2iE9WsUSso_UYCE',
        'password' => '3qoocGjKnfoYrtJhPrU',
        'version'  => '1.0.1'
    ]
];
$KEYs = [
    'public'   => getenv('TRAVIS_BUILD_DIR') . '/tests/keys/travis/cacert.pem',
    'private'  => getenv('TRAVIS_BUILD_DIR') . '/tests/keys/travis/privkey.pem',
    'password' => 'travis'
];
