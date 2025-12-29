<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Log;
use Exception; // Importa la clase Exception
use App\Persona;
use App\Proveedor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProvedorImport implements ToCollection
{
    

    private $errors = []; 


    public function collection(Collection $rows)
{
    $rowNumber = 1;
    $importacionExitosa = true;
    $erroresDetallados = [];
    try {

        DB::beginTransaction();

        foreach ($rows as $row) {
           
            try {
               
                // Crear la persona y obtener la instancia creada
                $persona = Persona::create([
                    'nombre' => $row[0],
                    'usuario' =>  Auth::user()->id,
                    'tipo_documento' => $row[1],
                    'num_documento' => $row[2],
                    'direccion' => $row[3],
                    'telefono' => $row[4],
                    'email' => $row[5],
                ]);
                

                // Crear el proveedor asociando el ID de la persona
                Proveedor::create([
                    'contacto' => $row[6],
                    'telefono_contacto' =>  $row[7],
                    'id' => $persona->id 
                ]);

            } catch (Exception $e) {
                $errorMensaje = "Error al procesar fila $rowNumber: " . $e->getMessage();
                $this->errors[] = $errorMensaje;
                $erroresDetallados[] = ['fila' => $rowNumber, 'error' => $errorMensaje, 'datos' => $row];

                $importacionExitosa = false;
            }

            $rowNumber++;
        }

        if ($importacionExitosa) {
            DB::commit(); // Confirmar la transacción si no hay errores
        } else {
            DB::rollBack(); // Revertir la transacción en caso de error
        }
    } catch (Exception $e) {
        DB::rollBack(); // Revertir la transacción en caso de error
        $importacionExitosa = false;
        $this->errors[] = "Error al procesar fila: " . $e->getMessage();
    }

    // Aquí puedes retornar los errores detallados para depurar
    return ['importacionExitosa' => $importacionExitosa, 'errores' => $this->errors, 'erroresDetallados' => $erroresDetallados];
}



    

    

    public function getErrors()
    {
        return $this->errors ?? [];
    }

    private function getErrorsResponse($importacionExitosa)
    {
        if (!$importacionExitosa) {
            return response()->json(['errors' => $this->errors], 422);
        } else {
            return response()->json(['mensaje' => 'Importación exitosa'], 200);
        }
    }

}