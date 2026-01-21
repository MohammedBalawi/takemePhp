<?php
define( 'WP_CACHE', true );
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'awfrappzzz_wp598' );

/** Database username */
define( 'DB_USER', 'awfrappzzz_wp598' );

/** Database password */
define( 'DB_PASSWORD', 'SCL-4.099p' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'rgebrvximvjqrs2fl2v14slbtq8lrg6bhhew2lnbngxbj378dzjnl0hoh18sfytc' );
define( 'SECURE_AUTH_KEY',  'fav3gnbydkmcgh8b7uhzmh1czfnexauxdikw2cldxyz81xmldegudbwsx1dgeceq' );
define( 'LOGGED_IN_KEY',    '0md276y6y3pkuzlikeljvirtngcowrkfdystkcrcyltqkw42wqjslnoyoopa2f6t' );
define( 'NONCE_KEY',        'dwbo6xgft3qzgafbz1z4swldo84vja1qlanwm5kifn9rkywdwmv7p1nr5n3evzyi' );
define( 'AUTH_SALT',        'nuy3nqmfynd2boq1wl6vzz87pxpe0xpeivy8ns8gszaza9uzl74tnvl8qmfj2ikp' );
define( 'SECURE_AUTH_SALT', 'glj6jchdpleoskqcgnu1vxfqsjethuyiq8x50w4rjr0xhy46h4bsfm1sctzlhbxj' );
define( 'LOGGED_IN_SALT',   'vxkw1fki1uk1ugtp2bq0z7qaqv8ki3sqr2e0hjjt2yjkcytartnqo3zueimyhvb2' );
define( 'NONCE_SALT',       '77ungnxzix2zmlerw61qgnkqpoin026pxxge2ldqw6ajrmra2a4peivjb4qdy0fd' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wpu9_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
