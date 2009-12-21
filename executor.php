<?php

/**
 * PHP Wonsole (web console)
 * Copyright (c) 2009 Christian Davén (http://www.daven.se)
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/mit-license.php
 */

if(!isset($_POST['code']))
    $_POST['code'] = '';

define('SCRIPT_FILENAME', 'phpw-tempfile.php');

function phpwExceptionHandler($exception)
{
    echo '<p>Uncaught exception: '.$exception->getMessage().'</p>';
}

function phpwErrorHandler($errno, $errstr, $errfile, $errline)
{
    switch ($errno)
    {
        case E_USER_ERROR:
            echo "Fatal error on line $errline\n\n";
            exit(1);
            break;

        case E_USER_WARNING:
            echo "WARNING: [$errno] $errstr on line $errline\n\n";
            break;

        case E_USER_NOTICE:
            echo "NOTICE: [$errno] $errstr  on line $errline\n\n";
            break;

        default:
            echo "$errstr on line $errline\n\n";
            break;
    }

    /* Don't execute PHP internal error handler */
    return true;
}

function phpwExcludeVars($vars)
{
    return array_diff_key($vars, array_fill_keys(array('_FILES', '_COOKIE', '_GET', '_POST', '_REQUEST', '_SERVER', '_ENV', 'GLOBALS', '__script_output'), null));
}

function phpwPrintREach($vars)
{
    $str = array();
    foreach($vars as $key => $val)
        $str[] = "<b>$key:</b> ".print_r($val, true);

    return join("\n", $str);
}

header('Content-Type: text/html; charset=UTF-8');

set_exception_handler('phpwExceptionHandler');
set_error_handler('phpwErrorHandler');

$fh = fopen(SCRIPT_FILENAME, 'w+');
fwrite($fh, "<?php ");
fwrite($fh, $_POST['code']);
fwrite($fh, "\n?>");
fclose($fh);

unset($fh);
unset($_POST['code']);

ob_start();
require(SCRIPT_FILENAME);
$__script_output = ob_get_contents();
ob_end_clean();

echo json_encode(array(
    'output' => $__script_output,
    'vars' => phpwPrintREach(phpwExcludeVars(get_defined_vars()))
));

@unlink(realpath(SCRIPT_FILENAME));

?>
