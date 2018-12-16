<?php

abstract class MedInTech_Log_Handler_Base implements MedInTech_Log_Handler_Interface
{
  protected $level;
  /** @var bool */
  protected $bubble;
  /** @var MedInTech_Log_Formatter_Interface */
  private $formatter;
  /** @var callable[] */
  public $processors = array();
  /** @var null */
  private $channels;

  public function __construct($level = MedInTech_Log_Logger::DEBUG, $bubble = true, $channels = null)
  {
    $this->setLevel($level);
    $this->setChannels($channels);
    $this->bubble     = $bubble;
    $this->processors = array();
  }

  public function supports(MedInTech_Log_Record $record)
  {
    if (!is_null($this->channels) && !in_array(strtolower($record->channel), $this->channels)) {
      return false;
    }
    return $record->level >= $this->level;
  }

  /**
   * @param MedInTech_Log_Record[] $records
   */
  public function batch(array $records) { foreach ($records as $record) $this->handle($record); }

  public function getLevel() { return $this->level; }
  public function setLevel($level) { $this->level = $level; }

  public function getChannels() { return $this->channels; }
  public function setChannels($channels)
  {
    if (empty($channels)) {
      $this->channels = $channels;
    } else {
      $this->channels = array();
      if (is_string($channels)) {
        $channels = preg_split('/\s*,\s*/', $channels);
      }
      foreach ($channels as $channel) {
        $this->channels[] = strtolower($channel);
      }
    }
  }

  public function isBubble() { return $this->bubble; }
  public function setBubble($bubble) { $this->bubble = $bubble; }

  public function getFormatter()
  {
    if (!$this->formatter) {
      $this->formatter = $this->getDefaultFormatter();
    }
    return $this->formatter;
  }
  public function setFormatter(MedInTech_Log_Formatter_Interface $formatter) { $this->formatter = $formatter; }

  public function close()
  {
  }

  public function __destruct()
  {
    try {
      $this->close();
    } catch (Exception $e) {
      // do nothing
    }
  }
  protected function getDefaultFormatter() { return new MedInTech_Log_Formatter_Line(); }
}