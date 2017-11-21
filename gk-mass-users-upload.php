<?php
/*
  Plugin Name: Mass Users Upload
  Plugin URI: http://www.greateck.com/
  Description: Allow User CSV List to be uploaded to wordpress
  Version: 0.1.3
  Author: Mohammad Farhat
  Author URI: http://www.greateck.com
  License: GPLv2
 */

add_action( 'admin_menu', 'gk_mass_user_prod_admin_menu' );

function gk_mass_user_prod_admin_menu() {
	add_users_page( 'Mass Users Upload', 'Mass Users Upload', 'manage_options', 'mass-users-upload', 'gk_mass_users_upload');
}

function gk_mass_users_upload() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	echo '<h2>Mass Users Upload</h2>';
	echo '<div class="wrap">';
	?>
	<script type="text/javascript">
	function check(){
		if(document.getElementById("uploadfiles").value == "" && jQuery( "#upload_file" ).is(":visible") ) {
		   alert("<?php _e( 'Please choose a file', 'gk-mass-users-upload' ); ?>");
		   return false;
		}
	}
	</script>
	<?php
	
	// print_r($_POST);
	if (isset($_POST['gk_import_process'])){
		//validating the nonce
		check_admin_referer( 'gk-mass-users-upload_'.get_current_user_id() );
		//gk_wpr_prod_remove_db();
		gk_fileupload_process( $_POST, false );
	}else{
		//display the file upload action
		?>
		<p>To insert the users, choose the proper CSV file, and then click start importing!</p>
		<p>You can download a sample of the CSV file format to use to fill your data <a href="<?php echo esc_url( plugins_url( 'sample_mass_users.csv', __FILE__ ) );?>">here</a></p>
		<p><i>Please note that duplicate users will be skipped.</i></p>
		<form method="POST" enctype="multipart/form-data" action="" accept-charset="utf-8" onsubmit="return check();">
			<div id="upload_file">
				<input type="file" name="uploadfiles[]" id="uploadfiles" size="35" class="uploadfiles" />
			</div>
			<?php wp_nonce_field( 'gk-mass-users-upload_'.get_current_user_id() );?>
			<input type="hidden" id="gk_import_process" name="gk_import_process">
			<br/>
			<input class="button-primary" type="submit" name="uploadfile" id="uploadfile_btn" value="Start importing!"/>
		</form>
		<?php
	}
	echo '</div>';
	echo '<br />';
}

function gk_user_id_exists( $user_id ){
	if ( get_userdata( $user_id ) === false )
	    return false;
	else
	    return true;
}

function gk_get_roles($user_id){
	$roles = array();
	$user = new WP_User( $user_id );

	if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
		foreach ( $user->roles as $role )
			$roles[] = $role;
	}

	return $roles;
}

