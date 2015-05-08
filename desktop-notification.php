<?php
 	/*
	Plugin Name: Desktop Notification
	Plugin URI: http://buddypress-desktop-notification.websupporter.net/
	Description: Send desktop notification to your users.
	Tags: buddypress,desktop notification,notification,ajax,user,message
	Contributors: websupporter
	Version: 0.6
	Text Domain: dn
	Author: Websupporter
	Author URI: http://www.websupporter.net/
	License: GPLv2 or later
	License URI: http://www.gnu.org/licenses/gpl-2.0.html
	*/

	add_action( 'wp_enqueue_scripts', 'dn_scripts' );
	function dn_scripts(){
		if( is_user_logged_in() ){
			wp_enqueue_script( 'dn-script', plugins_url( 'script.js', __FILE__ ), array( 'jquery' ) );
			wp_localize_script( 'dn-script',
				'dnStrings',
				array( 
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'mp3_url' => apply_filters( 'dn_mp3_url', plugins_url( '/res/sound.mp3', __FILE__ ) ),
					'callback' => apply_filters( 'dn_js_callback', '_wpNOTIFY' ),
					'interval' => apply_filters( 'dn_js_interval', 5000 )
				)
			);
		}
	}
	
	/**
	 * dn_query()
	 * Checks for new messages to send
	 **/
	add_action('wp_ajax_dn-query', 'dn_query');
	add_action('wp_ajax_dn-query', 'dn_query');
	function dn_query(){
		$entries = array();
		$entries = dn_messages_query();
		if( count( $entries ) == 0 )
			$entries = dn_notifications_query();
		if( count( $entries ) == 0 )
			$entries = dn_activities_query();
		echo json_encode( apply_filters( 'dn_entries', $entries ) );
		die();
	}
	
	
	
	/**
	 * dn_messages_query()
	 * Checks for new friendship requests
	 **/
	function dn_notifications_query(){
		$entries = array();
		$args['per_page'] = 1;
		$args['is_new'] = 1;
		if( bp_has_notifications( apply_filters( 'dn_has_notifications', $args ) ) ){
			while( bp_the_notifications() ){
				bp_the_notification();
				$entry = array();
				$entry['type'] = bp_get_the_notification_component_action();
				$entry['id'] = bp_get_the_notification_id();
				$title = explode( '_', $entry['type'] );
				foreach( $title as $key => $val )
					$title[ $key ] = ucfirst( $val );
				
				$entry['title'] = implode( ' ', $title );
				$desc = bp_get_the_notification_description();
				preg_match('/href="(.*?)"/i', $desc, $matches);
				$entry['link'] = $matches[1];
				$entry['content'] = wp_strip_all_tags( $desc );
				
				$avatar = get_avatar( bp_get_the_notification_item_id() );
				preg_match('/src="(.*?)"/i', $avatar, $matches);
				$entry['avatar'] = $matches[1];
				
				$entries[] = $entry;
			}
		}
		return $entries;
	}
	
	/**
	 * dn_messages_query()
	 * Checks for new messages to send
	 **/
	function dn_messages_query(){
		global $messages_template;
		
		$entries = array();
		
		$args['per_page'] = 1;
		$args['box'] = 'inbox';
		$args['type'] = 'unread';
		if ( bp_has_message_threads( apply_filters( 'dn_has_message_threads', $args ) ) ){
			while ( bp_message_threads() ){
				bp_message_thread();
				$entry = array();
				$entry['type'] = 'message';
				$entry['content'] = bp_get_message_thread_subject();
				$avatar = bp_get_message_thread_avatar();
				preg_match('/src="(.*?)"/i', $avatar, $matches);
				$entry['avatar'] = $matches[1];
				$entry['id'] = bp_get_message_thread_id();
				$user = bp_get_message_thread_from();
				preg_match( '/title="(.*?)"/i', $user, $matches );
				$entry['title'] = sprintf( __( '%s send you a message', 'dn' ), $matches[1] );
				$entry['link'] = bp_get_message_thread_view_link();
				$entries[] = $entry;
			}
		}
		return $entries;
	}
	
	
	
	/**
	 * dn_activities_query()
	 * Checks for new activities to send
	 **/
	function dn_activities_query(){
		global $activities_template;
		$args['action'] = array( 'activity_comment','activity_update','friendship_created' );
	
		$args['per_page'] = 1;
		$args['user_id'] = friends_get_friend_user_ids( bp_loggedin_user_id() );
		$offset = get_user_meta( get_current_user_id(), 'last-dn-notification', true );
		if( ! empty( $offset ) )
			$args['offset'] = ( 1 + $offset );
		$args['since'] = date( 'Y-m-d H:i:s', strtotime( $_POST['since'] ) );
		$args['display_comments'] = 'stream';
		$entries = array();
		if ( bp_has_activities( apply_filters( 'dn_has_activities', $args ) ) ){
				while ( bp_activities() ){
					$entry = array();
					bp_the_activity();
					
					$entry['type'] = bp_get_activity_type();
					$entry['content'] = '';
					if( in_array( bp_get_activity_type(), array( 'activity_update', 'activity_comment' ) ) ){
						$entry['content'] = wp_strip_all_tags( bp_get_activity_content_body() );
					}
					if( bp_get_activity_type() == 'friendship_created' ){
						$entry['content'] = wp_strip_all_tags( bp_get_activity_action() );
					}
					
					$avatar = bp_get_activity_avatar();
					preg_match('/src="(.*?)"/i', $avatar, $matches);
					$entry['avatar'] = $matches[1];
					$entry['id'] = bp_get_activity_id();
					$entry['title'] = $activities_template->activity->display_name;
					$entry['link'] = bp_activity_get_permalink( $entry['id'] );
				
					$entries[] = $entry;
				}
		}
		if( isset( $entries[0]['id'] ) )
			update_user_meta( get_current_user_id(), 'last-dn-notification', $entries[0]['id'] );
		
		return $entries;
	}
?>