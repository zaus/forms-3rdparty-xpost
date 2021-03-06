<?php
/*

Plugin Name: Forms-3rdparty Xml Post
Plugin URI: https://github.com/zaus/forms-3rdparty-xpost
Description: Converts submission from <a href="http://wordpress.org/plugins/forms-3rdparty-integration/">Forms 3rdparty Integration</a> to xml, json, add headers
Author: zaus, leadlogic
Version: 1.4.3
Author URI: http://drzaus.com
Changelog:
	0.1 init
	0.2 nesting
	0.3 doesn't need to be xml to nest, wrap
	0.4 fix per github issue #3
	0.4.3 post json + content-type defaults
	0.5 multipart style vs form/url; allow root trick
	1.0 autoclose option; robust enough to be v1
	1.2 mask style, base64+shortcodes; no longer need to escape xml wrapper
	1.3 addressing issue #7 with repeating nodes
	1.3.2 fix: bug parsing existing xml root; allow shortcodes in root
	1.4	replace style
	1.4.1 fix php7 constructor warning
	1.4.2 wrapper field is textarea for easier format usage
	1.4.3 supermask
*/



class Forms3rdpartyXpost {

	const N = 'Forms3rdpartyXpost';
	const B = 'Forms3rdPartyIntegration';

	public function __construct() {
		// attach a little later so other plugins can bypass submission 
		add_filter(self::B.'_service_filter_args', array(&$this, 'post_args'), 12, 3);

		// just provides a listing of placeholders
		// add_filter(self::B.'_service_metabox', array(&$this, 'service_metabox'), 10, 4);

		// configure whether to attach or not, how
		add_filter(self::B.'_service_settings', array(&$this, 'service_settings'), 10, 3);
		
		// register some shortcodes
		if(! shortcode_exists('base64') ) add_shortcode( 'base64', array(&$this, 'sc_base64') );
		if(! shortcode_exists('xpost-loop') ) add_shortcode( 'xpost-loop', array(&$this, 'sc_xpost_loop') );
	}

	const PARAM_HEADER = 'xpost-header';
	const PARAM_ASXML = 'as-xpost';
	const PARAM_WRAPPER = 'xpost-wrapper';
	const PARAM_SEPARATOR = '/'; // darn...interferes with actual xml in root; use '\' workaround later
	const PARAM_AUTOCLOSE = 'xpostac';

	const FORMAT_MASK = 'mask';
	const FORMAT_REPLACE = 'rpl';
	const ADVANCED_REPLACE = 'rplsc';


