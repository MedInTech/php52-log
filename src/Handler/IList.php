<?php

interface MedInTech_Log_Handler_IList extends Iterator
{
  public function push(MedInTech_Log_Handler_Interface $handler);
  public function unshift(MedInTech_Log_Handler_Interface $handler);
  public function pop();
  public function shift();

  public function isEmpty();
  public function getLength();
}