<?php

namespace tests;

use phpmock\phpunit\PHPMock;

class EmailCheckerTest extends \PHPUnit_Framework_TestCase
{
    use PHPMock;
    /**
     * @var \Hostinger\EmailChecker
     */
    private $emailChecker;

    public function setUp()
    {
        $this->emailChecker = new \Hostinger\EmailChecker();
    }

    public function testExecEnabled()
    {
        $ini_get = $this->getFunctionMock('Hostinger', 'ini_get');
        $disabledFunctions = '';
        $ini_get->expects($this->once())->with('disable_functions')->willReturn($disabledFunctions);
        $result = $this->emailChecker->execEnabled();
        $this->assertTrue($result);
    }

    public function testExecDisabled()
    {
        $ini_get = $this->getFunctionMock('Hostinger', 'ini_get');
        $disabledFunctions = 'exec,passthru,shell_exec,system';
        $ini_get->expects($this->once())->with('disable_functions')->willReturn($disabledFunctions);
        $result = $this->emailChecker->execEnabled();
        $this->assertFalse($result);
    }

    public function testGetMxRecordWithDigCmd_EmptyResults()
    {
        $exec = $this->getFunctionMock('Hostinger', 'exec');
        $exec->expects($this->once())->willReturn(null);

        $domain = 'example.com';
        $result = $this->emailChecker->getMxRecordWithDigCmd($domain);
        $this->assertEquals([], $result);
    }

    public function testGetMxRecordWithDigCmd_Timeout()
    {
        $exec = $this->getFunctionMock('Hostinger', 'exec');
        $digTimeoutMessage = ';; connection timed out; no servers could be reached';
        $exec->expects($this->once())->willReturn([$digTimeoutMessage]);

        $domain = 'hello.me';
        $result = $this->emailChecker->getMxRecordWithDigCmd($domain);
        $this->assertEquals([], $result);
    }

    public function testGetMxRecordWithDigCmd()
    {
        $exec = $this->getFunctionMock('Hostinger', 'exec');
        $digResultLine = 'example.com.            3600    IN      MX      0 smtp.secureserver.net.';
        $exec->expects($this->once())->willReturnCallback(
            function ($command, &$output, &$return_var = '') use ($digResultLine){
                $output = [$digResultLine];
            });

        $domain = 'example.com';
        $result = $this->emailChecker->getMxRecordWithDigCmd($domain);

        $this->assertArrayHasKey('host', $result[0]);
        $this->assertArrayHasKey('ttl', $result[0]);
        $this->assertArrayHasKey('class', $result[0]);
        $this->assertArrayHasKey('type', $result[0]);
        $this->assertArrayHasKey('pri', $result[0]);
        $this->assertArrayHasKey('target', $result[0]);
    }


}