	public function post_args($args, $service, $form) {

		// scan the post args for meta instructions

		// check for headers in the form of a querystring
		if( isset($service[self::PARAM_HEADER]) && !empty($service[self::PARAM_HEADER]) ) {
			// handle shortcodes, special ETL, for functions like BASE64
			$h = do_shortcode( $service[self::PARAM_HEADER] );
			
			parse_str($h, $headers);
			// do we already have some? merge
			if(isset($args['headers'])) {
				$args['headers'] = array_merge( (array)$args['headers'], $headers );
			}
			else {
				$args['headers'] = $headers;
			}
		}

		// are we reconstructing this post?
		// make sure to check in either case if $root was set which,
		// if you're sending XML, should be -- otherwise it doesn't make much sense as default post
		if(!isset($service[self::PARAM_ASXML])) return $args;
		$format = $service[self::PARAM_ASXML];

		### _log(__FUNCTION__ . '@' . __LINE__ . '::before', $args['body'] );
		
		// nest tags only if not masking or replacing
		if($format == self::FORMAT_MASK) {
			$args['body'] = $this->mask($args['body']);
		}
		elseif($format != self::FORMAT_REPLACE) {
			$args['body'] = $this->nest($args['body']);
		}

		### _log(__FUNCTION__ . '@' . __LINE__ . '::after', $args['body'] );
		
		// do we have a custom wrapper?
		if(isset($service[self::PARAM_WRAPPER]) && !empty($service[self::PARAM_WRAPPER]))
			$root = do_shortcode( $service[self::PARAM_WRAPPER] ); // overkill?
		else $root = null;

		// only rewrap if not masking AND not given xml
		### _log('wrap-root', $root);
		if(!empty($root) && ($root[0] != '<' && $format != self::FORMAT_MASK && $format != self::FORMAT_REPLACE && $format != self::ADVANCED_REPLACE)) {
			$wrapper = array_reverse( explode(self::PARAM_SEPARATOR, $root) );
			// loop through wrapper to wrap
			$root = array_pop($wrapper); // save terminal wrapper as root for xmlifying
			if(!empty($wrapper)) foreach($wrapper as $el) {
				$args['body'] = array($el => $args['body']);
			}
		}
		### _log('after-wrap-root', $root);

		// wrap
		switch($format) {
			// retain legacy < 0.4.2 support for original value ('true') vs desired 'xml'
			case 'true':
				$format = 'xml'; // correct so consolidated handling below works
			// don't wrap
			case self::FORMAT_REPLACE:
			case self::FORMAT_MASK:
			case self::ADVANCED_REPLACE:
			case 'xml':
				break;
			default:
				if(isset($root)) $args['body'] = array($root => $args['body']);
				break;
		}// wrap root

		// process nodes
		switch($format) {
			case 'true':
			case 'xml':
				$this->autoclose = isset($service[self::PARAM_AUTOCLOSE]) && $service[self::PARAM_AUTOCLOSE];

				// sorry for the sad hack to allow actual xml in root element -- https://github.com/zaus/forms-3rdparty-xpost/issues/8#issuecomment-77098615
				$args['body'] = $this->simple_xmlify($args['body'], null, isset($root) ? str_replace('\\', '/', $root) : 'post')->asXML();
				break;
			case 'multipart':
				// via https://gist.github.com/UmeshSingla/40b5f7b0fb7e0ade0438
				$boundary = wp_generate_password( 24 );

				if(!isset($args['headers'])) $args['headers'] = array();
				if(!isset($args['headers']['Content-Type'])) $args['headers']['Content-Type'] = 'multipart/form-data; boundary=' . $boundary;

				// not sure how wrap affects things...
				
				$args['body'] = $this->as_multipart($args['body'], $boundary);
				break;
			case 'json':
				// just in case...although they pretty much need php 5.3 anyway
				if(function_exists('json_encode')) {
					$args['body'] = json_encode($args['body']);
				}
				break;
			case self::FORMAT_MASK:
				$args['body'] = sprintf($root ? $root : '%s', implode('', $args['body']));
				break;
			case self::ADVANCED_REPLACE:
				// reformat special loop sections
				$args['body'] = $this->advanced_replace_body($args['body'], $root);

				### _log(__FUNCTION__, $format, $args['body']);
				break;
			case self::FORMAT_REPLACE:
				### _log(__CLASS__ . '.' . __FUNCTION__ . '/'. $format, $args['body'], $service['mapping']);
				$args['body'] = $this->replace_body($args['body'], $root);
				break;
			case 'x-www-form-urlencoded':
				$args['body'] = http_build_query($args['body']);
			//case 'form':
			default:
				break;
		}

		// also set appropriate headers if not already
		switch($format) {
			case 'x-www-form-urlencoded':
			case 'xml':
			case 'json':
				if(!isset($args['headers'])) $args['headers'] = array();
				if(!isset($args['headers']['Content-Type'])) $args['headers']['Content-Type'] = 'application/' . $format;
				break;
		}

		### _log('xposted body', $body, 'args', $args);

		// don't need to wrap with filter -- user can just hook to same forms-integration filter with lower priority
		return $args;
	}//--	fn	post_args

	
	
	
	
	#region -------- convert body -----------

