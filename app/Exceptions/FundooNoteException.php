<?php

namespace App\Exceptions;

use Exception;

class FundooNoteException extends Exception
{
    public function message($status)
    {
        return response()->json([
            'status' => $status,
            'message' => $this->getMessage()
        ]);
    }
}
