<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Articulo;
use App\Categoria;
use App\Grupo;
use App\Proveedor;
use App\Persona;
use App\Medida;
use App\Marca;
use App\Industria;
use Illuminate\Support\Facades\Log;
use Exception; // Importa la clase Exception
use Normalizer;

class ArticuloImport implements ToCollection
{
    private $categoriaMapping;
    private $grupoMapping;
    private $proveedorMapping;
    private $medidaMapping;
    private $marcaMapping;
    private $industriaMapping;
    private $personaMapping;
    private $errors = [];

    public function __construct()
    {
        $this->categoriaMapping = $this->createCategoriaMapping();
        $this->grupoMapping = $this->createGrupoMapping();
        $this->proveedorMapping = $this->createProveedorMapping();
        $this->medidaMapping = $this->createMedidaMapping();
        $this->marcaMapping = $this->createMarcaMapping();
        $this->industriaMapping = $this->createIndustriaMapping();
        $this->personaMapping = $this->createPersonaMapping();
    }

    private function createCategoriaMapping()
    {
        // Obtener las categorías y guardar los nombres originales (sin convertir a mayúsculas)
        return Categoria::pluck('nombre', 'id')->toArray();
    }
    

    private function createPersonaMapping()
    {
        return Persona::pluck('nombre', 'id')->toArray();
    }

    private function createGrupoMapping()
    {
        return Grupo::pluck('nombre_grupo', 'id')->toArray();
    }

    private function createProveedorMapping()
    {
        return Proveedor::pluck('contacto', 'id')->toArray();
    }

    private function createMedidaMapping()
    {
        return Medida::pluck('descripcion_medida', 'id')->toArray();
    }

    private function createMarcaMapping()
    {
        return Marca::pluck('nombre', 'id')->toArray();
    }

    private function createIndustriaMapping()
    {
        return Industria::pluck('nombre', 'id')->toArray();
    }

    public function collection(Collection $rows)
    {
        $rowNumber = 1;
        $importacionExitosa = true;

        try {
            \DB::beginTransaction();

            $emptyRowWarningAdded = false;
            foreach ($rows as $row) {
                // Detectar y advertir sobre filas completamente vacías
                $isEmptyRow = true;
                foreach ($row as $cell) {
                    if (trim($cell) !== '') {
                        $isEmptyRow = false;
                        break;
                    }
                }
                if ($isEmptyRow && !$emptyRowWarningAdded) {
                    $this->errors[] = "El Excel contiene espacios vacíos. Verifique entrando al Excel, use la función 'Buscar' y elimine todas las filas vacías antes de importar.";
                    $emptyRowWarningAdded = true;
                }
                // También saltar si código, nombre o proveedor están vacíos
                if (
                    $isEmptyRow ||
                    !isset($row[0]) || trim($row[0]) === '' ||
                    !isset($row[1]) || trim($row[1]) === '' ||
                    !isset($row[3]) || trim($row[3]) === ''
                ) {
                    $rowNumber++;
                    continue;
                }

                $rowErrors = [];

                $idCategoria = $this->getCategoriaId($row[2]);
                $idProveedor = $this->getProveedorId($row[3]);

                if (!$idCategoria) {
                    $rowErrors[] = "Error fila $rowNumber: No existe 'Linea $row[2]'";
                }

                if (!$idProveedor) {
                    $rowErrors[] = "Error fila $rowNumber: El proveedor '$row[3]' no está registrado";
                }

                try {
                    // Verificar si ya existe un artículo con el mismo código (case-insensitive)
                    $codigoImport = trim($row[0]);
                    $articuloExistente = Articulo::whereRaw('LOWER(codigo) = ?', [mb_strtolower($codigoImport)])->first();
                    if ($articuloExistente) {
                        $rowErrors[] = "Error fila $rowNumber: Ya existe un registro con el codigo: '$codigoImport' y es el registro '" . $articuloExistente->nombre . "'";
                        $importacionExitosa = false;
                    } else {
                        Articulo::create([
                            'codigo' => $row[0],
                            'nombre' => $row[1],
                            'nombre_generico' => $row[1],
                            'idcategoria' => $idCategoria,
                            'idproveedor' => $idProveedor,
                            'descripcion_fabrica' => $row[4],
                            'unidad_envase' => $row[5],
                            'precio_list_unid' => $row[6],
                            'precio_costo_unid' => $row[6],
                            'precio_costo_paq' => $row[6],
                            'precio_venta' => $row[7],
                            'precio_uno' => $row[7],
                            'precio_dos' => $row[8],
                            'precio_tres' => 0.00,
                            'precio_cuatro' => 0.00,
                            'costo_compra' => $row[6],
                            'stock' => 10,
                            'condicion' => 1,
                            'vencimiento' => '1',
                            'fotografia' => null,
                            'idmedida' => 1,
                        ]);
                    }
                    //dd($row[9]);
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), "Integrity constraint violation: 1062") !== false) {
                        $rowErrors[] = "Error fila $rowNumber: El producto '$row[1]' ya existe";
                    } else {
                        $rowErrors[] = "Error al procesar fila: " . $e->getMessage();
                    }

                    $importacionExitosa = false;
                }

