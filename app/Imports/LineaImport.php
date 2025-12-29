<?php

namespace App\Imports;

use App\Categoria;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToModel;

// class LineaImport implements ToCollection, WithHeadingRow
// {
//     /**
//     * @param Collection $collection
//     */
//     public function collection(Collection $collection)
//     {
//         //
//         foreach ($collection as $row) {
//             // Verifica si el valor de 'nombre' no es nulo o en blanco
//             if (!empty($row['nombre'])) {
//            // Busca si ya existe una marca con el mismo nombre
//                 $existingMarca = Categoria::where('nombre', $row['nombre'])->first();
        
//                 // Si no existe, inserta un nuevo registro
//                 if (!$existingMarca) {
//                     Categoria::create([
//                         'nombre' => $row['nombre'],
//                         'descripcion' => $row['descripcion'],
//                         //'codigoProductoSin' => trim($row['codigoProductoSin']) ?: null,
//                         //'codigoProductoSin' => $row['codigoProductoSin'],
//                         'codigoProductoSin' => $row['codigoProductoSin'] ?? null,
//                         //'codigoProductoSin' => $row['codigoProductoSin'] ?? null,
//                         'condicion' => $row['condicion'],
//                     ]);
//                 }
//                 // Puedes agregar un manejo especial si deseas hacer algo cuando ya existe el registro
//             }
//         }
//     }
// }

class LineaImport implements ToModel, WithHeadingRow
{
    // public function model(collection $row)
    // {
    //     try {
    //         // Verifica si el valor de 'nombre' no es nulo o en blanco
    //         if (!empty($row[0])) {
    //             // Busca si ya existe una categoría con el mismo nombre
    //             $existingCategoria = Categoria::where('nombre', $row[0])->first();

    //             // Si no existe, inserta un nuevo registro
    //             if (!$existingCategoria) {
    //                 return new Categoria([
    //                     'nombre' => $row[0],
    //                     'descripcion' => $row[1] ?? null,
    //                     'codigoProductoSin' => trim($row[2]) ?? null,
    //                     'condicion' => is_numeric($row['condicion']) ? $row['condicion'] : 0, // convierte a numérico o usa 0 si no es numérico]);
    //                     //'condicion' => isset($row['condicion']) ? $row['condicion'] : 0, // convierte a numérico o usa 0 si no es numérico
    //                     //'condicion' => $row[3] ?? null,
    //                 ]);
    //             }
    //         }
    //     } catch (\Exception $e) {
    //         // Registra el error en el log
    //         Log::error('Error al procesar fila:', ['error' => $e->getMessage(), 'row' => $row]);
    //     }

    //     Si hay un problema, retorna null para evitar la inserción
    //     return null;
    // }
    public function model(array $row)
    {
        // Verifica si los índices 'nombre' y 'condicion' existen en la fila
        if (isset($row['nombre'], $row['condicion'])) {
            // Busca si ya existe una marca con el mismo nombre
            $existingMarca = Categoria::where('nombre', $row['nombre'])->first();

            // Si no existe, inserta un nuevo registro
            if (!$existingMarca) {
                Categoria::create([
                    'nombre' => $row['nombre'],
                    'descripcion' => $row['descripcion'] ?? null,
                    'codigoProductoSin' => isset($row['codigoproductosin']) ? trim($row['codigoproductosin']) : null,
                    'condicion' => is_numeric($row['condicion']) ? $row['condicion'] : 0,
                ]);
                Log::info('Contenido de $row:', $row);
            }
            // Puedes agregar un manejo especial si deseas hacer algo cuando ya existe el registro
        } else {
            // Manejo de fila sin índice 'nombre' o 'condicion'
            Log::error("Error al procesar fila: " . json_encode(['error' => 'Undefined index: nombre o condicion', 'row' => $row]));
        }
    }
}