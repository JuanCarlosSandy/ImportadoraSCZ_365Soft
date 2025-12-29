<template>
    <main class="main">
    <!-- Breadcrumb -->
    <ol class="breadcrumb">
        
    </ol>
    <div class="container-fluid">
        <!-- Ejemplo de tabla Listado -->
        <div class="card">
            <div class="card-header">
                <i class="fa fa-align-justify"></i> Reporte de Inventarios
            </div>
            <div class="card-body">
                <div class="form-group row">
                    <div class="col-md-6">
                        <select class="form-control" v-model="AlmacenSeleccionado" @change="getDatosAlmacen">
                            <option value="0" disabled>Seleccione</option>
                            <option v-for="opcion in arrayAlmacenes" :key="opcion.id" :value="opcion.id">{{ opcion.nombre_almacen }}</option>
                        </select>
                        <button type="submit" @click="generarReporte" class="btn btn-primary">
                            <i class="fa fa-search"></i> Generar Reporte
                        </button>
                    </div>
                </div>
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Unidad X Paq.</th>
                            <th>Stock</th>
                            <th>Almacen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="inventario in arrayInventario" :key="inventario.id">
                            <td v-text="inventario.nombre_producto"></td>
                            <td v-text="inventario.unidad_envase"></td>
                            <td v-text="inventario.saldo_stock_total"></td>
                            <td v-text="inventario.nombre_almacen"></td>
                        </tr>
                    </tbody>
                </table>

                <div class="d-flex justify-content-between mt-3">
                    <button @click="exportarPDF" class="btn btn-danger">Exportar a PDF</button>
                    <button @click="exportarExcel" class="btn btn-success">Exportar a Excel</button>
                </div>
                <nav>
                    <ul class="pagination">
                        <li class="page-item" v-if="pagination.current_page > 1">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page - 1,buscar,criterio)">Ant</a>
                        </li>
                        <li class="page-item" v-for="page in pagesNumber" :key="page" :class="[page == isActived ? 'active' : '']">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(page,buscar,criterio)" v-text="page"></a>
                        </li>
                        <li class="page-item" v-if="pagination.current_page < pagination.last_page">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(pagination.current_page + 1,buscar,criterio)">Sig</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        <!-- Fin ejemplo de tabla Listado -->
    </div>
</main>
</template>

<script>

import jsPDF from 'jspdf';
import 'jspdf-autotable';
import * as XLSX from 'xlsx-js-style';
//import XLSXPopulate from 'xlsx-populate';

