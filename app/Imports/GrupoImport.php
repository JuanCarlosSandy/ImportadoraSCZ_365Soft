<?php

namespace App\Imports;

use App\Grupo;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Log;;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GrupoImport implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        //
        foreach ($collection as $row) {
            // Verifica si el valor de 'nombre' no es nulo o en blanco
            if (!empty($row['nombre_grupo'])) {
           // Busca si ya existe una marca con el mismo nombre
                $existingMarca = Grupo::where('nombre_grupo', $row['nombre_grupo'])->first();
        
                // Si no existe, inserta un nuevo registro
                if (!$existingMarca) {
                    Grupo::create([
                        'nombre_grupo' => $row['nombre_grupo'],
                    ]);
                    //Log::info('Contenido de $row:', $row);
                    Log::info('Contenido de $row:', $row->toArray());
                }
                // Puedes agregar un manejo especial si deseas hacer algo cuando ya existe el registro
            }
        }
    }
}
