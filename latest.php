<?php
/*
Plugin Name: Latest MobileMe Photos
Plugin URI: http://www.kayseins.de/latest-mobileme-photos-plugin-for-wordpress/
Description: Displays your recently uploaded or changed photos from your MobileMe webgallery.
Author: Kay Butter
Version: 0.7
Author URI: http://www.kayseins.de
*/

/*  Copyright 2009 Kay Butter (kay.butter@me.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('MAGPIE_CACHE_ON', 0);

define("MML_domain", "latest");
define("MML_widget", "latest-mobileme-photos");


add_action('activate_latest-mobileme-photos/latest.php', 'MML_install');
function MML_install() {
	$default = array(
		"username" => "",
		"count" => 12,
		"check_frequency" => 3600,
		"last_check" => 0,
		"last_update" => 0,
		"size" => 40
	);
	add_option("MML_settings", $default, '', yes);
	add_option("MML_cache", false, '', yes);
}

add_action('plugins_loaded', "MML_init");
function MML_init() {
	load_plugin_textdomain(MML_domain, PLUGINDIR."/".dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));
	register_sidebar_widget(MML_widget, 'MML_widget');
	wp_register_widget_control(MML_widget, "tada", 'MML_widget_control');
}

function MML_widget_control() {
	$settings = get_option("MML_settings");
	$widget_data = $_POST[MML_widgetname];
	if($widget_data["submit"]) {
		$settings["username"] = $widget_data["username"];
		$settings["count"] = $widget_data["count"];
		$settings["check_frequency"] = $widget_data["check_frequency"];
		$settings["size"] = $widget_data["size"];
		update_option("MML_settings", $settings);
		update_option("MML_cache", array());
	}
	$check_frequency = $settings["check_frequency"];
	$count = $settings["count"];
	$username = $settings["username"];
	$size = $settings["size"];
	
	?>
	<p><label for="MML_username"><?php echo __("MobileMe Username", MML_domain); ?>:</label><input type="text" name="<?php echo MML_widgetname; ?>[username]" value="<?php echo $username; ?>" id="MML_username" /></p>
	<p><label for="MML_count"><?php echo __("Display Limit", MML_domain); ?>:</label><input type="text" name="<?php echo MML_widgetname; ?>[count]" value="<?php echo $count; ?>" id="MML_count" /></p>
	<p><label for="MML_size"><?php echo __("Thumbnail Size", MML_domain); ?>:</label><input type="text" name="<?php echo MML_widgetname; ?>[size]" value="<?php echo $size; ?>" id="MML_size" /></p>
	<p><label for="MML_check_frequency"><?php echo __("Update Frequency", MML_domain); ?>:</label><input type="text" name="<?php echo MML_widgetname; ?>[check_frequency]" value="<?php echo $check_frequency; ?>" id="MML_check_frequency" /></p>
	<input type="hidden" name="<?php echo MML_widgetname; ?>[submit]" value="1"/>
	<?php
	
}

function MML_widget($args) {
	extract($args, EXTR_SKIP);
	echo $before_widget;
	echo $before_title.__("Latest Photos", MML_domain).$after_title;
	mm_latest_photos();
	echo $after_widget;
}

function mm_get_latest_photos() {
	$currentTime = time();
	$settings = get_option("MML_settings");
	$cache = get_option("MML_cache");
	
	$force_update = $_REQUEST['force_update'] || !(is_array($cache) && count($cache) > 0);	
	
	$check_for_updates = $force_update || !($currentTime <= $settings["last_check"]+$settings["check_frequency"]);

	if($check_for_updates) {
		$gallery = new MMGallery($settings["username"]);
		if($gallery->needsUpdate($settings["last_update"]) || $force_update) {
			$photos = $gallery->getRecentPhotos();
			$photos = array_slice ($photos, 0, $settings["count"]);
			$cache = $photos;
			$settings["last_update"] = $currentTime;
			update_option("MML_cache", $cache);
		} else {
			$photos = $cache;
		}
		$settings["last_check"] = $currentTime;
		update_option("MML_settings", $settings);
	} else {
		$photos = $cache;
	}
	return $photos;
}

function mm_latest_photos() {
	$photos = mm_get_latest_photos();
	$settings = get_option("MML_settings");
	if(is_array($photos) && count($photos) > 0) {
		echo "<div class='MMPhotos'>";
		foreach ($photos as $key => $photo) {
			echo sprintf('<a href="%s"><img width="%s" height="%s" src="%s" alt="%s" title="%s" /></a>', $photo["link"], $settings["size"], $settings["size"], $photo["square"], attribute_Escape($photo["title"]), attribute_Escape($photo["title"]));
		}
		echo "</div>";
	} else {
		echo __("Error: Images could not be loaded.", MML_domain);
		echo "<!--"; var_dump($settings); echo "-->";
	}
}


/**
* MMGallery
*/
class MMGallery {
	
	var $username;
	var $categoryFeedPattern = "http://gallery.me.com/--username--/?webdav-method=truthget&feedfmt=recentrss";
	var $categoryFeed = null;
	
	function MMGallery($username) {
		$username = str_replace("@me.com", "", $username);
		$username = str_replace("@mac.com", "", $username);
		$this->username = $username;
	}
	
	function getFeed($url) {
		include_once(ABSPATH . WPINC . '/rss.php');
		$feed = fetch_rss($url);

		return $feed;
	}
	function getCategoryFeed() {
		if(!$this->categoryFeed) {
			$url = str_replace("--username--", $this->username, $this->categoryFeedPattern);
			$this->categoryFeed = $this->getFeed($url);
		}
		return $this->categoryFeed;
	}
	
	function getCategoryUrls() {
		$feed = $this->getCategoryFeed();
		$list = array();
		if(!is_null($feed->items)) {
			foreach($feed->items as $item) {
				$list[] = $item["link"]."?webdav-method=truthget&feedfmt=recentrss&aggregate=-1";
			}
		}
		return $list;
	}
	function itemToPhoto($item) {
		$photo = array();
		$photo["link"] = $item["link"];
		$photo["square"] = $item["link"].".jpg?derivative=square&amp;source=web.jpg&amp;type=square";
		$photo["title"] = $item["title"];
		$photo["date"] = strtotime($item["pubdate"]);
		$photo["pubdate"] = $item["pubdate"];
		return $photo;
	}
	function getPhotosFromFeed($url) {
		$feed = $this->getFeed($url);
		$photos = array();
		if($feed && is_array($feed->items)) {
			foreach($feed->items as $item) {
				$photos[] = $this->itemToPhoto($item);
			}
		}
		return $photos;
	}
	function needsUpdate($last_updated) {
		$feed = $this->getCategoryFeed();
		$pubdate = strtotime($feed->channel["pubDate"]);
		return $last_updated < $pubdate;
	}
	function getRecentPhotos() {
		$urls = $this->getCategoryUrls();
		$photos = array();
		foreach($urls as $url) {
			$new = $this->getPhotosFromFeed($url);
			$photos = array_merge($photos, $new);
		}
		usort($photos, array(&$this, "comparePhotos"));
		return $photos;
	}

	function comparePhotos($photo1, $photo2) {
		if($photo1["date"] == $photo2["date"]) {
			return 0;
		}
		return ($photo1["date"] < $photo2["date"]) ? 1 : -1;
	}

}
?>