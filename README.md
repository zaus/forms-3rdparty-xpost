# Forms: 3rd-Party Dynamic Fields #

**Contributors:** zaus, skane

**Donate link:** http://drzaus.com/donate

**Tags:** contact form, form, contact form 7, CF7, gravity forms, GF, CRM, mapping, 3rd-party service, services, remote request, dynamic fields, get params

**Requires at least:** 3.0

**Tested up to:** 3.3.1

**Stable tag:** trunk

**License:** GPLv2 or later

Wordpress Plugin -- Provides some dynamic field values via placeholder to [Forms 3rdparty Integration](https://github.com/zaus/forms-3rdparty-integration).

## Description ##

Using pre-configured placeholders like `##UID##` or `##SITEURL##`, add dynamic fields to the normally map-only or static-only [Forms: 3rdparty Integration](http://wordpress.org/plugins/forms-3rdparty-integration/) plugin.

## Installation ##

1. Unzip, upload plugin folder to your plugins directory (`/wp-content/plugins/`)
2. Make sure [Contact Form 7]  or [Gravity Forms] is installed
2. Make sure [Forms: 3rdparty Integration](http://wordpress.org/plugins/forms-3rdparty-integration/) is installed
3. Activate plugin
4. Go to new admin subpage _"3rdparty Services"_ under the CF7 "Contact" menu or Gravity Forms "Forms" menu and configure services + field mapping.
5. Configure the new "Dynamic Fields" section to optionally attach the dynamic values to the notification messaging (and how)
6. Using the additional collapsed metabox for examples, add dynamic placeholders as "static values" to the service mapping.  Double-click examples to populate each textbox after selecting it.

[Contact Form 7]: http://wordpress.org/extend/plugins/contact-form-7/ "Contact Form 7"

[Gravity Forms]: http://www.gravityforms.com/ "Gravity Forms"


## Frequently Asked Questions ##

### I need help ###

Submit an issue to the [GitHub issue tracker] in addition to / instead of the WP Support Forums.

[GitHub issue tracker]: https://github.com/zaus/forms-3rdparty-dynamicfields/issues "GitHub issue tracker"


### How do I add / configure a service? ###

See "base plugin" [Forms: 3rdparty Integration](http://wordpress.org/plugins/forms-3rdparty-integration/).


Expand the box "Dynamic Placeholder Examples" for allowed dynamic fields.

### How do I add GET parameters to my service post? ###

Use the placeholder `##GET:{urlparam}##` as the static value, which will attach the value `XYZ` from the url in `?urlparam=XYZ`.

## Screenshots ##

__None available.__

## Changelog ##

### 0.3 ###
GET parameters.

### 0.2 ###
Attaches to notification.

### 0.1 ###
Base version - dynamic field replacement

## Upgrade Notice ##

None.