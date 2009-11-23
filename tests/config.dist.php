<?php

//uncomment the following to run tests in a checkout
//set_include_path(dirname(dirname(__FILE__) . DIR_SEPARATOR . '..'). PATH_SEPARATOR . get_include_path());


$options['openssh2'] = array(//common & sshExec options
                            'openssh_binary_path' => null,
                            'login_name' => 'user',
                            'password' => 'secret', //used also as passphrase
                            'hostname' => '127.0.0.1',
                            'command' => 'echo test',
                            'identity_file' => 'test_key.rsa',
                            'option' => null,
                            'port' => 22,
                            //keygen options
                            'silence' => null,
                            'bits' => '1024',
                            'type' => 'rsa',
                            'new_passphrase' => 'lucor',
                            'comment' => 'test_key',
                            'output_keyfile' => 'test_key.rsa',
                            'overwrite_existing_key' => 'y',
                            //scp options
                            'limit' => null,
                            'recursive' => null,
                            'remote_path' => '/tmp/scp_test.txt',
                            'local_path' => 'scp_test.txt',
                            //ssh-copy-id options
                            'public_identity_file' => 'test_key.rsa.pub',
);

$options['libssh2'] = array(//common & sshExec options
                            'login_name' => 'user',
                            'password' => 'secret', //used also as passphrase
                            'hostname' => '127.0.0.1',
                            'identity_file' => 'test_key.rsa',
                            'port' => 22,
                            //ssh options
                            'command' => 'echo test',
                            //keygen options -> unsupported
                            //scp options
                            'create_mode' => 0744,
                            'remote_path' => '/tmp/scp_test.txt',
                            'local_path' => 'scp_test.txt',
                            //ssh-copy-id options
                            'public_identity_file' => 'test_key.rsa.pub'
);
?>