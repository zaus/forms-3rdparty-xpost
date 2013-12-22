<?php
/*

Plugin Name: Forms-3rdparty Xml Post
Plugin URI: https://github.com/zaus/forms-3rdparty-xpost
Description: Converts submission from <a href="http://wordpress.org/plugins/forms-3rdparty-integration/">Forms 3rdparty Integration</a> to xml, add headers
Author: zaus, leadlogic
Version: 0.1
Author URI: http://drzaus.com
Changelog:
	0.1 init
*/



class Forms3rdpartyXpost {

	const N = 'Forms3rdpartyXpost';
	const B = 'Forms3rdPartyIntegration';

	public function Forms3rdpartyXpost() {
		// attach a little later so other plugins can bypass submission 
		add_filter(self::B.'_service_filter_args', array(&$this, 'post_args'), 12, 3);

		// just provides a listing of placeholders
		// add_filter(self::B.'_service_metabox', array(&$this, 'service_metabox'), 10, 4);

		// configure whether to attach or not, how
		add_filter(self::B.'_service_settings', array(&$this, 'service_settings'), 10, 3);
	}

	const PARAM_HEADER = 'xpost-header';
	const PARAM_ASXML = 'as-xpost';
	const PARAM_WRAPPER = 'xpost-wrapper';

	public function post_args($args, $service, $form) {

		// shorthand
		$body = &$args['body'];

		// scan the post args for meta instructions

		// check for headers in the form of a querystring
		if(isset($service[self::PARAM_HEADER])) {
			parse_str($service[self::PARAM_HEADER], $headers);
			// do we already have some? merge
			if(isset($args['headers'])) {
				$args['headers'] = array_merge( (array)$args['headers'], $headers );
			}
			else {
				$args['headers'] = $headers;
			}
		}
		// note that per-post mappings will override defaults given in main settings
		if(isset($body['#' . self::PARAM_HEADER])) {
			parse_str($body['#' . self::PARAM_HEADER], $headers);
			// do we already have some? merge
			if(isset($args['headers'])) {
				$args['headers'] = array_merge( (array)$args['headers'], $headers );
			}
			else {
				$args['headers'] = $headers;
			}
			unset($body['#' . self::PARAM_HEADER]); // remove meta command from post
		}


		// are we sending this form as xml?
		if( isset($body['#' . self::PARAM_ASXML]) ) {
			unset($body['#' . self::PARAM_ASXML]); // remove meta command from post
		}
		elseif(!isset($service[self::PARAM_ASXML]) || 'true' != $service[self::PARAM_ASXML]) return $args;

		
		// do we have a custom wrapper?
		if(isset($body['#' . self::PARAM_WRAPPER])) {
			$wrapper = array_reverse( explode('/', $body['#' . self::PARAM_WRAPPER]) );
			unset($body['#' . self::PARAM_WRAPPER]); // remove meta command from post
		}
		elseif(isset($service[self::PARAM_WRAPPER])) {
			$wrapper = array_reverse( explode('/', $service[self::PARAM_WRAPPER]) );
		}
		else {
			$wrapper = array('post');
		}


		// loop through wrapper to "convert" to xml
		foreach($wrapper as $el) {
			$body = $this->cheap_xmlify($body, $el);
		}

		return $args;
	}//--	fn	post_args


	function cheap_xmlify($arr, $root = 'x', $d = 0) {
		// could use instead http://stackoverflow.com/a/1397164/1037948
		$xml = '';
		$tab = "\n" . str_repeat("\t", $d+1);

		if($root) $xml .= "<$root>";

		if(is_array($arr)) {
			foreach($arr as $k => $v) {
				$xml .= $tab . self::cheap_xmlify($v, $k, $d+1);
			}
			$xml .= "\n";
		} else {
			$xml .= $arr;
		}

		if($root) $xml .= "</$root>";

		return $xml;
	}//--	fn	cheap_xmlify



	public function service_metabox($P, $entity) {

		?>
		<div id="metabox-<?php echo self::N; ?>" class="meta-box">
		<div class="shortcode-description postbox" data-icon="?">
			<h3 class="hndle"><span><?php _e('Xml Post', $P) ?></span></h3>
			
			<div class="description-body inside">

				<p class="description"><?php _e('Configure how to transform service post body into XML, and/or set headers.', $P) ?></p>
				<p class="description"><?php _e('Note: you may also specify these values per service as &quot;special&quot; mapped values -- see each field for instructions.', $P) ?></p>

				
			</div><!-- .inside -->
		</div>
		</div><!-- .meta-box -->
	<?php

	}//--	fn	service_metabox

	public function service_settings($eid, $P, $entity) {
		?>
		<fieldset><legend><span><?php _e('Xml Post'); ?></span></legend>
			<div class="inside">
				<p class="description"><?php _e('Configure how to transform service post body into XML, and/or set headers.', $P) ?></p>
				<p class="description"><?php _e('Note: you may also specify these values per service as &quot;special&quot; mapped values -- see each field for instructions.', $P) ?></p>

				<?php $field = self::PARAM_ASXML; ?>
				<div class="field">
					<label for="<?php echo $field, '-', $eid ?>"><?php _e('Post all services as XML?', $P); ?></label>
					<input id="<?php echo $field, '-', $eid ?>" type="checkbox" class="checkbox" name="<?php echo $P, '[', $eid, '][', $field, ']'?>" value="true"<?php echo isset($entity[$field]) ? ' checked="checked"' : ''?> />
					<em class="description"><?php _e('Should all services transform post body to xml?', $P);?> 
						<?php echo sprintf(__('Note: you can specify this per service with a static field mapping of <code>%s</code>.', $P), '#'.$field); ?></em>
				</div>
				<?php $field = self::PARAM_WRAPPER; ?>
				<div class="field">
					<label for="<?php echo $field, '-', $eid ?>"><?php _e('Xml Root Element(s)', $P); ?></label>
					<input id="<?php echo $field, '-', $eid ?>" type="text" class="text" name="<?php echo $P, '[', $eid, '][', $field, ']'?>" value="<?php echo isset($entity[$field]) ? esc_attr($entity[$field]) : 'post'?>" />
					<em class="description"><?php _e('Wrap contents all xml-transformed posts with this root element.  You may specify more than one by separating names with forward-slash', $P);?> (<code>/</code>).  
						<?php echo sprintf(__('Note: you can specify this per service with a static field mapping of <code>%s</code>.', $P), '#'.$field); ?></em>
				</div>
				<?php $field = self::PARAM_HEADER; ?>
				<div class="field">
					<label for="<?php echo $field, '-', $eid ?>"><?php _e('Post Headers', $P); ?></label>
					<input id="<?php echo $field, '-', $eid ?>" type="text" class="text" name="<?php echo $P, '[', $eid, '][', $field, ']'?>" value="<?php echo isset($entity[$field]) ? esc_attr($entity[$field]) : 'post'?>" />
					<em class="description"><?php _e('Override the post headers for all posts.  You may specify more than one by providing in &quot;querystring&quot; format', $P);?> (<code>Accept=json&amp;Content-Type=whatever</code>).  
						<?php echo sprintf(__('Note: you can specify this per service with a static field mapping of <code>%s</code>.', $P), '#'.$field); ?></em>
				</div>
			</div>
		</fieldset>
		<?php
	}//--	fn	service_settings



}//---	class	Forms3partydynamic

// engage!
new Forms3rdpartyXpost();