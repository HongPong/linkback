# linkback_webmention : Drupal 8 Webmentions

Linkback handler module to implement Webmentions in Drupal 8.

Webmentions are a successor specification to trackbacks and pingbacks
which permit website operators to automatically notify each other that
they have an incoming link. 

## Installation and Configuration

Enable linkback_webmention in the Modules list. Linkback must also be enabled.

A configuration page becomes available at /admin/config/linkback/webmention

In the menu, it is at Configuration > Web services > Linkback Webmention settings.

A form with test options is available at the adjacent Webmention Tests tab 
( /admin/config/linkback/webmention/tests ). This can test remote scraping from
sites like [Webmention.rocks](http://webmention.rocks).

It is a good idea to run the tests, so you can tell if the libraries are loaded
correctly. If the tests fail, it is likely you will not be able to process
incoming or outgoing Webmentions.


## W3C Description of a Webmention

See this document for recommended implementation details. By using a
community-developed library we can have a predictable behavior that
matches the specification.

[The W3C approved a Webmention specification recommendation on 12 January 2017](https://www.w3.org/TR/webmention/).

> A typical Webmention flow is as follows:
> 
> * Alice posts some interesting content on her site (which is set up to
>  receive Webmentions).
> * Bob sees this content and comments about it on his site, linking back
>   to Alice's original post.
> * Using Webmention, Bob's publishing software automatically notifies
>   Alice's server that her post has been linked to by the URL of Bob's 
>   post.
> * Alice's publishing software verifies that Bob's post actually contains
>   a mention of her post and then includes this information on her site.

## Developer Notes

This module is under development. [Post issues here](https://www.drupal.org/project/issues/linkback).

To learn more about Webmention, see [webmention.net](http://webmention.net).

The [webmention.io](http://webmention.io/) service can also act as a pingback->webmention
proxy which will allow you to accept pingbacks as if they were sent as webmentions.

Composer.json defines latest version of several libraries: 
```json
  "require": {
    "mf2/mf2": "dev-master",
    "microformats/test": "@dev",
    "indieweb/mention-client": "dev-master",
    "barnabywalters/mf-cleaner": "dev-master"
  }
```
* [mention-client](https://github.com/indieweb/mention-client-php) is used to generate
  standard Webmention objects.
* [mf2](https://github.com/indieweb/php-mf2/) is used by mention-client to process
  strings. Look at Readme.md in mention-client for many possible implementations.
* microformats/test is required by latest mf2 version.
* mf-cleaner can be used if needed for string formatting.



## Credits

* Drupal 8 linkback by [Aleix](https://drupal.org/u/aleix), 
  [sanduhrs](https://www.drupal.org/u/sanduhrs) and linkback_webmention implementation by
  [HongPong](https://www.drupal.org/u/HongPong)
* Based on [Vinculum](https://www.drupal.org/project/vinculum) (7.x-2.x) by
  manarth, sanduhrs, aleix, HongPong, sillygwail
* Code reused from [Webmention module code by webflo and tvm](https://www.drupal.org/project/webmention)
