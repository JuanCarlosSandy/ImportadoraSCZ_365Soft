<template>
    <main class="main">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="/">Escritorio</a></li>
        </ol>

        <div class="container-fluid">

            <div class="card">
                <div class="card-header">
                    <i class="fa fa-align-justify"></i> Recibo de Clientes por Documento
                    <button type="button" @click="abrirModal()" class="btn btn-primary" >
                        <i class="fa fa-search"></i>&nbsp;Filtros</button>
                    <button type="button" @click="exportarExcel()" class="btn btn-success">
                        <i class="icon-doc"></i>&nbsp;Exportar a Excel
                    </button>
                    <button type="button" @click="exportarPDF()" class="btn btn-danger">
                        <i class="icon-doc"></i>&nbsp;Exportar a PDF
                    </button>
                </div>
            
                <div class="card-body">
                
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>NUM. COMPROB.</th>
                                    <th>SUCURSAL</th>
                                    <th>VENDEDOR</th>
                                    <th>CLIENTE</th>
                                    <th>FECHA</th>
                                    <th>TIPO VENTA</th>
                                    <th>TIPO PAGO</th>
                                    <th>TIPO CAMBIO</th>
                                    <th>IMPORTE US</th>
                                    <th>IMPORTE Bs</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="venta in arrayDetalles">
                                    <td>{{ venta.num_comprobante }}</td>
                                    <td>{{ venta.nombre_sucursal }}</td>
                                    <td>{{ venta.nombre_vendedor }}</td>
                                    <td>{{ venta.nombre_cliente }}</td>
                                    <td>{{ venta.fecha_hora }}</td>
                                    <td>{{ venta.nombre_tipo_ventas }}</td>
                                    <td>{{ venta.nombre_tipo_pago }}</td>
                                    <td>{{ valor_moneda_principal }}</td>
                                    <td>{{ venta.venta_final }}</td>
                                    <td>{{ venta.importe_calculado }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                
                    <h5 v-if="arrayDetalles.length == 0" class="text-danger">Seleccione un cliente para ver datos.</h5>
                </div>

                <nav>
                    <ul class="pagination">
                        <li class="page-item" v-if="pagination.current_page > 1">
                            <a class="page-link" href="#"
                                @click.prevent="cambiarPagina(pagination.current_page - 1)">Ant</a>
                        </li>
                        <li class="page-item" v-for="page in pagesNumber" :key="page"
                            :class="[page == isActived ? 'active' : '']">
                            <a class="page-link" href="#" @click.prevent="cambiarPagina(page)"
                                v-text="page"></a>
                        </li>
                        <li class="page-item" v-if="pagination.current_page < pagination.last_page">
                            <a class="page-link" href="#"
                                @click.prevent="cambiarPagina(pagination.current_page + 1)">Sig</a>
                        </li>
                    </ul>
                </nav>
            
            </div>
        </div>

        <!-- contenido del modal -->
        <div class="modal" tabindex="-1" :class="{ 'mostrar': modal }" role="dialog" aria-labelledby="myModalLabel" style="display: none;" aria-hidden="true">
            <div class="modal-dialog modal-primary modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Filtros</h4>
                        <button type="button" class="close" @click="cerrarModal()" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="row">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="" class="font-weight-bold">Cliente
                                        <span class="text-danger">*</span>
                                    </label>
                                    <v-select :on-search="selectCliente" label="num_documento" :options="arrayCliente" placeholder="Num de documento ..." :onChange="getDatosCliente" @input="validarCliente">
                                    </v-select>
                                    <small v-if="clienteId">{{ nombre_cliente }}</small>
                                    <small v-if="!clienteId" class="text-danger">Selecciona un cliente.</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Vendedor
                                        <span class="text-danger">*</span>
                                    </label>
                                    <v-select :on-search="selectVendedor" label="nombre" :options="arrayEjecutivos" placeholder="Buscar encargado..." :onChange="getDatosVendedor">
                                    </v-select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="" class="font-weight-bold">Fecha Inicio: <span class="text-danger">*</span> </label>
                                    <input class="form-control" type="date" v-model="fechaInicio" >
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">    
                                    <label for="" class="font-weight-bold">Fecha Fin: <span class="text-danger">*</span></label>
                                    <input class="form-control" type="date" v-model="fechaFin">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Sucursal
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select v-model="sucursalId" class="form-control">
                                        <option value="0" disabled>Seleccione</option>
                                        <option value="todos">*TODOS*</option>
                                        <option v-for="sucursal in arraySucursales" :key="sucursal.id" :value="sucursal.id" v-text="sucursal.nombre">
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Tipo Pago
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select v-model="tipoPagoId" class="form-control">
                                        <option value="0" disabled>Seleccione</option>
                                        <option value="todos">*TODOS*</option>
                                        <option v-for="tipoPago in arrayTipoPagos" :key="tipoPago.id" :value="tipoPago.id" v-text="tipoPago.nombre_tipo_pago">
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Estado Venta
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select v-model="ventaEstado" class="form-control">
                                        <option value="0" disabled>Seleccione</option>
                                        <option value="todos">*TODOS*</option>
                                        <option value="Registrado">Registrado</option>
                                        <option value="Anulado">Anulado</option>
                                        <option value="Pendiente">Pendiente</option>
                                    </select>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" @click="cerrarModal()">Cerrar</button>
                        <button type="button" class="btn btn-primary" @click="generarReporte(1, 0); generarReporte(1, 1)">Generar reporte</button>
                    </div>
                </div>
            </div>
        </div>

    </main>
</template>

<script>
import vSelect from 'vue-select';

import jsPDF from 'jspdf';
import 'jspdf-autotable';
import * as XLSX from 'xlsx-js-style';

export default {
    data() {
        return {
            pagination: {
                'total': 0,
                'current_page': 0,
                'per_page': 0,
                'last_page': 0,
                'from': 0,
                'to': 0,
            },
            offset: 3,

            //moneda
            valor_moneda_principal: 0,
            simbolo_moneda_principal: '',

            arraySucursales : [],
            arrayEjecutivos: [],
            arrayCliente: [],
            arrayDetalles: [],
            arrayDetallesReporte: [],
            arrayTipoPagos: [],

            modal: 0,

            nombre_cliente: '',
            vendedorId: 'todos',
            sucursalId: 'todos',
            tipoPagoId: 'todos',
            ventaEstado: 'todos',
            clienteId: false,
            fechaInicio: '2024-01-01',
            fechaFin: '2024-06-30',
        }

    },
    components: {
        vSelect
    },
    computed: {
        isActived: function () {
            return this.pagination.current_page;
        },
        pagesNumber: function () {
            if (!this.pagination.to) {
                return [];
            }

            var from = this.pagination.current_page - this.offset;
            if (from < 1) {
                from = 1;
            }

            var to = from + (this.offset * 2);
            if (to >= this.pagination.last_page) {
                to = this.pagination.last_page;
            }

            var pagesArray = [];
            while (from <= to) {
                pagesArray.push(from);
                from++;
            }
            return pagesArray;
        },
    },
    methods: {
        validarCliente() {
            this.clienteId = this.arrayCliente && this.arrayCliente.length > 0;
        },

        abrirModal() {
            this.modal = 1;
        },

        cerrarModal() {
            this.modal = 0;
            this.sucursalId = 'todos';
            this.tipoPagoId = 'todos',
            this.ventaEstado = 'todos',
            this.fechaInicio = '2024-01-01';
            this.fechaFin = '2024-06-30';
            console.log('cerrarndo el modal');
        },

        datosConfiguracion() {
            let me = this;
            var url = '/configuracion';

            axios.get(url).then(function (response) {
                var respuesta = response.data;

                me.valor_moneda_principal = respuesta.configuracionTrabajo.valor_moneda_principal;
                me.simbolo_moneda_principal = respuesta.configuracionTrabajo.simbolo_moneda_principal;
            })
            .catch(function (error) {
                console.log(error);
            });
        },

        generarReporte(page, condicion) {
            let me = this;

            if (me.clienteId != false) {
                var url = '/recibo-cliente-por-documento?page='+ page + '&tipoCambio=' + this.valor_moneda_principal + '&vendedorId=' + this.vendedorId + '&sucursalId=' + this.sucursalId +
                    '&clienteId=' + this.clienteId + '&fechaInicio=' + this.fechaInicio + '&fechaFin=' + this.fechaFin + '&tipoPagoId=' + this.tipoPagoId + '&estado=' +
                    this.ventaEstado + '&condicion=' + condicion;

                if (condicion == 0) {
                    axios.get(url).then(function (response) {
                        var respuesta = response.data;
                        
                        me.arrayDetalles = respuesta.detalles.data;
                        me.pagination = respuesta.pagination;
                    })
                    .catch(function (error) {
                        console.log(error);
                    })

                    this.cerrarModal();
                }
                
                if (condicion == 1) {
                    axios.get(url).then(function (response) {
                        var respuesta = response.data;
                        me.arrayDetallesReporte = respuesta.detalles;
                        console.log('array reporte:', me.arrayDetallesReporte);
                    })
                    .catch(function (error) {
                        console.log(error);
                    });
                }
            }
        },

        cambiarPagina(page) {
            let me = this;
            me.pagination.current_page = page;
            me.generarReporte(page, 0);
        },

        selectSucursal() {
            let me = this;
            var url = '/sucursal/selectSucursal';
            axios.get(url).then(function (response) {
                var respuesta = response.data;
                me.arraySucursales = respuesta.sucursales;
                console.log('SUCURSAL',me.arraySucursales);
            })
            .catch(function (error) {
                console.log(error);
            });
        },

        selectTipoPago() {
            let me = this;
            var url = '/tipoPago/selectTipoPago';
            axios.get(url).then(function (response) {
                var respuesta = response.data;
                me.arrayTipoPagos = respuesta.tipo_pagos;
                console.log('TIPOS PAGOS:', me.arrayTipoPagos);
            })
            .catch(function (error) {
                console.log(error);
            });
        },

        selectCliente(numero) {
            let me = this;
            var url = '/cliente/selectClientePorNumero?numero=' + numero;
            axios.get(url).then(function (response) {
                let respuesta  = response.data;
                q: numero
                me.arrayCliente = respuesta.clientes;
                console.log(me.arrayCliente);
            })
            .catch(function (error) {
                console.log(error);
            });
        },

        //selectCliente2(search) {
        //    let me = this;
        //    var url = '/cliente/selectCliente?filtro=' + search;
        //    axios.get(url).then(function (response) {
        //        let respuesta = response.data;
        //        q: search
        //        me.arrayCliente = respuesta.clientes;
        //        console.log(me.arrayCliente);
        //    })
        //    .catch(function (error) {
        //        console.log(error);
        //    });
        //},

        getDatosCliente(val1) {
            let me = this;
            me.loading = true;
            me.clienteId = val1.id;
            me.nombre_cliente = val1.nombre;
            console.log(this.clienteId);
            console.log(this.nombre_cliente);
        },

        selectVendedor(search) {
            let me = this;
            let url = '/user/selectUser/filter?filtro=' + search;
            axios.get(url).then(function (response) {
                let respuesta = response.data;
                me.arrayEjecutivos = respuesta.usuarios;
                console.log(me.arrayEjecutivos)
            })
            .catch(function (error) {
                console.log(error);
            });
        },

        getDatosVendedor(val1) {
            let me = this;
            me.loading = true;
            me.vendedorId = val1.id;
            console.log(this.vendedorId);
        },

        // REPORTES
        exportarPDF() {
            if (this.arrayDetallesReporte.length != 0) {
                const pdf = new jsPDF({
                    orientation: 'landscape'
                });

                pdf.setFont('helvetica');
                const titulo = 'Recibo de Clientes Por Documento';
                const fecha = `Fecha: ${new Date().toLocaleDateString('es-ES', { year: 'numeric', month: 'numeric', day: 'numeric' })}`;
                pdf.setFont('helvetica');

                const pageSize = pdf.internal.pageSize;
                const pageWidth = pageSize.width;
                const textWidth = pdf.getStringUnitWidth(titulo) * pdf.internal.getFontSize() / pdf.internal.scaleFactor;

                const xPosition = (pageWidth - textWidth) / 2;

                pdf.text(titulo, xPosition, 10);
                pdf.text(fecha, 15, 20);

                const tableYPosition = 30;

                const columns = ['Num. Comprobante', 'Tipo Comprobante', 'Sucursal', 'Vendedor', 'Cliente', 'Fecha Venta',
                'Tipo Venta', 'Tipo Pago', 'Tipo Cambio', 'Importe US', 'Importe Bs'];

                const rows = this.arrayDetallesReporte.map(datos => [datos.num_comprobante, datos.tipo_comprobante, datos.nombre_sucursal, datos.nombre_vendedor, 
                    datos.nombre_cliente, datos.fecha_hora, datos.nombre_tipo_ventas, datos.nombre_tipo_pago, this.valor_moneda_principal, datos.venta_final, datos.importe_calculado]);

                pdf.autoTable({ head: [columns], body: rows, startY: tableYPosition });

                pdf.setFont('helvetica');
                pdf.save('recibo_clientes_por_documento.pdf');
            }
        },

        exportarExcel() {
            if (this.arrayDetallesReporte.length != 0) {
                const workbook = XLSX.utils.book_new();
                const worksheet = XLSX.utils.aoa_to_sheet([]);

                const startRow = 6;

                worksheet['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 3 } }];
                worksheet['A1'] = { t: 's', v: 'RECIBO DE CLIENTES POR DOCUMENTO', s: { 
                    font: { sz: 16, bold: true, color: { rgb: 'FFFFFF' } },
                    alignment: { horizontal: 'center', vertical: 'center' },
                    fill: { fgColor: { rgb: '3669a8' } } } };

                const fechaStyle = { font: { bold: true, color: { rgb: '000000' } }, border: { top: { style: 'thin', color: { auto: 1 } }, right: { style: 'thin', color: { auto: 1 } }, bottom: { style: 'thin', color: { auto: 1 } }, left: { style: 'thin', color: { auto: 1 } } } };
                worksheet['A2'] = { t: 's', v: `Fecha: ${new Date().toLocaleDateString('es-ES', { year: 'numeric', month: 'numeric', day: 'numeric' })}`, s: fechaStyle };

                const headers = ['Num. Comprobante', 'Tipo Comprobante', 'Sucursal', 'Vendedor', 'Cliente', 'Fecha Venta',
                'Tipo Venta', 'Tipo Pago', 'Tipo Cambio', 'Importe US', 'Importe Bs'];
                const headerStyle = { font: { bold: true, color: { rgb: 'FFFFFF' } }, fill: { fgColor: { rgb: '3669a8' } } };

                headers.forEach((header, index) => {
                    worksheet[XLSX.utils.encode_cell({ r: 4, c: index })] = { t: 's', v: header, s: headerStyle };
                });

                for (let i = 0; i < this.arrayDetallesReporte.length; i++) {
                    const { num_comprobante, tipo_comprobante, nombre_sucursal, nombre_vendedor, nombre_cliente, fecha_hora,
                    nombre_tipo_ventas, nombre_tipo_pago, valor_moneda_principal, venta_final, importe_calculado } = this.arrayDetallesReporte[i];

                    const rowData = [num_comprobante, tipo_comprobante, nombre_sucursal, nombre_vendedor, nombre_cliente, fecha_hora, 
                        nombre_tipo_ventas, nombre_tipo_pago, this.valor_moneda_principal, venta_final, importe_calculado];
                    
                        XLSX.utils.sheet_add_aoa(worksheet, [rowData], { origin: `A${startRow + i}` });
                }

                const columnWidths = [
                    { wch: 22 },
                    { wch: 20 },
                    { wch: 17 },
                    { wch: 20 },
                    { wch: 22 },
                    { wch: 20 },
                    { wch: 14 },
                    { wch: 12 },
                    { wch: 16 },
                    { wch: 12 },
                    { wch: 20 },
                ];
                worksheet['!cols'] = columnWidths;

                XLSX.utils.book_append_sheet(workbook, worksheet, 'recibo clientes-documento');

                XLSX.writeFile(workbook, 'recibo_clientes_por_documento.xlsx');
            }
        }
    },
    mounted() {
        this.datosConfiguracion();
        this.selectTipoPago();
        this.selectSucursal();
    }
}
</script>
