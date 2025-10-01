<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Koordinat Kantor
    |--------------------------------------------------------------------------
    |
    | Koordinat lokasi kantor untuk validasi absensi
    | Gunakan Google Maps untuk mendapatkan koordinat yang akurat
    |
    */

    'lokasi_kantor' => [
        'latitude' => env('KANTOR_LATITUDE', -8.666518),
        'longitude' => env('KANTOR_LONGITUDE', 115.183872),
        'nama' => env('KANTOR_NAMA', 'Kantor Pusat'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Radius Absensi
    |--------------------------------------------------------------------------
    |
    | Radius maksimal dalam meter dari kantor untuk bisa melakukan absensi
    |
    */

    'radius_maksimal' => env('ABSENSI_RADIUS', 100), // dalam meter

    /*
    |--------------------------------------------------------------------------
    | Jam Kerja
    |--------------------------------------------------------------------------
    |
    | Pengaturan jam kerja untuk menentukan status terlambat
    |
    */

    'jam_masuk' => env('JAM_MASUK', '08:00:00'),
    'jam_keluar' => env('JAM_KELUAR', '17:00:00'),

    /*
    |--------------------------------------------------------------------------
    | Fitur Foto
    |--------------------------------------------------------------------------
    |
    | Apakah wajib mengambil foto selfie saat absensi
    |
    */

    'wajib_foto' => env('ABSENSI_WAJIB_FOTO', false),

];
