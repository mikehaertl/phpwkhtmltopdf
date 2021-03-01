<?php
/*
// Some travis environments use phpunit > 6
$newClass = '\PHPUnit\Framework\TestCase';
$oldClass = '\PHPUnit_Framework_TestCase';
if (!class_exists($newClass) && class_exists($oldClass)) {
    class_alias($oldClass, $newClass);
}
 */

/**
 * Utility method to check the version of PHPUnit.
 *
 * Example: phpUnitVersion('<', '8.3'); // true e.g. for 8.2.1
 *
 * @param string $operator an operator like '>', '<', etc.
 * @param string $version the version to check against
 * @return bool whether PHPUnit matches the version to check
 */
function phpUnitVersion($operator, $version)
{
    $phpUnitVersion = class_exists('\PHPUnit\Runner\Version') ?
        call_user_func(array('\PHPUnit\Runner\Version', 'id')) :
        call_user_func(array('\PHPUnit_Runner_Version', 'id'));
    return version_compare($phpUnitVersion, $version, $operator);
}

require __DIR__ . '/../vendor/autoload.php';

