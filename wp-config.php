<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'teakadai');

/** MySQL database username */
define('DB_USER', 'teakadai');

/** MySQL database password */
define('DB_PASSWORD', 'roomq');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '| .BU`WY&f ER#&6}N:xjv##q2nmPiJ:_kTmwt;I?d/+cA~,/inBPmX5,;|*nbz@');
define('SECURE_AUTH_KEY',  '|Ne/kZ]CxoRQ2f[@?;g1q;8g[E)S-o&]ac^zJH1e~NF_`l*fGbQw|$9=4H;Vp:#)');
define('LOGGED_IN_KEY',    'l}4|H_5`P-emk{k Zp,*$3vM;4{xg3PDIuA;|Ts)Ih~DtJnLp+FMmsPps-##|e5a');
define('NONCE_KEY',        '{`3ZHA3,p|QI?Pn IJtX&(E/rh3JrX3lR?Iofnb-e`3|-j+sFmlk>#;X9p9tL1x6');
define('AUTH_SALT',        'fWm+m-JRakH3nxhVzRU{#C>DtR$+[ni%=Fi%>Y7IJ;77+5UQ_{BG[b7a)RM.WGx6');
define('SECURE_AUTH_SALT', 'aMIn9,Yu.+Fr+Htahz.-?e9]oy(t|X-o!@!zy^+JDO-Z9i/Z%Jm-y-{Jh9l*_-kq');
define('LOGGED_IN_SALT',   '*-C>Xo;z4+MX)Za-^FGI]=^((3e&u`b0PQyJj`I4g9m!uasq1zG-;IFHU3v*tk?]');
define('NONCE_SALT',       'y_A1X*H!BKn%D|kJG]Rs>Z61Bnq${ iLTeT*/f* U:/BiM@+1x+3r*h7;TR=C&pZ');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
