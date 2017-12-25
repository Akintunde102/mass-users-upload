=== Plugin Name ===

Plugin Name: Mass Users Upload
Contributors: mcfarhat
Tags: wordpress, bulk, user, upload
Requires at least: 4.3
Tested up to: 4.9
Stable tag: trunk
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

=== Short Summary ===

Mass Users Upload is a wordpress plugin that allows bulk creation of multiple users using a mass import standard CSV file template.

== Description ==

The plugin aims at adding support from within wordpress, to perform a bulk user upload and creation through using a pre-defined CSV file.

Per our client's request, we added the relevant menu under the Users left menu tab for ease of access and compatibility with user standard control access.

In order to utilize the plugin, you need to initially prepare your data. You have access from within the plugin for the standard format needed (link provided on the main plugin screen, but also in additional data below). You just need to create a copy this file, and fill in your data as single row entries. Standard format includes:
username,email,first_name,last_name,title,business_number,mobile_number,job_title,company,department,access_permissions,roles

Post preparation, you can now head to the plugin's screen again through Users -> Mass Users Upload. Screen there is minimalistic, and provides the simple upload button.
Once file has been uploaded, you would just need to click on the "Start Importing!" button for the plugin to initiate the process.

In case an entry is a duplicate, it will be skipped. Upon completion, the plugin will notify of completion, and provide a link to access the full list of users.

If you would like some custom work done, or have an idea for a plugin you're ready to fund, check our site at www.greateck.com or contact us at info@greateck.com

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/gk-mass-users-upload` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to the Users menu, under it you will find a new menu called "Mass Users Upload"
4. Clicking on it will take you to the upload screen.
5. That's it! Now you can perform the upload as you please.

== Screenshots ==
1. Screenshot showing the Mass Users Upload link in the Left Menu tab under Users menu <a href="https://www.dropbox.com/s/nsgx5cxohgjgq6e/Mass-user-upload-menu.png?dl=0">https://www.dropbox.com/s/nsgx5cxohgjgq6e/Mass-user-upload-menu.png?dl=0</a>
2. Screenshot showing the Core Functionality Screen <a href="https://www.dropbox.com/s/ufitm2avd6fdfm8/Main-Screen.png?dl=0">https://www.dropbox.com/s/ufitm2avd6fdfm8/Main-Screen.png?dl=0</a>

== Additional Data ==
1. CSV file showing the standard format to be used for data formatting the CSV bulk upload file <a href="https://www.dropbox.com/s/ee4p4icehuwf015/sample_mass_users.csv?dl=0">https://www.dropbox.com/s/ee4p4icehuwf015/sample_mass_users.csv?dl=0</a>

== Changelog ==

= 0.1.3 =
Initial Version