function gk_string_conversion( $string ){
	if(!preg_match('%(?:
    [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
    |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
    |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
    |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
    |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
    |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
    |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
    )+%xs', $string)){
		return utf8_encode($string);
    }
	else
		return $string;
}

//handle file upload
function gk_fileupload_process( $form_data ) {

  $path_to_file = $form_data["path_to_file"];
  $uploadfiles = $_FILES['uploadfiles'];

  if( empty( $uploadfiles["name"][0] ) ):
  	
  	  if( !file_exists ( $path_to_file ) )
  			wp_die( __( 'Error, we cannot find the file', 'gk-mass-users-upload' ) . ": $path_to_file" );

  	gk_import_users( $path_to_file, $form_data, 0 );

  else:
  	 
	  if ( is_array($uploadfiles) ) {

		foreach ( $uploadfiles['name'] as $key => $value ) {

		  // look only for uploaded files
		  if ($uploadfiles['error'][$key] == 0) {
			$filetmp = $uploadfiles['tmp_name'][$key];

			//clean filename and extract extension
			$filename = $uploadfiles['name'][$key];

			// get file info
			// @fixme: wp checks the file extension....
			$filetype = wp_check_filetype( basename( $filename ), array('csv' => 'text/csv') );
			$filetitle = preg_replace('/\.[^.]+$/', '', basename( $filename ) );
			$filename = $filetitle . '.' . $filetype['ext'];
			$upload_dir = wp_upload_dir();
			
			if ($filetype['ext'] != "csv") {
			  wp_die('File must be a CSV');
			  return;
			}

			/**
			 * Check if the filename already exist in the directory and rename the
			 * file if necessary
			 */
			$i = 0;
			while ( file_exists( $upload_dir['path'] .'/' . $filename ) ) {
			  $filename = $filetitle . '_' . $i . '.' . $filetype['ext'];
			  $i++;
			}
			$filedest = $upload_dir['path'] . '/' . $filename;

			/**
			 * Check write permissions
			 */
			if ( !is_writeable( $upload_dir['path'] ) ) {
			  wp_die( __( 'Unable to write to directory. Is this directory writable by the server?', 'gk-mass-users-upload' ));
			  return;
			}

			/**
			 * Save temporary file to uploads dir
			 */
			if ( !@move_uploaded_file($filetmp, $filedest) ){
			  wp_die( __( 'Error, the file', 'gk-mass-users-upload' ) . " $filetmp " . __( 'could not moved to', 'gk-mass-users-upload' ) . " : $filedest");
			  continue;
			}

			$attachment = array(
			  'post_mime_type' => $filetype['type'],
			  'post_title' => $filetitle,
			  'post_content' => '',
			  'post_status' => 'inherit'
			);

			$attach_id = wp_insert_attachment( $attachment, $filedest );
			require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
			$attach_data = wp_generate_attachment_metadata( $attach_id, $filedest );
			wp_update_attachment_metadata( $attach_id,  $attach_data );
			
			gk_import_users( $filedest, $form_data, $attach_id );
		  }
		}
	  }
  endif;
}


function gk_import_users( $file, $form_data, $attach_id = 0 ){?>
		<h2>Importing users</h2>	
		<?php
			set_time_limit(0);
			
			$imp_user_count = 0;

			global $wpdb;
			$wp_users_fields = array('user_login','user_nicename','user_email','display_name','first_name','last_name','user_registered','role');
			/* check for further details about fields https://codex.wordpress.org/Function_Reference/wp_update_user*/
			/*[0] => id [1] => user_nicename [2] => user_url [3] => display_name [4] => nickname [5] => first_name [6] => last_name [7] => description [8] => jabber [9] => aim [10] => yim [11] => user_registered [12] => password [13] => user_pass */

			$users_registered = array();
			$headers = array();
			$role = $form_data["role"];
			$update_roles_existing_users = $form_data["update_roles_existing_users"];
			$empty_cell_action = "leave";

			$activate_users_wp_members = "no_activate";

			$allow_multiple_accounts = "not_allowed";

			$approve_users_new_user_appove = "no_approve";
	
			$row = 0;

			ini_set('auto_detect_line_endings',TRUE);

			$delimiter = ',';//acui_detect_delimiter( $file );

			$manager = new SplFileObject( $file );
			while ( $data = $manager->fgetcsv( $delimiter ) ):
				if( empty($data[0]) )
					continue;

				if( count( $data ) == 1 )
					$data = $data[0];
				
				foreach ($data as $key => $value){
					$data[ $key ] = trim( $value );
				}

				for($i = 0; $i < count($data); $i++){
					$data[$i] = gk_string_conversion( $data[$i] );
					$data[$i] = maybe_unserialize( $data[$i] );
				}
				
				if($row == 0):
					// check min columns username - email
					if(count( $data ) < 12){
						echo "<div id='message' class='error'>" . __( 'Wrong file format', 'gk-mass-users-upload' ) . "</div>";
						break;
					}
					if ($data[0] != 'username'){
						echo "<div id='message' class='error'>" . __( 'Wrong file format', 'gk-mass-users-upload' ) . "</div>";
						break;						
					}
					
					foreach($data as $element){
						$headers[] = $element;
					}

					$columns = count( $data );

					
					/*
					
					echo "<h3>"; _e( 'Inserting and updating data', 'gk-mass-users-upload' ); echo "</h3>";
					
					echo "<table>
						<tr><th><?php _e( 'Row', 'gk-mass-users-upload' ); ?></th>";
						foreach( $headers as $element ) echo "<th>" . $element . "</th>"; 
					echo "</tr>";
					*/
					$row++;
				else:
					if( count( $data ) != $columns ): // if number of columns is not the same that columns in header
						echo '<script>alert("' . __( 'Row number', 'gk-mass-users-upload' ) . " $row " . __( 'does not have the same columns than the header, we are going to skip', 'gk-mass-users-upload') . '");</script>';
						continue;
					endif;

					$username = $data[0];
					$email = $data[1];
					$access_permissions = explode("-",$data[10]);
					$new_roles = explode("-",$data[11]);
					$user_id = 0;
					$problematic_row = false;
					$created = true;
					$password = wp_generate_password();

					
					if( username_exists( $username ) ){ // if user exists, we take his ID by login, we will update his mail if it has changed
						
							continue;
						
					}
					elseif( email_exists( $email ) && $allow_multiple_accounts == "not_allowed" ){ // if the email is registered, we take the user from this and we don't allow repeated emails
						
							continue;
					}
					else{
						$user_id = wp_create_user( $username, $password, $email );
					}
						
					if( is_wp_error( $user_id ) ){ // in case the user is generating errors after this check
						$error_string = $user_id->get_error_message();
						echo '<script>alert("' . __( 'Problems with user:', 'gk-mass-users-upload' ) . $username . __( ', we are going to skip. \r\nError: ', 'gk-mass-users-upload') . $error_string . '");</script>';
						continue;
					}

					$users_registered[] = $user_id;
					$user_object = new WP_User( $user_id );

					if( $created || $update_roles_existing_users == 'yes'){
						// if(!( in_array("administrator", gk_get_roles($user_id), FALSE) || is_multisite() && is_super_admin( $user_id ) )){
							
							
							$existing_roles = $user_object->roles;
							foreach ( $existing_roles as $existing_role ) {
								$user_object->remove_role( $existing_role );
							}
							
							
							if( !empty( $new_roles ) ){
								if( is_array( $new_roles ) ){
									foreach ($new_roles as $single_role) {
										$user_object->add_role( trim($single_role) );
									}	
								}
								else{
									$user_object->add_role( trim($new_roles) );
								}
							}
						// }
					}

					// WP Members activation
					if( $activate_users_wp_members == "activate" )
						update_user_meta( $user_id, "active", true );

					// New User Approve
					if( $approve_users_new_user_appove == "approve" )
						update_user_meta( $user_id, "approved", true );
					else
						update_user_meta( $user_id, "pending", true );
						
					if( $columns > 2 ){
						for( $i=2 ; $i<$columns-1; $i++ )://skipping last column as roles have been assigned by now
							
							//this is the column of the access_permissions, needs special handling
							if ($i == 10){
								if( !empty( $access_permissions ) ){
									if( is_array( $access_permissions ) ){
										foreach ($access_permissions as $acc_per) {
											update_user_meta( $user_id, trim($acc_per), true );
										}	
									}
									else{
										update_user_meta( $user_id, trim($access_permissions), true );
									}
								}
							}
							
							if( !empty( $data ) ){
								if( in_array( $headers[ $i ], $wp_users_fields ) ){ // wp_user data									
									if( empty( $data[ $i ] ) && $empty_cell_action == "leave" )
										continue;
									else
										wp_update_user( array( 'ID' => $user_id, $headers[ $i ] => $data[ $i ] ) );									
								}
								else{ // wp_usermeta data
									
									if( $data[ $i ] === '' ){
										if( $empty_cell_action == "delete" )
											delete_user_meta( $user_id, $headers[ $i ] );
										else
											continue;	
									}
									else
										update_user_meta( $user_id, $headers[ $i ], $data[ $i ] );
								}

							}
						endfor;
					}

					/*
					$styles = "";
					if( $problematic_row )
						$styles = "background-color:red; color:white;";
					
					echo "<tr style='$styles' ><td>" . ($row - 1) . "</td>";
					foreach ($data as $element)
						echo "<td>$element</td>";

					echo "</tr>\n";

					flush();
					
					*/

					if( $created ){
						//increase count of created users
						$imp_user_count++;
					}
						
				endif;

				$row++;						
			endwhile;

			if( $attach_id != 0 )
				wp_delete_attachment( $attach_id );

			/*echo "
			</table>
			<br/>";*/
			?>
			<p><?php _e( 'Import process finished.<br \> '.$imp_user_count.' user(s) have been successfully imported!<br \> You can check your new users by clicking ', 'gk-mass-users-upload' ); ?> <a href="<?php echo get_admin_url() . 'users.php'; ?>"><?php _e( 'here to see results', 'gk-mass-users-upload' ); ?></a></p>
			<?php
			ini_set('auto_detect_line_endings',FALSE);
		?>
<?php
}

?>