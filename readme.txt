=== Forms: 3rd-Party Dynamic Fields ===
Contributors: zaus, leadlogic
Donate link: http://drzaus.com/donate
Tags: contact form, form, contact form 7, CF7, gravity forms, GF, CRM, mapping, 3rd-party service, services, remote request, xml, soap, xml webservice, soap webservice
Requires at least: 3.0
Tested up to: 3.8
Stable tag: trunk
License: GPLv2 or later

Converts submission from <a href="http://wordpress.org/plugins/forms-3rdparty-integration/">Forms 3rdparty Integration</a> to xml, add headers.

== Description ==

Converts external submission from [Forms: 3rdparty Integration](http://wordpress.org/plugins/forms-3rdparty-integration/) plugin to an XML post; optionally can add custom headers (to allow SOAP submissions).

This plugin will turn the 3rdparty mappings into XML elements, so that each form post will be the value (or attribute) of an XML element.

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

    <credentials>
        <user>xyz</user>
        <pass>abc</pass>
    </credentials>

you would use `credentials/user` and `credentials/pass`, respectively.

== Screenshots ==

__None available.__

== Changelog ==

= 0.2 =
* Element nesting, attributes
* reworked "xmlify" using recursive `SimpleXMLElement`

= 0.1 =
Base version - xml and header transformation

== Upgrade Notice ==

None.