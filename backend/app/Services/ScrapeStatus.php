<?php

namespace App\Services;

enum ScrapeStatus
{
    case Created;
    case Updated;
    case Skipped;
    case Failed;
}
