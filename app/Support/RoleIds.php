<?php

namespace App\Support;

/**
 * role_id tetap — jangan filter staf lewat role_name (ganti nama role = pecah).
 */
class RoleIds
{
    public const DIREKSI = 1;

    public const DEVELOPER = 4;

    public const QC_GUDANG = 7;
}
