<?php

namespace Nexibi\PrintOne\Enums;

enum Format: string
{
    case SQ15 = 'POSTCARD_SQ15';
    case A5 = 'POSTCARD_A5';
    case A6 = 'POSTCARD_A6';

    case ECO_SQ15 = 'ECOCARD_SQ15';
    case ECO_A5 = 'ECOCARD_A5';
    case ECO_A6 = 'ECOCARD_A6';
}
