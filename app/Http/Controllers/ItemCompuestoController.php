<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;
use App\Exports\ProductExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Inventario;
use App\Articulo;
use App\Imports\ArticuloImport;
use App\Precio;
use App\ItemCompuesto;

class ItemCompuestoController extends Controller
{
     public function indexCompuesto(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $buscar = $request->buscar;
        $criterio = $request->criterio;

        if ($buscar == '') {
            $articulos = Articulo::join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
                ->join('medidas', 'articulos.idmedida', '=', 'medidas.id')
                ->select(
                    'articulos.id',
                    'articulos.idcategoria',
                    'articulos.idmedida',
                    'articulos.codigo',
                    'articulos.nombre',
                    'categorias.nombre as nombre_categoria',
                    'categorias.codigoProductoSin',
                    'categorias.actividadEconomica',
                    'medidas.descripcion_medida',
                    'articulos.precio_uno',
                    'articulos.precio_dos',
                    'articulos.precio_tres',
                    'articulos.precio_cuatro',
                    'articulos.precio_venta',
                    'articulos.descripcion',
                    'articulos.condicion',
                    'articulos.fotografia',
                    'medidas.codigoClasificador',
                    'articulos.descripcion_fabrica'
                )
                ->where('articulos.condicion', 1)
                ->where('articulos.tipo_producto', 'C')
                ->distinct()
                ->orderBy('articulos.id', 'asc')
                ->get();
        } else {
            $articulos = Articulo::join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
                ->join('medidas', 'articulos.idmedida', '=', 'medidas.id')
                ->select(
                    'articulos.id',
                    'articulos.idcategoria',
                    'articulos.idmedida',
                    'articulos.codigo',
                    'articulos.nombre',
                    'categorias.nombre as nombre_categoria',
                    'categorias.codigoProductoSin',
                    'categorias.actividadEconomica',
                    'medidas.descripcion_medida',
                    'articulos.precio_uno',
                    'articulos.precio_dos',
                    'articulos.precio_tres',
                    'articulos.precio_cuatro',
                    'articulos.precio_venta',
                    'articulos.descripcion',
                    'articulos.condicion',
                    'articulos.fotografia',
                    'medidas.codigoClasificador',
                    'articulos.descripcion_fabrica'
                )
                ->where(function ($query) use ($buscar) {
                    $query->where('articulos.nombre', 'like', '%' . $buscar . '%')
                        ->orWhere('articulos.descripcion', 'like', '%' . $buscar . '%')
                        ->orWhere('articulos.codigo', 'like', '%' . $buscar . '%')
                        ->orWhere('categorias.nombre', 'like', '%' . $buscar . '%');
                })
                ->where('articulos.condicion', 1)
                ->where('articulos.tipo_producto', 'C')
                ->distinct()
                ->orderBy('articulos.id', 'asc')
                ->get();
        }

        return [
            'articulos' => $articulos
        ];
    }

    public function storeCompuesto(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        // Buscar el último código válido tipo COM-#
        $ultimoArticulo = Articulo::where('codigo', 'like', 'COM-%')
            ->select(DB::raw('MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)) as max_codigo'))
            ->first();

        $ultimoNumero = $ultimoArticulo && $ultimoArticulo->max_codigo ? intval($ultimoArticulo->max_codigo) : 0;
        $nuevoNumero = $ultimoNumero + 1;
        $codigoGenerado = 'COM-' . $nuevoNumero;

        $articulo = new Articulo();
        $articulo->idcategoria = $request->idcategoria;
        $articulo->idmedida = 1;
        $articulo->codigo = $codigoGenerado;
        $articulo->nombre = $request->nombre;
        $articulo->precio_venta = $request->precio_uno;
        $articulo->precio_uno = $request->precio_uno;
        $articulo->precio_dos = 0.00;
        $articulo->precio_tres = $request->precio_tres;
        $articulo->precio_cuatro = $request->precio_cuatro;
        $articulo->descripcion = $request->descripcion;
        $articulo->tipo_producto = 'C';
        $articulo->condicion = '1';
        $articulo->unidad_envase = 1;
        $articulo->precio_costo_unid = 0.00;
        $articulo->precio_costo_paq = 0.00;
        $articulo->stock = 1;
        $articulo->costo_compra = 0.00; //new
        $articulo->descripcion_fabrica = '1';
        $articulo->save();
        // Guardar los productos seleccionados en itemcompuesto
        $productos = $request->productos;
        if (!is_array($productos)) {
            $productos = json_decode($productos, true);
        }
        foreach ($productos as $producto) {
            if (is_array($producto) && isset($producto['id'])) {
                ItemCompuesto::create([
                    'idarticulo' => $articulo->id,
                    'iditem' => $producto['id'],
                    'cantidad' => isset($producto['cantidad']) ? $producto['cantidad'] : 1,
                ]);
            } elseif ($producto) {
                // Compatibilidad con el formato anterior (solo id)
                ItemCompuesto::create([
                    'idarticulo' => $articulo->id,
                    'iditem' => $producto,
                    'cantidad' => 1,
                ]);
            }
        }

        return ['idArticulo' => $articulo->id];
    }

