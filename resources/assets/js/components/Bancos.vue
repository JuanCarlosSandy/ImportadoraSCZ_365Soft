<template>
    <main class="main">
        <div class="loading-overlay" v-if="isLoading">
            <div class="loading-container">
                <div class="spinner"></div>
                <div class="loading-text">LOADING...</div>
            </div>
        </div>
        <Panel>
            <template #header>
                <div class="panel-header">
                    <i class="pi pi-bars panel-icon"></i>
                    <h4 class="panel-title">BANCOS</h4>
                </div>
            </template>

            <div class="toolbar-container">
                <div class="search-bar">
                    <span class="p-input-icon-left">
                        <i class="pi pi-search" />
                        <InputText v-model="buscar" placeholder="Buscar Banco..." style="width: 100%;"
                            @input="onBuscarInput" class="p-inputtext-sm" />
                    </span>
                </div>
                <div class="toolbar">
                    <Button :label="mostrarLabel ? 'Reset' : ''" icon="pi pi-refresh" @click="resetBusqueda"
                        class="p-button-help p-button-sm" />
                    <Button :label="mostrarLabel ? 'Nuevo' : ''" icon="pi pi-plus"
                        @click="abrirModal('banco', 'registrar')" class="p-button-secondary p-button-sm" />
                </div>
            </div>

            <DataTable :value="arrayBancos" responsiveLayout="scroll" stripedRows
                class="p-datatable-gridlines p-datatable-sm tabla-pro">
                <Column header="Acciones">
                    <template #body="slotProps">
                        <Button icon="pi pi-pencil" class="p-button-warning p-button-sm btn-mini"
                            @click="abrirModal('banco', 'actualizar', slotProps.data)" v-tooltip.top="'Editar'"/>
                        <Button icon="pi pi-image" class="p-button-info p-button-sm btn-mini"
                            @click="mostrarLogoBanco(slotProps.data)" v-tooltip.top="'Logo'"/>
                    </template>
                </Column>
                <Column header="Banco">
                    <template #body="slotProps">
                        <div class="p-d-flex p-ai-center">
                            <span class="p-mr-2">{{ slotProps.data.nombre_banco }}</span>
                            <img :src="getBankUrl(slotProps.data.nombre_banco)" width="25" height="25" />
                        </div>
                    </template>
                </Column>
                <Column field="nombre_cuenta" header="Nombre de Cuenta" />
                <Column field="numero_cuenta" header="Número de cuenta" />
                <Column field="tipo_cuenta" header="Tipo de cuenta" />
            </DataTable>

            <!-- PAGINADOR -->
            <Paginator :rows="pagination.per_page" :totalRecords="pagination.total"
                :first="(pagination.current_page - 1) * pagination.per_page"
                @page="e => cambiarPagina(e.page + 1, buscar, criterio)"
                template="PrevPageLink PageLinks NextPageLink" />
        </Panel>

        <Dialog header="Logo del banco" :visible.sync="dialogLogoVisible" :modal="true" :closable="true"
            :containerStyle="{ width: '350px' }">

            <div class="p-d-flex p-jc-center p-ai-center" style="min-height: 150px;">
                <img v-if="selectedBanco" :src="getBankUrl(selectedBanco.nombre_banco)"
                    style="max-width: 100%; max-height: 250px; object-fit: contain;" />
            </div>
        </Dialog>

        <Dialog :visible.sync="modal" :modal="true" :closable="false" :containerStyle="{ width: '650px' }"
            class="p-fluid">

            <template #header>
                <h4>{{ tituloModal }}</h4>
            </template>
            <div v-if="paso === 1">
                <h5 class="p-mb-3">Seleccione un banco</h5>
                <div class="p-grid">
                    <div class="p-col-6 p-md-3 p-p-2" v-for="(bankName, code) in bancos" :key="code">
                        <Button
                            class="p-button-outlined p-button-info p-button-sm p-d-flex p-flex-column p-ai-center p-jc-center"
                            style="width: 100%; height: 100px;" @click="seleccionarBanco(code)">
                            <img :src="getBankUrl(bankName)" style="width: 40px; height: 40px; object-fit: contain;"
                                class="p-mb-2" />
                            <small>{{ bankName }}</small>
                        </Button>
                    </div>
                </div>

            </div>
            <form v-if="paso === 2" @submit.prevent="enviarFormulario">
                <div class="p-field p-text-center p-mb-3">
                    <h5>{{ selectedBank }}</h5>
                    <img :src="getBankUrl(selectedBank)" style="width: 80px; height: 80px; object-fit: contain;" />
                </div>

                <div class="p-field">
                    <label for="nombre" class="required-field">
                        <span class="required-icon">*</span>
                        Nombre de Cuenta
                    </label>
                    <InputText v-model="datosFormulario.nombre_cuenta" class="input-full"
                        :class="{ 'p-invalid': errores.nombre_cuenta }" />
                    <small v-if="errores.nombre_cuenta" class="p-error">
                        {{ errores.nombre_cuenta }}
                    </small>
                </div>

                <div class="p-field">
                    <label for="nombre" class="required-field">
                        <span class="required-icon">*</span>
                        Numero de Cuenta
                    </label>
                    <InputText type="number" v-model="datosFormulario.numero_cuenta" class="input-full"
                        :class="{ 'p-invalid': errores.numero_cuenta }" />
                    <small v-if="errores.numero_cuenta" class="p-error">
                        {{ errores.numero_cuenta }}
                    </small>
                </div>

                <div class="p-field">
                    <label for="descripcion" class="optional-field">
                        <i class="pi pi-info-circle optional-icon"></i>
                        Tipo de Cuenta
                        <span class="p-tag p-tag-secondary">Opcional</span>
                    </label>
                    <Dropdown v-model="datosFormulario.tipo_cuenta" :options="tiposDeCuenta" placeholder="Seleccione" class="dropdown-full"
                        :class="{ 'p-invalid': errores.tipo_cuenta }" />
                    <small v-if="errores.tipo_cuenta" class="p-error">
                        {{ errores.tipo_cuenta }}
                    </small>
                </div>
            </form>

            <template #footer>
                <Button v-if="paso === 2" label="Volver" icon="pi pi-arrow-left" class="p-button-sm p-button-secondary btn-sm"
                    @click="volverAPaso1" :disabled="isLoading" />

                <Button label="Cerrar" icon="pi pi-times" class="p-button-sm p-button-danger btn-sm" @click="cerrarModal()" 
                    :disabled="isLoading" />

                <Button v-if="tipoAccion == 1 && paso === 2" icon="pi pi-check" label="Guardar"
                    class="p-button-sm p-button-success btn-sm" @click="enviarFormulario" :disabled="isLoading" />

                <Button v-if="tipoAccion == 2 && paso === 2" icon="pi pi-check" label="Actualizar"
                    class="p-button-sm p-button-success btn-sm" @click="enviarFormulario" :disabled="isLoading" />
            </template>
        </Dialog>
    </main>
