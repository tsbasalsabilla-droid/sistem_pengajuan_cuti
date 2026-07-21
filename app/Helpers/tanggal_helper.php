<?php

if (!function_exists('formatTanggalIndonesia')) {

    function formatTanggalIndonesia($tanggal)
    {
        if (empty($tanggal) || $tanggal == '0000-00-00') {
            return '-';
        }

        $bulan = [
            1 => 'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        ];

        $timestamp = strtotime($tanggal);

        return date('j', $timestamp) . ' ' .
            $bulan[(int)date('n', $timestamp)] . ' ' .
            date('Y', $timestamp);
    }
}