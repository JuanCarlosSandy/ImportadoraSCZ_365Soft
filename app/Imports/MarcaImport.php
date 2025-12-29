<?php

namespace App\Imports;

use App\Marca;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
//use Maatwebsite\Excel\Concerns\WithHeadings;

// class MarcaImport implements ToCollection
// {
//     /**
//     * @param Collection $collection
//     */
//     public function collection(Collection $collection)
//     {
//         //
//     }
// }

//--------------------------------------------------------------------
// class MarcaImport implements ToCollection, WithHeadingRow
// {
//     public function collection(Collection $rows)
//     {
//         foreach ($rows as $row) {
//             // Verifica si el valor de 'nombre' no es nulo o en blanco
//             if (!empty($row['nombre'])) {
//                 Marca::create([
//                     'nombre' => $row['nombre'],
//                     'condicion' => $row['condicion'],
//                 ]);
//             }
//         }
//     }
// }
//-----------------------------------------------------------------
class MarcaImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Verifica si el valor de 'nombre' no es nulo o en blanco
            if (!empty($row['nombre'])) {
           // Busca si ya existe una marca con el mismo nombre
                $existingMarca = Marca::where('nombre', $row['nombre'])->first();
        
                // Si no existe, inserta un nuevo registro
                if (!$existingMarca) {
                    Marca::create([
                        'nombre' => $row['nombre'],
                        'condicion' => $row['condicion'],
                    ]);
                }
                // Puedes agregar un manejo especial si deseas hacer algo cuando ya existe el registro
            }
        }
    }
}

