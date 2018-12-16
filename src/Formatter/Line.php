<?php

class MedInTech_Log_Formatter_Line extends MedInTech_Log_Formatter_Normalize
{
  const SIMPLE_FORMAT = "[%time%] %levelName%.%channel%: %message% %context% %extra%\n";

  protected $format;
  protected $allowInlineLineBreaks;
  protected $ignoreEmptyContextAndExtra;
  protected $includeStacktraces;

  /**
   * @param string $format The format of the message
   * @param string $dateFormat The format of the timestamp: one supported by DateTime::format
   * @param bool $allowInlineLineBreaks Whether to allow inline line breaks in log entries
   * @param bool $ignoreEmptyContextAndExtra
   */
  public function __construct($format = null, $dateFormat = null, $allowInlineLineBreaks = false, $ignoreEmptyContextAndExtra = true)
  {
    $this->format                     = $format ? $format : self::SIMPLE_FORMAT;
    $this->allowInlineLineBreaks      = $allowInlineLineBreaks;
    $this->ignoreEmptyContextAndExtra = $ignoreEmptyContextAndExtra;
    parent::__construct($dateFormat);
  }

  public function includeStacktraces($include = true)
  {
    $this->includeStacktraces = $include;
    if ($this->includeStacktraces) {
      $this->allowInlineLineBreaks = true;
    }
  }

  public function allowInlineLineBreaks($allow = true)
  {
    $this->allowInlineLineBreaks = $allow;
  }

  public function ignoreEmptyContextAndExtra($ignore = true)
  {
    $this->ignoreEmptyContextAndExtra = $ignore;
  }

  /**
   * {@inheritdoc}
   */
  public function format(MedInTech_Log_Record $record)
  {
    $vars = parent::format($record);

    $output = $this->format;

    if (false !== strpos($output, '%message%')) {
      $output = str_replace('%message%', $this->stringify($vars->message), $output);
    }

    foreach ($vars->extra as $var => $val) {
      if (false !== strpos($output, '%extra.' . $var . '%')) {
        $output = str_replace('%extra.' . $var . '%', $this->stringify($val), $output);
        unset($vars->extra[$var]);
      }
    }

    foreach ($vars->context as $var => $val) {
      if (false !== strpos($output, '%context.' . $var . '%')) {
        $output = str_replace('%context.' . $var . '%', $this->stringify($val), $output);
        unset($vars->context[$var]);
      }
      if (false !== strpos($output, '%' . $var . '%')) {
        $output = str_replace('%' . $var . '%', $this->stringify($val), $output);
        unset($vars->context[$var]);
      }
    }

    if ($this->ignoreEmptyContextAndExtra) {
      if (empty($vars->context)) {
        unset($vars->context);
        $output = str_replace('%context%', '', $output);
      }

      if (empty($vars->extra)) {
        unset($vars->extra);
        $output = str_replace('%extra%', '', $output);
      }
    }

    foreach ($vars as $var => $val) {
      if (false !== strpos($output, '%' . $var . '%')) {
        $output = str_replace('%' . $var . '%', $this->stringify($val), $output);
      }
    }

    // remove leftover %extra.xxx% and %context.xxx% if any
    if (false !== strpos($output, '%')) {
      $output = preg_replace('/%(?:extra|context)\..+?%/', '', $output);
    }

    return $output;
  }

  public function batch(array $records)
  {
    $message = '';
    foreach ($records as $record) {
      $message .= $this->format($record);
    }

    return $message;
  }

  public function stringify($value)
  {
    return $this->replaceNewlines($this->convertToString($value));
  }

  protected function normalizeException($e)
  {
    // TODO 2.0 only check for Throwable
    if (!$e instanceof Exception) {
      throw new InvalidArgumentException('Exception expected, got ' . gettype($e) . ' / ' . get_class($e));
    }

    $previousText = '';
    if ($previous = $e->getPrevious()) {
      do {
        $previousText .= ', ' . get_class($previous) . '(code: ' . $previous->getCode() . '): ' . $previous->getMessage() . ' at ' . $previous->getFile() . ':' . $previous->getLine();
      } while ($previous = $previous->getPrevious());
    }

    $str = '[object] (' . get_class($e) . '(code: ' . $e->getCode() . '): ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine() . $previousText . ')';
    if ($this->includeStacktraces) {
      $str .= "\n[stacktrace]\n" . $e->getTraceAsString() . "\n";
    }

    return $str;
  }

  protected function convertToString($data)
  {
    if (null === $data || is_bool($data)) {
      return var_export($data, true);
    }

    if (is_scalar($data)) {
      return (string)$data;
    }

    if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
      return $this->toJson($data, true);
    }

    return str_replace('\\/', '/', $this->toJson($data, true));
  }

  protected function replaceNewlines($str)
  {
    if ($this->allowInlineLineBreaks) {
      if (0 === strpos($str, '{')) {
        return str_replace(array('\r', '\n', '\t'), array("\r", "\n", "\t"), $str);
      }

      return $str;
    }

    return str_replace(array("\r\n", "\r", "\n"), ' ', $str);
  }
}
