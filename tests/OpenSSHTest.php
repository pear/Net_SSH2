<?php
require_once 'PHPUnit/Framework/TestCase.php';
class OpenSSHTest extends PHPUnit_Framework_TestCase
{

    protected $client;

    private $scp_recv_file = 'scp_recv.txt';
    protected function setUp()
    {
        if (!file_exists('config.php')) {
            $this->markTestSkipped("Can't run test without setting up a local configution! See config.dist.php");
        }

        include 'config.php';
        include_once 'Net/SSH2.php';
        $this->client = Net_SSH2::factory('OpenSSH', $options['openssh2']);
    }

    public function testSshExec() {
        $this->client->identity_file = null;//force to use password
        $exit_code = $this->client->sshExec($std_output, $std_error);
        $this->assertEquals(0, $exit_code, $std_output);
    }

    public function testScpSend() {
        $this->client->identity_file = null;//force to use password
        $exit_code = $this->client->scpSend($std_output, $std_error);
        $this->assertEquals(0, $exit_code, $std_output);
    }

    public function testScpReceive() {
        $this->client->identity_file = null;//force to use password
        $this->client->local_path = $this->scp_recv_file;
        $exit_code = $this->client->scpReceive($std_output, $std_error);
        $this->assertEquals(0, $exit_code, $std_output);
        $this->assertFileExists($this->client->local_path, "File $this->scp_recv_file not found!");
    }

    public function testSshKeyGen() {
        $exit_code = $this->client->sshKeyGen($std_output, $std_error);
        $this->assertEquals(0, $exit_code, $std_output);
    }

    public function testSshCopyId() {
        $this->client->identity_file = null;//force to use password
        $exit_code = $this->client->sshCopyId($std_output, $std_error);
        $this->assertEquals(0, $exit_code, $std_output);
    }

    public function testSshExecWithKey() {
        $exit_code = $this->client->sshExec($std_output, $std_error);
        $this->assertEquals(0, $exit_code, $std_output);
    }

    public function testScpSendWithKey() {
        $exit_code = $this->client->scpSend($std_output, $std_error);
        $this->assertEquals(0, $exit_code, $std_output);
    }

    public function testScpReceiveWithKey() {
        $this->client->local_path = $this->scp_recv_file;
        $exit_code = $this->client->scpReceive($std_output, $std_error);
        $this->assertEquals(0, $exit_code, $std_output);
        $this->assertFileExists($this->client->local_path, "File $this->scp_recv_file not found!");
    }

    protected function tearDown()
    {
        unset($this->client);
        if (is_file($this->scp_recv_file)) {
           unlink($this->scp_recv_file);
        }
    }
}

?>