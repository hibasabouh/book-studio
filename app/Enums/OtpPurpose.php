<?php

namespace App\Enums;

enum OtpPurpose: string
{
    case Verify = 'verify';
    case Reset = 'reset';
}