</template>

<script>
import VueBarcode from 'vue-barcode';

import { esquemaBanco } from '../constants/validations';
import { bancos, tiposDeCuenta } from '../constants/banks';
import Panel from 'primevue/panel';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Paginator from 'primevue/paginator';
import Dialog from 'primevue/dialog';
import OverlayPanel from 'primevue/overlaypanel';
import Tooltip from 'primevue/tooltip';

export default {
    components: {
        Panel,
        Dropdown,
        InputText,
        InputNumber,
        Button,
        DataTable,
        Column,
        Paginator,
        Dialog,
        OverlayPanel
    },
    directives: {
        'tooltip': Tooltip
    },
    data() {
        return {
            paso: 1, // 1 = seleccionar banco, 2 = formulario
            dialogLogoVisible: false,
            selectedBanco: null,
            mostrarLabel: true,
            isLoading: false,
            bancos: bancos,
            tiposDeCuenta: tiposDeCuenta,
            datosFormulario: {
                nombre_cuenta: "",
                nombre_banco: "",
                numero_cuenta: "",
                tipo_cuenta: "Cuenta corriente"
            },
            errores: {},

            itemsPerPage: 5,

            selectedBank: "",
            filteredBanks: {},
            showDropdown: false,
            arrayBancos: [],
            modal: 0,
            tituloModal: '',
            tipoAccion: 0,
            pagination: {
                'total': 0,
                'current_page': 0,
                'per_page': 0,
                'last_page': 0,
                'from': 0,
                'to': 0,
            },
            offset: 3,
            criterio: 'nombre_cuenta',
            buscar: ''
        }
    },
    computed: {
        isActived: function () {
            return this.pagination.current_page;
        },
        pagesNumber: function () {
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
        seleccionarBanco(code) {
            this.selectedBankCode = code;
            this.selectedBank = this.bancos[code];
            this.datosFormulario.nombre_banco = this.selectedBank;

            this.paso = 2; // ir al paso 2
        },

        volverAPaso1() {
            this.paso = 1;
            this.selectedBank = null;
            this.selectedBankCode = null;

            this.datosFormulario.nombre_banco = '';
        },
        showBanks() {
            this.filteredBanks = this.bancos;
            this.showDropdown = true;
        },
        mostrarLogoBanco(banco) {
            this.selectedBanco = banco;
            this.dialogLogoVisible = true;
        },
        handleResize() {
            this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
        },
        resetBusqueda() {
            this.buscar = "";
            this.listarBancos(1, this.buscar);
        },
        onBuscarInput() {
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }
            this.searchTimeout = setTimeout(() => {
                this.listarBancos(1, this.buscar);
            }, 300);
        },
        async validarCampo(campo) {
            try {
                await esquemaBanco.validateAt(campo, this.datosFormulario);
                this.errores[campo] = null;
            } catch (error) {
                this.errores[campo] = error.message;
            }
        },
        async enviarFormulario() {

            await esquemaBanco.validate(this.datosFormulario, { abortEarly: false })
                .then(() => {
                    console.log(this.datosFormulario)
                    if (this.tipoAccion == 2) {
                        this.actualizarBanco(this.datosFormulario);
                    } else {
                        this.registrarBanco(this.datosFormulario);
                    }
                })
                .catch((error) => {
                    const erroresValidacion = {};
                    error.inner.forEach((e) => {
                        erroresValidacion[e.path] = e.message;
                    });

                    this.errores = erroresValidacion;
                });
        },

        formatFecha(fechaOriginal) {
            const fecha = new Date(fechaOriginal);
            const dia = fecha.getDate();
            const mes = fecha.getMonth() + 1;
            const anio = fecha.getFullYear();

            const diaFormateado = dia < 10 ? `0${dia}` : dia;
            const mesFormateado = mes < 10 ? `0${mes}` : mes;

            return `${diaFormateado}-${mesFormateado}-${anio}`;
        },
        getBankUrl(bankName) {
            const code = this.getBankCodeByName(bankName);
            return code ? `img/bancos/${code.toUpperCase()}.png` : null;
        },
        getBankCodeByName(bankName) {
            const lowerCaseName = bankName.toLowerCase();
            for (const [code, name] of Object.entries(this.bancos)) {
                if (name.toLowerCase() === lowerCaseName) {
                    return code;
                }
            }
            return null;
        },
        filterBanks() {

            const query = this.selectedBank.toLowerCase();
            const filteredEntries = Object.entries(this.bancos).filter(([code, country]) =>
                country.toLowerCase().includes(query)
            );
            this.filteredBanks = Object.fromEntries(filteredEntries.slice(0, 5));
            this.showDropdown = filteredEntries.length > 0;

        },
        selectBank(code) {
            this.selectedBank = this.bancos[code];
            this.showDropdown = false;
            this.pais = this.selectedBank;
            this.datosFormulario.nombre_banco = this.pais;
            this.validarCampo('nombre_banco');


        },
        selectClosestCountry() {
            const firstBankCode = Object.keys(this.filteredBanks)[0];
            if (firstBankCode) {
                this.selectBank(firstBankCode);
            }
        },
        listarBancos(page = 1, search = '') {
            axios.get('/bancos', {
                params: {
                    page: page,
                    buscar: search
                }
            })
                .then(response => {
                    this.arrayBancos = response.data.bancos.data;
                    this.pagination = response.data.pagination;
                    console.log(response);
                })
                .catch(error => {
                    console.error('Error al obtener los datos de los bancos:', error);
                });
        },
        cambiarPagina(page, buscar, criterio) {
            let me = this;
            me.pagination.current_page = page;
            me.listarBancos(page, buscar);
        },
        registrarBanco(datos) {
            this.isLoading = true;
            axios.post('/bancos/registrar', datos)
                .then(response => {
                    this.cerrarModal();

                    this.listarBancos(1, '');
                    this.toastSuccess("banco registrada correctamente");


                })
                .catch(error => {
                    console.error('Error al agregar un banco:', error);
                })
                .finally(() => {
                    // Desactivar loading con un pequeño retraso visual
                    setTimeout(() => {
                        this.isLoading = false;
                    }, 500);
                });

        },

        actualizarBanco(datos) {
            this.isLoading = true;
            console.log(datos)

            axios.put(`/bancos/actualizar`, datos)
                .then(response => {
                    this.cerrarModal();
                    this.toastSuccess("banco actualizada correctamente")
                    this.listarBancos(1, '');
                })
                .catch(error => {
                    console.error('Error al actualizar el banco:', error);
                })
                .finally(() => {
                    // Desactivar loading con un pequeño retraso visual
                    setTimeout(() => {
                        this.isLoading = false;
                    }, 500);
                });
        },


        cerrarModal() {
            this.modal = false;
            this.paso = 1; // volver al paso 1 siempre

            this.selectedBank = '';
            this.selectedBankCode = null;

            this.showDropdown = false;
            this.tituloModal = '';
        },
        abrirModal(modelo, accion, data = []) {
            switch (modelo) {
                case "banco": {
                    switch (accion) {

                        /* ======================
                        REGISTRAR BANCO
                        ======================= */
                        case 'registrar': {
                            this.modal = true;
                            this.tituloModal = 'Registrar Banco';
                            this.tipoAccion = 1;

                            // Form vacío
                            this.datosFormulario = {
                                nombre_cuenta: "",
                                nombre_banco: "",
                                numero_cuenta: "",
                                tipo_cuenta: "Cuenta corriente"
                            };

                            this.errores = {};

                            // PASO 1: Seleccionar banco
                            this.paso = 1;
                            this.selectedBank = null;
                            this.selectedBankCode = null;

                            break;
                        }

                        /* ======================
                        ACTUALIZAR BANCO
                        ======================= */
                        case 'actualizar': {
                            this.modal = true;
                            this.tituloModal = 'Actualizar Banco';
                            this.tipoAccion = 2;

                            this.datosFormulario = {
                                id: data['id'],
                                nombre_cuenta: data['nombre_cuenta'],
                                nombre_banco: data['nombre_banco'],
                                numero_cuenta: data['numero_cuenta'],
                                tipo_cuenta: data['tipo_cuenta']
                            };

                            this.errores = {};

                            // PASO 2 directamente
                            this.paso = 2;

                            // Seteo del banco seleccionado
                            this.selectedBank = data['nombre_banco'];

                            // Si quieres obtener el código del banco también:
                            const bankCode = Object.keys(this.bancos)
                                .find(code => this.bancos[code] === data['nombre_banco']);

                            this.selectedBankCode = bankCode || null;

                            break;
                        }
                    }
                }
            }
        },
        toastSuccess(mensaje) {
            this.$toasted.show(`
    <div style="height: 60px;font-size:16px;">
        <br>
        `+ mensaje + `.<br>
    </div>`, {
                type: "success",
                position: "bottom-right",
                duration: 4000
            });
        },
        toastError(mensaje) {
            this.$toasted.show(`
    <div style="height: 60px;font-size:16px;">
        <br>
        `+ mensaje + `<br>
    </div>`, {
                type: "error",
                position: "bottom-right",
                duration: 4000
            });
        }
    },
    async mounted() {
        this.handleResize();
        window.addEventListener("resize", this.handleResize);
        try {
            this.isLoading = true; // Activar loading
            await this.listarBancos(1, this.buscar);
        } catch (error) {
            console.error("Error en la carga inicial:", error);
            swal("Error", "Error al cargar los datos iniciales", "error");
        } finally {
            setTimeout(() => {
                this.isLoading = false; // Desactivar loading
            }, 500);
        }
    },
    beforeUnmount() {
        window.removeEventListener("resize", this.handleResize);
    },
}
</script>
<style scoped>
.required-field {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 4px;
}

