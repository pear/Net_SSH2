<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Net_SSH2 OpenSSH driver.
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
 * The Net_SSH2_OpenSSH class is a concrete implementation of the Net_SSH2
 * abstract class based on OpenSSH clienttool.
 *
 * @category  Net
 * @package   Net_SSH2
 * @author    Luca Corbo <lucor@ortro.net>
 * @copyright 2009 Luca Corbo
 * @license   GNU/LGPL v2.1
 * @link      http://pear.php.net/packages/Net_SSH2
 * @link      http://www.openssh.com
 */
class Net_SSH2_OpenSSH extends Net_SSH2
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
                                       'openssh_binary_path' => null,
                                       'login_name' => null,
                                       'password' => null,
                                       'hostname' => null,
                                       'identity_file' => null,
                                       'port' => 22,
                                       //ssh options
                                       'option' => null, //also used by scp
                                       'command' => null,
                                       // ssh-keygen options
                                       'silence' => null,
                                       'bits' => null,
                                       'type' => null,
                                       'new_passphrase' => '',
                                       'comment' => null,
                                       'output_keyfile' => null,
                                       'overwrite_existing_key' => null,
                                       // scp options
                                       //'option' => null,
                                       'limit' => null,
                                       'recursive' => null,
                                       'remote_path' => null,
                                       'local_path' => null,
                                       //ssh-copy-id options
                                       'public_identity_file' => null,
                                       );

    /**
     * Absolute path of the temporary script implements SSH_ASKPASS.
     *
     * @var string
     * @link http://www.openbsd.org/cgi-bin/man.cgi?query=ssh&sektion=1#ENVIRONMENT
     */
    protected $ssh_askpass_scripts = null;

    /**
     * Input stream
     *
     * @see exec()
     * @var string
     */
    private $_std_input = null;

    /**
     * Creates a new SSH object
     *
     * @param array $options optional. An array of options used to create the
     *                       SSH object. All options must be optional and are
     *                       represented as key-value pairs.
     */
    public function __construct($options = array())
    {
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
        if ($this->ssh_askpass_scripts !== null && is_file($this->ssh_askpass_scripts)) {
            System::rm($this->ssh_askpass_scripts);
        }
    }

    /**
     * Generate the temporary ssh_askpass script
     *
     * @param string $password The password to use for login.
     *
     * @return void
     */
    protected function sshAskPass($password)
    {

        $this->ssh_askpass_scripts =& File_Util::tmpFile() . "";
        
        $askpass_data = 'echo ' . $password . ' < /dev/null';
        File::write($this->ssh_askpass_scripts, $askpass_data);
        File::closeAll();
        chmod($this->ssh_askpass_scripts, 0700);
        
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
        //Check for valid options
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        $cmd = '';

        //Check for setsid utility required on such linux systems
        $setsid_path = System::which('setsid');

        if (is_file($setsid_path)) {
            $cmd .= $setsid_path . ' ';
        }

        //Prepare the command line to execute for sshExec
        $cmd .= $this->_getBinaryPath('ssh');

        if ($this->identity_file !== null) {
            $cmd .= ' -i ' . escapeshellarg($this->identity_file);
        }
        
        if ($this->login_name !== null) {
            $cmd .= ' -l ' . escapeshellarg($this->login_name);
        }

        if ($this->option !== null) {
            foreach ($this->option as $key => $option) {
                $cmd .= ' -o ' . escapeshellarg($option);
            }
        }

        if ($this->port !== null) {
            $cmd .= ' -p ' . $this->port;
        }

        if ($this->password !== null) {
            $this->sshAskPass($this->password);
            $cmd = 'DISPLAY=none:0.0 SSH_ASKPASS=' .
                   escapeshellarg($this->ssh_askpass_scripts) .
                   ' ' . $cmd;
        }

        if ($this->hostname !== null) {
            $cmd .= ' ' . escapeshellarg($this->hostname);
        }
        
        if ($this->command !== null) {
            //Check for input script
            if (is_file($this->command)) {
                //reading commands to execute from file
                $cmd .= ' < ' . $this->command;
            } else {
                $cmd .= ' ' . $this->command;
            }
        }

        return $this->exec($cmd, &$std_output, &$std_error);
    }

    /**
     * Generates authentication keys for ssh
     *
     * @param string $std_output The standard output of the executed command
     * @param string $std_error  The standard error of the executed command
     * @param array  $options    Additional options for the specified method
     *
     * @return mixed The exit code of the executed command or false on error
     */
    public function sshKeyGen(&$std_output, &$std_error, $options = array())
    {
        //Check for valid options
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        $cmd = $this->_getBinaryPath('ssh-keygen');

        if ($this->silence) {
            $cmd .= ' -q ';
        }

        if ($this->bits !== null) {
            $cmd .= ' -b ' . $this->bits;
        }

        if ($this->type !== null) {
            $cmd .= ' -t ' . $this->type;
        }

        $cmd .= ' -N ' . $this->new_passphrase;

        if ($this->comment !== null) {
            $cmd .= ' -C ' . $this->comment;
        }

        if ($this->output_keyfile !== null) {
            $cmd .= ' -f ' . $this->output_keyfile;
        }

        if ($this->overwrite_existing_key) {
            $this->_std_input = 'y';
        } else {
            $this->_std_input = 'n';
        }

        $exit_code = $this->exec($cmd, &$std_output, &$std_error);

        if (is_file($this->output_keyfile)) {
            chmod($this->output_keyfile, 0600);
        }

        return $exit_code;
    }

    /**
     * Install your public key in a remote machine’s authorized_keys
     *
     * @param string $std_output The standard output of the executed command
     * @param string $std_error  The standard error of the executed command
     * @param array  $options    Additional options for the specified method
     *
     * @throws Net_SSH2_Exception If trying if the public key option is not specified.
     * 
     * @return mixed The exit code of the executed command or false on error
     */
    public function sshCopyId(&$std_output, &$std_error, $options = array())
    {

        //Check for valid options
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        if ($this->public_identity_file !== null) {
            $cmd = 'echo "' . trim(File::readAll($this->public_identity_file),
                                                 "\n") . '"';
        } else {
            throw new Net_SSH2_Exception(Net_SSH2::getMessage(SSH2_OPTION_REQUIRED,
                        'public_identity_file'));
        }

        $cmd .= ' | ';

        if ($this->password !== null) {
            $this->sshAskPass($this->password);
            $cmd .= 'DISPLAY=none:0.0 SSH_ASKPASS=' .
                   escapeshellarg($this->ssh_askpass_scripts) . ' ';
        }

        //Check for setsid utility required on such linux systems
        $setsid_path = System::which('setsid');

        if (is_file($setsid_path)) {
            $cmd .= $setsid_path . ' ';
        }

        //Prepare the command line to execute for sshExec
        $cmd .= $this->_getBinaryPath('ssh');

        if ($this->login_name !== null) {
            $cmd .= ' -l ' . escapeshellarg($this->login_name);
        }

        if ($this->port !== null) {
            $cmd .= ' -p ' . $this->port;
        }



        if ($this->hostname !== null) {
            $cmd .= ' ' . escapeshellarg($this->hostname);
        }

        $cmd .= ' "umask 077; test -d .ssh || mkdir .ssh && touch .ssh/authorized_keys && chmod 600 .ssh/authorized_keys; cat >> .ssh/authorized_keys" || exit 1';

        return $this->exec($cmd, &$std_output, &$std_error);
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

        $cmd = $this->_scpCreateCommandLine('send');

        return $this->exec($cmd, &$std_output, &$std_error);
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
    public function scpReceive(&$std_output, &$std_error, $options = array()){

        //Check for valid options
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        $cmd = $this->_scpCreateCommandLine('receive');
        
        return $this->exec($cmd, &$std_output, &$std_error);
    }

    /**
     * Generate the command line for the scp methods
     *
     * @param string $mode Define the commandline to be created (send or receive)
     *
     * @return string The command line for the specified mode
     */
    private function _scpCreateCommandLine($mode)
    {

        $cmd = '';

        //Check for setsid utility required on such linux systems
        $setsid_path = System::which('setsid');

        if (is_file($setsid_path)) {
            $cmd .= $setsid_path . ' ';
        }

        //Prepare the command line to execute for sshExec
        $cmd .= $this->_getBinaryPath('scp');

        if ($this->recursive == 'y') {
             $cmd .= ' -r ';
        }

        if ($this->identity_file !== null) {
            $cmd .= ' -i ' . escapeshellarg($this->identity_file);
        }

        if ($this->port !== null) {
            $cmd .= ' -P ' . $this->port;
        }

        if ($this->limit !== null) {
            $cmd .= ' -l ' . $this->limit;
        }

        if ($this->option !== null) {
            foreach ($this->option as $key => $option) {
                $cmd .= ' -o ' . escapeshellarg($option);
            }
        }

        if ($this->password !== null) {
            $this->sshAskPass($this->password);
            $cmd = 'DISPLAY=none:0.0 SSH_ASKPASS=' .
                   escapeshellarg($this->ssh_askpass_scripts) .
                   ' ' . $cmd;
        }

        $remote_path = '';
        
        if ($this->login_name !== null) {
            $remote_path .= escapeshellarg($this->login_name) . '@';
        }
        if ($this->hostname) {
            $remote_path .= escapeshellarg($this->hostname) . ':';
        }

        $remote_path .= escapeshellarg($this->remote_path);

        $local_path = escapeshellarg($this->local_path);
        

        if ($mode == 'send') {
            $cmd .= ' ' . $local_path . ' ' . $remote_path;
        } else {
            $cmd .= ' ' . $remote_path . ' ' . $local_path;
        }
        
        return $cmd;
    }


    /**
     * Checks if the specified tool binary exists and returns the full path.
     *
     * @param string $file Binary file to check.
     *
     * @throws Net_SSH2_Exception If the specified SSH2 binary tool was not found.
     * @return void
     */
    private function _getBinaryPath($file)
    {
        //Check for ssh binary
        if (array_key_exists('ssh2_binary_path', $this->options)) {
            $ssh2_binary =
                escapeshellarg(File::buildpath(array($options['ssh2_binary_path'],
                                                     $file)));
        } else {
            $ssh2_binary = System::which($file);
        }
        if (!is_file($ssh2_binary)) {
            throw new Net_SSH2_Exception(
                Net_SSH2::getMessage(SSH2_BINARY_NOT_FOUND,
                                     $file)
            );
        }
        return $ssh2_binary;
    }

    /**
     * Execute the specified command.
     *
     * @param string $std_output The standard output of the executed command
     * @param string $std_error  The standard error of the executed command
     *
     * @throws Net_SSH2_Exception If the system does not support PTY.
     *
     * @return mixed The exit code of the executed command or false on error
     */
    protected function exec($command, &$std_output, &$std_error)
    {
        $exit_code = false;

        //This value can be set in the createCommandLine method implementation
        $std_input = $this->_std_input;

        $descriptorspec = array(0 => array("pty"),
                                1 => array("pty"),
                                2 => array("pty"));
        
        try {
            $process = proc_open($command, $descriptorspec, $pipes);
        } catch (Exception $e) {
            if ($this->password !== null) {
                //Only public/private key authentication is supported.
                throw new Net_SSH2_Exception(
                    Net_SSH2::getMessage(SSH2_PTY_NOT_SUPPORTED, $e->getMessage())
                );
            }
            exec($command, $std_output, $exit_code);
            return $exit_code;
        }

        if (is_resource($process)) {
            stream_set_blocking($pipes[0], false);
            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);

            if ($std_input !== null) {
                $std_output .= fgets($pipes[1], 4096);
                sleep(2);
                fwrite($pipes[0], $std_input . "\n");
                fflush($pipes[0]);
            }

            while (!feof($pipes[1])) {
                $std_output .= fgets($pipes[1], 4096);
            }

            while (!feof($pipes[2])) {
                $std_error .= fgets($pipes[2], 4096);
            }

            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exit_code = proc_close($process);
        }

        return $exit_code;
    }
}
?>
