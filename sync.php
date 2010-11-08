<?php

/*
    TO DO
    - Put Omaha, NE as WHERE
    - Do actual comparision for existing entries
    ... lots more
*/

error_reporting(E_ALL);

require 'util.php';
require 'SyncedEvent.php';
require 'GoogleEvents.php';
require 'MeetupEvents.php';

$maxConnectAttempts=5;

/* look for config override. if present and executed, then quit. */
$configOverridden = false;
@include 'config-override.php'; if ( $configOverridden ) exit();

/* load config last since currently the config file actually executes the sync */
require 'config.php';

