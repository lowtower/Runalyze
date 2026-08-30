<?php

declare(strict_types=1);

/*
 * All definitions of CONST variables have to be done here
 * otherwise the Autoloader will not work correct
 */

/**
 * Maximum value for integers
 */
define('INFINITY', PHP_INT_MAX);

/**
 * Number of seconds for a complete day
 */
define('DAY_IN_S', 86400);

/**
 * Current year
 */
define('YEAR', date('Y'));

/**
 * Default length for cutting strings
 */
define('CUT_LENGTH', 29);

/**
 * New line for echoing text
 */
define('NL', PHP_EOL);

/**
 * No break space
 */
define('NBSP', '&nbsp;');

/**
 * HTML-tag: break
 */
define('BR', '<br>');
