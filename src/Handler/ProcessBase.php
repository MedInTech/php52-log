<?php

abstract class MedInTech_Log_Handler_ProcessBase extends MedInTech_Log_Handler_Base implements MedInTech_Log_Handler_Interface
{
  public function handle(MedInTech_Log_Record $record)
  {
    if (!$this->supports($record)) return false;
    $record            = $this->process($record);
    $record->formatted = $this->getFormatter()->format($record);
    $this->write($record);

    return false === $this->bubble;
  }

  abstract protected function write(MedInTech_Log_Record $record);

  /**
   * @param MedInTech_Log_Record $record
   * @return MedInTech_Log_Record
   */
  protected function process(MedInTech_Log_Record $record)
  {
    foreach ($this->processors as $processor) {
      $record = call_user_func($processor, $record);
    }

    return $record;
  }
}