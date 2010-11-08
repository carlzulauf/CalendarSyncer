<?php

/*

Configuration file for Calendar Syncer.

I put as many comments as I could to explain how to configure this script.

*/

// LOGGING -- No logging occurs by default
// uncomment to turn on light logging
    //ZLogger::$threshold = 5;
// uncomment to turn on heavy logging
    //ZLogger::$threshold = 0;

// You can change the maximum connection attempts here
$maxConnectAttempts=5;

// Sync is initiated with a call to syncMeetupWithGoogle()
// Fill out the parameter values as shown below.

syncMeetupWithGoogle(

    // MEETUP API URI
    // Fill in the meetup.com API URI for your calendar. The example below shows
    // the expected format. Replace the {bracketted} portions with your own values.
    'http://api.meetup.com/events/?group_id={your meetup group ID#}&key={your meetup API key}',
    
    // GOOGLE CALENDAR USERNAME
    // Fill in your google calendar username.
    'my.google.username@gmail.com',
    
    // GOOGLE CALENDAR PASSWORD
    // Fill in the password for your google account
    'password',
    
    // GOOGLE CALENDAR NAME
    // The name of the specific calendar you want to sync with.
    // If you are using your default google calendar this is probably your
    // email address
    'my.google.username@gmail.com',
    
    // LOCATION (WHERE) FILTER
    // Putting a value here will overwrite the location of your private meetup,
    // allowing the private location to remain hidden from your potentially
    // public google calendar.
    // Leave FALSE here if you do not want to hide the meetup location from your
    // google calendar.
    // Examples: "see meetup.com for location", "TBA", "Springfield, USA", etc.
    // If you use an empty string ("") the location will be left blank in
    // google calendar.
    false,
    
    // DESCRIPTION FILTER
    // Similar to location filter, but for the meetup description.
    false
);

/*

You can duplicate the function call above as many times as you need to if you
have multiple meetup calendars you want to sync.

*/
