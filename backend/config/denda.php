<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tarif Denda Keterlambatan
    |--------------------------------------------------------------------------
    |
    | Besarnya denda keterlambatan yang dikenakan untuk setiap hari.
    | Dipakai di AdminController::setujuiPengembalian:
    |     denda = hariTerlambat * keterlambatan_per_hari
    | Ubah nilai di sini, tidak perlu sentuh kode controller.
    |
    */

    'keterlambatan_per_hari' => 3000,

];
