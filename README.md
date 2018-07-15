# Forms: 3rd-Party Xml Post #

**Contributors:** zaus, leadlogic

**Donate link:** http://drzaus.com/donate

**Tags:** contact form, form, contact form 7, CF7, gravity forms, GF, CRM, mapping, 3rd-party service, services, remote request, xml, json, soap, xml webservice, soap webservice, json webservice, nested fields, xpost

**Requires at least:** 3.0

**Tested up to:** 4.9.6

**Stable tag:** trunk

**License:** GPLv2 or later

Converts submission from [Forms 3rdparty Integration](http://wordpress.org/plugins/forms-3rdparty-integration/) to xml/json, add headers, or nest fields.

## Description ##

Converts external submission from [Forms: 3rdparty Integration](http://wordpress.org/plugins/forms-3rdparty-integration/) plugin to XML or JSON post; optionally can add custom headers (to allow SOAP submissions) or transform/combine separate fields into nested values.

This plugin will turn the 3rdparty mappings into XML elements, so that each form post will be the value (or attribute) of an XML element.

## Installation ##

1. Unzip, upload plugin folder to your plugins directory (`/wp-content/plugins/`)
2. Make sure [Contact Form 7][] or [Gravity Forms][] is installed
2. Make sure [Forms: 3rdparty Integration](http://wordpress.org/plugins/forms-3rdparty-integration/) is installed
3. Activate plugin
4. Go to new admin subpage _"3rdparty Services"_ under the CF7 "Contact" menu or Gravity Forms "Forms" menu and configure services + field mapping.
5. Configure the new "Xml Post" section to choose which services send as xml and/or determine special headers (given as a url querystring).
6. Nest fields by separating nodes with `/`, and indicate attributes with `@`.  Numeric indexes by themselves will result in repetition of the parent element (ex. `item/%i/sub` could make `<item><sub /></item><item><sub /></item>`).

[Contact Form 7]: http://wordpress.org/extend/plugins/contact-form-7/ "Contact Form 7"
[Gravity Forms]: http://www.gravityforms.com/ "Gravity Forms"

## Frequently Asked Questions ##

### I need help ###

Submit an issue to the [GitHub issue tracker] in addition to / instead of the WP Support Forums.

[GitHub issue tracker]: https://github.com/zaus/forms-3rdparty-xpost/issues "GitHub issue tracker"


### How do I add / configure a service? ###

See "base plugin" [Forms: 3rdparty Integration](http://wordpress.org/plugins/forms-3rdparty-integration/).


### How do I set headers ###

Provide the list of headers as though they were a URL querystring, so that

> Content-Type: something
> X-Special-Header: something-else

would be given as

> `Content-Type=something&X-Special-Header=something-else`

You may also use shortcodes such as `base64` in the header.

### How do I nest elements? ###

Separate element names within the same 3rdparty field mapping using `/`, so that in order to make:

    <credentials type="123">
        <user>xyz</user>
        <pass>abc</pass>
    </credentials>

you would use `credentials/@type`, `credentials/user` and `credentials/pass`, respectively.

**Note:** You may nest and wrap elements even if not transforming into XML; they will be submitted as multi-dimensional arrays like:

    credentials[@type]=123&credentials[user]=xyz&credentials[pass]=abc

### How do I repeat elements? ###

As of v1.3, if there is a standalone numerical index it will cause repetition of the "parent" element.

ex) If the post is:

	item => array (
			0 => value1,
			1 => value2,
			2 => value3
		)

it will result in

	<item>value1</item>
	<item>value2</item>
	<item>value3</item>

You can accomplish this with the Forms-3rdparty separator `[%]` to place your index appropriately.

### How do I set xml prolog attributes? ###

Just enter the entire root xml in the field, a la http://stackoverflow.com/questions/5992268/simplexml-how-to-correctly-set-encoding-and-xmins

### How do I autoclose/not autoclose empty values? ###

To produce `<SomeTag />`, make sure the "Autoclose" option is enabled.
To produce `<SomeTag></SomeTag>`, make sure the "Autoclose" option is unchecked.

### How do I completely customize the xml/wrappers/transform? ###

Use the 'Mask' format, which allows you to specify the result exactly as you want via string replacement (`sprintf`), or the 'Replace' format which will replace string tokens (`{{3rdparty}}`).  Useful for complex XML.

The 'Root Element' field will now be treated as a string-replacement mask (a la `sprintf` for "Mask" or `str_replace` for "Replace"), so make sure to include the post body with the appropriate placeholder(s) (`%s` for "Mask", `{{3rdparty_Fields}}` for "Replace").
For 'Mask' format, each '3rd-Party Field' will also be treated the same, using `%s` to indicate where the submission value should go.
For 'Replace' format, repeating fields are not handled -- it essentially looks for instances of each "3rd-Party Field" column and replaces it with the corresponding input value.

## Screenshots ##

__None available.__

## Changelog ##

### 1.4.2 ###
* wrapper field is textarea for easier format usage

### 1.4.1 ###
* fix constructor warning for PHP7

### 1.4 ###
* new string replacement format using mustache-style token placeholders `{{3rdparty}}`

### 1.3.3 ###
* actually fix #24 parsing xml in root

### 1.3.2 ###
* fix: bug parsing existing xml root
* allow shortcodes in root

### 1.3 ###
* removed somewhat useless numerical index prefixing (n0, n1, nEtc)
* replaced with element repetition instead

### 1.2 ###
* ignores xml root when considering escaped backslashes (compatibility break -- please update your setting accordingly)
* 'mask' format -- greater flexibility and control over field/wrapper
* shortcodes in header; base64 shortcode

### 1.0 ###
* autoclose option
* decided it was good enough to be v1

### 0.5 ###
* Added `multipart/form-data` and 'url' formatting per GitHub issue #6 https://github.com/zaus/forms-3rdparty-xpost/issues/6
* Added xml prolog/root workaround per GitHub issue #8 https://github.com/zaus/forms-3rdparty-xpost/issues/8
    * Can now enter actual xml as root element for finer customization

### 0.4.2 ###
Can post body as json instead

### 0.4 ###
Fixed GitHub issue #3 https://github.com/zaus/forms-3rdparty-xpost/issues/3:

* each plugin setting is only applied to that specific service if it has a value
* removed default `post` wrapper unless sending as xml (and if you send XML you should specify a wrapper)

### 0.3 ###
Can nest regular post fields even when not submitting XML.

### 0.2 ###
* Element nesting, attributes
* reworked "xmlify" using recursive `SimpleXMLElement`

### 0.1 ###
Base version - xml and header transformation

## Upgrade Notice ##

### 1.3.2 ###
* fixed a bug in root parsing for already xml, please let me know if it breaks your usage

### 1.3 ###
* no longer prefixes standalone numerical indexes with `n`
* instead will repeat the 'parent' element -- so a mapping of `item/%i/sub%i` could make `<item><sub1 /></item><item><sub2 /></item>`

### 1.2 ###
* no longer requires that you escape backslashes in the wrapper if providing XML (i.e. it starts with &lt;) -- breaks backwards compatibility