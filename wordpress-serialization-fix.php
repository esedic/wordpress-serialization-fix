<?php
/**
 * Plugin Name: Wordpress Serialization Fix
 * Plugin Uri: https://github.com/mateus007/wordpress-serialization-fix
 * Description: Fix broken serialized strings in Wordpress MySQL database
 * Version: 1.1.0
 * Author: Mateus Souza
 */

add_action('setup_theme', 'serializationFixRunScript', 1);
add_action('admin_menu', 'serializationFixAddMenu');
add_action('admin_notices', 'serializationFixedNotice');

/**
 * Add serialization fix menu
 * @return void
 */
function serializationFixAddMenu() {
	add_submenu_page(
		'tools.php',
		'Serialization Fix',
		'Serialization Fix',
		'manage_options',
		'serialization-fix',
		'serializationFixAdmin'
	);
}

/**
 * Show admin interface
 * @return void
 */
function serializationFixAdmin(){
	global $wpdb;
	$prefix = $wpdb->base_prefix;

	?>
	<div class="wrap">

		<h2>Wordpress Serialization Fix</h2>

		<form method="POST" action="#">
			<p>This plugin will automatically fix the following tables:<br/>
				- <b><?php echo $prefix ?>options</b><br/>
				- <b><?php echo $prefix ?>postmeta</b><br/>
				- <b><?php echo $prefix ?>usermeta</b></p>
			<input type="hidden" name="action" value="fix">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="Click to Fix Database!">
		</form>

	</div>
<?php
}

/**
 * Run serialization fix script
 * @return int
 */
function serializationFixRunScript(){
	global $wpdb, $serializationFixedCount;

	$count = 0;

	// OPTIONS
	$sql = "SELECT * FROM $wpdb->options WHERE option_value RLIKE 's:'";
	$options = $wpdb->get_results($sql);

	foreach( $options as $option ){

		$string = serializationFixString($option->option_value);

		if( $string == $option->option_value ){
			continue;
		}

		$count++;

		update_option(
			$option->option_name,
			unserialize($string),
			$option->autoload
		);

	}

	// POSTS META
	$sql = "SELECT * FROM $wpdb->postmeta WHERE meta_value RLIKE 's:'";
	$metas = $wpdb->get_results($sql);

	foreach( $metas as $meta ){

		$string = serializationFixString($meta->meta_value);

		if( $string == $meta->meta_value ){
			continue;
		}

		$count++;

		update_post_meta(
			$meta->post_id,
			$meta->meta_key,
			unserialize($string)
		);

	}

	// USER META
	$sql = "SELECT * FROM $wpdb->usermeta WHERE meta_value RLIKE 's:'";
	$metas = $wpdb->get_results($sql);

	foreach( $metas as $meta ){

		$string = serializationFixString($meta->meta_value);

		if( $string == $meta->meta_value ){
			continue;
		}

		$count++;

		update_user_meta(
			$meta->user_id,
			$meta->meta_key,
			unserialize($string)
		);

	}

	$serializationFixedCount = $count;
	return $count;
}

/**
 * Show serialization fixed notice
 * @return void
 */
function serializationFixedNotice(){
	global $serializationFixedCount;

	if( !isset($serializationFixedCount)
		OR !$serializationFixedCount ){
		return;
	}
    ?>
    <div class="updated notice">
        <p><strong>Database automatically fixed by Serialization Fix!</strong> <?php echo $serializationFixedCount ?> record(s) updated.</p></p>
    </div>
    <?php
}

/**
 * Fix broken serialized strings by recalculating length
 * @param string $string
 * @return string
 */
function serializationFixString($string){

	if( !is_serialized($string) ){
		return $string;
	}

	try {

		if( unserialize($string) == FALSE ){
			throw new Exception("Broken string", 1);
		}

	} catch(exception $e) {

		$string = preg_replace(
			'!s:(\d+):"(.*?)";!e',
			"'s:'.strlen('$2').':\"$2\";'",
			$string
		);

	}

	return $string;
}