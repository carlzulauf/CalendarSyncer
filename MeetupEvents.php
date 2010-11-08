<?php

/*
 * Example: $events = getMeetupEvents("http://api.meetup.com/events/?group_id={your group id}&key={your API key}
 */

function getMeetupEvents($api_url, $locationFilter = false, $descriptionFilter = "")
{
    $calendarJSON = file_get_contents($api_url);
    $calendarObj = json_decode($calendarJSON);
    ZLogger::pretty( "getMeetupEvents()-> decoded calendar JSON", $calendarObj );
    $meetupEvents = array();
    foreach( $calendarObj->results as $meetupEvent ) {
        // build address on a single line
        $where = "";
        if (strlen($meetupEvent->venue_name) > 0)
            $where .= $meetupEvent->venue_name . " - ";
        $where .= $meetupEvent->venue_address1;
        if (strlen($meetupEvent->venue_address2) > 0)
            $where .= ", " . $meetupEvent->venue_address2;
        if (strlen($meetupEvent->venue_address3) > 0)
            $where .= ", " . $meetupEvent->venue_address3;
        if (strlen($meetupEvent->venue_city) > 0)
            $where .= ", " . $meetupEvent->venue_city;
        if (strlen($meetupEvent->venue_state) > 0)
            $where .= ", " . $meetupEvent->venue_state;
        if (strlen($meetupEvent->venue_zip) > 0)
            $where .= " " . $meetupEvent->venue_zip;
        // build description
        $description = $meetupEvent->event_url;
        if ($descriptionFilter === false) {
            $description .= "\n\n" . strip_tags($meetupEvent->description);
        } elseif ( $descriptionFilter != "" ) {
            $description .= "\n\n{$descriptionFilter}";
        }
        // add event to array
        $event = new SyncedEvent();
        $event->meetup_uri = $meetupEvent->event_url;
        $event->title = $meetupEvent->name;
        $event->description = trim($description);
        $event->time = strtotime($meetupEvent->time);
        $event->where = $locationFilter === false ? $where : $locationFilter;
        $meetupEvents[$event->meetup_uri] = $event;
    }
    return $meetupEvents;
}

