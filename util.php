<?php

class ZLogger {

    public static $path = ""; // use current working directory
    public static $file = "CalendarSyncer.log";
    public static $threshold = 10; // 0 - log everything, 10 - log nothing

    public static function log ( $msg, $importance = 5, $file = false ) {
        $logFile = $file ? $file : self::$file;
        $msg = date("r") . ": $msg\n";
        if ( $importance >= self::$threshold )
            file_put_contents(self::$path . $logFile, $msg, FILE_APPEND | LOCK_EX);
    }

    public static function dump ( $name, $var, $importance = 0, $file = false ) {
        self::log( "$name\n" . print_r( $var, 1 ), $importance, $file );
    }

    public static function pretty ( $name, $var, $importance = 1, $file = false ) {
        self::log( "$name\n" . print_r( $var, 1 ), $importance, $file );
    }
}

function syncMeetupWithGoogle( $meetupURI, $googleUser, $googlePass, $calendarName, $location = false, $description = "" ) {

    echo "Starting sync of $calendarName @ " . date("r") . ".\n";
    ZLogger::log("Starting sync of $calendarName @ " . date("r") . ".");
    
    for ( $attempts=0; $attempts<$GLOBALS['maxConnectAttempts']; $attempts++ ) {
        try {
            $meetupEvents = getMeetupEvents( $meetupURI, $location, $description );
            ZLogger::pretty( "syncMeetupWithGoogle()->\$meetupEvents", $meetupEvents );
            break;
        }
        catch (Exception $e) {
            // just keep trying
        }
    }
    
    ZLogger::log("Attempting to retrieve Google Calendar info for $googleUser");
    for ( $attempts=0; $attempts<$GLOBALS['maxConnectAttempts']; $attempts++ ) {
        try {
            $gdataClient = getGdataClient( $googleUser, $googlePass );
            $gdataService = new Zend_Gdata_Calendar($gdataClient);
            $gdataCalendar = getNamedCalendar( $gdataService, $calendarName );
            $googleEvents = getGoogleEvents( $gdataService, getUserAddress( $gdataCalendar ) );
            //ZLogger::pretty( "syncMeetupWithGoogle()->\$googleEvents", $googleEvents );
            // if we get this far it means success. stop trying.
            break;
        }
        catch (Exception $e) {
            ZLogger::dump("Exception caught while attempting to retrieve Google Calendar data", $e, 4);
            // just keep trying
        }
    }
    if ( ! is_array( $googleEvents) ) {
        ZLogger::log("Unable to retrieve google calendar info. Quitting.");
        exit();
    }
    ZLogger::log("Completed retrieval of Google Calendar information. Attempting sync.");

    foreach( $meetupEvents as $url => $event ) {
        if ( array_key_exists( $url, $googleEvents ) ) {
            //echo "* - Event already on google. Comparing. $url\n";
            ZLogger::log("* - Event already on google. Comparing. $url");
            //$a = $meetupEvents[$url];
            $b = $googleEvents[$url];
            if (
                    $event->title != $b->title ||
                    $event->time != $b->time ||
                    $b->where != $event->where ||
                    $b->description !=  $event->description )
            {
                echo "* - Difference detected. Resyncing $url";
                if ($event->title != $b->title) echo "* - * - Titles don't match. {$event->title} <> {$b->title}\n";
                if ($event->time != $b->time) echo "* - * - Times don't match.\n";
                if ($b->where != $event->where )
                    echo "* - * - Locations don't match.\n";
                if ($b->description != $event->description ) {
                    echo "* - * - Descriptions don't match.\n";
                    //echo "google: {$b->description}\n";
                    //echo "meetup: {$event->description}\n";
                }
                //exit;
                ZLogger::log("* - * - Difference detected. Resyncing.");
                $b->gdata_object->title = $gdataService->newTitle($event->title);
                $b->gdata_object->where = array($gdataService->newWhere($event->where));
                $b->gdata_object->content = $gdataService->newContent($event->description);
                $when = $gdataService->newWhen();
                $when->startTime = date("c",$event->time);
                // automatic end time 2 hours after start time
                $when->endTime = date( "c", $event->time + ( 60 * 60 * 2 ) );
                $b->gdata_object->when = array($when);
                //print_r( $b->gdata_object ); break;
                //echo $b->gdata_object->getEditLink() . "\n"; break;
                $b->gdata_object->save();
            }
        } else {
            echo "* - New event. Adding. $url\n";
            ZLogger::log("* - New event. Adding. $url");
            $newEvent = new SyncedEvent();
            $newEvent->meetup_uri = $event->meetup_uri;
            $newEvent->title = $event->title;
            $newEvent->time = $event->time;
            $newEvent->where = ($location !== false ? $location : $event->where);
            $newEvent->description = ( $description !== false ? $description : $event->description );
            $createdEvent = createGoogleEvent( $gdataService, $gdataCalendar, $newEvent );
            echo "* - Created google event: " . $newEvent->title . " @ " . date("m/d/Y H:i",$newEvent->time) . "\n";
            ZLogger::log("* - Created google event: " .
                    $newEvent->title . " @ " . 
                    date("m/d/Y H:i",$newEvent->time));
        }
    }
    
    /* delete google events not on meetup */
    ZLogger::log("Checking if all google events are still on meetup.");
    foreach ( $googleEvents as $url => $event ) {
        if ( ! array_key_exists( $url, $meetupEvents) ) {
            /* event not on meetup. delete. */
            ZLogger::log(" * - Event not on meetup. Attempting to delete from google. $url");
            $event->gdata_object->delete();
        }
    }

    echo "Sync completed.\n\n";
    ZLogger::log("Sync completed.\n\n");
}