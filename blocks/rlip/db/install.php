<?php

function xmldb_block_rlip_install() {

    // Add a cron task for log rollover
    elis_tasks_update_definition('block_rlip');

}

