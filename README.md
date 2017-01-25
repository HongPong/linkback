# Linkback

Linkback for Drupal 8, a rebuilt version of Vinculum.

This module is the backend to store received linkbacks and to fire Events to
be used by Rules or by handler modules.

So without these modules it's not very useful. You can see a working handler
module in linkback-d8-pingback 
( https://github.com/aleixq/vinculum-d8_pingback ).

## Install Linkback

Install like a normal module.
Go to Configure > Services > Linkbacks. From there you can
configure how sending linkbacks must work: if using cron or if using manual
process. There is also lso you can see there the list of received linkbacks.

## Changes over vinculum-d7

Some substantial changes over how vinculum in Drupal 7 worked:
Changes:
  - Received linkbacks are now an Entity, so it is using base interfaces such
    as the EntityListBuilder, the EntityViewsData to create custom views, and
    generally all the Entity API.
  - Linkback availability on each content is enabled via Entity Field. So it
    can be configured generally via default field settings or on each bundle.
  - Trackbacks not implemented.
  - Validation of received ref-backs done in entity scope, so using new
    validation api (using Symfony constraints).
  - Crawling jobs using in-drupal8-core Symphony DomCrawler.
  - Web spider curling jobs using in-drupal8-core guzzle library.
  - Using Symfony EventDispatcher / EventSubcriber instead of hooks.
  
  What is not developed:
  - Allow altering source url and target when sending refbacks (
    hook_linkback_link_send ).
  - Check if linkback has been sent previously (always sends refback if
    sending is enabled).
  - Handler modules are not configured via general settings, simply enabling
    linkback handling modules. (Maybe it's better to plan enabled handlers to
    be configured in field settings).

## Development notes
 - Issue queue: https://www.drupal.org/project/issues/linkback
 - Vinculum D8 planning notes: https://www.drupal.org/node/2687129