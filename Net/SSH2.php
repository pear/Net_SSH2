<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Simple wrapper interface for the SSH2 utility tools.
 *
 * PHP version 5
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330,Boston,MA 02111-1307 USA
 *
 * @category  Net
 * @package   Net_SSH2
 * @author    Luca Corbo <lucor@ortro.net>
 * @copyright 2009 Luca Corbo
 * @license   GNU/LGPL v2.1
 * @link      http://pear.php.net/packages/Net_SSH2
 */

spl_autoload_register(array('Net_SSH2', 'autoload'));

/**
 * Simple wrapper interface for the SSH2 utility tools.
 *
 * @category  Net
 * @package   Net_SSH2
 * @author    Luca Corbo <lucor@ortro.net>
 * @copyright 2009 Luca Corbo
 * @license   GNU/LGPL v2.1
 * @link      http://pear.php.net/packages/Net_SSH2
 * @link      http://www.openssh.com/
 */
abstract class Net_SSH2
{

    /**
     * The method mapErrorCode in each SSH2 implementation maps
     * native codes to one of these.
     *
     * If you add a code here, make sure you also add a textual
     * version of it in Net_SSH2::getMessage().
     */
    const SSH2_OK = 0;
    const SSH2_ERROR = 1;
    const SSH2_PACKAGE_NOT_FOUND = 2;
    const SSH2_CLASS_NOT_FOUND = 3;
    const SSH2_OPTION_NOT_VALID = 4;
    const SSH2_BINARY_NOT_FOUND = 5;
    const SSH2_MODULE_NOT_FOUND = 6;
    const SSH2_PUBLIC_KEY_UNAVAILABLE = 7;
    const SSH2_UNSUPPORTED = 8;
    const SSH2_PTY_NOT_SUPPORTED = 9;

    /**
     * List of options managed by __set and __get methods
     *
     * @internal
     * @var array
     */
    protected $options = array();

    /**
     * Allowed SSH2 option arguments
     * List of options allowed in the implementation
     * for the specific driver
     *
     * @var array
     */
    protected $allowed_options = array();

    /**
     * Overloading of the __get method
     *
     * @param string $key The name of the variable that should be retrieved
     * 
     * @throws Net_SSH2_Exception If trying to get an undefined properties.
     * @return mixed The value of the object on success
     */
    public function __get($key)
    {
        if (!key_exists($key, $this->allowed_options)) {
            throw new Net_SSH2_Exception(
                Net_SSH2::getMessage(SSH2_OPTION_NOT_VALID, $key)
            );
        }
        if (isset($this->options[$key])) {
            return $this->options[$key];
        } else {
            //return the default value
            return $this->allowed_options[$key];
        }
    }

    /**
     * Overloading of the __set method
     *
     * @param string $key   The name of the properties that should be set
     * @param mixed  $value parameter specifies the value that the object 
     *                      should set the $key
     * 
     * @throws Net_SSH2_Exception If trying to set an undefined properties.
     * @return mixed True on success
     */
    public function __set($key, $value)
    {        
        if (!key_exists($key, $this->allowed_options)) {
            throw new Net_SSH2_Exception(
                Net_SSH2::getMessage(SSH2_OPTION_NOT_VALID, $key)
            );
        }
        $this->options[$key] = $value;
        return true;
    }

    /**
     * Class autoloading
     *
     * @param string $class_name The name of the class to load
     *
     */
    public static function autoload($class_name)
    {
        if (!class_exists($class_name)) {
            $class_file_path = str_replace('_', '/', $class_name) . '.php';
            require($class_file_path);
        }
    }
 
    /**
     * Attempts to return a concrete SSH2 instance of type $driver
     *
     * @param string $driver  The type of concrete SSH2 subclass to return.
     *                        Attempt to dynamically include the code for
     *                        this subclass.
     *
     * @param array  $options optional. An array of options used to create the
     *                        SSH2 object. All options must be optional and are
     *                        represented as key-value pairs.
     * 
     * @throws Net_SSH2_Exception If SSH2 package driver does not exist or
     *                               the class was not found.
     *
     * @return null
     */
    public static function factory($driver, $options = array())
    {
        $class = 'Net_SSH2_' . $driver;
        $obj   = new $class($options);
        return $obj;
    }

    /**
     * Abstract implementation of the sshExec() method.
     * Execute a command on a remote server
     * 
     * @param string $std_output The standard output of the executed command
     * @param string $std_error  The standard error of the executed command
     * @param array  $options    Additional options for the specified method
     *
     * @return mixed The exit code of the executed command or false on error
     */
    abstract public function sshExec(&$std_output, &$std_error, $options = array());

    /**
     * Abstract implementation of the sshCopyId() method.
     * Install your public key in a remote machine’s authorized_keys
     *
     * @param string $std_output The standard output of the executed command
     * @param string $std_error  The standard error of the executed command
     * @param array  $options    Additional options for the specified method
     *
     * @return mixed The exit code of the executed command or false on error
     */
    abstract public function sshCopyId(&$std_output, &$std_error, $options = array());

    /**
     * Abstract implementation of the sshKeyGen() method.
     * Generates authentication keys for ssh
     *
     * @param string $std_output The standard output of the executed command
     * @param string $std_error  The standard error of the executed command
     * @param array  $options    Additional options for the specified method
     *
     * @return mixed The exit code of the executed command or false on error
     */
    abstract public function sshKeyGen(&$std_output, &$std_error, $options = array());

    /**
     * Abstract implementation of the scpSend() method.
     * Send a file via SCP
     *
     * @param string $std_output The standard output of the executed command
     * @param string $std_error  The standard error of the executed command
     * @param array  $options    Additional options for the specified method
     *
     * @return mixed The exit code of the executed command or false on error
     */
    abstract public function scpSend(&$std_output, &$std_error, $options = array());

    /**
     * Abstract implementation of the scpReceive() method.
     * Request a file via SCP
     *
     * @param string $std_output The standard output of the executed command
     * @param string $std_error  The standard error of the executed command
     * @param array  $options    Additional options for the specified method
     *
     * @return mixed The exit code of the executed command or false on error
     */
    abstract public function scpReceive(&$std_output, &$std_error, $options = array());
 
    /**
     * Return a textual message for an SSH2 code
     *
     * @param int    $code  SSH2 code used to get the current code-message map.
     * @param string $value Optional. The argument to be inserted at the first
     *                        %-sign in the format string
     *
     * @return string The SSH2 message string
     *
     */
    protected function getMessage($code, $value = null)
    {
        static $codeMessages;
        if (!isset($codeMessages)) {
            $codeMessages = array(
                SSH2_OK                => 'No error',
                SSH2_ERROR             => 'Unknown error',
                SSH2_PACKAGE_NOT_FOUND => 'Unable to find package %s',
                SSH2_CLASS_NOT_FOUND   => 'Unable to load class %s',
                SSH2_OPTION_NOT_VALID  => 'Trying to use an undefined option "%s" for the object ' . __CLASS__,
                SSH2_BINARY_NOT_FOUND  => 'Unable to found the SSH2 binary command "%s"',
                SSH2_MODULE_NOT_FOUND  => 'Unable to found the SSH2 module "%s"',
                SSH2_OPTION_REQUIRED   => 'The option %s is required for the object ' . __CLASS__,
                SSH2_PUBLIC_KEY_UNAVAILABLE => 'Unable to read the public key: %s',
                SSH2_UNSUPPORTED       => 'Unsupported method',
                SSH2_PTY_NOT_SUPPORTED => '%s. Only key authentication is supported.'
            );
        }
        return sprintf($codeMessages[$code], $value);
    }
}
?>
