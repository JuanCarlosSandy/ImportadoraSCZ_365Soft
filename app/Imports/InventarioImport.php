<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Log;
use Exception; // Importa la clase Exception
use App\Almacen;
use App\Articulo;
use App\Inventario;

class InventarioImport implements ToCollection
{
    private $almacenMapping;
    private $articuloMapping;

    private $errors = [];

    public function __construct()
    {
        $this->almacenMapping = $this->createAlmacenMapping();
        $this->articuloMapping = $this->createArticuloMapping();

    }

    private function createAlmacenMapping()
    {
        return Almacen::pluck('nombre_almacen', 'id')->toArray();
    }
    private function createArticuloMapping()
    {
        return Articulo::pluck('codigo', 'id')->toArray();
    }
public function collection(Collection $rows)
{
    $rowNumber = 1;
    $importacionExitosa = true;
    try {
        \DB::beginTransaction();

        foreach ($rows as $row) {
            // Normalizar inputs básicos
            $almacenInput = isset($row[0]) ? trim($row[0]) : null;
            $articuloInput = isset($row[1]) ? trim($row[1]) : null;

            $idAlmacen = $this->getAlmacenId($almacenInput);
            $idArticulo = $this->getArticuloId($articuloInput);

            // Si faltan identificadores, registrar error y saltar fila
            if (!$idAlmacen) {
                $this->errors[] = "Fila $rowNumber: Almacén inválido o no encontrado ('{$almacenInput}')";
                $importacionExitosa = false;
                $rowNumber++;
                continue;
            }
            if (!$idArticulo) {
                $this->errors[] = "Fila $rowNumber: Artículo inválido o no encontrado ('{$articuloInput}')";
                $importacionExitosa = false;
                $rowNumber++;
                continue;
            }

            try {
                // Forzar fecha a 2099-12-31 para TODO (según pediste)
                $fechaVencimiento = '2099-12-31';

                // Normalizar cantidad: quitar espacios y cambiar coma por punto
                $rawCantidad = isset($row[2]) ? trim($row[2]) : '';
                $rawCantidad = str_replace(',', '.', $rawCantidad);
                // Si viene vacío o no numérico -> tomar 0 y marcar error
                if ($rawCantidad === '' || !is_numeric($rawCantidad)) {
                    $this->errors[] = "Fila $rowNumber: cantidad inválida ('{$row[2]}')";
                    $importacionExitosa = false;
                    $rowNumber++;
                    continue;
                }
                $cantidadPasada = (float) $rawCantidad;

                // obtener unidad_envase (1 si no)
                $unidadEnvase = $this->getUnidadEnvase($idArticulo);

                // cantidad real a guardar = cantidad pasada * unidad_envase
                $cantidadEnUnidades = $cantidadPasada * $unidadEnvase;

                // Buscar inventario existente
                $inventarioExistente = Inventario::where('idalmacen', $idAlmacen)
                    ->where('idarticulo', $idArticulo)
                    ->where('fecha_vencimiento', $fechaVencimiento)
                    ->first();

                if ($inventarioExistente) {
                    $inventarioExistente->saldo_stock = $inventarioExistente->saldo_stock + $cantidadEnUnidades;
                    $inventarioExistente->cantidad = $inventarioExistente->cantidad + $cantidadEnUnidades;
                    $inventarioExistente->save();
                } else {
                    Inventario::create([
                        'idalmacen' => $idAlmacen,
                        'idarticulo' => $idArticulo,
                        'fecha_vencimiento' => $fechaVencimiento,
                        'saldo_stock' => $cantidadEnUnidades,
                        'cantidad' => $cantidadEnUnidades,
                    ]);
                }
            } catch (Exception $e) {
                $this->errors[] = "Fila $rowNumber: error al procesar - " . $e->getMessage();
                $importacionExitosa = false;
            }

            $rowNumber++;
        }

        if ($importacionExitosa) {
            \DB::commit();
        } else {
            \DB::rollBack();
        }
    } catch (Exception $e) {
        \DB::rollBack();
        $importacionExitosa = false;
        $this->errors[] = "Error general: " . $e->getMessage();
    }

    return $this->getErrorsResponse($importacionExitosa);
}

private function getUnidadEnvase($idArticulo)
{
    if (!$idArticulo) {
        return 1.0;
    }

    $articulo = Articulo::find($idArticulo);
    if (!$articulo) {
        return 1.0;
    }

    $unidad = (float) ($articulo->unidad_envase ?? 1);

    // evitar multiplicar por 0
    return $unidad > 0 ? $unidad : 1.0;
}


private function getArticuloId($nombreArticulo)
{
    if (!$nombreArticulo) {
        return null;
    }
    $nombreArticulo = trim(strtolower($nombreArticulo));

    // $this->articuloMapping es [id => codigo], hacemos comparación case-insensitive
    foreach ($this->articuloMapping as $id => $codigo) {
        if (trim(strtolower($codigo)) === $nombreArticulo) {
            return $id;
        }
    }
    return null;
}
    private function getAlmacenId($nombreAlmacen)
    {
        $nombreAlmacen = trim(strtolower($nombreAlmacen));
        foreach ($this->almacenMapping as $id => $nombre) {
            if (trim(strtolower($nombre)) === $nombreAlmacen) {
                return $id;
            }
        }
        return null;
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
  private function normalizarFecha($fecha)
{
    // FORZAR 2099-12-31 para todas las filas (según tu pedido)
    return '2099-12-31';
}
}