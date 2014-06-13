<?php
/**
 * Plugin Name: Wordpress Serialization Fix
 * Plugin Uri: https://github.com/mateus007/wordpress-serialization-fix
 * Description: Fix the broken serialized strings in the mysql database
 * Version:     1.0.0
 * Author:      Mateus Souza
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
add_action( 'admin_menu', 'serializationFixAddMenu' );

function serializationFixAdmin(){
	?>
	<div class="wrap">

		<h2>Wordpress Serialization Fix</h2>

		<?php if($_POST['action'] AND $_POST['action']  == 'fix'): ?>
			<?php serializationFixRunScript() ?>
			<div class="updated settings-error"><p><strong>Database fixed!</strong></p></div>
		<?php endif ?>

		<form method="POST" action="#">
			<p>This plugin fix the tables: <b>##_options</b>, <b>##_postmeta</b> and <b>##_usermeta</b> (##_ is the table prefix, like <em>wp_</em>).</p>
			<input type="hidden" name="action" value="fix">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="Fix database!">
		</form>

	</div>
<?php
}

function serializationFixRunScript(){
	global $wpdb;

	// OPTIONS
	$sql = "SELECT option_id, option_value FROM $wpdb->options";
	$options = $wpdb->get_results($sql);

	foreach ( $options as $option ) {

		$fixedString = serializationFixRecalculateLength($option->option_value);
		if( $fixedString == $option->option_value ){
			continue;
		}

		$wpdb->update(
			$wpdb->options,
			array('option_value' => $fixedString), // data
			array('option_id'	=> $option->option_id) // where
		);

	}

	// POSTS META
	$sql = "SELECT meta_id, meta_value FROM $wpdb->postmeta";
	$metas = $wpdb->get_results($sql);

	foreach ( $metas as $meta ) {

		$fixedString = serializationFixRecalculateLength($meta->meta_value);
		if( $fixedString == $meta->meta_value ){
			continue;
		}

		$wpdb->update(
			$wpdb->postmeta,
			array('meta_value' => $fixedString), // data
			array('meta_id'	=> $meta->meta_id) // where
		);

	}

	// USER META
	$sql = "SELECT umeta_id, meta_value FROM $wpdb->usermeta";
	$metas = $wpdb->get_results($sql);

	foreach ( $metas as $meta ) {

		$fixedString = serializationFixRecalculateLength($meta->meta_value);
		if( $fixedString == $meta->meta_value ){
			continue;
		}

		$wpdb->update(
			$wpdb->usermeta,
			array('meta_value' => $fixedString), // data
			array('umeta_id'	=> $meta->umeta_id) // where
		);

	}

}

function serializationFixRecalculateLength($string) {
   $string = preg_replace('!s:(\d+):"(.*?)";!e', "'s:'.strlen('$2').':\"$2\";'", $string);
   return $string;
}
