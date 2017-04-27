# Linkback

Linkback for Drupal 8, a rebuilt version of Vinculum.

This module is the backend to store received linkbacks and to fire Events to
be used by Rules or by handler modules. So without these handler modules it's not
very useful.

Two modules are packaged with linkback: linkback_pingback and linkback_webmention . See
readmes within those modules for more information. Each of them processes activity that
is saved as linkback entities with their respective handlers.

## Install Linkback

Install like a normal module.

Go to Configure > Services > Linkbacks. From there you can
configure how sending linkbacks must work: if using cron or if using manual
process.

Already "registered" (i.e. saved, or created entities) linkbacks are controlled in a tab
in Content, at /admin/content/linkback . This includes both incoming and 
outgoing linkbacks.

### Configure content types to send and receive linkbacks

To activate Linkback functions you must add at least one field of type 
"Linkback handlers" to a node content type. 

If you keep the suggested default settings of "Send linkbacks" and
"Receive linkbacks" to true, nodes of these types will use each type of
linkback handler module that has been enabled.

* Known issue: [Two linkback handler fields of different names don't work](https://www.drupal.org/node/2847867)

### Permissions

* Administer Linkback entities: Allow to access the administration form to configure 
Linkback entities.
* Administer linkback: Perform administrative tasks with linkback.
* Create new Linkback entities
* Delete Linkback entities
* Edit Linkback entities
* View published Linkback entities
* View unpublished Linkback entities

## Changes from Vinculum in Drupal 7

Some substantial changes over how [Vinculum](https://drupal.org/project/vinculum) 
in Drupal 7 worked. Changes:
  - Received and sent linkbacks are now an Entity called Linkback, so this module
    uses base interfaces such as the EntityListBuilder, the EntityViewsData
    to create custom views, and generally all the Entity API.
  - Linkback availability on each content type is enabled via Entity field. 
    So it can be configured generally via default field settings or on each
    bundle.
  - Trackbacks not implemented.
  - Validation of received linkbacks done in entity scope, so using new
    validation API (using Symfony constraints).
  - Crawling jobs using Symfony DomCrawler in Drupal 8 core.
  - Web spider curling jobs using guzzle library in Drupal 8 core.
  - Using Symfony EventDispatcher / EventSubscriber instead of hooks.
  
## Still in development

  What is not developed:
  - Allow altering source url and target when sending linkbacks (
    hook_linkback_link_send ).
  - Check if linkback has been sent previously (always sends linkback if
    sending is enabled).
  - Handler modules are not configured via general settings, but simply by enabling
    linkback handling modules. (Maybe it's better to plan enabled handlers to
    be configured in field settings).

## Development notes
 - See [Developer.md](Developer.md) for more developer notes including all files and
 function signatures.
 - [Issue queue](https://www.drupal.org/project/issues/linkback)
 - [Vinculum D8 planning notes](https://www.drupal.org/node/2687129)
 - You can see a working handler module in [linkback-d8-pingback]( https://github.com/aleixq/vinculum-d8_pingback ).
 - [Pingback development issue](https://www.drupal.org/node/2846844)
 - [Webmention development issue](https://www.drupal.org/node/2846789)
