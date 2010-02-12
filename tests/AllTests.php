<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * PHPUnit 3.2 AllTests suite for the Net_OpenSSH package.
 *
 * These tests require the PHPUnit 3.2 package to be installed. PHPUnit is
 * installable using PEAR. See the
 * {@link http://www.phpunit.de/pocket_guide/3.2/en/installation.html manual}
 * for detailed installation instructions.
 *
 * This test suite follows the PEAR AllTests conventions as documented at
 * {@link http://cvs.php.net/viewvc.cgi/pear/AllTests.php?view=markup}.
 *
 * LICENSE:
 *
 * This library is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation; either version 2.1 of the
 * License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @category  Network
 * @package   Net_OpenSSH
 * @author    Luca Corbo <lucor@php.net>
 * @copyright 2009 Luca Corbo
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link      http://pear.php.net/package/Net_OpenSSH
 */


if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'AllTests::main');
}

require_once 'PHPUnit/TextUI/TestRunner.php';

require_once 'OpenSSHTest.php';
require_once 'LibSSH2Test.php';

/**
 * AllTests suite testing Net_OpenSSH
 *
 * @category  Network
 * @package   Net_OpenSSH
 * @author    Luca Corbo <lucor@php.net>
 * @copyright 2009 Luca Corbo
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link      http://pear.php.net/package/Net_OpenSSH
 */
class AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Net_SSH2 Tests');
        
        $suite->addTestSuite('OpenSSHTest');
        $suite->addTestSuite('LibSSH2Test');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
  AllTests::main();
}

?>
