<?php if (! defined('ABSPATH')) exit; // Exit if accessed directly

$app = 'locatoraid-rest';

$current = array();
$current['enabled'] = get_option($app . '_enabled', 1);
$current['auth_code'] = get_option($app . '_auth_code', '');

if (isset($_POST[$app . '_submit'])) {
    if (isset($_POST[$app])) {
        check_admin_referer($app . '-options', '_wpnonce');

        $props = array('enabled', 'auth_code');
        foreach ($props as $k) {
            $value = null;

            if ('enabled' === $k) {
                $value = isset($_POST[$app][$k]) && $_POST[$app][$k] ? 1 : 0;
            } else {
                if (isset($_POST[$app][$k])) {
                    $value = sanitize_text_field($_POST[$app][$k]);
                }
            }

            if (null !== $value && $current[$k] !== $value) {
                $option_name = $app . '_' . $k;
                update_option($option_name, $value);
                $current[$k] = $value;
            }
        }
	}
}

// $this->initOption();


// spaghetti starts here
?>

<?php if( isset($_POST[$app . '_submit']) ) : ?>
	<div id="message" class="updated fade">
		<p>
			<?php _e( 'Settings Saved', 'locatoraid' ) ?>
		</p>
	</div>
<?php endif; ?>

<form method="post" action="">
	<?php settings_fields( $app ); ?>
	<?php //do_settings_sections( $this->app ); ?>
	<table class="form-table">
		<tr valign="top">
		<th scope="row">Enable</th>
		<td>
			<?php $checked = $current['enabled'] ? ' checked' : ''; ?>
			<input type="checkbox" name="<?php echo $app; ?>[enabled]" value="1" <?php echo $checked; ?>/>
		</td>
		</tr>

	<?php if( $current['enabled'] ) : ?>
		<tr valign="top">
		<th scope="row">X-WP-Locatoraid-AuthCode</th>
		<td>
			<input type="text" name="<?php echo $app; ?>[auth_code]" value="<?php echo esc_attr( $current['auth_code'] ); ?>" />

			<div>
			For create, update and delete operations.
			</div>
		</td>
		</tr>
	<?php endif; ?>

		<tr valign="top">
			<th scope="row" style="padding-top: 0; padding-bottom: 0;">&nbsp;</th>
			<td style="text-align: left; padding-top: 0; padding-bottom: 0;">
				<input name="<?php echo $app; ?>_submit" type="submit" class="button-primary" value="Save" />
			<td>
		</tr>

	</table>
</form>

<?php if( ! $current['enabled'] ) return; ?>

<h3 class="hc-underline">List locations</h3>
<?php
$url = '/locatoraid/v3/locations';
$fullUrl = get_rest_url( NULL, $url );
?>

<p>
<strong>
GET <?php echo $url; ?>
</strong>
</p>

<p>
<strong>Arguments</strong>
</p>

<p>
page<br/>
per_page<br/>
search<br/>
</p>

<p>
<strong>Examples</strong>
</p>

<p>
<code>
GET <?php echo $fullUrl; ?>
</code>
</p>

<p>
<code>
GET <?php echo $fullUrl; ?>?page=2&per_page=100
</code>
</p>

<p>
<code>
GET <?php echo $fullUrl; ?>?search=helsinki
</code>
</p>

<h3 class="hc-underline">Retrieve a location</h3>
<?php
$url = '/locatoraid/v3/locations/&lt;id&gt;';
$fullUrl = get_rest_url( NULL, $url );
?>

<p>
<strong>
GET <?php echo $url; ?>
</strong>
</p>

<p>
<strong>Examples</strong>
</p>

<p>
<code>
GET <?php echo $fullUrl; ?>
</code>
</p>

<h3 class="hc-underline">Delete a location</h3>
<?php
$url = '/locatoraid/v3/locations/&lt;id&gt;';
$fullUrl = get_rest_url( NULL, $url );
?>

<p>
<strong>
DELETE <?php echo $url; ?>
</strong>
</p>

<p>
<strong>Headers</strong>
</p>

<p>
X-WP-Locatoraid-AuthCode [required]
</p>

<p>
<strong>Examples</strong>
</p>

<p>
You can also supply the list of ids separated by hyphen (-).
</p>

<?php
$url1 = '/locatoraid/v3/locations/123';
$fullUrl1 = get_rest_url( NULL, $url1 );
?>
<p>
<code>
DELETE <?php echo $fullUrl1; ?>
</code>
</p>

<?php
$url2 = '/locatoraid/v3/locations/123-234-345';
$fullUrl2 = get_rest_url( NULL, $url2 );
?>
<p>
<code>
DELETE <?php echo $fullUrl2; ?>
</code>
</p>

<h3 class="hc-underline">Create a location</h3>
<?php
$url = '/locatoraid/v3/locations';
$fullUrl = get_rest_url( NULL, $url );
?>

<p>
<strong>
POST <?php echo $url; ?>
</strong>
</p>

<p>
<strong>Headers</strong>
</p>

<p>
X-WP-Locatoraid-AuthCode [required]
</p>

<p>
<strong>Arguments</strong>
</p>

<?php
$p = $this->app->make('/locations/presenter');
$fields = $p->database_fields();
?>

<p>
<?php foreach( $fields as $f ) : ?>
	<?php echo $f; ?><br/>
<?php endforeach; ?>
</p>

<p>
<strong>Examples</strong>
</p>

<p>
<code>
POST <?php echo $fullUrl; ?>
</code>
</p>
