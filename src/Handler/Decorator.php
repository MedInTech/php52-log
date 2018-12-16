<?php

abstract class MedInTech_Log_Handler_Decorator implements MedInTech_Log_Handler_Interface
{
  /** @var MedInTech_Log_Handler_Interface */
  protected $handler;
  public function __construct(MedInTech_Log_Handler_Interface $handler) { $this->handler = $handler; }

  public function supports(MedInTech_Log_Record $record) { return $this->handler->supports($record); }
  public function handle(MedInTech_Log_Record $record) { $this->handler->handle($record); }
  public function batch(array $records) { $this->handler->batch($records); }
  public function setFormatter(MedInTech_Log_Formatter_Interface $formatter) { $this->handler->setFormatter($formatter); }
  /** @return MedInTech_Log_Formatter_Interface */
  public function getFormatter() { return $this->handler->getFormatter(); }

  public function getLevel() { return $this->handler->getLevel(); }
  public function setLevel($level) { return $this->handler->setLevel($level); }
  public function getChannels() { return $this->handler->getChannels(); }
  public function setChannels($channels) { return $this->handler->setChannels($channels); }
}