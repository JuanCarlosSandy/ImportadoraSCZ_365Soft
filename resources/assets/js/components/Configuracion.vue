<template>
    <main class="main">
        <!-- 🔄 Overlay de carga -->
        <div class="loading-overlay" v-if="isLoading">
            <div class="loading-container">
                <div class="spinner"></div>
                <div class="loading-text">CARGANDO...</div>
            </div>
        </div>

        <div class="card p-4">
            <h3 class="text-xl font-semibold text-blue-700 mb-3 flex align-items-center">
                <i class="pi pi-cog mr-2"></i> Configuración del Sistema de Ventas
            </h3>

            <div class="p-grid">
                <!-- COLUMNA IZQUIERDA -->
                <div class="p-col-12 p-md-6">
                    <!-- GESTIÓN DE TRABAJO -->
                    <Divider align="left">
                        <span class="text-primary font-medium">Gestión de trabajo</span>
                    </Divider>

                    <div class="p-d-flex p-ai-center p-jc-between mb-4">
                        <Button icon="pi pi-minus" class="p-button-sm p-button-primary" />
                        <InputText v-model="selectedYear" class="p-inputtext-sm p-text-center w-5" />
                        <Button icon="pi pi-plus" class="p-button-sm p-button-primary" />
                    </div>

                    <!-- PORCENTAJE DE UTILIDAD -->
                    <Divider align="left">
                        <span class="text-primary font-medium">Porcentaje de utilidad - precios de venta</span>
                    </Divider>

                    <div class="p-grid">
                        <div class="p-col-12 p-md-6">
                            <label class="block mb-2 font-medium">Precio de venta 1</label>
                            <div class="p-inputgroup">
                                <InputText v-model="precio_uno" class="p-inputtext-sm text-center" />
                                <span class="p-inputgroup-addon">%</span>
                            </div>
                        </div>
                        <div class="p-col-12 p-md-6">
                            <label class="block mb-2 font-medium">Precio de venta 2</label>
                            <div class="p-inputgroup">
                                <InputText v-model="precio_dos" class="p-inputtext-sm text-center" />
                                <span class="p-inputgroup-addon">%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA -->
                <div class="p-col-12 p-md-6">
                    <!-- CONFIGURACIÓN DE VENTAS -->
                    <Divider align="left">
                        <span class="text-primary font-medium">Configuración de ventas</span>
                    </Divider>

                    <div class="p-fluid p-formgrid p-grid">
                        <div class="p-col-12 p-md-12 mb-3">
                            <div class="p-d-flex p-ai-center p-jc-between">
                                <label>Habilitar descuentos</label>
                                <InputSwitch v-model="habilitarDescuentos" />
                            </div>
                        </div>
                        <div class="p-col-12 p-md-12 mb-3">
                            <div class="p-d-flex p-ai-center p-jc-between">
                                <label>Habilitar ofertas</label>
                                <InputSwitch v-model="habilitarOfertas" />
                            </div>
                        </div>
                        <div class="p-col-12 p-md-12 mb-3">
                            <div class="p-d-flex p-ai-center p-jc-between">
                                <label>Bonificación a clientes</label>
                                <InputSwitch v-model="habilitarBonificacion" />
                            </div>
                        </div>
                        <div class="p-col-12 p-md-12">
                            <div class="p-d-flex p-ai-center p-jc-between">
                                <label>Cambio de precio unitario</label>
                                <InputSwitch v-model="habilitarcambioprecio" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BOTÓN GUARDAR -->
            <div class="p-d-flex p-jc-start mt-3">
                <Button label="Guardar cambios" icon="pi pi-save" class="p-button-success px-5" :loading="isLoading"
                    @click="guardarPrecios" />
            </div>
        </div>
    </main>
</template>
<script>
import Dropdown from 'primevue/dropdown';
import Swal from 'sweetalert2';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Paginator from 'primevue/paginator';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Panel from 'primevue/panel';
import Steps from 'primevue/steps';
import Dialog from 'primevue/dialog';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import SelectButton from 'primevue/selectbutton';
import InputNumber from 'primevue/inputnumber';
import Divider from 'primevue/divider';
import InputSwitch from 'primevue/inputswitch';