	function mask($body) {
		// scan body to replace formatted mask
		// need a new target so we can enumerate the original
		$nest = array();

		foreach($body as $k => $v) {
			// attach to new result so we don't dirty the enumerator
			$nest []= sprintf($k, $v); // TODO: crazy parameter extraction to reference other parts of $body
		}
	
		return $nest;
	}//--	fn	nest

	function nest($body) {
		// scan body to turn depth-2 into nested depth-n list
		// need a new target so we can enumerate the original
		$nest = array();

		foreach($body as $k => $v) {
			if(false === strpos($k, self::PARAM_SEPARATOR)) continue;
		
			// remove original
			unset($body[$k]);
		
			// split, reverse, and russian-doll the values for each el
			$els = array_reverse(explode(self::PARAM_SEPARATOR, $k));

			foreach($els as $e) {
				$v = array($e => $v);
			}
			
			// attach to new result so we don't dirty the enumerator (although we already unset...)
			$nest = $this->array_merge_rec_i($nest, $v); //array_merge_recursive($nest, $v);
		}
	
		return array_merge($nest, $body);
	}//--	fn	nest
	
	private $autoclose = false;


	/**
	 * Special recursive merge that handles numeric indices the same as string.
	 * The missing piece from issue #11
	 * @param $arr the "base" array
	 * @param $ins the array to insert
	 */
	function array_merge_rec_i($arr,$ins) {
		// http://php.net/manual/en/function.array-merge-recursive.php#82976
			
		# Loop through all Elements in $ins:
		if (is_array($arr) && is_array($ins)) foreach ($ins as $k => $v) {
			# Key exists in $arr and both Elemente are Arrays: Merge recursively.
			if (isset($arr[$k]) && is_array($v) && is_array($arr[$k])) $arr[$k] = $this->array_merge_rec_i($arr[$k],$v);
				# Place more Conditions here (see below)
				# ...
				# Otherwise replace Element in $arr with Element in $ins:
			else $arr[$k] = $v;
		}
		# Return merged Arrays:
		return($arr);
	}

	function simple_xmlify($arr, SimpleXMLElement $root = null, $el = 'x', $parent = null) {
		// could use instead http://stackoverflow.com/a/1397164/1037948

		if(!isset($root) || null == $root) {
			// xml hack -- if not, self-close
			if(false === strpos($el, '/') && 0 !== strpos($el, '<')) $el = "<$el/>";
			$root = new SimpleXMLElement($el);
		}
		
		### _log(__FUNCTION__, array($arr, $root, $el, $parent));

		if(is_array($arr)) {
			foreach($arr as $k => $v) {
				// special: attributes
				if(is_string($k) && $k[0] == '@') $root->addAttribute(substr($k, 1),$v);
				// special: a numerical index only should mean repeating nodes per #7
				else if(is_numeric($k)) {
					// first time, just add it to the existing element
					if($k == 0) $this->simple_xmlify($v, $root, $el, $parent);
					else $this->simple_xmlify($v, $parent->addChild($root->getName()), $el, $parent);
				}
				// normal: append
				else $this->simple_xmlify($v, $root->addChild($k), $el, $root);
			}
		} else {
			// don't set a value if nothing
			if($this->autoclose && empty($arr)) {}
			else $root[0] = $arr;
		}

		return $root;
	}//--	fn	simple_xmlify

	function as_multipart($post_fields, $boundary) {
		// https://gist.github.com/UmeshSingla/40b5f7b0fb7e0ade0438
		// http://stackoverflow.com/questions/4238809/example-of-multipart-form-data
		$payload = '';
		foreach ( $post_fields as $name => $value ) {
			$payload .= <<<ENDFIELD
--$boundary
Content-Disposition: form-data; name="$name"

$value

ENDFIELD;
		}
		return $payload;
	}//--	fn	as_multipart

