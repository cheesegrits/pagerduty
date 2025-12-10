PHP PagerDuty Events API
=========
PHP implementation of the [PagerDuty Events API V2](https://developer.pagerduty.com/api-reference/f80f5db9acbe3-pager-duty-v2-events-api)

[![Latest Stable Version](https://poser.pugx.org/adilbaig/pagerduty/v/stable.svg)](https://packagist.org/packages/adilbaig/pagerduty) [![Total Downloads](https://poser.pugx.org/adilbaig/pagerduty/downloads.svg)](https://packagist.org/packages/adilbaig/pagerduty) 

Request for Maintainer
---
`8th June 2025` If you're interested in taking over the maintenance of this plugin, let me know. 

*A bit of background.* I started this plugin many years ago while working at a fintech startup. Our Ops team relied on PagerDuty and we wanted to hook up our internal alerting systems to PD. I didn't quite like what was available. I wanted something that was simple and flexible, but nothing more. This plugin has long since fulfilled that purpose. Over time people have come to use, contribute and rely on it. It is [PD's recommended community plugin](https://developer.pagerduty.com/docs/api-client-libraries) and the most downloaded PD plugin [on Packagist](https://packagist.org/?query=pagerduty). I've long since moved on; from the startup, from PHP and from PagerDuty. It's time someone else took over the maintenance here.

Your mission, should you choose to accept it, is to upgrade this plugin to PHP 8.4. Fork this repo, make the changes and send me a PR. Once committed, I will transfer ownership of the repo over.

**Update (2025-12-10):** @cheesegrits will be taking over maintenance of this project.

Compatibility
---

1. **v4.***: Requires `"php": ">=8.0"`  . PagerDuty Events API V2.
2. **v3.***: Requires `"php": ">=5.4.0"`. PagerDuty Events API V2. _If you are upgrading from a [2.* release](https://github.com/adilbaig/pagerduty/releases), make sure you pay attention to the contructor of the `TriggerEvent`_
3. **v2.***: Requires `"php": ">=5.4.0"`. PagerDuty Events API V1.

Features
---

- Compatible with [PagerDuty Events API V2](https://developer.pagerduty.com/api-reference/f80f5db9acbe3-pager-duty-v2-events-api).
- Trigger, Acknowledge and Resolve incidents.
- Attach [Links](https://developer.pagerduty.com/docs/events-api-v2/trigger-events/#the-links-property) and [Images](https://developer.pagerduty.com/docs/events-api-v2/trigger-events/#the-images-property) to your incident reports.
- Unit Tests


Installation
---
For **PHP 8.\*** projects, add this line to your project's `composer.json`
````
{
...
    "require": {
        "adilbaig/pagerduty": "4.*"
    }
...
}
````

For PHP versions < 8, add this line to your project's `composer.json`
````
{
...
    "require": {
        "adilbaig/pagerduty": "3.*"
    }
...
}
````

The packagist URL is https://packagist.org/packages/adilbaig/pagerduty

Usage
---

Trigger an event
 
````php
use \PagerDuty\TriggerEvent;
use \PagerDuty\Exceptions\PagerDutyException;

$routingKey = "1d334a4819fc4b67a795b1c54f9a"; //Replace this with the integration key of your service.

// In this example, we're triggering a "Service is down" message from a web server.
try {
    $event = new TriggerEvent(
        $routingKey, 
        "Service is down",  // A high-level, text summary message of the event. Will be used to construct an alert's description.
        "web-server-01",    // human-readable unique identifier, such as a hostname, for the system having the problem.
        TriggerEvent::ERROR,// How impacted the affected system is? Influences the priority of any created incidents. 
        true                // Generate the dedup_key from the driver. If false, the dedup_key will be generated on PD 
    );
    $responseCode = $event->send();
    if($responseCode == 200)
        echo "Success";
    elseif($responseCode == 429)
        echo "Rate Limited";  //You're being throttled. Slow down.
    else // An error occured. Try again later
        echo "Some error has occured. Try again later";
} catch(PagerDutyException $exception) { //This doesn't happen unless you've broken their guidelines. The API tries to minimize user mistakes
    var_dump($exception->getErrors());
}

````

Trigger event with custom connection, for example: using proxies and/or setting verbosity for debugging, etc.

````php

use \PagerDuty\TriggerEvent;
use \PagerDuty\Exceptions\PagerDutyException;
use \PagerDuty\Http\PagerDutyHttpConnection;

try {
    $routingKey = '1d334a4819fc4b67a795b1c54f9a';  //Replace this with the integration key of your service.

    $event = new TriggerEvent(
        $routingKey, 
        "Service is down",  // A high-level, text summary message of the event. Will be used to construct an alert's description.
        "web-server-01",    // human-readable unique identifier, such as a hostname, for the system having the problem.
        TriggerEvent::ERROR,// How impacted the affected system is? Influences the priority of any created incidents. 
        true                // Generate the dedup_key from the driver. If false, the dedup_key will be generated on PD 
    );

    // create a custom proxy connection
    $connection = new PagerDutyHttpConnection();

    // .. and set the proxy
    $connection->setProxy('https://user:password@your-proxy-ip-address:port');

    // set custom CURL options. Here we set verbosity for debugging
    $connection->addCurlOption('CURLOPT_VERBOSE', 1);
    
    // send event through proxy
    $connection->send($event);
}
catch(PagerDutyException $exception) { //This doesn't happen unless you've broken their guidelines. The API tries to minimize user mistakes
    var_dump($exception->getErrors());
}
catch (\Exception $e) {
    // A configuration exception
}

````

Automatically send only one PagerDuty incident for repeated errors

````php

//You will only see one incident on PD
(TriggerEvent($routingKey, "Service is down", "web-server-01", TriggerEvent::ERROR, true))->send();
(TriggerEvent($routingKey, "Service is down", "web-server-01", TriggerEvent::ERROR, true))->send();
(TriggerEvent($routingKey, "Service is down", "web-server-01", TriggerEvent::ERROR, true))->send();

````

Create a detailed 'trigger' event, add optional data. Dump the event and inspect response from PD

````php
use \PagerDuty\TriggerEvent;

//Taken from the `trigger` example @ https://v2.developer.pagerduty.com/docs/send-an-event-events-api-v2
//Send a detailed event, and store the `dedup_key` generated on the server

$event = new TriggerEvent(
    $routingKey, 
    "Example alert on host1.example.com", 
    "monitoringtool:cloudvendor:central-region-dc-01:852559987:cluster/api-stats-prod-003", 
    TriggerEvent::INFO
);
$event
    ->setPayloadTimestamp("2015-07-17T08:42:58.315+0000")
    ->setPayloadComponent("postgres")
    ->setPayloadGroup("prod-datapipe")
    ->setPayloadClass("deploy")
    ->setPayloadCustomDetails(["ping_time" => "1500ms", "load_avg" => 0.75])
    ->addLink("https://example.com/", "Link text")
    ->addImage("https://www.pagerduty.com/wp-content/uploads/2016/05/pagerduty-logo-green.png", "https://example.com/", "Example text"))
;

// Pass in the '$response' variable by reference if you want to inspect PD's response. This is optional, and you probably don't need this in production.
$response = null;
$responseCode = $event->send($response);
// In this case, we will save the `dedup_key` generated by the PD server
var_dump($response['dedup_key']);
````

Acknowledge an event
----

````php
(new AcknowledgeEvent($routingKey, "dedup key"))->send();
````

Resolve an event
----
````php
(new ResolveEvent($routingKey, "dedup key"))->send();
````

UnitTests
---

````bash
> ./vendor/bin/phpunit test/
.....                                                               5 / 5 (100%)

Time: 37 ms, Memory: 4.00MB

OK (5 tests, 6 assertions)
````

Questions
---

**Q.** How do i get the service key from PagerDuty?

**A.** In your PagerDuty console, click 'Configuration' > 'Services'. Click the link under 'Integrations' column. It's the 'Integration Key'

Read more here : https://v2.developer.pagerduty.com/v2/docs/events-api#getting-started

Requirements
---
This library needs the [curl pecl extension](https://php.net/curl).

In Ubuntu 16.04, install it like so :

    sudo apt install php-curl


In Ubuntu 18.04, install it like so :

    sudo apt install php8.3-curl

