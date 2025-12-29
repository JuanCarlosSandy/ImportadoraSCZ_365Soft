<?php

namespace App\Imports;

use App\Medida;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MedidaImport implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            // Verifica si el valor de 'nombre' no es nulo o en blanco
            if (!empty($row['descripcion_medida'])) {
           // Busca si ya existe una marca con el mismo nombre
                $existingMarca = Medida::where('descripcion_medida', $row['descripcion_medida'])->first();
        
                // Si no existe, inserta un nuevo registro
                if (!$existingMarca) {
                    Medida::create([
                        'descripcion_medida' => $row['descripcion_medida'],
                        'descripcion_corta' => $row['descripcion_corta'],
                        'estado' => $row['estado'],
                    ]);
                    //Log::info('Contenido de $row:', $row);
                    Log::info('Contenido de $row:', $row->toArray());
                }
                // Puedes agregar un manejo especial si deseas hacer algo cuando ya existe el registro
            }
        }
    }
}