	function mask_body($body, $wrapper) {
		return str_replace(
			array_map(function($k) { return '{{' . $k . '}}'; }, array_keys($body))
			, array_values($body)
			, $wrapper);
	}
	function replace_body($body, $wrapper, $prefix = '') {
		### _log(__FUNCTION__ . '@' . __LINE__, $body, $wrapper, $prefix);

		return str_replace(
			array_map(function($k) use($prefix) { return '{{' . $prefix . $k . '}}'; }, array_keys($body))
			, array_values($body)
			, $wrapper);
	}
	function advanced_replace_body($body, $wrapper) {
		// blah blah blah ##plc## something in between ##plc## more stuff afterwards
		//                ^start ^start+plclen         ^end   ^end+plclen
		foreach($body as $k => $v) {
			### _log(__FUNCTION__, $k, $v, $wrapper);
			
			// regular token replacement
			if(! is_array($v)) {
				$wrapper = str_replace('{{' . $k . '}}', $v, $wrapper);
				continue;
			}

			// check for shortcode loop placeholder
			$plc = $this->loop_placeholder($k);
			$start = strpos($wrapper, $plc);
			// if no loop placeholder, regular token replacement for each array
			if($start === false) {
				$wrapper = $this->replace_body($v, $wrapper, $k);
				continue;
			}

			$before = substr($wrapper, 0, $start);

			$plclen = strlen($plc);
			$start += $plclen; // skip the first placeholder, which will also correctly adjust the substr length
			$end = strpos($wrapper, $plc, $start);
			$loop = substr($wrapper, $start, $end - $start);

			$after = substr($wrapper, $end + $plclen);

			$wrapper = $before . implode('', array_map(function($sub) use($loop) { return $this->replace_body($sub, $loop); }, $v)) . $after;
		}

		return $wrapper;
	}
	#endregion -------- convert body -----------

	
	
	
	#region -------- shortcode -----------
	
	function sc_base64( $atts, $content = '' ) {
		$atts = shortcode_atts( array(), $atts, 'base64' );

		return base64_encode($content . implode('', $atts));
	}
	function sc_xpost_loop( $atts, $content = '' ) {
		$atts = shortcode_atts( array('on' => '', 'times' => 1), $atts, 'xpost-loop' );

		### _log(__FUNCTION__, $atts, $content);

		if(isset($atts['on'])) {
			$plc = $this->loop_placeholder($atts['on']);
			return "{$plc}$content{$plc}";
		}

		return str_repeat($content, $atts['times']);
	}
	function loop_placeholder($key) {
		return "##{$key}##";
	}
	
	#endregion -------- shortcode -----------
	

	// not used...here just in case we want inline help
	public function service_metabox($P, $entity) {

		?>
		<div id="metabox-<?php echo self::N; ?>" class="meta-box">
		<div class="shortcode-description postbox collapsed" data-icon="?">
			<h3 class="hndle actn"><span><?php _e('X Post', $P) ?></span></h3>
			
			<div class="description-body inside">

				<p class="description"><?php _e('Configure how to transform service post body into alternate format, and/or set headers.', $P) ?></p>
				<p class="description"><?php _e('Note: you may also specify these values per service as &quot;special&quot; mapped values -- see each field for instructions.', $P) ?></p>

				
			</div><!-- .inside -->
		</div>
		</div><!-- .meta-box -->
	<?php

	}//--	fn	service_metabox

	/**
	 * Get the list of formats for dropdown as value/label
	 */
	private function get_formats() {
		return array(
			'form' => 'Form',
			/* key should be 'xml', but 'true' for legacy support */
			'true' => 'XML',
			'json' => 'JSON',
			self::FORMAT_MASK => 'Format Mask',
			self::FORMAT_REPLACE => 'Replace Placeholders',
			self::ADVANCED_REPLACE => 'Advanced Placeholders',
			'multipart' => 'Multipart', // github issue #6
			'x-www-form-urlencoded' => 'URL' // this is really the same as 'form'...
		);
	}