export default {
    components: {
        Dropdown,
        DataTable,
        Column,
        Button,
        Paginator,
        Card,
        InputText,
        Button,
        Panel,
        Steps,
        Message,
        Dialog,
        Tag,
        SelectButton,
        InputNumber,
        Divider,
        InputSwitch
    },
    data() {
        return {
            habilitarDescuentos: false,
            habilitarOfertas: false,
            habilitarBonificacion: false,
            habilitarcambioprecio: false,
            isLoading: false,
            idMonedaVenta: -1,
            idMonedaCompra: 1,
            idMonedaPrincipal: -1,
            arrayMonedas: '',
            monedaSeleccionada: -1,
            id: 0,
            selectedYear: '',
            almacenPredeterminado: '',
            codigoProducto: '',
            consultarAlmacenes: '',
            limiteDescuento: '',
            maximoDescuento: 0,
            valuacionInventario: '',
            backupAutomatico: '',
            rutaBackup: '',
            saldosNegativos: '',
            monedaTrabajo: '',
            separadorDecimales: 'Punto',
            etiquetaPrecio1: 'Por mayor',
            mostrarEtiquetaPrecio1: 'No',
            margenEtiquetaPrecio1: 30,
            etiquetaPrecio2: 'Por mayor',
            mostrarEtiquetaPrecio2: 'No',
            margenEtiquetaPrecio2: 30,
            etiquetaPrecio3: 'Por mayor',
            mostrarEtiquetaPrecio3: 'No',
            margenEtiquetaPrecio3: 30,
            etiquetaPrecio4: 'Por mayor',
            mostrarEtiquetaPrecio4: 'No',
            margenEtiquetaPrecio4: 30,
            mostrarCostos: '',
            mostrarProveedor: '',
            mostrarSaldosStock: '',
            actualizarIVA: '',
            vendedorAsignado: '',
            devolucion: '',
            registroClienteObligatorio: '',
            editarNroDoc: '',
            buscarClientePorCodigo: '',
            mensajeCheckBox: '',
            activeTab: 0, // Inicialmente, se muestra el contenido de la primera pestaña
            opcion1: '',
            opcion2: '',
            opcion3: '',
            precios: [],
            // modal: 0,
            modal: 0,
            tituloModal: '',
            tipoAccion: 0,
            nombre_precio: '',
            porcentage: '',
            condicion: 1,
            almacenes: [],
            almacenSeleccionado: '',

            idUsuarioSeleccionado: '',
            puedeDescontar: false,
            listaUsuarios: [], // Aquí cargarás los usuarios
            precio_uno: 0,
            precio_dos: 0,
        };
    },
    computed: {
        idMonedaSeleccionada() {
            const monedaSeleccionada = this.arrayMonedas.find(moneda => moneda.nombre === this.monedaSeleccionada);
            return monedaSeleccionada ? monedaSeleccionada.id : null;
        }
    },
    methods: {
        toastSuccess(mensaje) {
            this.$toasted.show(
                `
            <div style="height: 50px;font-size:16px;">
                <br>
                ` +
                mensaje +
                `.<br>
            </div>`,
                {
                    type: "success",
                    position: "bottom-right",
                    duration: 2000,
                }
            );
        },
        async cargarPrecios() {
            try {
                const response = await axios.get("/configuracion/porcentajes");
                const precios = response.data;

                const venta1 = precios.find((p) => p.nombre_precio === "VENTA 1");
                const venta2 = precios.find((p) => p.nombre_precio === "VENTA 2");

                this.precio_uno = venta1 ? venta1.porcentage : 0;
                this.precio_dos = venta2 ? venta2.porcentage : 0;
            } catch (error) {
                console.error("❌ Error al cargar precios:", error);
            }
        },

        async guardarPrecios() {
            await this.guardar();

            const me = this; // 🔹 para mantener el contexto
            me.isLoading = true; // 🔹 activa el loading

            try {
                const payload = [
                    { nombre_precio: "VENTA 1", porcentage: me.precio_uno },
                    { nombre_precio: "VENTA 2", porcentage: me.precio_dos },
                ];

                const response = await axios.put("/configuracion/porcentajes", payload);

                me.isLoading = false; // 🔹 desactiva el loading

                // ✅ Mostrar toast personalizado
                me.toastSuccess("Porcentajes actualizados correctamente");

                console.log("📦 Datos guardados:", response.data);

            } catch (error) {
                me.isLoading = false;
                console.error("❌ Error al guardar precios:", error);

                // ❌ Mostrar toast de error (usando tu helper también si lo tenés)
                if (typeof me.toastError === "function") {
                    me.toastError("No se pudo actualizar los porcentajes");
                } else {
                    me.$toast.add({
                        severity: "error",
                        summary: "Error al guardar",
                        detail: "No se pudo actualizar los porcentajes.",
                        life: 4000,
                    });
                }
            }
        },

        async listarMonedas() {
            try {
                this.isLoading = true; // Activar loading
                const url = '/moneda/selectMoneda';
                const response = await axios.get(url);
                this.arrayMonedas = response.data.monedas;
            } catch (error) {
                console.error("Error al listar monedas:", error);
                swal("Error", "No se pudieron cargar las monedas", "error");
            } finally {
                setTimeout(() => {
                    this.isLoading = false; // Desactivar loading
                }, 500);
            }
        },
        async datosConfiguracion() {
            try {
                this.isLoading = true; // Activar loading
                const url = '/configuracion/editar';
                const response = await axios.get(url);
                const respuesta = response.data;

                // Asignar valores de configuración
                this.id = respuesta.configuracionTrabajo.id;
                this.idMonedaCompra = respuesta.configuracionTrabajo.idMonedaCompra;
                this.idMonedaVenta = respuesta.configuracionTrabajo.idMonedaVenta;
                this.idMonedaPrincipal = respuesta.configuracionTrabajo.idMonedaPrincipal;
                this.almacenPredeterminado = respuesta.configuracionTrabajo.almacenSeleccionado;
                this.selectedYear = respuesta.configuracionTrabajo.gestion;
                this.codigoProducto = respuesta.configuracionTrabajo.codigoProductos;
                this.limiteDescuento = respuesta.configuracionTrabajo.limiteDescuento;
                this.maximoDescuento = respuesta.configuracionTrabajo.maximoDescuento;
                this.valuacionInventario = respuesta.configuracionTrabajo.valuacionInventario;
                this.backupAutomatico = respuesta.configuracionTrabajo.backupAutomatico;
                this.rutaBackup = respuesta.configuracionTrabajo.rutaBackup;
                this.saldosNegativos = respuesta.configuracionTrabajo.saldosNegativos;
                this.monedaTrabajo = respuesta.configuracionTrabajo.monedaTrabajo;
                this.separadorDecimales = respuesta.configuracionTrabajo.separadorDecimales;
                this.mostrarCostos = respuesta.configuracionTrabajo.mostrarCostos;
                this.mostrarProveedor = respuesta.configuracionTrabajo.mostrarProveedores;
                this.mostrarSaldosStock = respuesta.configuracionTrabajo.mostrarSaldosStock;
                this.actualizarIVA = respuesta.configuracionTrabajo.actualizarIva;
                this.vendedorAsignado = respuesta.configuracionTrabajo.vendedorAsignado;
                this.devolucion = respuesta.configuracionTrabajo.permitirDevolucion;
                this.editarNroDoc = respuesta.configuracionTrabajo.editarNroDoc;
                this.registroClienteObligatorio = respuesta.configuracionTrabajo.registroClienteObligatorio;
                this.buscarClientePorCodigo = respuesta.configuracionTrabajo.buscarClientePorCodigo;
                // 🔹 NUEVOS SWITCHES
                this.habilitarDescuentos = respuesta.configuracionTrabajo.permitir_descuento === 1;
                this.habilitarOfertas = respuesta.configuracionTrabajo.permitir_ofertas === 1;
                this.habilitarBonificacion = respuesta.configuracionTrabajo.permitir_bonificacion === 1;
                this.habilitarcambioprecio = respuesta.configuracionTrabajo.permitir_cambioprecio === 1;
            } catch (error) {
                console.error("Error al cargar configuración:", error);
                swal("Error", "No se pudo cargar la configuración", "error");
            } finally {
                setTimeout(() => {
                    this.isLoading = false; // Desactivar loading
                }, 500);
            }
        },
        async guardar() {
            try {
                this.isLoading = true; // Activar loading

                // Guardar cambios en precios (si aplica)
                if (this.precios && this.precios.length > 0) {
                    await Promise.all(this.precios.map(precio => this.guardarCambios(precio)));
                }

                // 🔹 Convertir los switches a 0/1 antes de enviarlos
                const habilitarDescuentos = this.habilitarDescuentos ? 1 : 0;
                const habilitarOfertas = this.habilitarOfertas ? 1 : 0;
                const habilitarBonificacion = this.habilitarBonificacion ? 1 : 0;
                const habilitarcambioprecio = this.habilitarcambioprecio ? 1 : 0;

                // Guardar configuración general
                await axios.put('/configuracion/actualizar', {
                    idMonedaCompra: this.idMonedaCompra,
                    idMonedaVenta: this.idMonedaVenta,
                    idMonedaPrincipal: this.idMonedaPrincipal,
                    selectedYear: this.selectedYear,
                    almacenPredeterminado: this.almacenSeleccionado,
                    codigoProducto: this.codigoProducto,
                    limiteDescuento: this.limiteDescuento,
                    maximoDescuento: this.maximoDescuento,
                    valuacionInventario: this.valuacionInventario,
                    backupAutomatico: this.backupAutomatico,
                    rutaBackup: this.rutaBackup,
                    saldosNegativos: this.saldosNegativos,
                    monedaTrabajo: this.monedaTrabajo,
                    separadorDecimales: this.separadorDecimales,
                    mostrarCostos: this.mostrarCostos,
                    mostrarProveedor: this.mostrarProveedor,
                    mostrarSaldosStock: this.mostrarSaldosStock,
                    actualizarIVA: this.actualizarIVA,
                    vendedorAsignado: this.vendedorAsignado,
                    devolucion: this.devolucion,
                    editarNroDoc: this.editarNroDoc,
                    registroClienteObligatorio: this.registroClienteObligatorio,
                    buscarClientePorCodigo: this.buscarClientePorCodigo,
                    id: this.id,

                    // 🟢 Nuevos campos de los switches
                    habilitarDescuentos,
                    habilitarOfertas,
                    habilitarBonificacion,
                    habilitarcambioprecio,
                });

                swal("GUARDADO", "La configuración ha sido actualizada", "success");
            } catch (error) {
                console.error("Error al guardar:", error);
                swal("Error", "No se pudo guardar la configuración", "error");
            } finally {
                this.isLoading = false; // Desactivar loading
            }
        },

        async sacarBackupBaseDatos() {
            try {
                this.isLoading = true; // Activar loading
                const response = await axios.get('/backup');

                if (response.data.exito) {
                    swal("Éxito!", response.data.exito, "success");
                } else {
                    swal("Error!", response.data.error, "error");
                }
            } catch (error) {
                console.error("Error al realizar backup:", error);
                swal("Error", "No se pudo realizar el backup", "error");
            } finally {
                setTimeout(() => {
                    this.isLoading = false; // Desactivar loading
                }, 500);
            }
        },
        cancelar() {
            // Lógica para cancelar la acción
        },
        listarPrecio() {
            let me = this;
            var url = '/preciosactivos';
            axios.get(url).then(function (response) {
                var respuesta = response.data;
                me.precios = respuesta.precio.data.map(precio => ({
                    ...precio,
                    habilitado: precio.habilitado === 1 // Convertir el valor numérico en un booleano
                }));
            }).catch(function (error) {
                console.log(error);
            });
        },
        registrarPrecio() {
            let me = this;

            axios.post('/precios/registrar', {
                'nombre_precio': this.nombre_precio,
                'porcentage': this.porcentage,
            }).then(function (response) {
                me.cerrarModal();
                console.log(response)
                me.listarPrecio();
                me.datosConfiguracion();
                //me.listarPrecio(1,'','nombre_precio');
            }).catch(function (error) {
                console.log(error);
            });
        },
        guardarCambios(precio) {
            //let me = this;
            let accion = precio.condicion ? 'activar' : 'desactivar';
            axios.put(`/precios/${precio.id}/${accion}`)
        },
        abrirModal(modelo, accion, data = []) {
            switch (modelo) {
                case "precioss":
                    {
                        switch (accion) {
                            case 'registrar':
                                {
                                    this.modal = 1;
                                    this.tituloModal = 'Registrar Precio';
                                    this.tipoAccion = 1;
                                    this.nombre_precio = '';
                                    this.porcentage = '';
                                    break;
                                }
                            case 'actualizar':
                                {
                                    break;
                                }
                        }
                    }
            }
        },
        cerrarModal() {
            this.modal = 0;
            this.tituloModal = '';
            this.nombre_precio = '';
            this.porcentage = '';
        },

        async obtenerAlmacenes() {
            try {
                const response = await axios.get('/configuracion/ruta-a-tu-endpoint-para-obtener-almacenes');
                this.almacenes = response.data;
                console.log(this.almacenes);
            } catch (error) {
                console.error('Error al obtener los almacenes:', error);
            }
        },

        async obtenerUsuarios() {
            try {
                const response = await axios.get('/api/user/selectUsuario'); // Ajusta tu ruta si es diferente
                this.listaUsuarios = response.data.usuarios;
            } catch (error) {
                console.error('Error al obtener usuarios:', error);
            }
        },

        async guardarAutorizacion() {
            if (!this.idUsuarioSeleccionado) {
                alert('Debe seleccionar un vendedor.');
                return;
            }

            try {
                await axios.post('/api/autorizaciones-descuento', {
                    idusuario: this.idUsuarioSeleccionado,
                    puede_descontar: this.puedeDescontar ? 1 : 0,
                });
                swal(
                    'AUTORIAZADO',
                    'La autorizacion a sido realizada.',
                    'success'
                )
            } catch (error) {
                console.error('Error al guardar autorización:', error);
                alert('Ocurrió un error al guardar.');
            }
        }
    },
    watch: {
        precio_uno(nuevo, viejo) {
            if (viejo !== undefined && nuevo !== viejo) {
                this.$toast.add({
                    severity: "info",
                    summary: "Campo modificado",
                    detail: "Has cambiado el valor de Venta 1.",
                    life: 2500,
                });
            }
        },
        precio_dos(nuevo, viejo) {
            if (viejo !== undefined && nuevo !== viejo) {
                this.$toast.add({
                    severity: "info",
                    summary: "Campo modificado",
                    detail: "Has cambiado el valor de Venta 2.",
                    life: 2500,
                });
            }
        },
    },

    created() {
        this.obtenerAlmacenes();
    },

    async mounted() {

        try {
            this.isLoading = true; // Activar loading
            await Promise.all([
                this.cargarPrecios(),
                this.listarMonedas(),
                this.listarPrecio(),
                this.obtenerUsuarios(),
                this.datosConfiguracion()
            ]);
        } catch (error) {
            console.error("Error en la carga inicial:", error);
            swal("Error", "Error al cargar los datos iniciales", "error");
        } finally {
            this.isLoading = false; // Desactivar loading
        }
    },
};
</script>

<style>
.nav-link.active {
    background-color: #f8f9fa;
}

.mostrar {
    display: list-item !important;
    opacity: 1 !important;
    position: absolute !important;
    background-color: #3c29297a !important;
}

/* Estilos del loader */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(5px);
    padding: 30px;
    border-radius: 15px;
}

.spinner {
    width: 80px;
    height: 80px;
    border: 4px solid rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    border-top: 4px solid rgba(255, 255, 255, 0.9);
    animation: spin 1s linear infinite;
}

.loading-text {
    margin-top: 20px;
    color: rgba(255, 255, 255, 0.9);
    letter-spacing: 3px;
    font-size: 14px;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}
</style>