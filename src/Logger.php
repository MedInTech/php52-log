<?php

class MedInTech_Log_Logger implements MedInTech_Log_Interface
{
  const DEBUG     = 100;
  const INFO      = 200;
  const NOTICE    = 250;
  const WARNING   = 300;
  const ERROR     = 400;
  const CRITICAL  = 500;
  const ALERT     = 550;
  const EMERGENCY = 600;

  protected static $levels = array(
    self::DEBUG     => 'DEBUG',
    self::INFO      => 'INFO',
    self::NOTICE    => 'NOTICE',
    self::WARNING   => 'WARNING',
    self::ERROR     => 'ERROR',
    self::CRITICAL  => 'CRITICAL',
    self::ALERT     => 'ALERT',
    self::EMERGENCY => 'EMERGENCY',
  );
  /** @var string */
  protected $channel;
  /** @var MedInTech_Log_Handler_IList */
  protected $handlers;
  /** @var callable[] */
  public $processors;

  public function __construct($channel, MedInTech_Log_Handler_IList $handlers = null, array $processors = array())
  {
    $this->channel    = $channel;
    $this->handlers   = $handlers ? $handlers : new MedInTech_Log_Handler_List();
    $this->processors = $processors;
  }

  public static function getLevelName($level)
  {
    if (!isset(self::$levels[$level])) {
      throw new InvalidArgumentException('Level "' . $level . '" is not defined, use one of: ' . implode(', ', array_keys(self::$levels)));
    }

    return self::$levels[$level];
  }

  public function log($level, $message, array $context = array())
  {
    $levelName = self::getLevelName($level);

    if (version_compare(PHP_VERSION, '5.3.0', '<')) {
      list($us) = explode(' ', microtime());
      $ts = new DateTime(date('Y-m-d H:i:s.') . sprintf('%06s', $us * 1e6));
    } elseif (PHP_VERSION_ID < 70100) {
      $ts = DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)));
    } else {
      $ts = new DateTime();
    }
    $record            = new MedInTech_Log_Record();
    $record->message   = (string)$message;
    $record->context   = $context;
    $record->level     = $level;
    $record->levelName = $levelName;
    $record->channel   = $this->channel;
    $record->time      = $ts;

    foreach ($this->processors as $processor) {
      if (is_callable($processor)) {
        $record = call_user_func($processor, $record);
      }
    }

    /** @var MedInTech_Log_Handler_Interface $handler */
    foreach ($this->handlers as $handler) {
      if (true === $handler->handle($record)) break;
    }

    return true;
  }
  public function debug($message, array $context = array()) { return $this->log(self::DEBUG, $message, $context); }
  public function info($message, array $context = array()) { return $this->log(self::INFO, $message, $context); }
  public function notice($message, array $context = array()) { return $this->log(self::NOTICE, $message, $context); }
  public function warning($message, array $context = array()) { return $this->log(self::WARNING, $message, $context); }
  public function error($message, array $context = array()) { return $this->log(self::ERROR, $message, $context); }
  public function critical($message, array $context = array()) { return $this->log(self::CRITICAL, $message, $context); }
  public function alert($message, array $context = array()) { return $this->log(self::ALERT, $message, $context); }
  public function emergency($message, array $context = array()) { return $this->log(self::EMERGENCY, $message, $context); }

  public function getChannel() { return $this->channel; }
  public function getHandlers() { return $this->handlers; }

  public function cloneForChannel($channel)
  {
    if ($this->channel === $channel) return $this;
    $logger          = clone $this;
    $logger->channel = $channel;
    return $logger;
  }
  public function __clone()
  {
    $this->handlers   = clone $this->handlers;
  }

  public static function getDefaultStreamLogger($stream = 'php://output', $channel = 'default', $level = self::DEBUG)
  {
    $handler   = new MedInTech_Log_Handler_Stream($stream, $level);
    $formatter = new MedInTech_Log_Formatter_Line(null, 'Y-m-d H:i:s.u', false);
    $handler->setFormatter($formatter);

    $logger = new self($channel);
    $logger->getHandlers()->push($handler);

    return $logger;
  }
}
