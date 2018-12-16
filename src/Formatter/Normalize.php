<?php

abstract class MedInTech_Log_Formatter_Normalize implements MedInTech_Log_Formatter_Interface
{
  const SIMPLE_DATE = "Y-m-d H:i:s.u";

  protected $dateFormat;

  public function __construct($dateFormat = null) { $this->dateFormat = $dateFormat ? $dateFormat : self::SIMPLE_DATE; }
  public function format(MedInTech_Log_Record $record) { return $this->normalize($record); }

  public function batch(array $records)
  {
    foreach ($records as $key => $record) {
      $records[$key] = $this->format($record);
    }

    return $records;
  }

  protected function normalize($data)
  {
    if (null === $data || is_scalar($data)) {
      if (is_float($data)) {
        if (is_infinite($data)) {
          return ($data > 0 ? '' : '-') . 'INF';
        }
        if (is_nan($data)) {
          return 'NaN';
        }
      }

      return $data;
    }

    if (is_array($data)) {
      $normalized = array();

      $count = 1;
      foreach ($data as $key => $value) {
        if ($count++ >= 1000) {
          $normalized['...'] = 'Over 1000 items (' . count($data) . ' total), aborting normalization';
          break;
        }
        $normalized[$key] = $this->normalize($value);
      }

      return $normalized;
    }
    if ($data instanceof MedInTech_Log_Record) {
      $normalized = clone $data;
      foreach ($normalized as $field => $value) {
        $normalized->$field = $this->normalize($value);
      }
      return $normalized;
    }

    if ($data instanceof DateTime) {
      return $data->format($this->dateFormat);
    }

    if (is_object($data)) {
      if ($data instanceof Exception) {
        return $this->normalizeException($data);
      }

      // non-serializable objects that implement __toString stringified
      if (method_exists($data, '__toString') && !$data instanceof JsonSerializable) {
        $value = $data->__toString();
      } else {
        // the rest is json-serialized in some way
        $value = $this->toJson($data, true);
      }

      return sprintf("[object] (%s: %s)", get_class($data), $value);
    }

    if (is_resource($data)) {
      return sprintf('[resource] (%s)', get_resource_type($data));
    }

    return '[unknown(' . gettype($data) . ')]';
  }

  protected function normalizeException($e)
  {
    if (!$e instanceof Exception) {
      throw new InvalidArgumentException('Exception expected, got ' . gettype($e) . ' / ' . get_class($e));
    }

    $data = array(
      'class'   => get_class($e),
      'message' => $e->getMessage(),
      'code'    => $e->getCode(),
      'file'    => $e->getFile() . ':' . $e->getLine(),
    );

    if ($e instanceof SoapFault) {
      if (isset($e->faultcode)) {
        $data['faultcode'] = $e->faultcode;
      }

      if (isset($e->faultactor)) {
        $data['faultactor'] = $e->faultactor;
      }

      if (isset($e->detail)) {
        $data['detail'] = $e->detail;
      }
    }

    $trace = $e->getTrace();
    foreach ($trace as $frame) {
      if (isset($frame['file'])) {
        $data['trace'][] = $frame['file'] . ':' . $frame['line'];
      } elseif (isset($frame['function']) && $frame['function'] === '{closure}') {
        // We should again normalize the frames, because it might contain invalid items
        $data['trace'][] = $frame['function'];
      } else {
        // We should again normalize the frames, because it might contain invalid items
        $data['trace'][] = $this->toJson($this->normalize($frame), true);
      }
    }

    if ($previous = $e->getPrevious()) {
      $data['previous'] = $this->normalizeException($previous);
    }

    return $data;
  }

  protected function toJson($data, $ignoreErrors = false)
  {
    // suppress json_encode errors since it's twitchy with some inputs
    if ($ignoreErrors) {
      return @$this->jsonEncode($data);
    }

    $json = $this->jsonEncode($data);

    if ($json === false) {
      $json = $this->handleJsonError(json_last_error(), $data);
    }

    return $json;
  }

  private function jsonEncode($data)
  {
    if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
      return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    $json = json_encode($data);
    return preg_replace_callback('/\\\\u(\w{4})/', array($this, 'unescapeUnicode'), $json);
  }

  private function unescapeUnicode($matches)
  {
    return html_entity_decode('&#x' . $matches[1] . ';', ENT_COMPAT, 'UTF-8');
  }

  private function handleJsonError($code, $data)
  {
    if ($code !== JSON_ERROR_UTF8) {
      $this->throwEncodeError($code, $data);
    }

    if (is_string($data)) {
      $this->detectAndCleanUtf8($data);
    } elseif (is_array($data)) {
      array_walk_recursive($data, array($this, 'detectAndCleanUtf8'));
    } else {
      $this->throwEncodeError($code, $data);
    }

    $json = $this->jsonEncode($data);

    if ($json === false) {
      $this->throwEncodeError(json_last_error(), $data);
    }

    return $json;
  }

  private function throwEncodeError($code, $data)
  {
    switch ($code) {
      case JSON_ERROR_DEPTH:
        $msg = 'Maximum stack depth exceeded';
        break;
      case JSON_ERROR_STATE_MISMATCH:
        $msg = 'Underflow or the modes mismatch';
        break;
      case JSON_ERROR_CTRL_CHAR:
        $msg = 'Unexpected control character found';
        break;
      case JSON_ERROR_UTF8:
        $msg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
        break;
      default:
        $msg = 'Unknown error';
    }

    throw new RuntimeException('JSON encoding failed: ' . $msg . '. Encoding: ' . var_export($data, true));
  }

  public function detectAndCleanUtf8(&$data)
  {
    if (is_string($data) && !preg_match('//u', $data)) {
      $data = preg_replace_callback(
        '/[\x80-\xFF]+/',
        array('MedInTech_Log_Formatter_Normalize', 'utf8_encode_closure'),
        $data
      );
      $data = str_replace(
        array('¤', '¦', '¨', '´', '¸', '¼', '½', '¾'),
        array('€', 'Š', 'š', 'Ž', 'ž', 'Œ', 'œ', 'Ÿ'),
        $data
      );
    }
  }
  public static function utf8_encode_closure($m)
  {
    return utf8_encode($m[0]);
  }
}
