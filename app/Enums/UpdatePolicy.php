<?php

namespace App\Enums;

enum UpdatePolicy: string
{
    case Manual = 'manual';
    case SecurityOnly = 'security_only';
    case PatchAuto = 'patch_auto';
    case MinorAuto = 'minor_auto';
}
