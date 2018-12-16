<?php

interface MedInTech_Log_Handler_Interface
{
  /**
   * @param MedInTech_Log_Record $record
   * @return boolean
   */
  public function supports(MedInTech_Log_Record $record);
  public function handle(MedInTech_Log_Record $record);
  /** @param MedInTech_Log_Record[] $records */
  public function batch(array $records);

  public function setFormatter(MedInTech_Log_Formatter_Interface $formatter);
  /** @return MedInTech_Log_Formatter_Interface */
  public function getFormatter();

  public function getLevel();
  public function setLevel($level);

  public function getChannels();
  public function setChannels($channels);
}