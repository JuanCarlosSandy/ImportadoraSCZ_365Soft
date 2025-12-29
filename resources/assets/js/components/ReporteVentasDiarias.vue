<template>
    <main class="main">
        <div class="container-fluid py-3"></div>
        <div class="card flex-grow-1">
            <div class="card-header">
                <i class="fa fa-align-justify"></i> REPORTE DEL DIA
            </div>
            <div class="card-body">
                <template v-if="listado == 1">
                    <div class="row align-items-center mb-3">
                        <div class="col-md-3 pe-md-2">
                            <div class="form-group">
                                <b-form-datepicker v-model="fecha" locale="es" class="form-control"></b-form-datepicker>
                            </div>
                        </div>
                        <div class="col-md-3 px-md-2">
                            <div class="form-group">
                                <select v-model="idcategoria" class="form-select form-select-sm">
                                    <option value="0" disabled>Seleccione</option>
                                    <option value="all">Todas las categorías</option>
                                    <option v-for="categoria in arrayCategoria" :key="categoria.id"
                                        :value="categoria.id">{{ categoria.nombre }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 px-md-2">
                            <div class="form-group">
                                <select v-model="idUsuario" class="form-select form-select-sm">
                                    <option value="0" disabled>Seleccione Usuario</option>
                                    <option value="all">Todos los usuarios</option>
                                    <option v-for="usuario in arrayUsuarios" :key="usuario.id" :value="usuario.id">
                                        {{ usuario.usuario }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 ps-md-1">
                            <button type="button" @click="generarReporte" class="btn btn-primary btn-generar w-100">
                                <i class="fa fa-search"></i> Generar Reporte
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Opciones</th>
                                    <th>Cliente</th>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio</th>
                                    <th>Total</th>
                                    <th>Número Factura</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="venta in arrayVentas" :key="venta.id">
                                    <td>
                                        <button type="button" @click="verVenta(venta.id)"
                                            class="btn btn-success btn-sm">
                                            <i class="icon-eye"></i>
                                        </button>
                                    </td>
                                    <td>{{ venta.cliente ? venta.cliente : 'Sin Nombre' }}</td>
                                    <td>{{ venta.articulo }}</td>
                                    <td>{{ venta.cantidad }}</td>
                                    <td>{{ venta.precio }}</td>
                                    <td>{{ venta.total }}</td>
                                    <td>{{ venta.num_comprobante }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-right">
                            <strong>Total Ganado: </strong>Bs. {{ totalGanado }}
                        </div>
                        <button @click="exportarPDF" class="btn btn-danger">Exportar a PDF</button>
                        <button @click="exportarExcel" class="btn btn-success">Exportar a Excel</button>
                        <button @click="exportarWhatsapp" class="btn btn-success">Exportar a Whatsapp</button>
                    </div>
                    <nav>
                        <ul class="pagination justify-content-center mt-3">
                            <li class="page-item" v-if="pagination.current_page > 1">
                                <a class="page-link" href="#"
                                    @click.prevent="cambiarPagina(pagination.current_page - 1, buscar, criterio)">Ant</a>
                            </li>
                            <li class="page-item" v-for="page in pagesNumber" :key="page"
                                :class="[page == isActived ? 'active' : '']">
                                <a class="page-link" href="#" @click.prevent="cambiarPagina(page, buscar, criterio)">{{
                    page }}</a>
                            </li>
                            <li class="page-item" v-if="pagination.current_page < pagination.last_page">
                                <a class="page-link" href="#"
                                    @click.prevent="cambiarPagina(pagination.current_page + 1, buscar, criterio)">Sig</a>
                            </li>
                        </ul>
                    </nav>
                </template>
                <template v-if="listado == 2">
                    <div class="row mb-3">
                        <div class="col-md-9">
                            <div class="form-group">
                                <label>Cliente</label>
                                <p>{{ cliente }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Impuesto</label>
                            <p>{{ impuesto }}</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tipo Comprobante</label>
                                <p>{{ tipo_comprobante }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Serie Comprobante</label>
                                <p>{{ serie_comprobante }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Número Comprobante</label>
                                <p>{{ num_comprobante }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Artículo</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                    <th>Descuento</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody v-if="arrayDetalle.length">
                                <tr v-for="detalle in arrayDetalle" :key="detalle.id">
                                    <td>{{ detalle.articulo }}</td>
                                    <td>{{ detalle.precio }}</td>
                                    <td>{{ detalle.cantidad }}</td>
                                    <td>{{ detalle.descuento }}</td>
                                    <td>{{ (detalle.precio * detalle.cantidad - detalle.descuento).toFixed(2) }}
                                    </td>
                                </tr>
                                <tr class="table-active">
                                    <td colspan="4" class="text-end fw-bold">Total:</td>
                                    <td>$ {{ total.toFixed(2) }}</td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="5" class="text-center">No hay artículos agregados</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="form-group row mt-3">
                        <div class="col-md-12">
                            <button type="button" @click="ocultarDetalle()" class="btn btn-secondary w-100">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </main>
</template>

<script>
import axios from 'axios';
import jsPDF from 'jspdf';
import 'jspdf-autotable';
import * as XLSX from 'xlsx-js-style';

export default {
    data() {
        return {
            telefonoEmpresa: 0,
            idUsuario: 'all',
            arrayUsuarios: [],
            fecha: new Date().toISOString().split('T')[0],
            cliente: "",
            num_comprobante: "",
            impuesto: "",
            tipo_comprobante: "",
            serie_comprobante: "",
            total: null,
            listado: 1,
            arrayVentas: [],
            arrayCategoria: [],
            arrayDetalle: [],
            idcategoria: 'all',
            pagination: {
                total: 0,
                current_page: 0,
                per_page: 0,
                last_page: 0,
                from: 0,
                to: 0,
            },
            offset: 3,
            criterio: 'nombre',
            buscar: ''
        };
    },
    computed: {
        isActived() {
            return this.pagination.current_page;
        },
        totalGanado() {
            return this.arrayVentas.reduce((total, venta) => total + venta.precio * venta.cantidad, 0).toFixed(2);
        },
        pagesNumber() {
            if (!this.pagination.to) {
                return [];
            }
            let from = this.pagination.current_page - this.offset;
            if (from < 1) {
                from = 1;
            }
            let to = from + (this.offset * 2);
            if (to >= this.pagination.last_page) {
                to = this.pagination.last_page;
            }
            let pagesArray = [];
            while (from <= to) {
                pagesArray.push(from);
                from++;
            }
            return pagesArray;
        }
    },
    methods: {
        selectUsuarios() {
            let me = this;
            var url = '/usuario/selectUsuario';
            axios.get(url).then(function (response) {
                var respuesta = response.data;
                me.arrayUsuarios = respuesta.usuarios;
            }).catch(function (error) {
                console.log(error);
            });
        }, onContext(ctx) {
            this.context = ctx
        },
        onContext(ctx) {
            this.context = ctx
        },
        cambiarPagina(page) {
            let me = this;
            //Actualiza la página actual
            me.pagination.current_page = page;
            //Envía la petición para visualizar la data de esa página
            me.listarRol(page, me.buscar, me.criterio);
        },
        ocultarDetalle() {
            this.listado = 1;
            this.codigo = null;
            this.arrayArticulo.length = 0;
            this.precioseleccionado = null;
            this.medida = null;
            this.nombreCliente = null;
            this.documento = null;
            this.email = null;

        },

        verVenta(id) {
            let me = this;
            me.listado = 2;
            //Obtener datos del ingreso
            var arrayVentaT = [];
            var url = '/venta/obtenerCabecera?id=' + id;

            axios.get(url).then(function (response) {

                var respuesta = response.data;
                arrayVentaT = respuesta.venta;

                me.cliente = arrayVentaT[0]['nombre'];
                me.tipo_comprobante = arrayVentaT[0]['tipo_comprobante'];
                me.serie_comprobante = arrayVentaT[0]['serie_comprobante'];
                me.num_comprobante = arrayVentaT[0]['num_comprobante'];
                me.impuesto = arrayVentaT[0]['impuesto'];
                me.total = arrayVentaT[0]['total'];
            })
                .catch(function (error) {
                    console.log(error);
                });

            //obtener datos de los detalles
            var url = '/venta/obtenerDetalles?id=' + id;

            axios.get(url).then(function (response) {
                //console.log(response);
                var respuesta = response.data;
                me.arrayDetalle = respuesta.detalles;

            })
                .catch(function (error) {
                    console.log(error);
                });
        },
        generarReporte() {
            let me = this;

            var url = '/ventas-diarias?fecha=' + me.fecha + '&idCategoria=' + me.idcategoria + '&idUsuario=' + me.idUsuario;

            axios.get(url)
                .then(function (response) {
                    if ('mensaje' in response.data && response.data.mensaje === 'Ninguna Venta Realizada en la Fecha Indicada') {
                        swal("Ninguna Venta", "No se realizaron ventas en la fecha seleccionada", "info");
                    } else {
                        var respuesta = response.data;
                        me.arrayVentas = respuesta.ventas;


                    }
                })
                .catch(function (error) {
                    console.log(error);
                });
        },



        selectCategoria() {
            let me = this;
            var url = '/categoria/selectCategoria';
            axios.get(url).then(function (response) {
                //console.log(response);
                var respuesta = response.data;
                me.arrayCategoria = respuesta.categorias;
            })
                .catch(function (error) {
                    console.log(error);
                });
        },
        exportarExcel() {
            const workbook = XLSX.utils.book_new();

            const worksheet = XLSX.utils.aoa_to_sheet([]);

            const startRow = 5;

            worksheet['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 3 } }];
            worksheet['A1'] = {
                t: 's', v: 'REPORTE DE VENTAS DIARIAS', s: {
                    font: { sz: 16, bold: true, color: { rgb: 'FFFFFF' } },
                    alignment: { horizontal: 'center', vertical: 'center' },
                    fill: { fgColor: { rgb: '3669a8' } }
                }
            };


            const fechaStyle = { font: { bold: true, color: { rgb: '000000' } }, border: { top: { style: 'thin', color: { auto: 1 } }, right: { style: 'thin', color: { auto: 1 } }, bottom: { style: 'thin', color: { auto: 1 } }, left: { style: 'thin', color: { auto: 1 } } } };
            worksheet['A2'] = { t: 's', v: `Fecha: ${this.fecha}`, s: fechaStyle };

            worksheet['!merges'].push({ s: { r: 1, c: 0 }, e: { r: 2, c: 3 } });

            const headerStyle = { font: { bold: true, color: { rgb: 'FFFFFF' } }, fill: { fgColor: { rgb: '3669a8' } } };
            const headers = ['Cliente', 'Monto', 'Número Factura', 'Usuario'];

            headers.forEach((header, index) => {
                worksheet[XLSX.utils.encode_cell({ r: 3, c: 3 })] = { t: 's', v: header, s: headerStyle };
            });

            for (let i = 0; i < this.arrayVentas.length; i++) {
                const rowData = Object.values(this.arrayVentas[i]);
                XLSX.utils.sheet_add_aoa(worksheet, [rowData], { origin: `A${startRow + i}` });
            }

            const totalGanado = this.totalGanado;

            const totalRow = [`Total Ganado: Bs. ${totalGanado}`];

            worksheet['!merges'].push({ s: { r: startRow + this.arrayVentas.length, c: 0 }, e: { r: startRow + this.arrayVentas.length, c: 3 } });

            XLSX.utils.sheet_add_aoa(worksheet, [totalRow], {
                origin: `A${startRow + this.arrayVentas.length + 1}`
            });

            const columnWidths = [
                { wch: 27.8 },
                { wch: 16.0 },
                { wch: 18.6 },
                { wch: 15.2 }
            ];
            worksheet['!cols'] = columnWidths;

            XLSX.utils.book_append_sheet(workbook, worksheet, 'Reporte Ventas');

            XLSX.writeFile(workbook, 'reporte_ventas_diarias.xlsx');
        },


        exportarPDF() {
            const pdf = new jsPDF();

            pdf.setFont('bold');
            const titulo = 'Reporte de Ventas Diarias';
            const fecha = `Fecha: ${this.fecha}`;
            pdf.setFont('normal');

            const pageSize = pdf.internal.pageSize;
            const pageWidth = pageSize.width;
            const textWidth = pdf.getStringUnitWidth(titulo) * pdf.internal.getFontSize() / pdf.internal.scaleFactor;

            const xPosition = (pageWidth - textWidth) / 2;

            pdf.text(titulo, xPosition, 10);
            pdf.text(fecha, 15, 20);

            const tableYPosition = 30;

            const columns = ['Cliente', 'Producto', 'Cantidad', 'Precio', 'Total', 'Número Factura'];
            const rows = this.arrayVentas.map(venta => [venta.cliente, venta.articulo, venta.cantidad, venta.precio, (venta.precio * venta.cantidad), venta.num_comprobante]);
            pdf.autoTable({ head: [columns], body: rows, startY: tableYPosition });

            pdf.setFont('bold');
            const totalGanado = `Total Ganado: Bs. ${this.totalGanado}`;
            pdf.text(totalGanado, 15, pdf.autoTable.previous.finalY + 10);
            pdf.setFont('normal');

            pdf.save('reporte_ventas_diarias.pdf');
        },

        exportarWhatsapp() {
            const data = {
                telefono: this.telefonoEmpresa,
                fecha: this.fecha,
                ventas: this.arrayVentas,
                totalGanado: this.totalGanado
            };
            axios.post('/enviarWhatsapp', data)
                .then(function (response) {
                    if (response.data.error) {
                        swal("Error", response.data.error.error_data.details, "error");
                    } else {
                        swal("Éxito", "Reporte enviado a WhatsApp con éxito.", "success");
                    }
                })
                .catch(function (error) {
                    console.error('Error:', error);
                    alert('Ocurrió un error al enviar el reporte.');
                });
        },
        datosEmpresa() {
            let me = this;
            var url = '/empresa';

            axios.get(url).then(function (response) {
                var respuesta = response.data;
                console.log(respuesta);
                me.telefonoEmpresa = respuesta.empresa.telefono;
            })
                .catch(function (error) {
                    console.log(error);
                });
        },



        cambiarPagina(page, buscar, criterio) {
            let me = this;
            //Actualiza la página actual
            me.pagination.current_page = page;
            //Envia la petición para visualizar la data de esa página
            me.listarRol(page, buscar, criterio);
        }
    },
    mounted() {
        this.selectCategoria();
        this.generarReporte();
        this.selectUsuarios();
        this.datosEmpresa();
    }
}
</script>
<style>
.mostrar {
    display: list-item !important;
    opacity: 1 !important;
    position: absolute !important;
    background-color: #3c29297a !important;
}

.div-error {
    display: flex;
    justify-content: center;
}

.text-error {
    color: red !important;
    font-weight: bold;
}

.custom-select {
    color: #000;
    /* Establece el color de texto en negro */
    background-color: #fff;
    /* Establece el color de fondo en blanco */
    border: 1px solid #ccc;
    /* Establece un borde gris claro */
    padding: 0.5rem 1rem;
    /* Añade un poco de espaciado interno */
    font-size: 1rem;
    /* Establece el tamaño de fuente */
}

.form-group {
    margin-bottom: 1rem;
}

.form-select {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
}

.form-control {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
}

/* Estilos para el botón "Generar Reporte" */
.btn-generar {
    margin-top: 1rem;
}

/* Estilos responsivos para los selects */
@media (max-width: 767.98px) {

    .form-select,
    .form-control {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
    }
}

thead {
    background-color: #343a40;
    color: white;
}
</style>