=== Forms: 3rd-Party Xml Post ===
Contributors: zaus, leadlogic
Donate link: http://drzaus.com/donate
Tags: contact form, form, contact form 7, CF7, gravity forms, GF, CRM, mapping, 3rd-party service, services, remote request, xml, json, soap, xml webservice, soap webservice, json webservice, nested fields
Requires at least: 3.0
Tested up to: 4.1
Stable tag: trunk
License: GPLv2 or later

Converts submission from <a href="http://wordpress.org/plugins/forms-3rdparty-integration/">Forms 3rdparty Integration</a> to xml/json, add headers, or nest fields.

== Description ==

Converts external submission from [Forms: 3rdparty Integration](http://wordpress.org/plugins/forms-3rdparty-integration/) plugin to an XML or JSON post; optionally can add custom headers (to allow SOAP submissions) or transform/combine separate fields into nested values.

This plugin can turn the 3rdparty mappings into XML elements, so that each form post will be the value (or attribute) of an XML element.

== Installation ==

1. Unzip, upload plugin folder to your plugins directory (`/wp-content/plugins/`)
2. Make sure [Contact Form 7][] or [Gravity Forms][] is installed
2. Make sure [Forms: 3rdparty Integration](http://wordpress.org/plugins/forms-3rdparty-integration/) is installed
3. Activate plugin
4. Go to new admin subpage _"3rdparty Services"_ under the CF7 "Contact" menu or Gravity Forms "Forms" menu and configure services + field mapping.
5. Configure the new "Xml Post" section to choose which services send as xml and/or determine special headers (given as a url querystring).
6. Nest fields by separating nodes with `/`, and indicate attributes with `@`.

[Contact Form 7]: http://wordpress.org/extend/plugins/contact-form-7/ "Contact Form 7"
[Gravity Forms]: http://www.gravityforms.com/ "Gravity Forms"

== Frequently Asked Questions ==

= I need help =

Submit an issue to the [GitHub issue tracker][] in addition to / instead of the WP Support Forums.

[GitHub issue tracker]: https://github.com/zaus/forms-3rdparty-xpost/issues "GitHub issue tracker"

= How do I add / configure a service? =

See "base plugin" [Forms: 3rdparty Integration](http://wordpress.org/plugins/forms-3rdparty-integration/).

= How do I set headers =

Provide the list of headers as though they were a URL querystring, so that

> Content-Type: something
> X-Special-Header: something-else

would be given as

> `Content-Type=something&X-Special-Header=something-else`

= How do I nest elements? =

Separate element names within the same 3rdparty field mapping using `/`, so that in order to make:

    <credentials type="123">
        <user>xyz</user>
        <pass>abc</pass>
    </credentials>

you would use `credentials/@type`, `credentials/user` and `credentials/pass`, respectively.

**Note:** You may nest and wrap elements even if not transforming into XML; they will be submitted as multi-dimensional arrays like:

    credentials[@type]=123&credentials[user]=xyz&credentials[pass]=abc

== Screenshots ==

__None available.__

== Changelog ==

= 0.4.2 =
Can post body as json instead

= 0.4 =
Fixed GitHub issue #3 https://github.com/zaus/forms-3rdparty-xpost/issues/3:

* each plugin setting is only applied to that specific service if it has a value
* removed default `post` wrapper unless sending as xml (and if you send XML you should specify a wrapper)

= 0.3 =
Can nest regular post fields even when not submitting XML.

= 0.2 =
* Element nesting, attributes
* reworked "xmlify" using recursive `SimpleXMLElement`

= 0.1 =
Base version - xml and header transformation

== Upgrade Notice ==

None.