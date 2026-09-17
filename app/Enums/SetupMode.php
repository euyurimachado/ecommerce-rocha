<?php

namespace App\Enums;

enum SetupMode: string
{
    case InitialInstall = 'initial_install';
    case Reconfigure = 'reconfigure';
}