.required-icon {
    color: #e74c3c;
    font-size: 1rem;
    font-weight: bold;
    margin-right: 0.2rem;
}

/* Estilos para campos opcionales */
.optional-field {
    display: flex;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 4px;
    align-items: center;
    gap: 0.4rem;
    font-weight: 500;
    color: #6c757d;
}

.optional-icon {
    color: #17a2b8;
    font-size: 0.5rem;
}

.tabla-pro {
    width: 100%;
    white-space: nowrap;
    /* evita salto de columnas */
    overflow-x: auto;
}

.tabla-pro .p-datatable-wrapper {
    overflow-x: auto;
}

.tabla-pro th,
.tabla-pro td {
    text-align: center;
    vertical-align: middle;
    font-size: 0.85rem;
    padding: 0.5rem;
}

.tabla-pro img {
    border-radius: 4px;
    object-fit: contain;
}

/* 🔹 Estilo general uniforme para todos los inputs */
.input-uniforme {
    width: 50%;
    font-size: 0.8rem;
    padding: 6px 8px;
    border-radius: 6px;
    box-sizing: border-box;
    height: 30px;
}

.input-cambio {
    width: 100%;
    font-size: 0.8rem;
    padding: 6px 8px;
    border-radius: 6px;
    box-sizing: border-box;
    height: 30px;
}

