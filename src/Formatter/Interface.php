<?php

interface MedInTech_Log_Formatter_Interface
{
  public function format(MedInTech_Log_Record $record);
  /** @param MedInTech_Log_Record[] $record */
  public function batch(array $record);
}