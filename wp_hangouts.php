<?php
/*
Plugin Name: Hangouts ~CosmoQuest
Plugin URI: http://cosmoquest.org
Description:  A simple plugin that let's you embed a live Google Hangout on Air in a page or widget and announce the next show.
Author: Joe Moore
Version: 1.0
Author URI: http://cosmoquest.org
*/
global $jal_db_version;
$jal_db_version = "1.11";
//add/edits three database tables.
//TODO Not entirely certain this has been done right.
function cq_0_jal_install () {
   global $wpdb;
   global $jal_db_version;
   $installed_ver = get_option( "jal_db_version" );
	if( $installed_ver != $jal_db_version ) {
		$table_name = $wpdb->prefix . "hangouts"; 
		$sql = "CREATE TABLE $table_name (
		id mediumint(11) NOT NULL AUTO_INCREMENT,
		live tinyint(4) DEFAULT '0' NULL,
		hashtag varchar(500) NULL,
		embed blob NULL,
		show_id mediumint(11) NOT NULL,  
		show_time varchar(500) NULL,
		episode_description varchar(500) NULL,
		UNIQUE KEY id (id)
		);";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		$table_name = $wpdb->prefix . "hangout_shows"; 
		$sql = "CREATE TABLE $table_name (
		id mediumint(11) NOT NULL AUTO_INCREMENT,
		name varchar(255) NULL,
		url varchar(500) NULL,
		description varchar(500) NULL,
		UNIQUE KEY id (id)
		);";
		dbDelta( $sql );
		$table_name = $wpdb->prefix . "hangout_data"; 
		$sql = "CREATE TABLE $table_name (
		id mediumint(11) NOT NULL AUTO_INCREMENT,
		youtube_channel_name varchar(500) NULL,
		youtube_channel varchar(500) NULL,
		youtube_channel_url varchar (500) NULL,
		youtube_channel_link_text varchar(500) NULL,
		show_link_to_youtube mediumint(11) NULL,
		width mediumint(11),
		UNIQUE KEY id (id)
		);";
		dbDelta( $sql );
		update_option( "jal_db_version", $jal_db_version );
	}
}
//copy/pasta from wordpress site on updating tables with automatic updates
function cq_0_update_db_check() {
    global $jal_db_version;
    if (get_site_option( 'jal_db_version' ) != $jal_db_version) {
        cq_0_jal_install();
    }
}
add_action( 'plugins_loaded', 'cq_0_update_db_check' );
register_activation_hook( __FILE__, 'cq_0_jal_install' );
add_action('admin_menu', 'cq_0_hangoutMenu');
add_action('wp_print_scripts','cq_0_addCalendar');
add_shortcode('hangout', 'cq_0_hangoutHandler');
//Adds tabs in admin's sidebar
function cq_0_hangoutMenu(){
add_menu_page( 'Hangouts', 'Hangouts', 'manage_options', 'hangouts', 'cq_0_hangout_dashboard_widget_function','','4' );
add_submenu_page( 'hangouts', 'add/edit_shows', 'Add Edit Shows', 'manage_options', 'hangout_add_show', 'cq_0_add_edit_shows');
add_submenu_page( 'hangouts', 'settings', 'Settings', 'manage_options', 'hangout_settings', 'cq_0_settings');
}
//Code that is called when User specifies the [hangout] short code.
//This is meant to pretty much be the whole page
function cq_0_hangoutHandler($atts, $content){
	global $wpdb;
	/*if (!$atts['width']) { $atts['width'] = 1000; }
	if (!$atts['height']) { $atts['height'] = 562; }
	$hangout_data = $wpdb->prefix . "hangout_data"; 
	$results = $wpdb->get_results("SELECT youtube_channel FROM $hangout_data",ARRAY_A);
	$html ='<iframe width="'.$atts['width'].'" height="'.$atts['height'].'" src="'.$atts['src'].'" frameborder="0" allowfullscreen></iframe>';
	$html .="<br><a target='_blank' href='".$results[0]['youtube_channel']."'>youtube channel</a>";
	return $html;*/
	//gets all relevant data from db for most recent Hangout
	$hangout_data = $wpdb->prefix . "hangout_data"; 
	$hangouts = $wpdb->prefix . "hangouts";
	$hangout_show = $wpdb->prefix . "hangout_shows";
	$results = $wpdb->get_results("SELECT * FROM $hangout_data",ARRAY_A);
	$width = $results[0]['width'];
	$height = floor($width/(16/9));
	$channel_name = $results[0]['youtube_channel_name'];
	$playlist = $results[0]['youtube_channel'];
	$channel_link = $results[0]['youtube_channel_url'];
	$link_text = $results[0]['youtube_channel_link_text'];
	$show_link = $results[0]['show_link_to_youtube'];
	$results = $wpdb->get_results("SELECT * FROM $hangouts,$hangout_show where show_id = $hangout_show.id order by $hangouts.id desc limit 1",ARRAY_A);
	$live = $results[0]['live'];
	$hashtag = $results[0]['hashtag'];
	$embed = $results[0]['embed'];
	$show_time = $results[0]['show_time'];
	$show_name = $results[0]['name'];
	$show_url = $results[0]['url'];
	$description = $results[0]['description'];
	$episode_description = $results[0]['episode_description'];
	//display different things depending on live or not
	if($live == 1){
		$html =  "<p>Now Live: <a target='_blank' href='$show_url'>$show_name</a></p>";
		$html .='<iframe width="'.$width.'" height="'.$height.'" src="'.$embed.'?autoplay=1" frameborder="0" allowfullscreen></iframe>';
		if($show_link == 1){
			$html .= "<br><a href='$channel_link'>$link_text</a>";
		//	$html .= "<p>Click the above link to join in the comments.</p>";
		}
	}
	else{
		$html = "<p>Coming Up: <a target='_blank' href='$show_url'>$show_name</a></p>";
		$html .= "<p>$show_time</p>";
		$html .= "<p>$description</p>";
		$html .= "<p>$episode_description</p>";
		$html .='<iframe width="'.$width.'" height="'.$height.'" src="'.$playlist.'" frameborder="0" allowfullscreen></iframe>';
		if($show_link == 1)
			$html .= "<br><a target='_blank' href='$channel_link'>$link_text</a>";
	}
	return $html;
}
//neat calendar caller for deciding when a hangout will be
function cq_0_addCalendar(){
 wp_enqueue_script(
        'calendar',
        plugins_url( '/js/calendar.js',  __FILE__ )
       );
}
//Main Hangout Tab from Admin's sidebar
 function cq_0_hangout_dashboard_widget_function() {
	// Display whatever it is you want to show.
	global $wpdb;
	$hangout_shows = $wpdb->prefix . "hangout_shows"; 
	$hangouts =  $wpdb->prefix . "hangouts";
	$hangout_data = $wpdb->prefix . "hangout_data";
	$width = 1000;
	$results = $wpdb->get_results("SELECT width FROM $hangout_data order by id desc limit 1",ARRAY_A);
	if(results != null)
		$width = $results[0]['width'];
	$height = floor($width/(16/9));
	//This page handles its own form posting
	if(isset($_POST['task'])){
			// Write next show information
			if($_POST['task'] == "end") {
			$results = $wpdb->get_results("SELECT id FROM $hangouts order by id desc limit 1",ARRAY_A);
			$wpdb->update( 
				$hangouts, 
				array('live' => 0), 
				array('id' => $results[0]['id']), 
				array('%d'), 
				array('%d') 
			);
			$show_id = mysql_real_escape_string($_POST['show']);
			$show_description = mysql_real_escape_string($_POST['show_description']);
			$get_show = $wpdb->get_results("SELECT * FROM $hangout_shows WHERE id = $show_id",ARRAY_A);
			if (count($get_show) == 0){
				echo "Invalid show number"; die(); 
			}
			$show = $get_show[0]; 
			$show_id = $show['id'];
			$show_name = $show['name'];
			$show_time = mysql_real_escape_string($_POST['date'])." ".mysql_real_escape_string($_POST['time']);                        
			$time_url = "http://www.timeanddate.com/worldclock/fixedtime.html?msg=";
			$time_url .= str_replace(" ","+",$show_name);
			$time_url .= "&iso=";
			$time_url .= substr($show_time,-9,4);		
			$month_words = array("Jan", "Feb", "Mar","Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
			$month_num   = array("01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12");		
			$time_url .= str_replace($month_words, $month_num, substr($show_time, 3,3));
			$time_url .= substr($show_time,0,2);
			$time_url .= "T".substr($show_time,-4,4)."&p1=256";
			$temp = date("g:ia l, F d, o ", strtotime(substr($show_time,-4,4)." ".substr($show_time,0,11)));
			$show_time = "$temp Pacific (<a href='$time_url'>view all times here</a>)";
			$result = $wpdb->insert(
				$hangouts, 
				array('live' => 0,'show_id' => $show_id,'show_time' => $show_time,'episode_description' => $show_description),  
				array('%d','%d','%s','%s')
			);
				echo"<script>alert('Scheduling complete')</script>";
			}//starting the hangout 
			else if ($_POST['task'] == "start") {                 
					$orig = $_POST['embed'];
					$embed = mysql_real_escape_string($orig);			
					// Get the hashtag for twitter
					$hashtag = mysql_real_escape_string($_POST['tag']);
				    $results = $wpdb->get_results("SELECT id FROM $hangouts order by id desc limit 1",ARRAY_A);	
					$show_id = mysql_real_escape_string($_POST['show']);				
					$wpdb->update( 
					$hangouts, 
					array('live' => 1,'embed' => $embed,'hashtag' => $hashtag,'show_id' => $show_id), 
					array('id' => $results[0]['id']), 
					array('%d','%s','%s','%d'), 
					array('%d') 
				    );
					echo"<script>alert('Hangout Started')</script>";
		
			}
	} 
	$results2 = $wpdb->get_results("select * from $hangout_data",ARRAY_A);
	$results = $wpdb->get_results("select * from $hangout_shows",ARRAY_A);
	//This page really shouldn't be allowed to do anything until the user has given data to
	//The other two* pages
	//* at time of writing
	if(count($results) == 0 ||count($results2) == 0){
		echo"<a href='./admin.php?page=hangout_settings'>Go here for setup</a><br>";
		echo"<a href='./admin.php?page=hangout_add_show'>Go here to add show and make this page useful</a>";
	}
	//show useful tools
	else{
	?>	
	<div style="width:800px;height:500px;">
	<form style="float:left;" action="admin.php?page=hangouts" method="POST" enctype="multipart/form-data" class="show_form">
		<input type="hidden" name="action" value="admin">
		<input type="hidden" name="task" value="end">
		<?php			  
				 echo "<p id='announce_name' class='form_title'>Show Name: ";
				 echo "<select name='show'>";				
				 for($i = 0; $i < count($results); $i++) {
					echo "<option value='".$results[$i]['id']."'>".$results[$i]['name']."</option>";
				 }
				 echo "</select></p>";
				 echo "<p id='time_title' class='form_title'>Time: ";
				 echo "<select name='time'>";
				 for ($i = 0; $i<2400; $i+= 70) {
					echo "<option value='".sprintf("%04s", $i)."'>";
					echo sprintf("%04s", $i);
					echo "</option>";
					$i+=30;
					echo "<option value='".sprintf("%04s", $i)."'>";
					echo sprintf("%04s", $i);
					echo "</option>";
				 }
				 echo "</select> <span id='pacific' class='form_title'>PACIFIC TIMEZONE</span></p>"; 
				 echo "<p id='date_title' class='form_title'>Date: <input name='date' onclick='scwShow(this,event);' value='".$date."' /></p>";
				 echo "<p id='add_title' class='form_title'>Additional Information:</p><textarea id='description_form' name='show_description' cols='36' rows='5'></textarea><br/>";
		?>
		<input type="submit" value="Announce Show" id="announce_show_button" class="submit_button_hangout">
	</form>
	<?php
	$mostRecentHangout = $wpdb->get_results("SELECT * FROM $hangouts order by id desc limit 1",ARRAY_A);
	//Second half of the Main Hangout admin page
	//don't show if admin hasn't announced yet.
	if(count($mostRecentHangout) > 0){
		$show_id = $mostRecentHangout[0]['show_id'];
		echo'<form id="launchForm" style="float:right;" action="admin.php?page=hangouts" method="POST" enctype="multipart/form-data" class="show_form">';
		echo'<input type="hidden" name="action" value="admin">';
		echo'<input type="hidden" name="task" value="start">';
		echo'<ul id="start_show_data">';
		echo'<li id="show_name" >';
		echo "<p id='announce_name' class='form_title'>Show Name: ";
		echo "<select name='show'>";
		for($i = 0; $i < count($results); $i++){
			$temp ="";
			if($results[$i]['id'] == $show_id)
				$temp ="selected='selected'";
			echo "<option ".$temp." value='".$results[$i]['id']."'>".$results[$i]['name']."</option>";
		}
		echo "</select></p></li>";
		echo'<li id="hashtag_form_list">';
		//echo'<p id="hashtag_title" class="form_title">Enter the twitter hashtag for the show:</p>';
		//echo'<textarea name="tag" onclick="this.value=\'\';" onfocus="this.select()"onblur="this.value=!this.value?\'#Example\':this.value;" id="hashtag_form">';
		//echo'#Example';
		//echo'</textarea></li>';
		echo'<li id="embed_list">';
		echo'<p id="embed_title" class="form_title">Enter the embed code for the show:</p>';
		echo'<textarea id="embed" name="embed"></textarea>';
		echo'</li>';
		echo'<input class="submit_button_hangout" onclick="cq_0_prepareEmbedSubmit();" type="button" value="Launch Show">';
		echo'</ul>';
		echo'</form>';
	}
	?></div><br/>
	<script type="text/javascript">
	//currently not used code that gets user a shortcode may be needed later
		/*function createshortcode(id){
			temp = document.getElementById('embedIframe').children[0];
			span = document.createElement("p");
			span.innerHTML = '[hangout show="'+id+'" width="'+temp.width+'" height="'+temp.height+'" src="'+temp.src+'"]';
			temp = document.getElementById('embedIframe');
			temp.appendChild(span);
		}*/
		//because really I only need the src of the code YouTube gives the user.
		function cq_0_prepareEmbedSubmit(){
			embed = document.getElementById("embed") 
			temp = document.createElement("div");
			temp.id ='temp';
			temp.innerHTML = embed.value;	
			document.getElementsByTagName("body")[0].appendChild(temp);
			temp = document.getElementById('temp').children[0];
			if (typeof temp === "undefined")
			{		
				alert("improper embed code");
				temp = document.getElementById('temp');
				temp.parentNode.removeChild(temp);
			}
			else{
				embed.value = temp.src;
				document.getElementById("launchForm").submit();
			}
		}
	</script>
	<?php
	$results = $wpdb->get_results("SELECT show_id,live, FROM $hangouts order by id desc limit 1",ARRAY_A);
	 /* if($results[0]['live'] == 1){
		echo "<div id='embedIframe'>";
		echo $results[0]['embed'];
		echo "</div><script>createshortcode(".$results[0]['show_id'].");</script>";
	  }*/
	  echo"<span>Shortcode: [hangout]</span>";
	}
} 
//This page lets you add edit or delete Shows you do Hangouts for
function cq_0_add_edit_shows(){
global $wpdb;
$table_name = $wpdb->prefix . "hangout_shows"; 
//this page also takes care of handling its own form posting
	if(isset($_POST['hangout_action'])){ 		
		if($_POST['hangout_action'] =='add_show'){ 
			$name = mysql_real_escape_string($_POST['show_name']);
			$url = mysql_real_escape_string($_POST['show_url']); 
			$description = mysql_real_escape_string($_POST['description']);
			$wpdb->insert( $table_name, 
			array( 'name' => $name,'url' => $url,'description' => $description),  
			array( '%s', '%s','%s','%s')
			);
					echo"<script>alert('Show Added')</script>";
		
		}
		else if ($_POST['hangout_action'] =='update_show'){
 		  $description = mysql_real_escape_string($_POST['description']);
		  $delete = mysql_real_escape_string($_POST['delete']);
		  $name = mysql_real_escape_string($_POST['show_name']);
		  $url = mysql_real_escape_string($_POST['show_url']);
		  $id = mysql_real_escape_string($_POST['show_id']);
		  if($delete =="delete"){
			$wpdb->delete( $table_name, array('id'=>$id), array( '%d' ) );
			echo"<script>alert('Show Deleted')</script>";			
		  }
		  else{
				$wpdb->update( 
					$table_name, 
					array( 'name' => $name,'url' => $url,'description' => $description), 
					array( 'id' => $id ), 
					array('%s','%s','%s','%s'), 
					array( '%d' ) 
				);
				echo"<script>alert('Show Information Updated')</script>";
		  }		  		
		}
	}
	$results = $wpdb->get_results("select * from $table_name",ARRAY_A);
	for($i = 0; $i < count($results);$i++){
		if($i == 0){
			echo "Existing shows:<br/><table style='text-align:center; ' border=0>";
			echo "<tr style='font-weight:bold;'><td>Delete</td><td>Show Name</td><td>URL</td><td></td></tr><tr style='font-weight:bold;'><td></td><td></td><td>Description</td><td></td></tr>";
		}
		$id = $results[$i]['id'];
		$name = $results[$i]['name'];
		$url = $results[$i]['url'];
		$description = $results[$i]['description']; 
		echo"<form name='update_show_$id' action='./admin.php?page=hangout_add_show' method='post'>";
		echo"<input type='hidden' name='hangout_action' value='update_show'>";
		echo"<input type='hidden' name='show_id' value='$id'>";
		echo"<tr><td><input type='checkbox' name='delete' value='delete'></td>";
		echo"<td><input type='text' name='show_name' value='$name'></td>";
		echo"<td><input style='width:500px;' name='show_url' type='text' value='$url'></td><td></td></tr>";
		echo"<tr><td></td><td>";
		echo"<td><textarea style='width:500px;' name='description'>$description</textarea></td>";
		echo"<td><input type='submit' value='Update'></td></tr>";
		echo "</form>";
		if($i == count($results)-1)
			echo "</table><br>";
	}
	echo "<form name='add_show' action='./admin.php?page=hangout_add_show' method='post'>";
	echo "<p>Add new show:</p>";
	echo "<p>Name: <input type='text' name='show_name'> &nbsp;&nbsp;URL: <input type='text' name='show_url'></p>";
	echo "<p>Description: <textarea style='width:500px;' name='description'></textarea></p>";
	echo "<input type='hidden' name='hangout_action' value='add_show'>";
	echo "<input type='submit' value='Add Show'>";
	echo "</form>";
}
//Page that gets general information from the admin
function cq_0_settings(){
	global $wpdb;
	//and you guessed it. It handles its own form posts
	if(isset($_POST['hangout_action'])){  
		if($_POST['hangout_action'] =='link_channel'){
			$table_name = $wpdb->prefix . "hangout_data"; 
			$youtube_channel_name = mysql_real_escape_string($_POST['youtube_channel_name']);
			$youtube_channel_url = mysql_real_escape_string($_POST['youtube_channel_url']);
		    $youtube_channel = mysql_real_escape_string($_POST['youtube_embed']);
			$link_channel = mysql_real_escape_string($_POST['link_channel']);
			$link_text = mysql_real_escape_string($_POST['link_text']);
			$width = mysql_real_escape_string($_POST['width']);
			if(is_numeric($width)){
				if($width < 1 || $width > 5000)
					die("WIDTH NOT WITH ACCEPTABLE BOUNDS");
			}
			else
				die("WIDTH IS NOT A NUMBER");
			$delete = $wpdb->query("TRUNCATE TABLE '$table_name'"); 
			$wpdb->insert($table_name, 
				array( 
				'youtube_channel_name' => $youtube_channel_name, 
				'youtube_channel_url' => $youtube_channel_url,
				'youtube_channel' => $youtube_channel,
				'youtube_channel_link_text' => $link_text,
				'show_link_to_youtube' => $link_channel,
				'width' => $width
				),  
				array('%s','%s','%s','%s','%d','%d')
				); 
				echo"<script>alert('Information Updated')</script>";
		}
	}
	$table_name = $wpdb->prefix . "hangout_data"; 
	$results = $wpdb->get_results("select * from $table_name",ARRAY_A);
	$playlist = '<iframe width="'.$results[0]['width'].'" height="'.floor($results[0]['width']/(16/9)).'" src="'.$results[0]['youtube_channel'].'" frameborder="0" allowfullscreen></iframe>';
	echo "<form id='update_settings' name='link_to_channel' action='./admin.php?page=hangout_settings' method='post'>";
	echo "<p>Youtube Channel Name:<input name='youtube_channel_name' value='".$results[0]['youtube_channel_name']."' type='text'> Youtube Playlist Embed Code:<textarea id='playlist'  name='youtube_embed' >".$playlist."</textarea></p>";
	echo "<p>Youtube Channel URL:<input name='youtube_channel_url' value='".$results[0]['youtube_channel_url']."' type ='text'></p>";
	echo "<input type='hidden' name='hangout_action' value='link_channel'>";
	echo "<p><input checked='checked' type='checkbox' value='1' name='link_channel'> Do you want to show a link to your channel?</p>";
	echo "<p>Link Text<input value='".$results[0]['youtube_channel_link_text']."' type='text' name='link_text'></p>";
	echo "<p>Please select Hangout width YouTube uses 16:9 aspect ratio.<br/>Width: <input type='number' value='".$results[0]['width']."' name='width' min='1' max='5000'></p>";
	echo "<input type='button' onclick='cq_0_prepareSubmit();' value='Submit'>";
	echo "</form>";
	?><script type="text/javascript"> function cq_0_prepareSubmit(){
			embed = document.getElementById("playlist") 
			temp = document.createElement("div");
			temp.id ='temp';
			temp.innerHTML = embed.value;
			document.getElementsByTagName("body")[0].appendChild(temp);
			temp = document.getElementById('temp').children[0];
			if (typeof temp === "undefined")
			{		
				alert("improper embed code");
				temp = document.getElementById('temp');
				temp.parentNode.removeChild(temp);
			}
			else{
				embed.value = temp.src;
				document.getElementById("update_settings").submit();
			} 
		}</script>
<?php
}
//Adds widget for SideBar
class Hangout_Widget extends WP_Widget {
	function __construct() {
		parent::__construct(
			'hangout_widget', // Base IDst
			__('Hangout', 'text_domain'), // Name
			array( 'description' => __( 'Shows a Hangout widget', 'text_domain' ), ) // Args
		);
	}
	//shows the widget to the blog user
	public function widget( $args, $instance ) {
		global $wpdb;
		$hangouts =  $wpdb->prefix . "hangouts";
		$hangoutShows =  $wpdb->prefix . "hangout_shows";
		$hangoutData = $wpdb->prefix . "hangout_data";
		$results = $wpdb->get_results("SELECT show_id,live,embed,name,youtube_channel_name,youtube_channel,youtube_channel_url,description,episode_description,show_time FROM $hangouts,$hangoutShows,$hangoutData where show_id = $hangoutShows.id order by $hangouts.id desc limit 1",ARRAY_A);
		$width = $instance[ 'embed_player_width' ];
		$height = floor($width/(16/9));
		if(!isset($results[0]['live']))
		return;
		$title = apply_filters( 'widget_title', $instance['title'] );
		echo $args['before_widget'];
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];
			//changes based on live or not
		if($results[0]['live'] == 1){
			echo "<div id='embedIframe_widget'>";
			echo "<p>Now Live!</p>";
			if($instance['show_title'])
				echo "<p>".$results[0]['name']."</p>";
			if($instance['embed_player'])
				echo '<iframe width="'.$width.'"  src="'.$results[0]['embed'].'" frameborder="0" allowfullscreen></iframe><br>';
			if($instance['link_channel'])
				echo "<a href='".$results[0]['youtube_channel_url']."'>".$results[0]['youtube_channel_name']."</a><br>";
			echo "</div>";
		}
		else{
			echo "<div id='embedIframe_widget'>";
			echo "<p>Coming Up</p>";
			if($instance['show_title'])
				echo "<p>".$results[0]['name']."</p>";
			if($instance['off_show_time'])
				echo "<p>".$results[0]['show_time']."</p>";
			if($instance['off_show_description']){
				echo "<p>".$results[0]['description']."</p>";
				echo "<p>".$results[0]['episode_description']."</p>";
				}
			if($instance['off_embed_player'])
			 echo '<iframe width="'.$width.'"  src="'.$results[0]['youtube_channel'].'" frameborder="0" allowfullscreen></iframe><br>';
			if($instance['off_link_channel'])
				echo "<a href='".$results[0]['youtube_channel_url']."'>".$results[0]['youtube_channel_name']."</a><br>";
			echo "</div>";
		}
		echo $args['after_widget'];
	} 
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'New title', 'text_domain' );
		}
		if ( isset( $instance[ 'embed_player_width' ] ) ) {
			$width = $instance[ 'embed_player_width' ];
			if(is_numeric($width)){
				if($width < 1 || $width > 5000)
					$width = 500;
			}
			else
				$width = 500;
		}
		else
			$width = 500;
		//this sets up all the options for the widget.
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>* When a show is live *</p>
		<p>
		<input class="checkbox" type="checkbox" <?php checked($instance['show_title'], 'on'); ?> id="<?php echo $this->get_field_id('show_title'); ?>" name="<?php echo $this->get_field_name('show_title'); ?>" /> 
		<label for="<?php echo $this->get_field_id( 'show_title' ); ?>"><?php _e( 'Display Show Title' ); ?></label> 
		</p>
		<p>
		<input class="checkbox" type="checkbox" <?php checked($instance['embed_player'], 'on'); ?> id="<?php echo $this->get_field_id('embed_player'); ?>" name="<?php echo $this->get_field_name('embed_player'); ?>" /> 
		<label for="<?php echo $this->get_field_id( 'embed_player' ); ?>"><?php _e( 'Embed Player' ); ?></label> 		
		</p>
		<p>
		<input type="number" id="<?php echo $this->get_field_id('embed_player_width'); ?>" value="<?php echo esc_attr( $width ); ?>" name="<?php echo $this->get_field_name('embed_player_width'); ?>" min="1" max="5000">
		<label for="<?php echo $this->get_field_id( 'embed_player_width' ); ?>"><?php _e( 'px wide' ); ?></label> 		
		</p>
		<p>
		<input class="checkbox" type="checkbox" <?php checked($instance['link_channel'], 'on'); ?> id="<?php echo $this->get_field_id('link_channel'); ?>" name="<?php echo $this->get_field_name('link_channel'); ?>" /> 
		<label for="<?php echo $this->get_field_id( 'link_channel' ); ?>"><?php _e( 'Link to Channel (link is set in settings)' ); ?></label> 		
		</p>
		<p>* When Off Air *</p>
		<p>
		<input class="checkbox" type="checkbox" <?php checked($instance['off_show_title'], 'on'); ?> id="<?php echo $this->get_field_id('off_show_title'); ?>" name="<?php echo $this->get_field_name('off_show_title'); ?>" /> 
		<label for="<?php echo $this->get_field_id( 'off_show_title' ); ?>"><?php _e( 'Show Title' ); ?></label> 
		</p>
		<p>
		<input class="checkbox" type="checkbox" <?php checked($instance['off_show_time'], 'on'); ?> id="<?php echo $this->get_field_id('off_show_time'); ?>" name="<?php echo $this->get_field_name('off_show_time'); ?>" /> 
		<label for="<?php echo $this->get_field_id( 'off_show_time' ); ?>"><?php _e( 'Show Time' ); ?></label> 		
		</p>
		<p>
		<input class="checkbox" type="checkbox" <?php checked($instance['off_show_description'], 'on'); ?> id="<?php echo $this->get_field_id('off_show_description'); ?>" name="<?php echo $this->get_field_name('off_show_description'); ?>" /> 
		<label for="<?php echo $this->get_field_id( 'off_show_description' ); ?>"><?php _e( 'Show Description' ); ?></label> 		
		</p>
		<input class="checkbox" type="checkbox" <?php checked($instance['off_embed_player'], 'on'); ?> id="<?php echo $this->get_field_id('off_embed_player'); ?>" name="<?php echo $this->get_field_name('off_embed_player'); ?>" /> 
		<label for="<?php echo $this->get_field_id( 'off_embed_player' ); ?>"><?php _e( 'Show Channel Embed' ); ?></label> 		
		</p>
		<p>
		<input class="checkbox" type="checkbox" <?php checked($instance['off_link_channel'], 'on'); ?> id="<?php echo $this->get_field_id('off_link_channel'); ?>" name="<?php echo $this->get_field_name('off_link_channel'); ?>" /> 
		<label for="<?php echo $this->get_field_id( 'off_link_channel' ); ?>"><?php _e( 'Show Link to Channel' ); ?></label> 		
		</p>
		<?php 
	} 
	//Update function for widget class
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title']);
		$instance['show_title'] = strip_tags( $new_instance['show_title']);
		$instance['embed_player'] = strip_tags( $new_instance['embed_player']);
		$instance['embed_player_width'] = strip_tags( $new_instance['embed_player_width']);
		$instance['link_channel'] = strip_tags( $new_instance['link_channel']);	
		$instance['off_show_title'] = strip_tags( $new_instance['off_show_title']);
		$instance['off_show_time'] = strip_tags( $new_instance['off_show_time']);
		$instance['off_show_description'] = strip_tags( $new_instance['off_show_description']);
		$instance['off_embed_player'] = strip_tags( $new_instance['off_embed_player']);
		$instance['off_link_channel'] = strip_tags( $new_instance['off_link_channel']);
		return $instance;
	}
} 
function cq_0_register_hangout_widget() {
    register_widget( 'hangout_widget' );
}
add_action( 'widgets_init', 'cq_0_register_hangout_widget' );
?>