/* 🔹 Addon uniforme */
.addon-small {
    background-color: #f3f4f6;
    font-size: 0.8rem;
    color: #374151;
    border-radius: 6px 0 0 6px;
    padding: 6px 10px;
}

/* 🔹 Alinear grupos de inputs */
.custom-input-group .form-control {
    border-radius: 0 6px 6px 0;
    font-size: 0.8rem;
    height: 33px;
}

/* 🔹 Input deshabilitado o de solo lectura */
.form-control[readonly],
.form-control:disabled {
    background-color: #f9fafb;
    color: #6b7280;
}

.optional-tag {
    background-color: #eff6ff;
    color: #2563eb;
    font-size: 0.7rem;
    border-radius: 4px;
    padding: 0.1rem 0.3rem;
    margin-left: 4px;
}

/* 🔹 Contenedor del input y el botón */
.p-inputgroup {
    display: flex;
    align-items: stretch;
    width: 100%;
}

/* 🔹 Input principal (Buscar Producto) */
.input-full {
    width: 100%;
    font-size: 0.8rem;
    padding: 6px 8px;
    border-radius: 6px 0 0 6px;
    box-sizing: border-box;
}

/* Ajuste para InputText de PrimeVue */
.input-full>>>.p-inputtext {
    width: 100% !important;
    font-size: 0.8rem;
    padding: 6px 8px;
    border-radius: 6px 0 0 6px;
}

/* 🔹 Botón de búsqueda */
.p-inputgroup .p-button {
    border-radius: 0 6px 6px 0;
    font-size: 0.8rem;
    padding: 6px 10px;
}

/* 🔹 Label obligatorio */
.label-input {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 4px;
}

.text-required {
    color: #dc2626;
    /* rojo */
    font-weight: 700;
}

/* Estilo uniforme para Dropdown (igual que InputText) */
.dropdown-full {
    width: 100% !important;
    font-size: 0.8rem;
    border-radius: 6px;
    box-sizing: border-box;
}

/* Input dentro del dropdown */
.dropdown-full>>>.p-dropdown-label {
    padding: 6px 8px !important;
    font-size: 0.8rem;
}

/* Flecha del dropdown */
.dropdown-full>>>.p-dropdown-trigger {
    width: 2rem !important;
}

/* Borde al focus */
.dropdown-full>>>.p-dropdown {
    border: 1px solid #ccc;
    transition: border 0.2s;
}

.dropdown-full>>>.p-dropdown.p-focus {
    border-color: #0ea5e9;
    box-shadow: 0 0 0 0.15rem rgba(14, 165, 233, 0.25);
}

/* 🔹 Opciones del panel (lista desplegable) */
.dropdown-full>>>.p-dropdown-panel .p-dropdown-item {
    font-size: 0.8rem !important;
    padding: 6px 10px !important;
    min-height: auto !important;
    /* evita que queden muy grandes */
}

.form-section {
    margin-bottom: 1rem;
}

.input-label {
    display: block;
    font-size: 0.8rem;
    /* más pequeño */
    font-weight: 600;
    /* seminegrita */
    color: #374151;
    /* gris oscuro elegante */
    margin-bottom: 0.25rem;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    /* opcional: da un toque más pro */
}

.p-error {
    font-weight: 700;
    font-size: 0.8rem;
}

/* ===== CONTENEDOR GENERAL ===== */
.detalle-venta-pro {
    background: #ffffff;
    border-radius: 0.5rem;
    padding: 0.75rem;
    max-width: 1500px;
    margin: 0 auto;
    box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
    animation: fadeIn 0.4s ease;
    font-family: 'Inter', 'Segoe UI', sans-serif;
}

/* ===== ENCABEZADO ===== */
.detalle-header-pro {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    border-bottom: 2px solid #f1f5f9;
    padding-bottom: 0.1rem;
    margin-bottom: 1rem;
}

