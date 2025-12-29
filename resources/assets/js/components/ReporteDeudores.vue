<template>
  <main class="main">
    <div class="loading-overlay" v-if="isLoading">
      <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">LOADING...</div>
      </div>
    </div>
    <Toast :breakpoints="{ '920px': { width: '100%', right: '0', left: '0' } }" style="padding-top: 10px;"
      appendTo="body" :baseZIndex="99999" />
    <Panel :toggleable="false" class="cliente-panel">
      <template #header>
        <div class="panel-header">
          <i class="pi pi-users panel-icon"></i>
          <h4 class="panel-title">CLIENTES</h4>
        </div>
      </template>
      <div class="toolbar-layout">
        <div class="item item-busqueda">
          <label class="label-input">Buscar Clientes</label>
          <div class="p-inputgroup">

            <span class="p-inputgroup-addon">
              <i class="pi pi-search"></i>
            </span>
            <InputText v-model="buscar" placeholder="Buscar cliente..." class="p-inputtext-sm"
              @input="listarPersona(buscar, criterio)" />
            <Button icon="pi pi-refresh" class="p-button-help p-button-sm" @click="resetBuscar" />
          </div>
        </div>

        <!-- 📅 Fecha Inicio -->
        <div class="item item-fecha">
          <label class="label-input">Fecha inicio</label>
          <Calendar v-model="fechaInicio" dateFormat="yy/mm/dd" placeholder="Fecha inicio" class="p-inputtext-sm"
            :touchUI="true" showIcon />
        </div>

        <!-- 📅 Fecha Fin -->
        <div class="item item-fecha">
          <label class="label-input">Fecha Fin</label>
          <Calendar v-model="fechaFin" dateFormat="yy/mm/dd" placeholder="Fecha fin" class="p-inputtext-sm"
            :touchUI="true" showIcon />
        </div>

        <!-- 🌍 Ubicación -->
        <div class="item item-ubicacion">
          <label class="label-input">Ubicación</label>
          <select class="select-ubicacion" v-model="ubicacionSeleccionada" @change="filtrarPorUbicacion">
            <option value="">Todas las ubicaciones</option>
            <option v-for="(u, i) in ubicacionesUnicas" :key="i" :value="u">
              {{ u }}
            </option>
          </select>
        </div>

      </div>
      <DataTable :value="arrayPersona" class="p-datatable-gridlines p-datatable-sm" :paginator="true" :rows="11"
        responsiveLayout="scroll">
        <Column field="nombre" header="Nombres" class="d-none d-md-table-cell"></Column>
        <Column header="Documento de Identidad" class="d-none d-md-table-cell">
          <template #body="slotProps">
            {{ getDocumentoIdentidad(slotProps.data) }}
          </template>
        </Column>
        <Column header="Teléfono">
          <template #body="slotProps">
            <span v-if="formatTelefono(slotProps.data).existe">
              {{ formatTelefono(slotProps.data).texto }}
            </span>
            <span v-else class="text-red-500 flex items-center gap-1" style="color: rgb(231, 76, 60)">
              <i class="pi pi-exclamation-triangle"></i>
              {{ formatTelefono(slotProps.data).texto }}
            </span>
          </template>
        </Column>
        <Column header="Dirección" class="d-none d-md-table-cell">
          <template #body="slotProps">
            <span v-if="slotProps.data.direccion && slotProps.data.direccion.trim() !== ''">
              {{ slotProps.data.direccion }}
            </span>
            <span v-else class="text-muted">
              -
            </span>
          </template>
        </Column>

        <Column header="Saldo Crédito">
          <template #body="slotProps">

            <span v-if="slotProps.data.saldo_credito_total > 0" class="badge bg-danger"
              style="padding: 4px 8px; border-radius: 4px;">
              {{ slotProps.data.saldo_credito_total.toFixed(2) }} Bs
            </span>

            <span v-else class="badge bg-success" style="padding: 4px 8px; border-radius: 4px;">
              0.00 Bs
            </span>

          </template>
        </Column>
        <Column header="Acciones" style="width: 100px;">
          <template #body="slotProps">
            <Button icon="pi pi-file-pdf" class="p-button-danger p-button-sm btn-mini"
              @click="abrirReporteCredito(slotProps.data)" />
          </template>
        </Column>
      </DataTable>
    </Panel>

    <!-- Dialog Cliente -->
    <Dialog :visible.sync="modal" :modal="true" :closable="false" :containerStyle="dialogContainerStyle">
      <!-- Header personalizado -->
      <template #header>
        <div class="dialog-header">
          <i class="pi pi-user header-icon"></i>
          <span class="header-title">{{ tituloModal }}</span>
        </div>
      </template>

      <!-- Formulario -->
      <form @submit.prevent="enviarFormulario" class="p-fluid form-compact">
        <!-- Nombre del cliente -->
        <div class="p-field input-container">
          <label for="nombre" class="label-input">
            Nombre del Cliente <span class="text-required">*</span>
          </label>
          <InputText id="nombre" v-model="datosFormulario.nombre" placeholder="Ingrese el nombre del cliente"
            autocomplete="off" class="input-full" :class="{ 'p-invalid': errores.nombre }"
            @input="validarCampo('nombre')" required autofocus />
          <small v-if="errores.nombre" class="text-error">{{ errores.nombre }}</small>
        </div>

        <!-- Tipo de documento -->
        <div class="p-field input-container">
          <label for="tipo_documento" class="label-input">
            Tipo de Documento <span class="text-required">*</span>
          </label>
          <Dropdown id="tipo_documento" v-model="datosFormulario.tipo_documento" :options="tipoDocumentoOptions"
            optionLabel="label" optionValue="value" placeholder="Selecciona un tipo de documento" class="dropdown-full"
            :class="{ 'p-invalid': errores.tipo_documento }" @change="validarCampo('tipo_documento')" />
          <small v-if="errores.tipo_documento" class="text-error">{{ errores.tipo_documento }}</small>
        </div>

        <!-- Número de documento -->
        <div class="p-field input-container">
          <label class="label-input">
            N° {{ getTipoDocumentoLabel() }} <span class="text-required">*</span>
          </label>
          <InputText v-model="datosFormulario.num_documento" :class="{ 'p-invalid': errores.num_documento }"
            placeholder="Ingrese el número" class="input-full" @input="validarCampo('num_documento')"
            autocomplete="off" />
          <small v-if="errores.num_documento" class="text-error">{{ errores.num_documento }}</small>
        </div>

        <!-- Complemento (opcional) -->
        <div class="p-field input-container">
          <label class="optional-field">
            <i class="pi pi-info-circle optional-icon"></i>
            Complemento <span class="optional-tag">Opcional</span>
          </label>
          <div class="p-inputgroup">
            <InputText id="complemento" v-model="datosFormulario.complemento" :disabled="!mostrarComplemento"
              placeholder="Ej. A o B" class="input-full" autocomplete="off" />
            <Button icon="pi pi-check" class="btn-sm btn-mini" @click="mostrarComplemento = !mostrarComplemento"
              type="button" />
          </div>
        </div>

        <!-- Teléfono (opcional) -->
        <div class="p-field input-container">
          <label class="optional-field">
            <i class="pi pi-phone optional-icon"></i>
            Teléfono <span class="optional-tag">Opcional</span>
          </label>
          <InputText id="telefono" v-model="datosFormulario.telefono" class="input-full" placeholder="Ej. 70000000"
            autocomplete="off" />
        </div>
      </form>

      <!-- Footer -->
      <template #footer>
        <div class="d-flex gap-2 justify-content-end modal-footer-buttons">
          <Button label="Cerrar" icon="pi pi-times" class="p-button-danger btn-sm" @click="cerrarModal" type="button" />
          <Button v-if="tipoAccion == 1" label="Guardar" icon="pi pi-check" class="p-button-success btn-sm"
            @click="enviarFormulario" type="button" />
          <Button v-if="tipoAccion == 2" label="Actualizar" icon="pi pi-refresh" class="p-button-warning btn-sm"
            @click="enviarFormulario" type="button" />
        </div>
      </template>
    </Dialog>

    <Dialog v-if="modalImportar" :visible.sync="modalImportar" :modal="true">
      <ImportarExcelCliente @cerrar="cerrarModalImportar"></ImportarExcelCliente>
    </Dialog>

    <!-- Dialog Configuración de Monto Bonificación -->
    <Dialog :visible.sync="dialogMontoCliente" :modal="true" :closable="false"
      :containerStyle="{ width: '480px', borderRadius: '10px', overflow: 'hidden' }">

      <!-- Header personalizado -->
      <template #header>
        <div class="dialog-header">
          <i class="pi pi-cog header-icon"></i>
          <span class="header-title">Configuración de Monto Bonificación</span>
        </div>
      </template>

      <!-- Contenido -->
      <div class="p-fluid form-compact">
        <p class="dialog-subtext">
          Define el monto mínimo de compras y si será acumulativo.
        </p>

        <!-- Campo Monto -->
        <div class="p-field">
          <label class="label-input">
            Monto mínimo de compras <span class="text-required">*</span>
          </label>
          <InputNumber v-model="montoCliente.monto" mode="currency" currency="BOB" locale="es-BO" :min="0"
            :maxFractionDigits="2" class="input-number-full" />
        </div>

        <!-- Fecha de actualización -->
        <div class="p-field">
          <label class="label-input">Última fecha de actualización</label>
          <InputText v-model="montoCliente.fecha_actualizacion" disabled class="input-full-disabled" />
        </div>

        <!-- Switch acumulativo -->
        <div class="p-field flex-field">
          <InputSwitch v-model="montoCliente.es_acumulativo" />
          <label class="label-input">¿Periodo de Tiempo?</label>
        </div>

        <!-- Información adicional -->
        <transition name="fade">
          <div v-if="montoCliente.es_acumulativo" class="info-box info-blue">
            <small>
              💡 Cuando el monto tiene <strong>periodo acumulativo</strong>, al finalizar el periodo desde la última
              fecha
              actualizada, los montos acumulados se reiniciarán.
            </small>

            <div class="p-field mt-2">
              <label class="label-input-blue">Fecha de inicio del periodo</label>
              <input type="date" v-model="montoCliente.fecha_inicio" class="input-full-date" />
            </div>

            <div class="p-field mt-2">
              <label class="label-input-blue">Periodo de acumulación (en meses)</label>
              <InputNumber v-model="montoCliente.periodo_meses" :min="1" :maxFractionDigits="0" placeholder="Ej. 3"
                class="input-number-full" />
              <small class="small-info">Indica cada cuántos meses se acumularán las compras.</small>
            </div>
          </div>

          <div v-else class="info-box info-red">
            <small>
              ⚠️ Cuando el monto <strong>no tiene periodo de acumulación</strong>, los clientes seguirán acumulando
              compras
              incluso después de alcanzar el monto mínimo.
            </small>
          </div>
        </transition>
      </div>

      <!-- Footer -->
      <template #footer>
        <div class="d-flex gap-2 justify-content-end modal-footer-buttons">
          <Button label="Cancelar" icon="pi pi-times" class="p-button-danger btn-sm" @click="dialogMontoCliente = false"
            type="button" />
          <Button label="Guardar" icon="pi pi-check" class="p-button-success btn-sm" @click="guardarMontoCliente"
            type="button" />
        </div>
      </template>
    </Dialog>
    <Dialog v-model:visible="dialogRango" header="Seleccionar rango de fechas" modal :style="{ width: '350px' }">
      <div class="p-field">
        <label>Rango de fechas</label>
        <Calendar v-model="rangoFechas" selectionMode="range" dateFormat="yy-mm-dd" showIcon
          class="p-inputtext-sm w-full" />
      </div>

      <div class="mt-3 flex justify-content-end gap-2">
        <Button label="Cancelar" class="p-button-secondary p-button-sm" @click="dialogRango = false" />
        <Button label="Generar" class="p-button-primary p-button-sm" @click="confirmarReporte()" />
      </div>
    </Dialog>

  </main>