	public function service_settings($eid, $P, $entity) {
		?>
		<fieldset class="postbox"><legend class="hndle"><span><?php _e('X Post'); ?></span></legend>
			<div class="inside">
				<p class="description"><?php _e('Configure how to transform service post body into alternate format, and/or set headers.', $P) ?></p>
				<p class="description"><?php _e('Leave any field blank to ignore it.', $P) ?></p>

				<?php $field = self::PARAM_ASXML; ?>
				<div class="field">
					<label for="<?php echo $field, '-', $eid ?>"><?php _e('Post service format:', $P); ?></label>
					<select id="<?php echo $field, '-', $eid ?>" class="select" name="<?php echo $P, '[', $eid, '][', $field, ']'?>">
						<?php foreach($this->get_formats() as $val => $lbl) : ?>
							<option value="<?php echo esc_attr($val), '"'; selected($entity[$field], $val) ?>><?php _e($lbl, $P) ?></option>
						<?php endforeach ?>
					</select>
					<em class="description"><?php _e('Should service transform post body format?  Default is "form" (unchanged), and "url" is essentially the same.', $P);?></em>
				</div>
				<?php $field = self::PARAM_WRAPPER; ?>
				<div class="field">
					<label for="<?php echo $field, '-', $eid ?>"><?php _e('Root Element(s)', $P); ?></label>
					<textarea id="<?php echo $field, '-', $eid ?>" class="text" name="<?php echo $P, '[', $eid, '][', $field, ']'?>"><?php echo isset($entity[$field]) ? esc_html($entity[$field]) : 'post'?></textarea>
					<em class="description"><?php _e('Wrap contents of transformed posts with this root element.  You may specify more than one by separating names with forward-slash', $P);?> (<code>/</code>), e.g. <code>Root/Child1/Child2</code>.</em>
					<em class="description"><?php echo sprintf(__('You may also enter xml prolog and/or xml, or a mask with placeholder %s for the body, or replacement placeholders like %s.', $P), '<code>%s</code>', '<code>{{3rdpartyToken}}</code>');?></em>
				</div>
				<?php $field = self::PARAM_HEADER; ?>
				<div class="field">
					<label for="<?php echo $field, '-', $eid ?>"><?php _e('Post Headers', $P); ?></label>
					<input id="<?php echo $field, '-', $eid ?>" type="text" class="text" name="<?php echo $P, '[', $eid, '][', $field, ']'?>" value="<?php echo isset($entity[$field]) ? esc_attr($entity[$field]) : ''?>" />
					<em class="description"><?php echo sprintf(__('Override the post headers for all posts.  You may specify more than one by providing in &quot;querystring&quot; format.  Allows shortcodes like %s.  Example) %s.', $P), '<code>[base64]</code>', '<code>Accept=json&amp;Content-Type=whatever&amp;Authorization=Basic [base64]user:pass[/base64]</code>');?></em>
				</div>
				<?php $field = self::PARAM_AUTOCLOSE; ?>
				<div class="field">
					<label for="<?php echo $field, '-', $eid ?>"><?php _e('Autoclose?', $P); ?></label>
					<input id="<?php echo $field, '-', $eid ?>" type="checkbox" class="checkbox" name="<?php echo $P, '[', $eid, '][', $field, ']'?>" value="1" <?php if(!isset($entity[$field])) $entity[$field] = 0; checked($entity[$field], 1)?> />
					<em class="description"><?php _e('Should empty xml elements autoclose or remain as open/close.', $P);?> (e.g. `true` = <code>&lt;el /&gt;</code> or `false` = <code>&lt;el&gt;&lt;/el&gt;</code>).</em>
				</div>
			</div>
		</fieldset>
		<?php
	}//--	fn	service_settings



}//---	class	Forms3rdpartyXpost

// engage!
new Forms3rdpartyXpost();