.detalle-titulo-pro {
    font-size: 1.2rem;
    font-weight: 700;
    color: #111827;
    margin: 0;
}

.detalle-subtitulo-pro {
    font-size: 0.7rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.detalle-meta-pro {
    display: flex;
    gap: 2rem;
}

.label-pro {
    font-size: 0.7rem;
    text-transform: uppercase;
    color: #6b7280;
    letter-spacing: 0.04em;
}

.valor-pro {
    font-size: 0.8rem;
    font-weight: 600;
    color: #111827;
    margin-top: 0.15rem;
}

/* ===== CLIENTE ===== */
.detalle-cliente-pro {
    background: #f9fafb;
    border-radius: 0.2rem;
    padding: 0.05rem 1rem;
    margin-bottom: 1.0rem;
    border: 1px solid #e5e7eb;
}

/* ===== TABLA ===== */
.detalle-tabla-pro .p-datatable {
    border-radius: 0.2rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.p-datatable tbody tr:hover {
    background-color: #f9fafb;
}

/* ===== RESUMEN ===== */
.detalle-resumen-pro {
    margin-top: 1.2rem;
    border-top: 1px solid #e5e7eb;
    padding-top: 0.7rem;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
    text-align: right;
}

.resumen-linea-pro {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    font-size: 0.8rem;
    color: #374151;
}

.total-final-pro {
    font-size: 0.8rem;
    font-weight: 700;
    color: #111827;
}

/* ===== FOOTER ===== */
.detalle-footer-pro {
    margin-top: 2rem;
    text-align: right;
}

/* ===== ANIMACIÓN ===== */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.input-con-desplegable {
    position: relative;
    width: 100%;
}

.desplegable-simple {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    max-height: 180px;
    /* ligeramente más compacto */
    overflow-y: auto;
    background: #ffffff;
    border: 1px solid #d1d5db;
    /* color gris suave como otros inputs */
    border-radius: 6px;
    /* mismo borde que inputs */
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    list-style: none;
    padding: 0;
    margin: 2px 0 0 0;
    font-size: 0.8rem;
    /* tamaño uniforme */
}

.desplegable-simple li {
    padding: 6px 8px;
    /* igual que los inputs */
    cursor: pointer;
    transition: background-color 0.2s;
}

.desplegable-simple li:hover,
.desplegable-simple li.seleccionado {
    background-color: #f1f5f9;
    /* color azul muy claro, uniforme con info-box */
}

/* Panel Content Spacing */
>>>.p-panel .p-panel-content {
    padding: 1rem;
}

>>>.p-panel .p-panel-header {
    padding: 0.75rem 1rem;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
}

>>>.p-panel .p-panel-header .p-panel-title {
    font-weight: 600;
}

/* Arreglar icono de lupa - Centrado perfecto */
.search-bar .p-input-icon-left {
    position: relative;
    width: 100%;
}

.search-bar .p-input-icon-left i {
    position: absolute;
    left: 0.75rem;
    top: 0;
    bottom: 0;
    margin: auto 0;
    height: 1rem;
    z-index: 2;
    color: #6c757d;
    pointer-events: none;
    display: flex;
    align-items: center;
    line-height: 1;
}

.search-bar .p-input-icon-left .p-inputtext {
    padding-left: 2.5rem !important;
    width: 100%;
}

.panel-header {
    display: flex;
    align-items: center;
}

.panel-title {
    margin-left: 8px;
}

/* Responsive Dialog Styles */
.responsive-dialog>>>.p-dialog {
    margin: 0.75rem;
    max-height: 90vh;
    overflow-y: auto;
}

.responsive-dialog>>>.p-dialog-content {
    overflow-x: auto;
    padding: 0.8rem;
}

.responsive-dialog>>>.p-dialog-header {
    padding: 1rem 0.75rem;
    font-size: 1.1rem;
}

.responsive-dialog>>>.p-dialog-footer {
    padding: 0.75rem 1rem;
    gap: 0.5rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

/* Toolbar Responsive - Mantener en una línea */
.toolbar-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    gap: 0.75rem;
    flex-wrap: nowrap;
}

.toolbar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-shrink: 0;
}

.search-bar {
    flex-grow: 1;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    min-width: 0;
    margin-right: 1rem;
}

/* DataTable Responsive */
>>>.p-datatable {
    font-size: 0.75rem;
}

>>>.p-datatable .p-datatable-tbody>tr>td {
    padding: 0.4rem;
    word-break: break-word;
    text-align: left;
}

>>>.p-datatable .p-datatable-thead>tr>th {
    padding: 0.35rem 0.4rem;
    font-size: 0.75rem;
}

/* Inputs compactos en la tabla de detalles */
>>>.p-datatable .p-inputnumber {
    height: 32px !important;
    width: 100% !important;
}

>>>.p-datatable .p-inputnumber .p-inputtext {
    height: 32px !important;
    padding: 0.25rem 0.3rem !important;
    font-size: 0.875rem !important;
    width: 100% !important;
    text-align: center !important;
}

>>>.p-datatable .form-control-sm {
    height: 32px !important;
    padding: 0.25rem 0.3rem !important;
    font-size: 0.875rem !important;
    width: 100% !important;
    text-align: center !important;
}

/* Tablet Styles */
@media (max-width: 1024px) {
    .responsive-dialog>>>.p-dialog {
        margin: 0.5rem;
        max-height: 95vh;
    }

    >>>.p-datatable {
        font-size: 0.85rem;
    }
}

/* Mobile Styles */
@media (max-width: 768px) {
    .toolbar .p-button .p-button-label {
        display: none;
    }

    .responsive-dialog>>>.p-dialog {
        margin: 0.25rem;
        max-height: 98vh;
    }

    .responsive-dialog>>>.p-dialog-content {
        padding: 0.75rem;
    }

    .responsive-dialog>>>.p-dialog-header {
        padding: 0.75rem 1rem;
        font-size: 1rem;
    }

    .responsive-dialog>>>.p-dialog-footer {
        padding: 0.5rem 1rem;
        justify-content: flex-end;
    }

    .toolbar-container {
        gap: 0.5rem;
    }

    >>>.p-datatable {
        font-size: 0.8rem;
    }

    >>>.p-datatable .p-datatable-tbody>tr>td {
        padding: 0.4rem 0.3rem;
    }

    >>>.p-datatable .p-datatable-thead>tr>th {
        padding: 0.5rem 0.3rem;
        font-size: 0.75rem;
    }

    /* Ajustar botones en móviles */
    >>>.p-button-sm {
        font-size: 0.75rem !important;
        padding: 0.375rem 0.5rem !important;
        min-width: auto !important;
    }

    /* Reducir altura del input buscador */
    .search-bar .p-inputtext-sm {
        padding: 0.35rem 0.5rem 0.35rem 2.5rem !important;
        font-size: 0.85rem !important;
    }

    /* Inputs más compactos en móviles */
    >>>.p-datatable .p-inputnumber {
        height: 28px !important;
        width: 100% !important;
    }

    >>>.p-datatable .p-inputnumber .p-inputtext {
        height: 28px !important;
        padding: 0.2rem 0.25rem !important;
        font-size: 0.8rem !important;
        width: 100% !important;
        text-align: center !important;
    }

    >>>.p-datatable .form-control-sm {
        height: 28px !important;
        padding: 0.2rem 0.25rem !important;
        font-size: 0.8rem !important;
        width: 100% !important;
        text-align: center !important;
    }
}

/* Extra Small Mobile */
@media (max-width: 480px) {
    .toolbar .p-button .p-button-label {
        display: none;
    }

    .responsive-dialog>>>.p-dialog {
        margin: 0.1rem;
        max-height: 99vh;
    }

    .responsive-dialog>>>.p-dialog-content {
        padding: 0.5rem;
    }

    .responsive-dialog>>>.p-dialog-header {
        padding: 0.5rem 0.75rem;
        font-size: 0.95rem;
    }

    .responsive-dialog>>>.p-dialog-footer {
        padding: 0.5rem 0.75rem;
        justify-content: flex-end;
    }

    .responsive-dialog>>>.p-dialog-footer .p-button {
        width: auto;
        margin-bottom: 0.25rem;
    }

    /* Toolbar mantiene elementos en una línea */
    .toolbar-container {
        gap: 0.4rem;
        flex-wrap: nowrap;
    }

    .toolbar {
        flex-shrink: 0;
        min-width: auto;
    }

    .search-bar {
        flex: 1;
        min-width: 0;
    }

    /* Reducir más la altura del input buscador en móviles pequeños */
    .search-bar .p-inputtext-sm {
        padding: 0.3rem 0.5rem 0.3rem 2.5rem !important;
        font-size: 0.8rem !important;
    }

    >>>.p-datatable {
        font-size: 0.75rem;
    }

    >>>.p-datatable .p-datatable-tbody>tr>td {
        padding: 0.3rem 0.2rem;
    }

    >>>.p-datatable .p-datatable-thead>tr>th {
        padding: 0.4rem 0.2rem;
        font-size: 0.7rem;
    }

    /* Inputs extra compactos en móviles pequeños */
    >>>.p-datatable .p-inputnumber {
        height: 26px !important;
        width: 100% !important;
    }

    >>>.p-datatable .p-inputnumber .p-inputtext {
        height: 26px !important;
        padding: 0.15rem 0.2rem !important;
        font-size: 0.75rem !important;
        width: 100% !important;
        text-align: center !important;
    }

    >>>.p-datatable .form-control-sm {
        height: 26px !important;
        padding: 0.15rem 0.2rem !important;
        font-size: 0.75rem !important;
        width: 100% !important;
        text-align: center !important;
    }
}

/* Paginator Responsive */
@media (max-width: 768px) {
    >>>.p-paginator {
        flex-wrap: wrap !important;
        justify-content: center;
        font-size: 0.85rem;
        padding: 0.5rem;
    }

    >>>.p-paginator .p-paginator-page,
    >>>.p-paginator .p-paginator-next,
    >>>.p-paginator .p-paginator-prev,
    >>>.p-paginator .p-paginator-first,
    >>>.p-paginator .p-paginator-last {
        min-width: 32px !important;
        height: 32px !important;
        font-size: 0.85rem !important;
        padding: 0 6px !important;
        margin: 2px !important;
    }
}

@media (max-width: 480px) {
    >>>.p-paginator {
        font-size: 0.8rem;
        padding: 0.4rem;
    }

    >>>.p-paginator .p-paginator-page,
    >>>.p-paginator .p-paginator-next,
    >>>.p-paginator .p-paginator-prev,
    >>>.p-paginator .p-paginator-first,
    >>>.p-paginator .p-paginator-last {
        min-width: 28px !important;
        height: 28px !important;
        font-size: 0.8rem !important;
        padding: 0 4px !important;
        margin: 1px !important;
    }
}

/* Action Buttons in DataTable */
>>>.p-datatable .p-button {
    margin-right: 0.25rem;
}

@media (max-width: 768px) {
    >>>.p-datatable .p-button {
        margin-right: 0.15rem;
        margin-bottom: 0.15rem;
    }
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

.step-indicators {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.step {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #ccc;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.step.active {
    background-color: #007bff;
    color: white;
}

.step.completed {
    background-color: #28a745;
    color: white;
}

.modal-header {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    padding: 0.5rem 0rem;
    margin: 0;
    box-sizing: border-box;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
}

.modal-title {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
    color: #111827;
    letter-spacing: -0.01em;
    flex: 1;
    text-align: left;
}

.close-button {
    border: none;
    background: #de0000;
    color: #ffffff;
    font-size: 1rem;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.close-button:hover {
    background: #e5e7eb;
    color: #111827;
    transform: scale(1.05);
    cursor: pointer;
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

.p-dialog-mask {
    z-index: 9990 !important;
}

.p-dialog {
    z-index: 9990 !important;
}

.swal2-zindex-fix {
    z-index: 100050 !important;
}

/* Estilos específicos para las columnas de inputs en la tabla de ventas */
.column-precio-unidad,
.column-unidades,
.column-descuento {
    min-width: 80px !important;
    max-width: 120px !important;
}

/* Estilos para los inputs de precio, cantidad y descuento */
.input-precio-unidad,
.input-unidades,
.input-descuento {
    width: 100% !important;
    min-width: 70px !important;
    max-width: 110px !important;
    text-align: center !important;
    font-size: 0.8rem !important;
    padding: 0.2rem 0.3rem !important;
}

/* Responsive para tablets */
@media (max-width: 768px) {

    .column-precio-unidad,
    .column-unidades,
    .column-descuento {
        min-width: 70px !important;
        max-width: 90px !important;
    }

    .input-precio-unidad,
    .input-unidades,
    .input-descuento {
        min-width: 60px !important;
        max-width: 80px !important;
        font-size: 0.75rem !important;
        padding: 0.15rem 0.2rem !important;
    }
}

/* Responsive para móviles */
@media (max-width: 480px) {

    .column-precio-unidad,
    .column-unidades,
    .column-descuento {
        min-width: 60px !important;
        max-width: 75px !important;
    }

    .input-precio-unidad,
    .input-unidades,
    .input-descuento {
        min-width: 55px !important;
        max-width: 70px !important;
        font-size: 0.7rem !important;
        padding: 0.1rem 0.15rem !important;
    }

    /* Ajustar headers de las columnas en móviles */
    >>>.p-datatable .p-column-header-content {
        font-size: 0.7rem !important;
        padding: 0.3rem 0.2rem !important;
    }

    /* Hacer que las columnas de inputs sean más compactas */
    >>>.p-datatable .p-datatable-tbody>tr>td.column-precio-unidad,
    >>>.p-datatable .p-datatable-tbody>tr>td.column-unidades,
    >>>.p-datatable .p-datatable-tbody>tr>td.column-descuento {
        padding: 0.2rem 0.1rem !important;
    }
}
</style>

<style>
/* SweetAlert v1 (swal) */
.sweet-alert {
    z-index: 99999 !important;
}

.sweet-overlay {
    z-index: 99998 !important;
}

/* SweetAlert v2 (Swal) */
.swal2-container {
    z-index: 99999 !important;
}

.swal2-popup {
    z-index: 99999 !important;
}

.swal2-backdrop-show {
    z-index: 99998 !important;
}

/* Clase personalizada para z-index */
.swal-zindex {
    z-index: 99999 !important;
}

/* Asegurar que todos los elementos de SweetAlert estén por encima */
div[class*="swal"] {
    z-index: 99999 !important;
}
</style>

<style>
/* Estilos para dispositivos móviles */
@media (max-width: 768px) {

    .p-dropdown,
    .p-inputtext {
        height: 32px !important;
        font-size: 0.875rem !important;
        padding: 0.25rem 0.5rem !important;
    }

    /* FORZAR tamaño y centrado del texto seleccionado en el Dropdown */
    .p-dropdown .p-dropdown-label {
        height: 100% !important;
        display: flex !important;
        align-items: center !important;
        font-size: 0.75rem !important;
        padding: 0 0.4rem !important;
    }

    .p-dropdown .p-dropdown-label span,
    .p-dropdown .p-dropdown-label-container {
        font-size: 0.75rem !important;
        line-height: 1 !important;
        display: flex !important;
        align-items: center !important;
        height: 100% !important;
    }

    .p-dropdown .p-dropdown-trigger {
        height: 100% !important;
        display: flex !important;
        align-items: center !important;
        padding: 0 0.3rem !important;
    }

    /* Ajustar el panel del dropdown */
    .p-dropdown-panel .p-dropdown-items .p-dropdown-item {
        padding: 0.25rem 0.5rem !important;
        font-size: 0.875rem !important;
    }

    /* Forzar el tamaño del texto seleccionado */
    .p-dropdown .p-dropdown-label,
    .p-dropdown .p-dropdown-label span {
        font-size: 0.75rem !important;
        line-height: 1.1 !important;
    }

    .p-dropdown .p-dropdown-label-container {
        display: flex !important;
        align-items: center !important;
        height: 100% !important;
    }

    /* Resto de tus estilos */
    .p-inputgroup .p-inputtext {
        height: 32px !important;
    }

    .p-inputgroup .p-button {
        height: 32px !important;
        width: 32px !important;
        padding: 0.25rem !important;
    }

    #buscarA {
        height: 32px !important;
        padding: 0.25rem 0.5rem !important;
        font-size: 0.875rem !important;
    }

    .p-float-label .p-inputtext {
        height: 36px !important;
        padding: 0.5rem 0.75rem !important;
        font-size: 0.875rem !important;
    }

    .p-float-label {
        margin-bottom: 1rem !important;
        position: relative !important;
    }

    .p-float-label label {
        top: 50% !important;
        left: 0.75rem !important;
        transform: translateY(-50%) !important;
        font-size: 0.875rem !important;
        transition: all 0.2s ease !important;
        pointer-events: none !important;
    }

    .p-float-label .p-inputtext:focus~label,
    .p-float-label .p-inputtext.p-filled~label,
    .p-float-label input:not(:placeholder-shown)~label {
        top: -0.25rem !important;
        left: 0.75rem !important;
        font-size: 0.75rem !important;
        transform: translateY(0) !important;
        background: white !important;
        padding: 0 0.25rem !important;
        z-index: 1 !important;
    }

    .step-content h5 {
        margin-bottom: 1rem !important;
        font-size: 1.25rem !important;
    }

    .step-content .p-grid {
        margin-top: 0.5rem !important;
    }

    .card .form-control,
    .input-group .form-control {
        height: 32px !important;
        font-size: 0.875rem !important;
        padding: 0.25rem 0.5rem !important;
    }

    .input-group-text {
        height: 32px !important;
        font-size: 0.875rem !important;
        padding: 0.25rem 0.5rem !important;
        display: flex !important;
        align-items: center !important;
    }

    .card-body label {
        font-size: 0.875rem !important;
        margin-bottom: 0.25rem !important;
    }

    .p-datatable .p-inputtext-sm,
    .p-datatable .input-precio-unidad,
    .p-datatable .input-unidades input,
    .p-datatable .input-descuento input {
        height: 28px !important;
        font-size: 0.75rem !important;
        padding: 0.2rem 0.3rem !important;
    }

    .p-datatable .p-inputnumber input {
        height: 28px !important;
        font-size: 0.75rem !important;
        padding: 0.2rem 0.3rem !important;
    }

    .p-datatable .p-datatable-tbody>tr>td {
        padding: 0.3rem !important;
    }

    .p-datatable .p-button-sm {
        padding: 0.2rem 0.3rem !important;
        font-size: 0.7rem !important;
    }

    .responsive-dialog .p-dialog-content {
        padding: 0.75rem !important;
    }

    .step-content {
        padding: 0.5rem !important;
    }

    .buttons {
        margin-top: 1rem !important;
        gap: 0.5rem !important;
    }

    .buttons .btn {
        padding: 0.5rem 1rem !important;
        font-size: 0.875rem !important;
    }
}

/* Estilos adicionales para mejorar la experiencia móvil */
@media (max-width: 480px) {

    .p-dropdown,
    .p-inputtext {
        height: 30px !important;
        font-size: 0.8rem !important;
        padding: 0.2rem 0.4rem !important;
    }

    /* FORZAR tamaño del texto del Dropdown en pantallas pequeñas */
    .p-dropdown .p-dropdown-label {
        font-size: 0.7rem !important;
        padding: 0 0.3rem !important;
        height: 30px !important;
        display: flex !important;
        align-items: center !important;
    }

    .p-dropdown .p-dropdown-label span,
    .p-dropdown .p-dropdown-label-container {
        font-size: 0.7rem !important;
        height: 100% !important;
        display: flex !important;
        align-items: center !important;
    }

    .p-dropdown-panel .p-dropdown-items .p-dropdown-item {
        padding: 0.2rem 0.4rem !important;
        font-size: 0.8rem !important;
    }

    .p-float-label .p-inputtext {
        height: 34px !important;
        padding: 0.4rem 0.6rem !important;
        font-size: 0.8rem !important;
    }

    .p-float-label label {
        font-size: 0.8rem !important;
        left: 0.6rem !important;
    }

    .p-float-label .p-inputtext:focus~label,
    .p-float-label .p-inputtext.p-filled~label,
    .p-float-label input:not(:placeholder-shown)~label {
        top: -0.2rem !important;
        left: 0.6rem !important;
        font-size: 0.7rem !important;
    }

    .step-content h5 {
        font-size: 1.1rem !important;
        margin-bottom: 0.75rem !important;
    }

    .card .form-control,
    .input-group .form-control {
        height: 30px !important;
        font-size: 0.8rem !important;
        padding: 0.2rem 0.4rem !important;
    }

    .input-group-text {
        height: 30px !important;
        font-size: 0.8rem !important;
        padding: 0.2rem 0.4rem !important;
    }

    .p-datatable .p-datatable-tbody>tr>td {
        padding: 0.25rem !important;
    }

    .p-datatable .p-inputtext-sm,
    .p-datatable .input-precio-unidad,
    .p-datatable .input-unidades input,
    .p-datatable .input-descuento input,
    .p-datatable .p-inputnumber input {
        height: 26px !important;
        font-size: 0.7rem !important;
        padding: 0.15rem 0.25rem !important;
    }

    .text-green {
        color: green;
        font-weight: bold;
    }

    .text-red {
        color: red;
        font-weight: bold;
    }

    .text-orange {
        color: orange;
        font-weight: bold;
    }

    .text-purple {
        color: purple;
        font-weight: bold;
    }

    .text-blue {
        color: blue;
        font-weight: bold;
    }

    .text-yellow {
        color: brown;
        font-weight: bold;
    }
}
</style>
