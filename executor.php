<?php

/**
 * PHP Wonsole (web console)
 * Copyright (c) 2009 Apprikos (http://apprikos.se)
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/mit-license.php
 */

define('SCRIPT_FILENAME', 'phpw-tempfile.php');

require 'TVarDumper.php';

// Show exception message
function phpwExceptionHandler($exception)
{
    echo "<br><b>Uncaught exception:</b> ".$exception->getMessage()."<br>";
}

// Show error message
function phpwErrorHandler($errno, $errstr, $errfile, $errline)
{
    // We use our own error handler simply to remove the "file" information from the messages.
    // This is not very pretty, I know.

    switch ($errno)
    {
        case E_WARNING:
        case E_USER_WARNING:
            echo "<br><b>Warning:</b> $errstr on line $errline<br>";
            break;

        case E_NOTICE:
        case E_USER_NOTICE:
            echo "<br><b>Notice:</b> $errstr on line $errline<br>";
            break;

        case E_STRICT:
            echo "<br><b>Strict Standards:</b> $errstr on line $errline<br>";
            break;

        case E_DEPRECATED:
        case E_USER_DEPRECATED:
            echo "<br><b>PHP Deprecated:</b> $errstr on line $errline<br>";
            break;

        case E_RECOVERABLE_ERROR:
            echo "<br><b>Recoverable Error:</b> $errstr on line $errline<br>";
            break;

        default:
            echo "<br><b>Error $errno:</b> $errstr on line $errline<br>";
            break;
    }

    return true;
}

// Removes some variables from a list of variables. These are default variables in PHP and
// some variables that are necessary for this script to run, but the user doesn't need to see them.
function phpwExcludeVars($vars)
{
    return array_diff_key($vars, array_fill_keys(array(
        '__script_output',
        '_FILES',
        '_COOKIE',
        '_GET',
        '_POST',
        '_REQUEST',
        '_SERVER',
        '_ENV',
        'GLOBALS',
        'HTTP_ENV_VARS',
        'HTTP_POST_VARS',
        'HTTP_GET_VARS',
        'HTTP_COOKIE_VARS',
        'HTTP_SERVER_VARS',
        'HTTP_POST_FILES',
    ), null));
}

// Call print_r() for each variable to print its contents
function phpwPrintREach($vars)
{
    $str = array();
    foreach($vars as $key => $val)
    {
        $str[] = "<b>$key:</b> ".TVarDumper::dump($val);
    }

    return join("\n", $str);
}

// Make sure we catch and show all errors to the user
set_exception_handler('phpwExceptionHandler');
set_error_handler('phpwErrorHandler');
ini_set('display_errors', true);
ini_set('track_errors', false);
error_reporting(E_ALL | E_STRICT);

header('Content-Type: text/html; charset=UTF-8');

$code = '';
if(isset($_POST['code']))
{
    $code = $_POST['code'];
    unset($_POST['code']);
}

// If "magic quotes" are enabled for _GET, _POST and _COOKIE variables,
// quotes in the source code will be escaped, which will ruin it. (Magic
// Quotes can be enabled or disabled in php.ini.)
if(get_magic_quotes_gpc())
    $code = stripslashes($code);

// Open temporary script file and write code
$fh = fopen(SCRIPT_FILENAME, 'w+');
fwrite($fh, "<?php ");
fwrite($fh, $code);
fwrite($fh, " ?>");
fclose($fh);

// Unset these variables, so that they won't be shown to the user
unset($fh);
unset($code);

// Execute temporary script file
ob_start();
require(SCRIPT_FILENAME);
$__script_output = ob_get_contents();
ob_end_clean();

// Return output and a variable dump in JSON
echo json_encode(array(
    'output' => $__script_output,
    'vars' => phpwPrintREach(phpwExcludeVars(get_defined_vars()))
));

// Delete temporary script file
@unlink(realpath(SCRIPT_FILENAME));

?>
