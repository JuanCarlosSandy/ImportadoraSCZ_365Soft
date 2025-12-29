<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Industria;

class IndustriaImport implements ToCollection
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            Industria::create([
                'nombre' => $row[0],
                'estado' => $row[1] ? '1' : '0',
            ]);
        }
    }
}
