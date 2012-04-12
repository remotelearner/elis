<?php

require_once(dirname(__FILE__).'/../lib.php');

function xmldb_block_rlip_install() {
    // Ensure that scheduling is setup correctly
    rlip_schedulding_init();
}
