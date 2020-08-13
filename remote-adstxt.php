<?php
/**
 * Plugin Name:     Remote Ads.txt
 * Description:     Appends ads.txt file from a remote URL to the output from the 10up Ads.txt plugin installed locally
 * Author:          Sterner Stuff
 * Author URI:      https://sternerstuff.dev
 * Text Domain:     remote-adstxt
 * Domain Path:     /languages
 * Version:         1.0.1
 *
 * @package         Remote_Adstxt
 */

namespace RemoteAdstxt;

add_filter( 'ads_txt_content', __NAMESPACE__ . '\append_remote_adstxt_content' );

function append_remote_adstxt_content( $adstxt_content ) {

	$remote_adstxt = get_transient( 'remote_adstxt' );
	if( !$remote_adstxt ) {
		$remote_adstxt = fetch_remote_adstxt();
		set_transient( 'remote_adstxt', $remote_adstxt, 4 * HOUR_IN_SECONDS );
		do_action( 'remote_adstxt_updated' );
	}
	
	return $adstxt_content . PHP_EOL . $remote_adstxt;

}

add_action( 'admin_notices', __NAMESPACE__ . '\alert_remote_adstxt' );

function alert_remote_adstxt()
{
	if(get_current_screen()->id != 'settings_page_adstxt-settings') {
		return;
	}
	$url = get_remote_adstxt_url(); ?>
	<div class="notice notice-info is-dismissible">
        <p><?php echo 'A third-party ads.txt is being appended to yours from <a href="' . $url . '" target="_blank">' . $url . '</a>.'; ?></p>
    </div>
<?php }

/**
 * Get ads.txt content from remote URL
 * @return string The request body or, if there was an error, an empty string.
 */
function fetch_remote_adstxt() 
{
	$bucket_url = get_remote_adstxt_url();
	$response = wp_remote_get( $bucket_url );

	if ( is_array( $response ) && ! is_wp_error( $response ) ) {
		if( $response['response']['code'] == 200 ) {
			return $response['body'];
		}
		trigger_error( 'Remote ads.txt did not return a 200. Responded ' . $response['response']['code'] . ' ' . $response['body'], E_WARNING );
		return '';
	}

	trigger_error( $response->get_error_message(), E_WARNING );
	return '';
}

function get_remote_adstxt_url()
{
	return apply_filters( 'remote_adstxt_bucket_url', 'https://storage.googleapis.com/js-partners-prod/collider/ads.txt' );
}