                if (!empty($rowErrors)) {
                    $this->errors = array_merge($this->errors, $rowErrors);
                }

                $rowNumber++;
            }

            if ($importacionExitosa) {
                \DB::commit(); // Confirmar la transacción si no hay errores
            } else {
                \DB::rollBack(); // Revertir la transacción en caso de error
            }
        } catch (Exception $e) {
            \DB::rollBack(); // Revertir la transacción en caso de error
            $importacionExitosa = false;
            $this->errors[] = "Error al procesar fila: " . $e->getMessage();
        }

        return $this->getErrorsResponse($importacionExitosa);
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

    // Normaliza y elimina diacríticos para comparar textos
    private function normalizeText($text)
    {
        $text = mb_strtolower(trim($text));
        if (class_exists('Normalizer')) {
            $text = Normalizer::normalize($text, Normalizer::FORM_D);
            $text = preg_replace('/\p{Mn}/u', '', $text); // Elimina diacríticos
        }
        return $text;
    }

    private function getCategoriaId($nombreCategoria)
    {
        if ($nombreCategoria === null) return null;
        $nombreCategoriaNorm = $this->normalizeText($nombreCategoria);
        foreach ($this->categoriaMapping as $id => $nombre) {
            if ($this->normalizeText($nombre) === $nombreCategoriaNorm) {
                return $id;
            }
        }
        return null;
    }

    private function getGrupoId($nombreGrupo)
    {
        if ($nombreGrupo === null) return null;
        $nombreGrupoNorm = $this->normalizeText($nombreGrupo);
        foreach ($this->grupoMapping as $id => $nombre) {
            if ($this->normalizeText($nombre) === $nombreGrupoNorm) {
                return $id;
            }
        }
        return null;
    }

    private function getProveedorId($nombreProveedor)
    {
        if ($nombreProveedor === null) return null;
        $nombreProveedorTrim = trim($nombreProveedor);
        $nombreProveedorLower = mb_strtolower($nombreProveedorTrim);
        // Buscar en el mapping existente
        foreach ($this->personaMapping as $id => $nombre) {
            if (mb_strtolower(trim($nombre)) === $nombreProveedorLower) {
                return $id;
            }
        }
        // Si no existe, crear en Persona y Proveedor
        $persona = new \App\Persona();
        $persona->nombre = $nombreProveedorTrim;
        $persona->save();
        $proveedor = new \App\Proveedor();
        $proveedor->id = $persona->id;
        $proveedor->contacto = $nombreProveedorTrim;
        $proveedor->save();
        // Actualizar el mapping para futuras búsquedas en la misma importación
        $this->personaMapping[$persona->id] = $nombreProveedorTrim;
        return $persona->id;
    }

    private function getMedidaId($descripcionMedida)
    {
        if ($descripcionMedida === null) return null;
        $descripcionMedida = mb_strtolower(trim($descripcionMedida));
        foreach ($this->medidaMapping as $id => $nombre) {
            if (mb_strtolower(trim($nombre)) === $descripcionMedida) {
                return $id;
            }
        }
        return null;
    }

    private function getMarcaId($nombreMarca)
    {
        if ($nombreMarca === null) return null;
        $nombreMarca = mb_strtolower(trim($nombreMarca));
        foreach ($this->marcaMapping as $id => $nombre) {
            if (mb_strtolower(trim($nombre)) === $nombreMarca) {
                return $id;
            }
        }
        return null;
    }

    private function getIndustriaId($nombreIndustria)
    {
        if ($nombreIndustria === null) return null;
        $nombreIndustria = mb_strtolower(trim($nombreIndustria));
        foreach ($this->industriaMapping as $id => $nombre) {
            if (mb_strtolower(trim($nombre)) === $nombreIndustria) {
                return $id;
            }
        }
        return null;
    }
}
