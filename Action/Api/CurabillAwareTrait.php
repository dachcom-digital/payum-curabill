<?php

namespace DachcomDigital\Payum\Curabill\Action\Api;

use DachcomDigital\Payum\Curabill\Exception\CurabillException;

trait CurabillAwareTrait
{
    /**
     * @param \ArrayAccess      $details
     * @param CurabillException $e
     * @param object            $request
     */
    protected function populateDetailsWithError(\ArrayAccess $details, CurabillException $e, $request)
    {
        $details['error_request'] = get_class($request);
        $details['error_file'] = $e->getFile();
        $details['error_line'] = $e->getLine();
        $details['error_code'] = (int)$e->getCode();
        $details['error_message'] = utf8_encode($e->getMessage());
    }
}
