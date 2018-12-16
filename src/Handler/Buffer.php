<?php

class MedInTech_Log_Handler_Buffer extends MedInTech_Log_Handler_Base implements MedInTech_Log_Handler_Interface
{
  private $records = array();
  /** @var MedInTech_Log_Handler_Interface */
  private $next = null;

  public function setNext(MedInTech_Log_Handler_Interface $handler)
  {
    $this->next = $handler;
  }
  public function handle(MedInTech_Log_Record $record)
  {
    if ($this->supports($record)) {
      $this->records[] = $record;
    }
  }

  public function handleBuffered()
  {
    if ($this->next) {
      foreach ($this->records as $record) {
        $this->next->handle($record);
      }
    }
  }
}
