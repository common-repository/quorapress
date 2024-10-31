<?php
/*
Plugin Name: QuoraPress
Plugin URI: http://jinxcode.com/quorapress
Description: Show your Quora posts in Wordpress
Version: 0.2
Author: Stefan Mortensen
Author URI: http://jinxcode.com
License: GPL2
*/

/*  Copyright 2011  Stefan Mortensen  (email : stefan@jinxed.se)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


// Options
add_action('admin_menu', 'quorapress_menu');

function quorapress_shorttag_wrap($attributes){
	extract(shortcode_atts(array(
		'type' => 'answers',
		'items' => '10',
	), $attributes));	
	
	quorapress_show($type,$items);
}

add_shortcode('quora','quorapress_shorttag_wrap');

function quorapress_show($type = "answers",$numItems = 10){
	global $wpdb;

	$table_name = $wpdb->prefix . "quorapress_settings";
	$posts_table = $wpdb->prefix . "quorapress_posts";
	$quora_name = $wpdb->get_var("SELECT quoraname FROM $table_name LIMIT 1");
	if($quora_name=="Quora-Name"){
		echo "Please enter your Quora name in the plugin settings...";
	}
	else{
	$cache_time = $wpdb->get_var("SELECT lastcache FROM $table_name");
	$cache_int = $wpdb->get_var("SELECT cacheint FROM $table_name LIMIT 1");

	// Cache time has not expired
	if($cache_time + ($cache_int *60) > time() && $cache_time != 0){
		
		$items = $wpdb->get_results("SELECT * FROM $posts_table WHERE q_type = '$type' ORDER by time DESC LIMIT $numItems");
		$tmptime = 0;
		foreach ($items as $item) {
			if(date("m/d", strtotime($item->time)) != $tmptime)
				echo "<b>".date("m/d/Y", strtotime($item->time)).": </b><br/><a href=".$item->url.">".$item->title."</a><br>";
			else
				echo "<a href=".$item->url.">".$item->title."</a><br>";
				$tmptime = date("m/d", strtotime($item->time));
		}
	
	}
	else { // Cache time has expired
		
		$quora_url = "http://www.quora.com/$quora_name/$type/rss";
		require_once 'rss_php.php';  
	
		$rss = new rss_php;
		$rss->load($quora_url);
		$items = $rss->getItems(true);
		$i = 0;
		$tmptime = 0;
		foreach( $items as $item){
			if($i<=$numItems){
				if(date("m/d", strtotime($item[pubDate][value])) != $tmptime)
					echo "<b>".date("m/d/Y", strtotime($item[pubDate][value])).": </b><br/><a href=".$item[link][value].">".$item[title][value]."</a><br>";
				else
					echo "<a href=".$item[link][value].">".$item[title][value]."</a><br>";
					
				$tmptime = date("m/d", strtotime($item[pubDate][value]));
				
				// Do we need to add this post to the cache?
				$cached_page = $wpdb->get_var("SELECT title FROM $posts_table WHERE title='".$item[title][value]."'");
				if($cached_page!=$item[title][value]){
					$time = date('Y-m-d g:i:s a', strtotime($item[pubDate][value]));
					$title = $item[title][value];
					$description = strip_tags ( $item[description][value]);
					$url = $item[link][value];
					$wpdb->insert( $posts_table, array( 'time' => "$time", 'title' => "$title", 'body' => "$description", 'url' => "$url", 'q_type' => "$type"));
				}
			}
		
			$i++;
			// There is also description...
		}
		// Update cache time
		$wpdb->update( $table_name, array('lastcache' => time()), array('id' => '1') ); 
	//echo "<pre>".print_r($items)."</pre>";
	}
	}

}



function quorapress_menu() {

  add_options_page('QuoraPress Options', 'QuoraPress', 'manage_options', 'quorapress-options', 'quorapress_options');

}

function quorapress_options() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	echo '<div class="wrap">';
	echo "<h2>QuoraPress Options</h2>";	
	global $wpdb;
	
	$hidden_field_name = 'quorapress_submit_hidden';
	$table_name = $wpdb->prefix . "quorapress_settings";
	$quora_name_field = "quora-name-field";
	$quora_cache_field = "quora-cache-field";
	

	if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
		// Read their posted value
        $opt_name = $_POST[ $quora_name_field ];
		$opt_cache = $_POST[ $quora_cache_field ];
        // Save the posted value in the database
         $wpdb->update( $table_name, array('quoraname' => $opt_name, 'cacheint' => $opt_cache), array('id' => '1') ); 
		
		echo "Settings saved...<br/>";
	}
	
	$quora_name = $wpdb->get_var("SELECT quoraname FROM $table_name LIMIT 1");
	$quora_cache = $wpdb->get_var("SELECT cacheint FROM $table_name LIMIT 1");
?>
	<form name="form1" method="post" action="">
		<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
		<p><b><?php _e("Quora name:", 'menu-test' ); ?> </b>
			<input type="text" name="<?php echo $quora_name_field; ?>" value="<?php echo $quora_name; ?>" size="20"><br/>
			<i>Your Quora name is found on your profile page.<br/> For example my profile: http://www.quora.com/Stefan-Mortensen/ <br/> where "Stefan-Mortensen" is my username.</i>
		</p>
		<p><b><?php _e("Reload cache after (minutes):", 'menu-cache' ); ?> </b>
			<input type="text" name="<?php echo $quora_cache_field; ?>" value="<?php echo $quora_cache; ?>" size="4"><br/>
			<i>QuoraPress caches information from Quora to reduce load times.</i>
		</p>		
		<hr />

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
		</p>
	</form>

<?php
  echo '</div>';

}

// Install plugin, create required database tables
global $jal_db_version;
$jal_db_version = "1.0";

function jal_install () {
   global $wpdb;
   global $jal_db_version;

   $table_name = $wpdb->prefix . "quorapress_posts";
   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      
		$sql = "CREATE TABLE " . $table_name . " (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time DATETIME NOT NULL,
		title text NOT NULL,
		body text NOT NULL,
		q_type varchar(12) NOT NULL,
		url VARCHAR(55) NOT NULL,
		UNIQUE KEY id (id)
		);";
	
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}  
	
	$table_name = $wpdb->prefix . "quorapress_settings";
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      
		$sql = "CREATE TABLE " . $table_name . " (
		id mediumint(9) NOT NULL,
		quoraname text NOT NULL,
		cacheint int(4) NOT NULL,
		lastcache bigint(11) DEFAULT '0' NOT NULL,
		UNIQUE KEY id (id)
		);";
		dbDelta($sql);
		$wpdb->insert( $table_name, array( 'id' => 1, 'quoraname' => 'Quora-Name', 'cacheint' => 5) );
	}
     
	add_option("jal_db_version", $jal_db_version);

   
}

register_activation_hook(__FILE__,'jal_install');



?>