</template>

<script>
import Swal from 'sweetalert2';
import Card from "primevue/card";
import Panel from "primevue/panel";
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Dropdown from "primevue/dropdown";
import AutoComplete from "primevue/autocomplete";
import Dialog from "primevue/dialog";
import Paginator from "primevue/paginator";
import Message from "primevue/message";
import InputNumber from "primevue/inputnumber";
import InputSwitch from "primevue/inputswitch";
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
import { esquemaCliente } from "../constants/validations";
import Calendar from 'primevue/calendar';
import Listbox from "primevue/listbox";
import { drop } from 'lodash';


export default {
  components: {
    Card,
    Panel,
    Button,
    DataTable,
    Column,
    InputText,
    Dropdown,
    AutoComplete,
    Dialog,
    Paginator,
    Message,
    InputSwitch,
    InputNumber,
    ToastService,
    Toast,
    Calendar,
    Listbox,
  },
  data() {
    return {
      dropdownVisible: false,
      ubicaciones: [],
      ubicacionSeleccionada: null,
      fechaInicio: null,
      fechaFin: null,
      rangoFechas: null,
      dialogRango: false,
      clienteSeleccionado: null,
      permitir_bonificacion: false,
      dialogMontoCliente: false,
      montoCliente: {
        id: null,
        monto: 0,
        fecha_actualizacion: "",
        es_acumulativo: false,
        periodo_meses: null, // nuevo campo
        fecha_inicio: "", // nuevo campo
      },
      mostrarLabel: true,
      isLoading: false,
      tipoDocumentoOptions: [
        { label: "CI - CEDULA DE IDENTIDAD", value: "1" },
        { label: "CEX - CEDULA DE IDENTIDAD DE EXTRANJERO", value: "2" },
        { label: "NIT - NÚMERO DE IDENTIFICACIÓN TRIBUTARIA", value: "5" },
        { label: "PAS - PASAPORTE", value: "3" },
        { label: "OD - OTRO DOCUMENTO DE IDENTIDAD", value: "4" },
      ],
      criterioOptions: [
        { label: "Búsqueda Global", value: "global" },
        { label: "Nombre", value: "nombre" },
        { label: "Documento", value: "num_documento" },
        { label: "Email", value: "email" },
        { label: "Teléfono", value: "telefono" },
      ],
      datosFormulario: {
        nombre: "",
        tipo_documento: "",
        num_documento: "",
        complemento: "",
      },
      errores: {},
      rolUsuario: "",
      arrayUsuarioV: [],
      usuarioSeleccionado: "",
      idusuario: "",
      arrayDetalleUsuario: [],
      activaredit: false,
      arrayUsuarioFiltro: [],
      usuarioSeleccionadodos: "",
      usuariodos_id: "",
      role_id: "",
      mostrarComplemento: false,
      modalImportar: false,
      arrayPersona: [],
      modal: false,
      tituloModal: "",
      tipoAccion: 0,
      errorPersona: 0,
      errorMostrarMsjPersona: [],
      pagination: {
        total: 0,
        current_page: 0,
        per_page: 0,
        last_page: 0,
        from: 0,
        to: 0,
      },
      criterio: "global",
      buscar: "",
    };
  },
  computed: {
    ubicacionesUnicas() {
      console.log("Ubicaciones cargadas:", this.ubicaciones);

      // Elimina duplicados y devuelve la lista final
      return [...new Set(this.ubicaciones)];
    },
    dialogContainerStyle() {
      if (window.innerWidth <= 480) {
        // Móviles pequeños
        return { width: "95vw", maxWidth: "380px", margin: "0 auto" };
      } else if (window.innerWidth <= 768) {
        // Tablets
        return { width: "85vw", maxWidth: "500px", margin: "0 auto" };
      } else if (window.innerWidth <= 1024) {
        // Pantallas medianas
        return { width: "70vw", maxWidth: "600px", margin: "0 auto" };
      } else {
        // Escritorios
        return { width: "450px", maxWidth: "90vw", margin: "0 auto" };
      }
    },
  },
  methods: {

    cerrarDropdownFuera(event) {
      const dropdownEl = this.$refs.dropdownUbicacion
        ? this.$refs.dropdownUbicacion.$el
        : null;

      if (dropdownEl && !dropdownEl.contains(event.target)) {
        this.dropdownVisible = false;
      }
    },



    filtrarPorUbicacion() {
      this.listarPersona(this.buscar, this.criterio, this.ubicacionSeleccionada);
    },

    async cargarUbicaciones() {
      try {
        const res = await axios.get('/cliente/ubicaciones');
        this.ubicaciones = res.data.ubicaciones;
      } catch (error) {
        console.error("Error cargando ubicaciones", error);
      }
    },

    abrirModalRango(persona) {
      this.clienteSeleccionado = persona;
      this.rangoFechas = null;
      this.dialogRango = true;
    },

    confirmarReporte() {
      if (!this.rangoFechas || this.rangoFechas.length !== 2) {
        this.$toast.add({
          severity: "warn",
          summary: "Rango incompleto",
          detail: "Selecciona un rango de fechas válido.",
          life: 3000,
        });
        return;
      }

      const [inicio, fin] = this.rangoFechas;

      let url =
        `/venta/${this.clienteSeleccionado.id}/reporte-credito` +
        `?inicio=${inicio}&fin=${fin}`;

      window.open(url, "_blank");

      this.dialogRango = false;
    },
    abrirReporteCredito(persona) {
      if (!persona.id) return;

      if (!this.fechaInicio || !this.fechaFin) {
        this.$toast.add({
          severity: "warn",
          summary: "Fechas requeridas",
          detail: "Debes seleccionar fecha inicio y fecha fin.",
          life: 3000
        });
        return;
      }

      // 🔥 Convertir fechas correctamente a YYYY-MM-DD
      const inicio = this.formatFecha(this.fechaInicio);
      const fin = this.formatFecha(this.fechaFin);

      const url =
        `/venta/${persona.id}/reporte-credito` +
        `?inicio=${inicio}&fin=${fin}`;

      window.open(url, "_blank");
    },


    formatFecha(dateObj) {
      const d = new Date(dateObj);
      const month = (d.getMonth() + 1).toString().padStart(2, "0");
      const day = d.getDate().toString().padStart(2, "0");
      return `${d.getFullYear()}-${month}-${day}`;
    },
    datosConfiguracion() {
      let me = this;
      var url = "/configuracion";
      axios
        .get(url)
        .then(function (response) {
          let respuesta = response.data;
          me.permitir_bonificacion =
            respuesta.configuracionTrabajo.permitir_bonificacion;
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    async cargarMontoCliente() {
      try {
        const response = await axios.get("/monto-bonificacion");
        if (response.data.length > 0) {
          const registro = response.data[0]; // Solo existe uno
          this.montoCliente = {
            id: registro.id,
            monto: registro.monto,
            fecha_actualizacion: registro.fecha_actualizacion,
            es_acumulativo: !!registro.es_acumulativo,
            periodo_meses: registro.periodo_meses || 1, // 🔹 Nuevo campo, por defecto 1 mes
            fecha_inicio: registro.fecha_inicio,
          };
        }
      } catch (error) {
        console.error("Error al cargar monto:", error);
      }
    },

    abrirDialogMontoCliente() {
      if (!this.montoCliente.fecha_actualizacion) {
        this.montoCliente.fecha_actualizacion = new Date().toISOString().slice(0, 10);
      }
      this.dialogMontoCliente = true;
    },

    async guardarMontoCliente() {
      try {
        const formData = {
          monto: this.montoCliente.monto,
          fecha_actualizacion: new Date().toISOString().slice(0, 10),
          fecha_inicio: this.montoCliente.fecha_inicio || null, // 🔹 nuevo campo
          es_acumulativo: this.montoCliente.es_acumulativo ? 1 : 0,
          periodo_meses: this.montoCliente.periodo_meses || null, // 🔹 nuevo campo
        };

        if (this.montoCliente.id) {
          await axios.put(`/monto-bonificacion/actualizar/${this.montoCliente.id}`, formData);
          this.$toast.add({ severity: "success", summary: "Actualizado", detail: "Monto actualizado correctamente", life: 3000 });
        } else {
          await axios.post("/monto-bonificacion/registrar", formData);
          this.$toast.add({ severity: "success", summary: "Registrado", detail: "Monto registrado correctamente", life: 3000 });
        }

        this.dialogMontoCliente = false;
        this.cargarMontoCliente();
      } catch (error) {
        console.error("Error al guardar monto:", error);
        this.$toast.add({ severity: "error", summary: "Error", detail: "No se pudo guardar el monto", life: 3000 });
      }
    },
    formatTelefono(data) {
      if (data.telefono) {
        return { existe: true, texto: data.telefono };
      }
      return { existe: false, texto: "No registrado" };
    },
    toastSuccess(mensaje) {
      this.$toasted.show(
        `
    <div style="height: 60px;font-size:16px;">
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
    toastError(mensaje) {
      this.$toasted.show(
        `
    <div style="height: 60px;font-size:16px;">
        <br>
        ` +
        mensaje +
        `<br>
    </div>`,
        {
          type: "error",
          position: "bottom-right",
          duration: 2000,
        }
      );
    },
    resetBuscar() {
      this.buscar = "";
      this.listarPersona(this.buscar, this.criterio);
    },
    handleResize() {
      this.mostrarLabel = window.innerWidth > 768;
    },
    getDocumentoIdentidad(data) {
      const tipoAbreviado = this.getAbreviaturaTipoDocumento(
        data.tipo_documento
      );
      return `${tipoAbreviado} ${data.num_documento}`;
    },
    getAbreviaturaTipoDocumento(tipo) {
      switch (tipo) {
        case "1":
          return "CI";
        case "2":
          return "CEX";
        case "3":
          return "Pas"; // Abreviatura de Pasaporte
        case "4":
          return "OD";
        case "5":
          return "NIT";
        default:
          return tipo ? tipo.substring(0, 3).toUpperCase() : ""; // Abreviatura genérica
      }
    },

    getTipoDocumentoLabel() {
      switch (this.datosFormulario.tipo_documento) {
        case "1":
          return "CI";
        case "2":
          return "CEX";
        case "3":
          return "Pasaporte";
        case "5":
          return "NIT";
        default:
          return "Documento";
      }
    },
    onPageChange(event) {
      this.cambiarPagina(event.page + 1, this.buscar, this.criterio);
    },
    async validarCampo(campo) {
      try {
        await esquemaCliente.validateAt(campo, this.datosFormulario);
        this.errores[campo] = null;
      } catch (error) {
        this.errores[campo] = error.message;
      }
    },
    async enviarFormulario() {
      try {
        await esquemaCliente.validate(this.datosFormulario, {
          abortEarly: false,
        });
        if (this.tipoAccion == 1) {
          this.registrarPersona(this.datosFormulario);
        } else {
          this.datosFormulario.usuariodos_id = this.idusuario;
          this.actualizarPersona(this.datosFormulario);
        }
        this.mostrarComplemento = false;
      } catch (error) {
        const erroresValidacion = {};
        error.inner.forEach((e) => {
          erroresValidacion[e.path] = e.message;
        });
        this.errores = erroresValidacion;
      }
    },
    async listarPersona(buscar, criterio) {
      try {
        this.isLoading = true;

        const url = `/clienteUbicacion?buscar=${buscar}&criterio=${criterio}&ubicacion=${this.ubicacionSeleccionada || ''}`;

        const response = await axios.get(url);
        this.arrayPersona = response.data.usuarios;

      } catch (error) {
        console.error("Error al listar clientes:", error);
      } finally {
        this.isLoading = false;
      }
    },

    async selectUsuarioFiltro(event) {
      try {
        this.isLoading = true; // Activar loading
        const url = "/cliente/usuario/filtro?filtro=" + event.query;
        const response = await axios.get(url);
        this.arrayUsuarioFiltro = response.data.usuariodos;
      } catch (error) {
        console.error("Error al filtrar usuarios:", error);
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "Error al filtrar usuarios",
          life: 3000,
        });
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
    },
    getDatosUsuarioFiltro(event) {
      if (event.value && event.value.iduse) {
        this.usuariodos_id = event.value.iduse;
        this.usuarioSeleccionadodos = event.value.nombre;
        this.listarPersona(this.buscar, this.criterio);
      }
    },
    cambiarPagina(page, buscar, criterio) {
      this.pagination.current_page = page;
      this.listarPersona(buscar, criterio);
    },
    async registrarPersona(datos) {
      try {
        this.isLoading = true; // Activar loading
        await axios.post("/cliente/registrar", datos);
        this.cerrarModal();
        await this.listarPersona(this.buscar, this.criterio);
        this.$toast.add({
          severity: "success",
          summary: "Cliente Registrado",
          detail: "El registro fue exitoso",
          life: 2500,
        });
      } catch (error) {
        this.toastError("No se pudo registrar el cliente");
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    async actualizarPersona(datos) {
      try {
        this.isLoading = true; // Activar loading
        await axios.put("/cliente/actualizar", datos);
        this.cerrarModal();
        await this.listarPersona(this.buscar, this.criterio);
        this.$toast.add({
          severity: "success",
          summary: "Cliente Actualizado",
          detail: "Actualizacion exitosa",
          life: 2500,
        });
      } catch (error) {
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudo actualizar",
          life: 3000,
        });
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    cerrarModal() {
      this.modal = false;
      this.tituloModal = "";
      this.errorPersona = 0;
      this.activaredit = false;
      this.idusuario = "";
    },
    abrirModal(modelo, accion, data = []) {
      if (modelo === "persona") {
        this.modal = true;
        if (accion === "registrar") {
          this.tituloModal = "REGISTRAR CLIENTE";
          this.tipoAccion = 1;
          this.datosFormulario = {
            nombre: "",
            tipo_documento: "",
            num_documento: "",
            complemento: "",
            direccion: "",
            telefono: "",
            email: "",
          };
        } else if (accion === "actualizar") {
          this.tituloModal = "ACTUALIZAR CLIENTE";
          this.tipoAccion = 2;
          this.datosFormulario = { ...data, usuariodos_id: "" };
          this.activaredit = true;
          this.verUsuario(data);
        }
      }
    },
    cargarReporteExcel() {
      window.open("/cliente/listarReporteClienteExcel", "_blank");
    },
    getTipoDocumentoText(value) {
      const tipos = {
        "1": "CI",
        "2": "CEX",
        "4": "NIT",
        "3": "PAS",
      };
      return tipos[value] || "";
    },
    recuperarIdRol() {
      this.rolUsuario = window.userData.rol;
    },

    async selectUsuario(search, loading) {
      try {
        this.isLoading = true; // Activar loading
        loading(true);
        const url = "/cliente/selectUusarioVend?filtro=" + search;
        const response = await axios.get(url);
        this.arrayUsuarioV = response.data.clientes;
      } catch (error) {
        console.error("Error al seleccionar usuario:", error);
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "Error al cargar usuarios",
          life: 3000,
        });
      } finally {
        loading(false);
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 5000);
      }
    },
    getDatosUsuario(val1) {
      let me = this;
      me.loading = true;

      if (val1 && val1.ID_use) {
        me.idusuario = val1.ID_use;
        me.usuarioSeleccionado = val1.nombre;
      }
    },
    async verUsuario(data) {
      try {
        this.isLoading = true; // Activar loading
        this.idusuario = data.usuario;
        const url = "/cliente/usuario?idusuario=" + this.idusuario;
        const response = await axios.get(url);
        this.arrayDetalleUsuario = response.data.usuario;
        this.usuarioSeleccionado = this.arrayDetalleUsuario[0].nombre;
      } catch (error) {
        console.error("Error al ver usuario:", error);
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "Error al cargar detalles del usuario",
          life: 3000,
        });
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
    },

    async listarDatosuser() {
      try {
        const response = await axios.get("/user-info");
        const userData = response.data.user;
        this.usuariodos_id = userData.iduse;
        this.role_id = userData.idrol;

        if (this.role_id == 1) {
          this.usuariodos_id = "";
        }

        await this.listarPersona(this.buscar, this.criterio);
      } catch (error) {
        console.error("Error al obtener la información del usuario:", error);
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "Error al obtener información del usuario",
          life: 3000,
        });
      }
    },

    abrirModalImportar() {
      this.modalImportar = 1;
    },
    cerrarModalImportar() {
      this.modalImportar = 0;
      this.listarPersona(this.buscar, this.criterio);
    },
  },

  async mounted() {
    try {
      await this.cargarUbicaciones();
      this.listarPersona(this.buscar, this.criterio);
      this.isLoading = true; // Activar loading
      this.datosConfiguracion(),
        await Promise.all([this.listarDatosuser(), this.recuperarIdRol()]);
      this.handleResize();
      window.addEventListener("resize", this.handleResize);
      this.cargarMontoCliente(); // cargar al iniciar
      document.addEventListener("click", this.cerrarDropdownFuera, true);

    } catch (error) {
      console.error("Error en la carga inicial:", error);
      this.$toast.add({
        severity: "error",
        summary: "Error",
        detail: "Error al cargar los datos iniciales",
        life: 3000,
      });
    } finally {
      this.isLoading = false; // Desactivar loading
    }
  },
  beforeDestroy() {
    window.removeEventListener("resize", this.handleResize);
  },
  beforeUnmount() {
    document.removeEventListener("click", this.cerrarDropdownFuera, true);
  },
};
</script>

<style scoped>
/*estilos extras de bonificacion*/
.dialog-subtext {
  font-size: 0.85rem;
  color: #6b7280;
  margin-bottom: 1rem;
}

.label-input-blue {
  font-weight: 600;
  color: #0369a1;
  font-size: 0.8rem;
}

.label-addon {
  background-color: #f3f4f6;
  font-size: 0.8rem;
  color: #374151;
}

.label-addon-blue {
  background-color: #e0f2fe;
  font-size: 0.8rem;
  color: #0369a1;
}

.info-box {
  border-radius: 8px;
  padding: 10px;
  font-size: 0.8rem;
  line-height: 1.3;
}

.info-blue {
  background-color: #e0f2fe;
  border-left: 4px solid #0284c7;
  color: #0369a1;
}

.info-red {
  background-color: #fee2e2;
  border-left: 4px solid #dc2626;
  color: #991b1b;
}

.small-info {
  display: block;
  color: #0369a1;
  font-size: 0.75rem;
  margin-top: 4px;
}

.flex-field {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 8px;
}

/* Transición suave para el bloque de información */
.fade-enter-active,
.fade-leave-active {
  transition: all 0.25s ease;
}

.fade-enter,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-5px);
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

/* 🔹 Estilo más pequeño para todos los Toasts */
.p-toast {
  width: 300px !important;
  /* más angosto */
  font-size: 0.75rem !important;
  /* texto más pequeño */
}

.p-toast-message {
  padding: 0.6rem 0.8rem !important;
  /* menos espacio interno */
  border-radius: 6px !important;
}

.p-toast-message-content {
  gap: 0.4rem !important;
  /* reduce separación entre ícono y texto */
}

.p-toast-message-text {
  line-height: 1.2;
}

.p-toast-summary {
  font-weight: 600;
  font-size: 0.85rem !important;
}

.p-toast-detail {
  font-size: 0.8rem !important;
  opacity: 0.9;
}

/* 🔹 Ícono más pequeño */
.p-toast-icon {
  font-size: 1rem !important;
}

/* 🔹 Márgenes y posición */
.p-toast-top-right {
  top: 1rem !important;
  right: 1rem !important;
}

/* 🔹 Header personalizado */
.dialog-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 0;
}

.header-icon {
  font-size: 1rem;
  color: #2563eb;
  /* azul elegante */
}

.header-title {
  font-size: 0.95rem;
  font-weight: 600;
  color: #1f2937;
  /* gris oscuro */
  letter-spacing: 0.3px;
}

/* 🔹 Botones pequeños */
.btn-sm {
  font-size: 0.8rem;
  padding: 0.3rem 0.7rem;
  border-radius: 6px;
  line-height: 1.1;
}

.btn-sm .pi {
  font-size: 0.75rem;
  margin-right: 4px;
}

.modal-footer-buttons {
  margin-top: 10px;
  padding-top: 0.5rem;
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

.optional-tag {
  background-color: #eff6ff;
  color: #2563eb;
  font-size: 0.7rem;
  border-radius: 4px;
  padding: 0.1rem 0.3rem;
  margin-left: 4px;
}

.input-full {
  width: 100%;
  font-size: 0.8rem;
  padding: 6px 8px;
  border-radius: 6px;
  box-sizing: border-box;
}

.input-full:focus {
  border-color: #3b82f6;
  /* azul elegante al enfocar */
  box-shadow: 0 0 0 1px #3b82f6;
}

/* 🔹 Estilo especial para InputNumber */
.input-number-full {
  width: 100%;
}

.input-number-full>>>.p-inputtext {
  width: 100% !important;
  font-size: 0.8rem;
  padding: 6px 8px;
  box-sizing: border-box;
}

.input-full-date {
  width: 100%;
  font-size: 0.8rem;
  padding: 6px 8px;
  border-radius: 6px;
  box-sizing: border-box;
}

.input-full-disabled {
  width: 100%;
  font-size: 0.8rem;
  color: #6b7280;
  background-color: #f3f4f6;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  box-sizing: border-box;
  padding: 6px 8px;
}

/* 🔹 Error */
.div-error {
  margin-top: 0.5rem;
}

.text-error {
  color: #dc2626;
  font-size: 0.8rem;
  background-color: #fee2e2;
  border: 1px solid #fecaca;
  border-radius: 6px;
  padding: 6px 10px;
  line-height: 1.2;
}

.text-error div+div {
  margin-top: 2px;
}

.input-container {
  position: relative;
  padding-bottom: 10px;
  margin-bottom: 8px;
}

.input-container .p-inputtext {
  width: 100%;
  margin-bottom: 0;
}

.error-message {
  position: absolute;
  bottom: 2px;
  /* Ajustado para tener más espacio arriba del input */
  left: 0;
  font-size: 0.75rem;
  /* Tamaño de fuente más pequeño */
  margin-top: 0;
  /* Eliminado margen superior */
}

/* Panel Styles */
.cliente-panel {
  margin-bottom: 1rem;
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

/* Search Bar Styles */
.search-bar {
  flex-grow: 1;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  min-width: 0;
  margin-right: 1rem;
}

.search-bar .p-inputgroup {
  width: 100%;
}

.search-bar .p-inputgroup-addon {
  background: #f8fafc;
  border-color: #d1d5db;
  color: #6b7280;
}

.search-bar .p-inputtext {
  border-left: none;
}

.search-bar .p-inputtext:focus {
  box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.5);
  border-color: #3b82f6;
}

.search-bar .p-inputgroup-addon+.p-inputtext:focus {
  border-left-color: #3b82f6;
}

/* Responsive Dialog Styles */
.responsive-dialog>>>.p-dialog {
  margin: 0.75rem;
  max-height: 90vh;
  overflow-y: auto;
}

.responsive-dialog>>>.p-dialog-content {
  overflow-x: auto;
  padding: 0.75rem 1rem;
  /* Reducido padding vertical */
}

.responsive-dialog>>>.p-dialog-header {
  padding: 0.75rem 1.5rem;
  /* Reducido padding vertical */
  font-size: 1.1rem;
}

.responsive-dialog>>>.p-dialog-footer {
  padding: 0.5rem 1.5rem;
  /* Reducido padding vertical */
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


/* Formulario compacto - Reducir espacios entre campos */
.form-compact>>>.p-field {
  margin-bottom: 0.25rem !important;
  /* Reducido de 0.5rem a 0.25rem */
}

>>>.p-fluid .p-field {
  margin-bottom: 0.25rem;
  /* Reducido de 0.5rem a 0.25rem */
}

/* Reducir padding del contenedor del diálogo */
.responsive-dialog>>>.p-dialog-content {
  padding: 0.75rem 1rem !important;
  /* Reducido padding vertical */
}

/* Estilos para campos obligatorios */
.required-field {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-weight: 600;
  color: #2c3e50;
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
  align-items: center;
  gap: 0.4rem;
  font-weight: 500;
  color: #6c757d;
}

.optional-icon {
  color: #17a2b8;
  font-size: 0.8rem;
}

.activo {
  color: green;
  font-weight: bold;
}

.status-badge {
  padding: 0.25em 0.5em;
  border-radius: 4px;
  color: white;
}

.status-badge.active {
  background-color: rgb(0, 225, 0);
}

.status-badge.inactive {
  background-color: red;
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
  font-size: 0.78rem;
}

.p-dialog-mask {
  z-index: 9990 !important;
}

.p-dialog {
  z-index: 9990 !important;
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
    padding: 0.5rem 0.75rem;
    /* Más compacto en móviles */
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.5rem 1rem;
    /* Reducido padding vertical */
    font-size: 1rem;
  }

  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.4rem 1rem;
    /* Reducido padding vertical */
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

  /* Ajustar botón "Nuevo" para que coincida con otros botones */
  .toolbar>>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  /* Reducir altura del input buscador */
  .search-bar .p-inputtext-sm {
    padding: 0.35rem 0.5rem !important;
    font-size: 0.85rem !important;
  }

  /* Ajustar el addon del icono de búsqueda en móviles */
  .search-bar .p-inputgroup-addon {
    padding: 0.35rem 0.5rem !important;
    font-size: 0.85rem !important;
  }

  /* Ajustar iconos en móviles */
  .required-icon {
    font-size: 0.8rem;
  }

  .optional-icon {
    font-size: 0.6rem;
  }

  >>>.p-inputtext,
  >>>.p-dropdown,
  >>>.p-inputnumber-input {
    font-size: 0.9rem;
    padding: 0.5rem;
  }

  /* Reducir espacios entre campos en móviles */
  .input-container {
    padding-bottom: 10px;
    /* Aumentado para dar espacio al error en móviles */
    margin-bottom: 6px;
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
    padding: 0.4rem 0.5rem;
    /* Más compacto en móviles extra pequeños */
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.4rem 0.75rem;
    /* Reducido padding vertical */
    font-size: 0.95rem;
  }

  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.3rem 0.75rem;
    /* Reducido padding vertical */
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

  /* Ajustar botones para que coincidan */
  .toolbar>>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  /* Reducir más la altura del input buscador en móviles pequeños */
  .search-bar .p-inputtext-sm {
    padding: 0.3rem 0.5rem !important;
    font-size: 0.8rem !important;
  }

  /* Ajustar el addon del icono de búsqueda en móviles extra pequeños */
  .search-bar .p-inputgroup-addon {
    padding: 0.3rem 0.5rem !important;
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

  /* Iconos más pequeños en móviles extra pequeños */
  .required-icon {
    font-size: 0.7rem;
  }

  .optional-icon {
    font-size: 0.55rem;
  }

  >>>.p-inputtext,
  >>>.p-dropdown,
  >>>.p-inputnumber-input {
    font-size: 0.85rem;
    padding: 0.4rem;
  }

  >>>.p-tag {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
  }

  /* Espacios aún más compactos en móviles extra pequeños */
  .input-container {
    padding-bottom: 10px;
    /* Aumentado para dar espacio al error en móviles pequeños */
    margin-bottom: 4px;
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

>>>.p-fileupload .p-button.p-fileupload-choose {
  background-color: #22c55e !important;
  border-color: #22c55e !important;
  color: #ffffff !important;
  transition: all 0.2s ease-in-out !important;
}

/* Efecto hover */
>>>.p-fileupload .p-button.p-fileupload-choose:enabled:hover {
  background-color: #16a34a !important;
  border-color: #16a34a !important;
}

/* Efecto focus */
>>>.p-fileupload .p-button.p-fileupload-choose:focus {
  box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.5) !important;
}

/* Efecto active (cuando se hace clic) */
>>>.p-fileupload .p-button.p-fileupload-choose:enabled:active {
  background-color: #15803d !important;
  border-color: #15803d !important;
}

/* Estilo cuando está deshabilitado */
>>>.p-fileupload .p-button.p-fileupload-choose:disabled {
  background-color: #22c55e !important;
  border-color: #22c55e !important;
  opacity: 0.6;
}

>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose) {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  color: #ffffff !important;
  transition: all 0.2s ease-in-out !important;
}

/* Efecto hover */
>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):enabled:hover {
  background: #dc2626 !important;
  border-color: #dc2626 !important;
}

/* Efecto focus */
>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):focus {
  box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.5) !important;
}

/* Efecto active (cuando se hace clic) */
>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):enabled:active {
  background: #b91c1c !important;
  border-color: #b91c1c !important;
}

/* Estilo cuando está deshabilitado */
>>>.p-fileupload .p-fileupload-buttonbar .p-button.p-component:not(.p-fileupload-choose):disabled {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  opacity: 0.6;
}

>>>.p-fileupload .p-fileupload-files .p-button {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  color: #ffffff !important;
  transition: all 0.2s ease-in-out !important;
}

/* Efecto hover */
>>>.p-fileupload .p-fileupload-files .p-button:enabled:hover {
  background: #dc2626 !important;
  border-color: #dc2626 !important;
}

/* Efecto focus */
>>>.p-fileupload .p-fileupload-files .p-button:focus {
  box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.5) !important;
}

/* Efecto active (cuando se hace clic) */
>>>.p-fileupload .p-fileupload-files .p-button:enabled:active {
  background: #b91c1c !important;
  border-color: #b91c1c !important;
}

/* Estilo cuando está deshabilitado */
>>>.p-fileupload .p-fileupload-files .p-button:disabled {
  background: #ef4444 !important;
  border-color: #ef4444 !important;
  opacity: 0.6;
}

/* Asegurar que el icono dentro del botón también sea blanco */
>>>.p-fileupload .p-fileupload-files .p-button .p-button-icon {
  color: #ffffff !important;
}

>>>.p-fileupload-row>div:first-child {
  display: none !important;
}

>>>.p-dialog .p-dialog-content {
  padding: 0 1.5rem 0rem 1.5rem;
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

.toolbar-layout {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding-bottom: 1rem;
}

/* Buscador ocupa más espacio */
.item-busqueda {
  flex: 2;
}

/* Calendarios tamaño normal */
.item-fecha {
  flex: 1;
}

/* Ubicación ocupa más pero no excesivo */
.item-ubicacion {
  flex: 2;
  display: flex;
  flex-direction: column;
}

.select-ubicacion {
  width: 100%;
  height: 42px;
  border-radius: 6px;
  border: 1px solid #ccc;
  padding: 0 8px;
}
</style>
