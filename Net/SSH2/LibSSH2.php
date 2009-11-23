<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Net_SSH2 libssh2 driver.
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

/**
 * The Net_SSH2_LibSSH2 class is a concrete implementation of the Net_SSH2
 * abstract class based on PHP Secure Shell2 Bindings to the libssh2 library.
 *
 * @category  Net
 * @package   Net_SSH2
 * @author    Luca Corbo <lucor@ortro.net>
 * @copyright 2009 Luca Corbo
 * @license   GNU/LGPL v2.1
 * @link      http://pear.php.net/packages/Net_SSH2
 * @link      http://www.php.net/manual/en/book.ssh2.php
 * @link      http://sourceforge.net/projects/libssh2
 */
class Net_SSH2_LibSSH2 extends Net_SSH2
{
    /**
     * SSH option arguments
     * List of the allowed options managed by __set and __get methods
     *
     * @var array
     * @see Net_SSH2_ssh::__construct()
     * @link http://www.openbsd.org/cgi-bin/man.cgi?query=ssh
     * @link http://www.openbsd.org/cgi-bin/man.cgi?query=scp
     * @link http://www.openbsd.org/cgi-bin/man.cgi?query=ssh-keygen
     */
    
    protected $allowed_options = array(//common options
                                       'login_name' => null,
                                       'password' => null,
                                       'hostname' => null,
                                       'identity_file' => null,
                                       'port' => 22,
                                       //ssh options
                                       'command' => null,
                                       // ssh-keygen options
                                       // Unsupported
                                       // scp options
                                       'create_mode' => 0644,
                                       'remote_path' => null,
                                       'local_path' => null,
                                        //ssh-copy-id options
                                       'public_identity_file' => null,
                                       );

    /**
     * Creates a new SSH object
     *
     * @param array $options optional. An array of options used to create the
     *                       SSH object. All options must be optional and are
     *                       represented as key-value pairs.
     * @throws Net_SSH2_Exception If the ssh2 module is not found.
     */
    public function __construct($options = array())
    {
        if (!function_exists("ssh2_connect")) {
            $module = 'ssh2.so';
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
               $module = 'php_ssh2.dll';
            }
            throw new Net_SSH2_Exception(
                Net_SSH2::getMessage(SSH2_MODULE_NOT_FOUND, $module)
            );
        };

        //Check for valid options
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }
        
    }

    /**
     * Overloading of the __destruct method, ensure to remove the SSH_ASKPASS
     * script if created.
     */
    public function __destruct()
    {
    }

    /**
     * Connect and authenticate on a remote server
     *
     * @throws Net_SSH2_Exception If connection or authentication fails.
     * @return resource Returns a resource on success
     */
    private function _authenticate()
    {

        $connection = null;
        
        // try to authenticate with username and password
        try {
            $connection = ssh2_connect($this->hostname, $this->port);
            if ($this->identity_file == null) {
                ssh2_auth_password($connection,
                                   $this->login_name,
                                   $this->password);
            } else {
                ssh2_auth_pubkey_file($connection,
                                      $this->login_name,
                                      $this->public_identity_file,
                                      $this->identity_file,
                                      $this->password);
            }
            
        } catch (Exception $e) {
            throw new Net_SSH2_Exception($e->getMessage());
        }
        
        return $connection;
    }

    /**
     * Execute a command on a remote server
     *
     * @param string $std_output The standard output of the executed command
     * @param string $std_error  The standard error of the executed command
     * @param array  $options    Additional options for the specified method
     *
     * @return mixed The exit code of the executed command or false on error
     */
    public function sshExec(&$std_output, &$std_error, $options = array())
    {

        $exit_code = 255;
        
        //Check for valid options
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        try {
            $connection    = $this->_authenticate();
            $stdout_stream = ssh2_exec($connection, $this->command);
            $stderr_stream = ssh2_fetch_stream($stdout_stream, SSH2_STREAM_STDERR);
        } catch (Exception $e) {
            $std_output = $e->getMessage();
            return $exit_code;
        }

        while (!feof($stdout_stream)) {
            $std_output .= fgets($stdout_stream, 4096);
        }

        while (!feof($stderr_stream)) {
            $std_error .= fgets($stderr_stream, 4096);
        }

        fclose($stdout_stream);
        fclose($stderr_stream);
        $exit_code = 0;
        
        return $exit_code;
    }

    /**
     * Generates authentication keys for ssh
     *
     * @param string $std_output The standard output of the executed command
     * @param string $std_error  The standard error of the executed command
     * @param array  $options    Additional options for the specified method
     *
     * @throws Net_SSH2_Exception Unsupported method.
     *
     * @return mixed The exit code of the executed command or false on error
     */
    public function sshKeyGen(&$std_output, &$std_error, $options = array())
    {
        throw new Net_SSH2_Exception(Net_SSH2::getMessage(SSH2_UNSUPPORTED));
    }

    /**
     * Install your public key in a remote machine’s authorized_keys
     *
     * @param string $std_output The standard output of the executed command
     * @param string $std_error  The standard error of the executed command
     * @param array  $options    Additional options for the specified method
     *
     * @throws Net_SSH2_Exception If the public key is not found.
     * 
     * @return mixed The exit code of the executed command or false on error
     */
    public function sshCopyId(&$std_output, &$std_error, $options = array())
    {

        //Check for valid options
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        if (!is_readable($this->public_identity_file)) {
            throw new Net_SSH2_Exception(Net_SSH2::getMessage(SSH2_PUBLIC_KEY_UNAVAILABLE));
        }

        $exit_code = 255;

        $pub_key = trim(File::readAll($this->public_identity_file), "\n");

        $pub_key_array = explode(' ', $pub_key);

        try {
            $connection = $this->_authenticate($std_output);
            $pkey       = ssh2_publickey_init($connection);
            ssh2_publickey_add($pkey, 
                               $pub_key_array[0],
                               base64_decode($pub_key_array[1]),
                               false,
                               array('comment'=>$pub_key_array[2]));
            $exit_code = 0;
        } catch (Exception $e) {
            $std_output = $e->getMessage();
        }

        return $exit_code;
    }

    /**
     * Send a file via SCP
     *
     * @param string $std_output The standard output of the executed command
     * @param string $std_error  The standard error of the executed command
     * @param array  $options    Additional options for the specified method
     *
     * @return mixed The exit code of the executed command or false on error
     */
    public function scpSend(&$std_output, &$std_error, $options = array())
    {

        //Check for valid options
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        $exit_code = 255;
        
        try {
            $connection = $this->_authenticate($std_output);
            ssh2_scp_send($connection,
                          $this->local_path,
                          $this->remote_path,
                          $this->create_mode
                         );
            $exit_code = 0;
        } catch (Exception $e) {
            $std_output = $e->getMessage();
        }
        
        return $exit_code;
    }

    /**
     * Request a file via SCP
     *
     * @param string $std_output The standard output of the executed command
     * @param string $std_error  The standard error of the executed command
     * @param array  $options    Additional options for the specified method
     *
     * @return mixed The exit code of the executed command or false on error
     */
    public function scpReceive(&$std_output, &$std_error, $options = array())
    {

        //Check for valid options
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        $exit_code = 255;

        try {
            $connection = $this->_authenticate($std_output);
            ssh2_scp_recv($connection,
                          $this->remote_path,
                          $this->local_path);
            $exit_code = 0;
        } catch (Exception $e) {
            $std_output = $e->getMessage();
        }

        return $exit_code;
    }
}
?>
