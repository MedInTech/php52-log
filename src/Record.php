<?php

class MedInTech_Log_Record
{
  /** @var string */
  public $channel;
  /** @var string */
  public $message;
  /** @var array */
  public $context = array();

  public $level;
  public $levelName;

  /** @var DateTime */
  public $time;

  public $extra = array();

  public $formatted;
}
