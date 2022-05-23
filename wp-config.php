<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'quebelleza2022' );

/** MySQL database username */
define( 'DB_USER', 'forge' );

/** MySQL database password */
define( 'DB_PASSWORD', 'bHioKupqxE7ndkroMpuk' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

define('WP_MEMORY_LIMIT', '512M');
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
define( 'AUTH_KEY',         ',[d28+*8qmS%l}>020OH;4m6Z!vP8)jKu,ld$kOd>J^|zU?jy4+*d_hktOaxl#VK' );
define( 'SECURE_AUTH_KEY',  'I?+H:dY/?VL]Q]$T[kENah@TZaJ0~]3-(7_bdPK]}& CQzO@YMEGg0{NJ|pURt^P' );
define( 'LOGGED_IN_KEY',    '9rYPXiZ3Dt9L37chf8?3!:0x!^Nl+4{CH}N,m)B%c|sox$YC4]Plhg0%5a@M%}nk' );
define( 'NONCE_KEY',        '51SW`~7[glCpE1%i9~xOHx.,twQVyq4}?Y:,Me3.~@~6vxt5C)GZPKs6tjU$r4vG' );
define( 'AUTH_SALT',        'JTc`(Y9k!<;zQaqQTNh8tDja.8=WMh$<BC-Gn*2;f3EZE}D$|M[B.J~@5T|Wpsrd' );
define( 'SECURE_AUTH_SALT', 'JAm41,/T=8;6b#OG D^*bkS[?$1E6u|tD|.^pz<JjM{h+,IZl?.1f sN!TB1[`xc' );
define( 'LOGGED_IN_SALT',   'X%HY#=Lx(BC7#{Xq+IIB*>Rk!<? (wz~nq@1PiZ833g9wt^(L8!IN$KQQ/37lV/w' );
define( 'NONCE_SALT',       's@ommknd}C7B]_ge=r7e)4!Rf/6.Qc[}B9A-E*KbF*~Y@/sCV%kP^O{;Wq{V81:=' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'qu3b3ll3z4_';

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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', true );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
