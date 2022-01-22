<?php

namespace App\Exceptions;

use Exception;

class FundooNoteException extends Exception
{
    public function message()
    {
        return response()->json([
            'status' => $this->getCode(),
            'message' => $this->getMessage()
        ]);
    }
}
