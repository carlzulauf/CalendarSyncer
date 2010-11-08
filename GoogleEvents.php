<?php

require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_HttpClient');
Zend_Loader::loadClass('Zend_Gdata_Calendar');

function getGdataClient( $user, $pass ) {
    ZLogger::log("Authenticating with Google Calendar API",4);
    $service = Zend_Gdata_Calendar::AUTH_SERVICE_NAME;
    $client = Zend_Gdata_ClientLogin::getHttpClient($user,$pass,$service);
    ZLogger::log("Authenticated with Google Calendar API",4);
    return $client;
}

function getNamedCalendar(Zend_Gdata_Calendar $gdataCal,$calendarName) {
    ZLogger::log("Looking up calendar '$calendarName'",4);
    //$gdataCal = new Zend_Gdata_Calendar($client);
    $calFeed = $gdataCal->getCalendarListFeed();
    foreach ($calFeed as $key => $calendar) {
        //echo "calendar $key: " . $calendar->title->text . "\n";
        if ($calendar->title->text == $calendarName) {
            ZLogger::log("Found calendar '$calendarName'",4);
            return $calendar;
        }
    }
}

function getUserAddress( Zend_Gdata_Calendar_ListEntry $calendar ) {
    $uri = $calendar->id->text;
    $start = strrpos($uri,"/") + 1;
    return str_replace("%40","@",substr($uri,$start));
}

function createGoogleEvent (
        Zend_Gdata_Calendar $gdataCal,
        Zend_Gdata_Calendar_ListEntry $calendar,
        SyncedEvent $event)
{
    //$gdataCal = new Zend_Gdata_Calendar($client);
    $newEvent = $gdataCal->newEventEntry();

    $newEvent->title = $gdataCal->newTitle($event->title);
    $newEvent->where = array($gdataCal->newWhere($event->where));
    $newEvent->content = $gdataCal->newContent($event->description);
    $when = $gdataCal->newWhen();
    $when->startTime = date("c",$event->time);
    // automatic end time 2 hours after start time
    $when->endTime = date( "c", $event->time + ( 60 * 60 * 2 ) );
    $newEvent->when = array($when);

    // Upload the event to the calendar server
    // A copy of the event as it is recorded on the server is returned
    try {
        $createdEvent = $gdataCal->insertEvent($newEvent,$calendar->link[0]->href);
    } catch ( Zend_Gdata_App_HttpException $e ) {
        print_r( $e );
    }
    return $createdEvent;
}

function getGoogleEvents( Zend_Gdata_Calendar $service, $calendar )
{
    ZLogger::log("Retrieving events from Google Calendar",4);
    //$service = new Zend_Gdata_Calendar($client);

    $query = $service->newEventQuery();
    $query->setUser($calendar);
    $query->setVisibility('private');
    $query->setProjection('full');
    $query->setOrderby('starttime');
    $query->setFutureevents('true');
    $query->setMaxResults(1000);

    $eventFeed = $service->getCalendarEventFeed($query);

    $events = array();
    $uriPattern = "/(http|ftp|https):\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&:\/~\+#]*[\w\-\@?^=%&\/~\+#])?/i";
    foreach( $eventFeed as $event ) {
        $content = $event->content->__toString();
        if ( preg_match( $uriPattern, $content, $match ) ) {
            // check for duplicates
            if ( ! array_key_exists( $match[0], $events ) ) {
                ZLogger::log("Found event on Google Calendar: {$match[0]}",4);
                $sync = new SyncedEvent();
                $sync->meetup_uri = $match[0];
                $sync->description = trim($content);
                $sync->gdata_object = $event;
                $sync->title = $event->title->__toString();
                $sync->time = strtotime($event->when[0]->getStartTime());
                $sync->where = $event->where[0]->__toString();
                $events[$sync->meetup_uri] = $sync;
            } else {
                ZLogger::log("Duplicate event ({$match[0]}). Deleting.");
                $event->delete();
            }
        }
    }
    return $events;
}