export default {
data (){
    return {
        rol_id: 0,

        totalGanado: 0,
        modal : 0,
        tituloModal : '',
        tipoAccion : 0,
        AlmacenSeleccionado: 1,
        almacenSeleccionado: 1,
        idalmacen: 0,
        arrayAlmacenes: [],
        arrayInventario:[],
        pagination : {
            'total' : 0,
            'current_page' : 0,
            'per_page' : 0,
            'last_page' : 0,
            'from' : 0,
            'to' : 0,
        },
        offset : 3,
        criterio : '',
        buscar : ''
    }
},
computed:{
    isActived: function(){
        return this.pagination.current_page;
    },
    //Calcula los elementos de la paginación
    pagesNumber: function() {
        if(!this.pagination.to) {
            return [];
        }
        
        var from = this.pagination.current_page - this.offset; 
        if(from < 1) {
            from = 1;
        }

        var to = from + (this.offset * 2); 
        if(to >= this.pagination.last_page){
            to = this.pagination.last_page;
        }  

        var pagesArray = [];
        while(from <= to) {
            pagesArray.push(from);
            from++;
        }
        return pagesArray;             

    }
},
methods : {

    selectAlmacen() {
                let me = this;
                var url = '/almacen/selectAlmacen';
                axios.get(url).then(function (response) {
                    var respuesta = response.data;
                    me.arrayAlmacenes = respuesta.almacenes;
                    console.log('ALMACEN:',me.arrayAlmacenes);
                })
                .catch(function (error) {
                    console.log(error);
                });
            },
    getDatosAlmacen() {
                let me = this;
                if (me.AlmacenSeleccionado !== '') {
                    me.loading = true;
                    me.almacenSeleccionado = me.AlmacenSeleccionado; // Almacenar el valor seleccionado
                    me.idalmacen = Number(me.AlmacenSeleccionado);
                    console.log('IDalmacen: ' + me.idalmacen);
                    
                }
    },
    generarReporte() {
        let me = this;
        var url = '/reporte-almacen?&idAlmacen=' + me.almacenSeleccionado;
        axios.get(url)
                .then(function (response) {
                    if ('mensaje' in response.data && response.data.mensaje === 'No existe articulos en el almacen seleccionado') {
                    swal("Ningun Articulo", "El almacen seleccionado no tiene articulos", "info");
                    me.arrayInventario=[];
                    }else{
                        var respuesta = response.data;
                        console.log("ARRAy:",respuesta);
                        me.arrayInventario = respuesta.inventarios;
                        console.log('LLEGA:',me.arrayInventario);
                    }
                })
                .catch(function (error) {
                    console.log(error);
                });
    },

    exportarPDF() {
    const pdf = new jsPDF();

    pdf.setFont('helvetica');
    const titulo = 'Reporte de Inventarios';
    const fecha = `Fecha: ${new Date().toLocaleDateString('es-ES', { year: 'numeric', month: 'numeric', day: 'numeric' })}`;
    pdf.setFont('helvetica');

    const pageSize = pdf.internal.pageSize;
    const pageWidth = pageSize.width;
    const textWidth = pdf.getStringUnitWidth(titulo) * pdf.internal.getFontSize() / pdf.internal.scaleFactor;

    const xPosition = (pageWidth - textWidth) / 2;

    pdf.text(titulo, xPosition, 10);
    pdf.text(fecha, 15, 20);

    const tableYPosition = 30;

    const columns = ['Producto', 'Unidad X Paq.', 'Stock', 'Almacen'];
    const rows = this.arrayInventario.map(inventario => [inventario.nombre_producto, inventario.unidad_envase, inventario.saldo_stock_total, inventario.nombre_almacen]);
    pdf.autoTable({ head: [columns], body: rows, startY: tableYPosition });

    pdf.setFont('helvetica');
    pdf.save('reporte_inventarios.pdf');
},

exportarExcel() {
    const workbook = XLSX.utils.book_new();

    const worksheet = XLSX.utils.aoa_to_sheet([]);

    const startRow = 5;

    worksheet['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 3} }];
    worksheet['A1'] = { t: 's', v: 'REPORTE DE INVENTARIO', s: { 
        font: { sz: 16, bold: true, color: { rgb: 'FFFFFF' } },
        alignment: { horizontal: 'center', vertical: 'center' },
        fill: { fgColor: { rgb: '3669a8' } } } };


    const fechaStyle = { font: { bold: true, color: { rgb: '000000' } }, border: { top: { style: 'thin', color: { auto: 1 } }, right: { style: 'thin', color: { auto: 1 } }, bottom: { style: 'thin', color: { auto: 1 } }, left: { style: 'thin', color: { auto: 1 } } } };
    worksheet['A2'] = { t: 's', v: `Fecha: ${new Date().toLocaleDateString('es-ES', { year: 'numeric', month: 'numeric', day: 'numeric' })}`, s: fechaStyle };

    worksheet['!merges'].push({ s: { r: 1, c: 0 }, e: { r: 2, c: 3} });

    const headerStyle = { font: { bold: true, color: { rgb: 'FFFFFF' } }, fill: { fgColor: { rgb: '3669a8' } } };
    const headers = ['Producto', 'Unidad X Paq.', 'Stock', 'Almacen'];


    headers.forEach((header, index) => {
        worksheet[XLSX.utils.encode_cell({ r: 3, c: index })] = { t: 's', v: header, s: headerStyle };
    });

    for (let i = 0; i < this.arrayInventario.length; i++) {
        const rowData = Object.values(this.arrayInventario[i]);
        XLSX.utils.sheet_add_aoa(worksheet, [rowData], { origin: `A${startRow + i}` });
    }

    const columnWidths = [
        { wch: 27.8 },
        { wch: 16.0 },   
        { wch: 18.6 },
        { wch: 15.2 }
    ];
    worksheet['!cols'] = columnWidths;

    XLSX.utils.book_append_sheet(workbook, worksheet, 'Reporte inventarios');

    XLSX.writeFile(workbook, 'reporte_inventario.xlsx');
    },
},
mounted() {
    this.selectAlmacen();
    
}
}
</script>
<style>    
.modal-content{
width: 100% !important;
position: absolute !important;
}
.mostrar{
display: list-item !important;
opacity: 1 !important;
position: absolute !important;
background-color: #3c29297a !important;
}
.div-error{
display: flex;
justify-content: center;
}
.text-error{
color: red !important;
font-weight: bold;
}
</style>
