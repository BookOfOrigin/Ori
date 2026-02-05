<?php

namespace Origin\Utilities\Bucket;

trait Binary{
  public function Binary($value = null){
    return $this->Bucket(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['function'], $value);
  }
}