    public function updateCompuesto(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        try {
            DB::beginTransaction();

            $articulo = Articulo::findOrFail($request->id);
            $articulo->idcategoria = $request->idcategoria;
            $articulo->nombre = $request->nombre;
            $articulo->precio_venta = $request->precio_venta;
            $articulo->precio_uno = $request->precio_uno;
            $articulo->precio_dos = $request->precio_dos;
            $articulo->precio_tres = $request->precio_tres;
            $articulo->precio_cuatro = $request->precio_cuatro;
            $articulo->descripcion = $request->descripcion;
            $articulo->save();

            // Eliminar todos los productos compuestos actuales
            ItemCompuesto::where('idarticulo', $articulo->id)->delete();

            // Insertar los nuevos productos seleccionados
            $productos = $request->productos;
            if (!is_array($productos)) {
                $productos = json_decode($productos, true);
            }
            foreach ($productos as $producto) {
                if (is_array($producto) && isset($producto['id'])) {
                    ItemCompuesto::create([
                        'idarticulo' => $articulo->id,
                        'iditem' => $producto['id'],
                        'cantidad' => isset($producto['cantidad']) ? $producto['cantidad'] : 1,
                    ]);
                } elseif ($producto) {
                    // Compatibilidad con el formato anterior (solo id)
                    ItemCompuesto::create([
                        'idarticulo' => $articulo->id,
                        'iditem' => $producto,
                        'cantidad' => 1,
                    ]);
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            // Puedes agregar un log aquí para depurar
        }
    }

    public function getItemsCompuestosDetalle($idarticulo)
    {
        // Trae los productos relacionados con el artículo compuesto, incluyendo nombre, categoría y proveedor
        $items = ItemCompuesto::where('itemcompuesto.idarticulo', $idarticulo)
            ->join('articulos', 'itemcompuesto.iditem', '=', 'articulos.id')
            ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->join('proveedores', 'articulos.idproveedor', '=', 'proveedores.id')
            ->join('personas', 'proveedores.id', '=', 'personas.id')
            ->join('medidas', 'articulos.idmedida', '=', 'medidas.id')
            ->select(
                'itemcompuesto.id as id_item_compuesto', // ✅ ID de la relación
                'articulos.id',
                'articulos.codigo',
                'articulos.nombre',
                'articulos.precio_uno',
                'categorias.nombre as categoria',
                'personas.nombre as proveedor',
                'articulos.descripcion',
                'categorias.codigoProductoSin',
                'categorias.actividadEconomica',
                'medidas.codigoClasificador'
            )
            ->get();

        return response()->json($items);
    }

    public function getItemsCompuestos($idarticulo)
{
    // 1. Obtener el artículo compuesto (padre)
    $articulo = Articulo::find($idarticulo);

    // 2. Obtener los productos relacionados
    $items = ItemCompuesto::where('idarticulo', $idarticulo)
        ->join('articulos', 'itemcompuesto.iditem', '=', 'articulos.id')
        ->leftJoin('categorias', 'articulos.idcategoria', '=', 'categorias.id')
        ->select(
            'articulos.id',
            'articulos.codigo',
            'articulos.nombre',
            'articulos.descripcion',
            'articulos.precio_uno',
            'articulos.precio_costo_unid',
            'itemcompuesto.cantidad',
            'categorias.nombre as nombre_categoria'
        )
        ->get();

    return response()->json([
        'nombre_compuesto' => $articulo ? $articulo->nombre : null,
        'items' => $items
    ]);
}

    public function verificarStockCompuesto(Request $request)
    {
        $idCompuesto = $request->input('idarticulo');
        $cantidad = $request->input('cantidad', 1);
        $idAlmacen = $request->input('idalmacen'); // opcional, si manejas stock por almacén

        // Obtener los items hijos y la cantidad requerida de cada uno
        $items = ItemCompuesto::where('idarticulo', $idCompuesto)
            ->get(['iditem', 'cantidad']);

        $faltantes = [];
        foreach ($items as $item) {
            $iditem = $item->iditem;
            $cantidadPorCompuesto = $item->cantidad;
            $requerido = $cantidad * $cantidadPorCompuesto;
            // Si manejas stock por almacén, consulta en inventarios, si no, en articulos
            if ($idAlmacen) {
                $stock = \DB::table('inventarios')
                    ->where('idarticulo', $iditem)
                    ->where('idalmacen', $idAlmacen)
                    ->sum('saldo_stock');
            } else {
                $stock = \DB::table('articulos')
                    ->where('id', $iditem)
                    ->value('stock');
            }
            $nombreItem = \DB::table('articulos')->where('id', $iditem)->value('nombre');

            if ($stock < $requerido) {
                $faltantes[] = [
                    'iditem' => $iditem,
                    'nombre_item' => $nombreItem,
                    'stock' => $stock,
                    'requerido' => $requerido
                ];
            }
        }
        if (count($faltantes) > 0) {
            return response()->json([
                'success' => false,
                'faltantes' => $faltantes
            ]);
        } else {
            return response()->json([
                'success' => true
            ]);
        }
    }
}