<?php

use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{
  /** @var MedInTech_Log_Interface */
  private $logger;
  private $f;

  protected function setUp()
  {
    $this->f = tmpfile();
    MedInTech_Log_Logger::getDefaultStreamLogger($this->f, 'test');
  }
  protected function tearDown()
  {
    fclose($this->f);
  }

  public function testLevels()
  {
    $this->logger = MedInTech_Log_Logger::getDefaultStreamLogger($this->f, 'test');
    $this->logAllLevels();

    fseek($this->f, 0);
    $read = fread($this->f, 4096);
    $this->assertNotEmpty($read);
    $lines = explode("\n", trim($read));
    $this->assertCount(7, $lines);
    foreach ($lines as $line) {
      $m = $this->parseLogLine($line);
      $this->assertRegExp('#\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d.\d{6}#', $m['date']);
      $this->assertEquals('test', $m['channel']);
      $this->assertNotFalse(strpos($m['message'], '{"aux":42}'));
    }
  }

  public function testLevelFilter()
  {
    $this->logger = MedInTech_Log_Logger::getDefaultStreamLogger($this->f, 'test', MedInTech_Log_Logger::ERROR);

    $this->logAllLevels();

    fseek($this->f, 0);
    $read = fread($this->f, 4096);
    $this->assertNotEmpty($read);
    $lines = explode("\n", trim($read));
    $this->assertCount(4, $lines);
    foreach ($lines as $line) {
      $m = $this->parseLogLine($line);
      $this->assertRegExp('#\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d.\d{6}#', $m['date']);
      $this->assertEquals('test', $m['channel']);
      $this->assertNotFalse(strpos($m['message'], '{"aux":42}'));
    }
  }

  public function testCloneForChannel()
  {
    $logger = MedInTech_Log_Logger::getDefaultStreamLogger($this->f, 'test', MedInTech_Log_Logger::ERROR);
    $this->logger = $logger->cloneForChannel('clone');

    $this->logAllLevels();
    fseek($this->f, 0);
    $read = fread($this->f, 4096);
    $this->assertNotEmpty($read);
    $lines = explode("\n", trim($read));
    $this->assertCount(4, $lines);
    foreach ($lines as $line) {
      $m = $this->parseLogLine($line);
      $this->assertRegExp('#\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d.\d{6}#', $m['date']);
      $this->assertEquals('clone', $m['channel']);
      $this->assertNotFalse(strpos($m['message'], '{"aux":42}'));
    }
  }
  public function testProcessors()
  {
    $this->logger = MedInTech_Log_Logger::getDefaultStreamLogger($this->f, 'test', MedInTech_Log_Logger::ERROR);
    $this->logger->processors[] = create_function('$rec', '$rec->context["processed"] = true; return $rec;');

    $this->logAllLevels();
    fseek($this->f, 0);
    $read = fread($this->f, 4096);
    $this->assertNotEmpty($read);
    $lines = explode("\n", trim($read));
    $this->assertCount(4, $lines);
    foreach ($lines as $line) {
      $m = $this->parseLogLine($line);
      $this->assertRegExp('#\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d.\d{6}#', $m['date']);
      $this->assertEquals('test', $m['channel']);
      $this->assertNotFalse(strpos($m['message'], '{"aux":42,"processed":true}'));
    }
  }

  protected function logAllLevels()
  {
    $this->logger->emergency('Emergency test %level%', array('level' => 'emerg', 'aux' => 42));
    $this->logger->alert('Alert test %level%', array('level' => 'alert', 'aux' => 42));
    $this->logger->critical('Critical test %level%', array('level' => 'critical', 'aux' => 42));
    $this->logger->error('Error test %level%', array('level' => 'error', 'aux' => 42));
    $this->logger->warning('Warning test %level%', array('level' => 'warn', 'aux' => 42));
    $this->logger->info('Info test %level%', array('level' => 'info', 'aux' => 42));
    $this->logger->debug('Debug test %level%', array('level' => 'debug', 'aux' => 42));
  }

  protected function parseLogLine($line)
  {
    $this->assertNotFalse(preg_match('#^\[(?<date>[^\]]+)\] (?<level>\w+)\.(?<channel>\w+): (?<message>.*)$#', $line, $matches));
    return $matches;
  }

}