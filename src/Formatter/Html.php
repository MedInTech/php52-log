<?php

class MedInTech_Log_Formatter_Html extends MedInTech_Log_Formatter_Normalize
{
  /**
   * Translates Monolog log levels to html color priorities.
   */
  protected $logLevels = array(
    MedInTech_Log_Logger::DEBUG     => '#cccccc',
    MedInTech_Log_Logger::INFO      => '#468847',
    MedInTech_Log_Logger::NOTICE    => '#3a87ad',
    MedInTech_Log_Logger::WARNING   => '#c09853',
    MedInTech_Log_Logger::ERROR     => '#f0ad4e',
    MedInTech_Log_Logger::CRITICAL  => '#FF7708',
    MedInTech_Log_Logger::ALERT     => '#C12A19',
    MedInTech_Log_Logger::EMERGENCY => '#000000',
  );

  /**
   * @param string $dateFormat The format of the timestamp: one supported by DateTime::format
   */
  public function __construct($dateFormat = null)
  {
    parent::__construct($dateFormat);
  }

  /**
   * Creates an HTML table row
   *
   * @param  string $th Row header content
   * @param  string $td Row standard cell content
   * @param  bool $escapeTd false if td content must not be html escaped
   * @return string
   */
  protected function addRow($th, $td = ' ', $escapeTd = true)
  {
    $th = htmlspecialchars($th, ENT_NOQUOTES, 'UTF-8');
    if ($escapeTd) {
      $td = '<pre>' . htmlspecialchars($td, ENT_NOQUOTES, 'UTF-8') . '</pre>';
    }

    $row = <<<HTML
<tr style="padding: 4px;border-spacing: 0;text-align: left;">
  <th style="background: #cccccc" width="100px">$th:</th>
  <td style="padding: 4px;border-spacing: 0;text-align: left;background: #eeeeee">$td</td>
</tr>
HTML;

    return $row;
  }

  /**
   * Create a HTML h1 tag
   *
   * @param  string $title Text to be in the h1
   * @param  int $level Error level
   * @return string
   */
  protected function addTitle($title, $level)
  {
    $title = htmlspecialchars($title, ENT_NOQUOTES, 'UTF-8');

    $html = <<<HTML
<h1 style="background: {$this->logLevels[$level]}; color: #ffffff; padding: 5px;" class="monolog-output">
  $title
</h1>
HTML;
    return $html;
  }

  public function format(MedInTech_Log_Record $record)
  {
    $output = $this->addTitle($record->levelName, $record->level);
    $output .= '<table cellspacing="1" width="100%" class="monolog-output">';

    $output .= $this->addRow('Message', (string)$record->message);
    $output .= $this->addRow('Time', $record->time->format($this->dateFormat));
    $output .= $this->addRow('Channel', $record->channel);
    if ($record->context) {
      $embeddedTable = '<table cellspacing="1" width="100%">';
      foreach ($record->context as $key => $value) {
        $embeddedTable .= $this->addRow($key, $this->convertToString($value));
      }
      $embeddedTable .= '</table>';
      $output        .= $this->addRow('Context', $embeddedTable, false);
    }
    if ($record->extra) {
      $embeddedTable = '<table cellspacing="1" width="100%">';
      foreach ($record->extra as $key => $value) {
        $embeddedTable .= $this->addRow($key, $this->convertToString($value));
      }
      $embeddedTable .= '</table>';
      $output        .= $this->addRow('Extra', $embeddedTable, false);
    }

    return $output . '</table>';
  }

  /**
   * Formats a set of log records.
   *
   * @param  MedInTech_Log_Record[] $records A set of records to format
   * @return mixed The formatted set of records
   */
  public function batch(array $records)
  {
    $message = '';
    foreach ($records as $record) {
      $message .= $this->format($record);
    }

    return $message;
  }

  protected function convertToString($data)
  {
    if (null === $data || is_scalar($data)) {
      return (string)$data;
    }

    $data = $this->normalize($data);
    if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
      return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    return str_replace('\\/', '/', json_encode($data));
  }
}
