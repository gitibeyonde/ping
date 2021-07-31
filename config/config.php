<?php

/**
 * VIEWS
 */

define("MAIN_VIEW", "main_view");
define("DEVICE_CONFIG", "config");
define("DEVICE_VIEW", "device_view");
define("DEVICE_SETTING", "device_setting");
define("TEMP_VIEW", "temp_view");
define("MOTION_VIEW", "temp_view");
define("HISTORY_VIEW", "history_view");
define("PLAY_HISTORY", "play_history");
define("FOR_IMAGE", "for_image");
define("FOR_IMAGE_HEADER", "for_image_header");
define("LIVE_DASH", "live_dash");
define("MOTION_DASH", "motion_dash");
define("USAGE_DASH", "usage_dash");
define("USAGE_DETAILS", "usage_details");
define("ALERT_DASH", "alert_dash");
define("GRID_CONFIG", "grid_config");
define("ANALYTICS", "analytics");
define("EVENTS_VIEW", "events_view");
define("LIVE_VIEW", "live_view");
define("LIVE_SNAP", "live_snap");
define("REFRESH_VIEW", "refresh_view");
define("DEVICE_CONFIG_VIEW", "device_config_view");
define("SIP_VIEW", "sip_view");
define("PROFILE_VIEW", "profile_view");
define("LOGOUT_VIEW", "logout_view");

define("ADMIN_MAIN_VIEW", "admin_main_view");
/**
 * Configuration for: Database Connection
 * This is the place where your database login constants are saved
 *
 * For more info about constants please @see http://php.net/manual/en/function.define.php
 * If you want to know why we use "define" instead of "const" @see http://stackoverflow.com/q/2447791/1114320
 *
 * DB_HOST: database host, usually it's "127.0.0.1" or "localhost", some servers also need port info
 * DB_NAME: name of the database. please note: database and database table are not the same thing
 * DB_USER: user for your database. the user needs to have rights for SELECT, UPDATE, DELETE and INSERT.
 *          by the way, it's bad style to use "root", but for development it will work.
 * DB_PASS: the password of the above user
 */
define("DB_HOST", "mysql.ibeyonde.com");
define("DB_NAME", "ibe");
define("DB_USER", "admin");
define("DB_PASS", "1b6y0nd6");


/**
 * SIP
 */
define("SIP_USER", "admin");
define("SIP_PASS", "password");
define("SIP_HOST", "sip.ibeyonde.com");
define("SIP_PORT", "5080");

/**
 * Configuration for: Cookies
 * Please note: The COOKIE_DOMAIN needs the domain where your app is,
 * in a format like this: .mydomain.com
 * Note the . in front of the domain. No www, no http, no slash here!
 * For local development .127.0.0.1 or .localhost is fine, but when deploying you should
 * change this to your real domain, like '.mydomain.com' ! The leading dot makes the cookie available for
 * sub-domains too.
 * @see http://stackoverflow.com/q/9618217/1114320
 * @see http://www.php.net/manual/en/function.setcookie.php
 *
 * COOKIE_RUNTIME: How long should a cookie be valid ? 1209600 seconds = 2 weeks
 * COOKIE_DOMAIN: The domain where the cookie is valid for, like '.mydomain.com'
 * COOKIE_SECRET_KEY: Put a random value here to make your app more secure. When changed, all cookies are reset.
 */
define("COOKIE_RUNTIME", 1209600);
define("COOKIE_DOMAIN", ".app.ibeyonde.com");
define("COOKIE_SECRET_KEY", "1gp@TMPS{+$78sfpMJFe-92s");

/**
 * Configuration for: S3
 */
define("S3_VERSION", "latest");
define("S3_KEY", "AKIAIDLKQ2PEKIKAVKBA");
define("S3_SECRET", "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");
define("S3_REGION", "us-west-2");

/**
 * Configuration for: Email server credentials
 */
define("SES_VERSION", "latest");
define("SES_KEY", "AKIAJRYQJ3FXMKJYU4CQ");
define("SES_SECRET", "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");
define("SES_REGION", "us-west-2");

/**
 * Configuration for: password reset email data
 * Set the absolute URL to password_reset.php, necessary for email password reset links
 */
define("EMAIL_PASSWORDRESET_URL", "http://app.ibeyonde.com/password_reset.php");
define("EMAIL_PASSWORDRESET_FROM", "no-reply@ibeyonde.com");
define("EMAIL_PASSWORDRESET_FROM_NAME", "IbeyondE Devices On Cloud");
define("EMAIL_PASSWORDRESET_SUBJECT", "Password reset for IbeyondeE Cloud Device Access");
define("EMAIL_PASSWORDRESET_CONTENT", "Please click on this link to reset your password:");

/**
 * Configuration for: verification email data
 * Set the absolute URL to register.php, necessary for email verification links
 */
define("EMAIL_VERIFICATION_URL", "http://app.ibeyonde.com/register.php");
define("EMAIL_VERIFICATION_FROM", "no-reply@ibeyonde.com");
define("EMAIL_VERIFICATION_FROM_NAME", "IbeyondE Devices On Cloud");
define("EMAIL_VERIFICATION_SUBJECT", "Account activation for IbeyondeE Cloud Device Access");
define("EMAIL_VERIFICATION_CONTENT", "Please click on this link to activate your account: ");

/**
 * Configuration for: Hashing strength
 * This is the place where you define the strength of your password hashing/salting
 *
 * To make password encryption very safe and future-proof, the PHP 5.5 hashing/salting functions
 * come with a clever so called COST FACTOR. This number defines the base-2 logarithm of the rounds of hashing,
 * something like 2^12 if your cost factor is 12. By the way, 2^12 would be 4096 rounds of hashing, doubling the
 * round with each increase of the cost factor and therefore doubling the CPU power it needs.
 * Currently, in 2013, the developers of this functions have chosen a cost factor of 10, which fits most standard
 * server setups. When time goes by and server power becomes much more powerful, it might be useful to increase
 * the cost factor, to make the password hashing one step more secure. Have a look here
 * (@see https://github.com/panique/php-login/wiki/Which-hashing-&-salting-algorithm-should-be-used-%3F)
 * in the BLOWFISH benchmark table to get an idea how this factor behaves. For most people this is irrelevant,
 * but after some years this might be very very useful to keep the encryption of your database up to date.
 *
 * Remember: Every time a user registers or tries to log in (!) this calculation will be done.
 * Don't change this if you don't know what you do.
 *
 * To get more information about the best cost factor please have a look here
 * @see http://stackoverflow.com/q/4443476/1114320
 *
 * This constant will be used in the login and the registration class.
 */
define("HASH_COST_FACTOR", "10");
