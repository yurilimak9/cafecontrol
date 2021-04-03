<?php

/**
 * DATABASE
 */
define("CONF_DB_HOST", "localhost");
define("CONF_DB_USER", "root");
define("CONF_DB_PASS", "");
define("CONF_DB_NAME", "cafecontrol");


/**
 * PROJECT URLs
 */
define("CONF_URL_BASE", "https://cafecontrol.com.br");
define("CONF_URL_TEST", "https://www.localhost/cafecontrol");
define("CONF_URL_ADMIN", "/admin");


/**
 * SITE
 */
define("CONF_SITE_NAME", "CaféControl");
define("CONF_SITE_TITLE", "Gerencie suas contas com o melhor café");
define("CONF_SITE_DESC", "O CafeControl é um gerenciador de contas simples, poderoso e gratuito. O prazer de tomar um café e ter o controle total de suas contas.");
define("CONF_SITE_LANG", "pt_BR");
define("CONF_SITE_DOMAIN", "cafecontrol.com.br");

define("CONF_SITE_ADDR_STREET", "NOME DA RUA");
define("CONF_SITE_ADDR_NUMBER", "0000");
define("CONF_SITE_ADDR_COMPLEMENT", "COMPLEMENTO");
define("CONF_SITE_ADDR_CITY", "NOME CIDADE");
define("CONF_SITE_ADDR_STATE", "UR");
define("CONF_SITE_ADDR_ZIPCODE", "CEP");


/**
 * SOCIAL
 */
define("CONF_SOCIAL_TWITTER_CREATOR", "@yurilimak9");
define("CONF_SOCIAL_TWITTER_PUBLISHER", "@yurilimak9");
define("CONF_SOCIAL_FACEBOOK_APP", "000000000");
define("CONF_SOCIAL_FACEBOOK_PAGE", "--------");
define("CONF_SOCIAL_FACEBOOK_AUTHOR", "yurilimak9");
define("CONF_SOCIAL_GOOGLE_PAGE", "000000000");
define("CONF_SOCIAL_GOOGLE_AUTHOR", "000000000");
define("CONF_SOCIAL_INSTAGRAM_PAGE", "---------");
define("CONF_SOCIAL_YOUTUBE_PAGE", "--------");


/**
 * DATES
 */
define("CONF_DATE_BR", "d/m/Y H:i:s");
define("CONF_DATE_APP", "Y-m-d H:i:s");


/**
 * PASSWORD
 */
define("CONF_PASSWD_MIN_LEN", 8);
define("CONF_PASSWD_MAX_LEN", 40);
define("CONF_PASSWD_ALGO", PASSWORD_DEFAULT);
define("CONF_PASSWD_OPTION", ["cost" => 10]);


/**
 * MESSAGE
 */
define("CONF_MESSAGE_CLASS", "message");
define("CONF_MESSAGE_INFO", "info icon-info");
define("CONF_MESSAGE_SUCCESS", "success icon-check-square-o");
define("CONF_MESSAGE_WARNING", "warning icon-warning");
define("CONF_MESSAGE_ERROR", "error icon-warning");


/**
 * VIEW
 */
define("CONF_VIEW_PATH", __DIR__ . "/../../shared/views");
define("CONF_VIEW_EXT", "php");
define("CONF_VIEW_THEME", "cafeweb");
define("CONF_VIEW_APP", "cafeapp");


/**
 * UPLOAD
 */
define("CONF_UPLOAD_DIR", "storage");
define("CONF_UPLOAD_IMAGE_DIR", "images");
define("CONF_UPLOAD_FILE_DIR", "files");
define("CONF_UPLOAD_MEDIA_DIR", "medias");


/**
 * IMAGES
 */
define("CONF_IMAGE_CACHE", CONF_UPLOAD_DIR . "/" . CONF_UPLOAD_IMAGE_DIR . "/cache");
define("CONF_IMAGE_SIZE", 2000);
define("CONF_IMAGE_QUALITY", ["jpg" => 75, "png" => 5]);


/**
 * MAIL
 */
define("CONF_MAIL_HOST", "smtp-relay.sendinblue.com");
define("CONF_MAIL_PORT", "587");
define("CONF_MAIL_USER", "yurigoncalveslima33@gmail.com");
define("CONF_MAIL_SUPPORT", "yurigoncalveslima33@yahoo.com");
define("CONF_MAIL_PASS", "xsmtpsib-19bf9e8089094b21c029f90491109f1f7ee9fa65e7873360f71c38fe91ba550e-5Lt6JBMyCsAE4UYN");
define("CONF_MAIL_SENDER", ["name" => "Yuri Gonçalves", "address" => "yurigoncalveslima33@gmail.com"]);

define("CONF_MAIL_OPTION_LANG", "br");
define("CONF_MAIL_OPTION_HTML", true);
define("CONF_MAIL_OPTION_AUTH", true);
define("CONF_MAIL_OPTION_SECURE", "tls");
define("CONF_MAIL_OPTION_CHARSET", "utf-8");