<template>
    <div class="modal fade" tabindex="-1" :class="{ 'mostrar': true }" role="dialog" aria-labelledby="myModalLabel"
        style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-primary modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    Cotizacion Venta
                </div>
                <div class="modal-body">
                    <div class="row mx-2 flex justify-content-between">
                        <!-- <p class="h4"> Cotizacion Venta:</p> -->
                        <button type="button" class="btn btn-outline-success" @click="abrirVenta()">
                                <i class="fa fa-shopping-cart"></i>
                                Realizar Venta
                        </button>
                    </div>
                        <div>
                            <dl class="row">
                            <dt class="col-sm-3">ID:</dt>
                            <dd class="col-sm-9">
                                {{ arrayCotizacionSeleccionado.id }}
                            </dd>
                            <dt class="col-sm-3">Fecha Venta:</dt>
                            <dd class="col-sm-9">
                                {{ arrayCotizacionSeleccionado.fecha_hora }}
                            </dd>
                            <dt class="col-sm-3">Cliente:</dt>
                            <dd class="col-sm-9">
                                {{ arrayCotizacionSeleccionado.nombre }}
                            </dd>
                            <dt class="col-sm-3">NIT/CI:</dt>
                            <dd class="col-sm-9">
                                {{ arrayCotizacionSeleccionado.fecha_hora }}
                            </dd>
                            <dt class="col-sm-3">IMPUESTO:</dt>
                            <dd class="col-sm-9">
                                {{ arrayCotizacionSeleccionado.impuesto }}
                            </dd>
                        </dl>
                        <table class="table table-responsive table-bordered table-striped table-sm"> 
                            <thead> 
                                <tr>
                                    <td colspan="7">
                                        <h6 class="text-center font-weight-bold ">Detalle de Cotizacion Venta</h6>
                                    </td>
                                </tr>                   
                                <tr>
                                    <th>CODIGO</th>
                                    <th>PRODUCTO</th>
                                    <th>CANTIDAD</th>
                                    <th>DESCUENTO</th>
                                    <th>IMPORTE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="cotventa in arrayCotizacionVentDet" :key="cotventa.id">
                                    <td v-text="cotventa.codigo"></td>
                                    <td v-text="cotventa.nombre_articulo"></td>
                                    <td v-text="cotventa.cantidad"></td>
                                    <td v-text="cotventa.descuento"></td>
                                    <td >{{ cotventa.prectotal}}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-secondary" @click="cerrarModal()">Cerrar</button>
                </div>
                
            </div>
        </div>
    </div>
</template>
<script>
    export default {

        props: {
            arrayCotizacionVentDet: {
            type: Array, // Indica que esperas un array
            required: true // Opcional: indica si la prop es obligatoria o no
            },
            arrayCotizacionSeleccionado: {
            type: Object, // Indica que esperas un array
            required: true // Opcional: indica si la prop es obligatoria o no
            }
        },
        data() {
            return {
            // Inicializa los datos locales si es necesario
            };
        },
        methods: {
            abrirVenta(){
                const datos={
                    cotizacion:this.arrayCotizacionSeleccionado,
                    detalles:this.arrayCotizacionVentDet
                }
                this.$emit('abrirVenta',datos);

            },
            cerrarModal(){
                this.$emit('cerrar');
            },
            // abrirVenta(){
            //     const datos={
            //         pedido:this.arrayPedidoSeleccionado,
            //         detalles:this.arrayPedidoProvDet
            //     }
            //     this.$emit('abrirVenta',datos);

            // }
        }

    };
</script>