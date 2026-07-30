<?php

namespace App\Support;

class TagudinBarangays
{
    /** PSGC-coded barangays represented by the project's Tagudin boundary GeoJSON. */
    public static function all(): array
    {
        return [
            ['name' => 'Ag-aguman', 'code' => '102933001'], ['name' => 'Ambalayat', 'code' => '102933002'],
            ['name' => 'Baracbac', 'code' => '102933003'], ['name' => 'Bario-an', 'code' => '102933004'],
            ['name' => 'Baritao', 'code' => '102933005'], ['name' => 'Borono', 'code' => '102933006'],
            ['name' => 'Becques', 'code' => '102933007'], ['name' => 'Bimmanga', 'code' => '102933008'],
            ['name' => 'Bio', 'code' => '102933009'], ['name' => 'Bitalag', 'code' => '102933010'],
            ['name' => 'Bucao East', 'code' => '102933011'], ['name' => 'Bucao West', 'code' => '102933012'],
            ['name' => 'Cabaroan', 'code' => '102933013'], ['name' => 'Cabugbugan', 'code' => '102933014'],
            ['name' => 'Cabulanglangan', 'code' => '102933015'], ['name' => 'Dacutan', 'code' => '102933016'],
            ['name' => 'Dardarat', 'code' => '102933017'], ['name' => 'Del Pilar', 'code' => '102933018'],
            ['name' => 'Farola', 'code' => '102933019'], ['name' => 'Gabur', 'code' => '102933020'],
            ['name' => 'Garitan', 'code' => '102933021'], ['name' => 'Jardin', 'code' => '102933022'],
            ['name' => 'Lacong', 'code' => '102933023'], ['name' => 'Lantag', 'code' => '102933024'],
            ['name' => 'Las-ud', 'code' => '102933025'], ['name' => 'Libtong', 'code' => '102933026'],
            ['name' => 'Lubnac', 'code' => '102933027'], ['name' => 'Magsaysay', 'code' => '102933028'],
            ['name' => "Malaca\u{00F1}ang", 'code' => '102933029'], ['name' => 'Pacac', 'code' => '102933030'],
            ['name' => 'Pallogan', 'code' => '102933031'], ['name' => 'Pudoc East', 'code' => '102933032'],
            ['name' => 'Pudoc West', 'code' => '102933033'], ['name' => 'Pula', 'code' => '102933034'],
            ['name' => 'Quirino', 'code' => '102933035'], ['name' => 'Ranget', 'code' => '102933036'],
            ['name' => 'Rizal', 'code' => '102933038'], ['name' => 'Salvacion', 'code' => '102933039'],
            ['name' => 'San Miguel', 'code' => '102933040'], ['name' => 'Sawat', 'code' => '102933041'],
            ['name' => 'Tallaoen', 'code' => '102933044'], ['name' => 'Tampugo', 'code' => '102933045'],
            ['name' => 'Tarangotong', 'code' => '102933046'],
        ];
    }

    public static function codes(): array
    {
        return array_column(self::all(), 'code');
    }
}
