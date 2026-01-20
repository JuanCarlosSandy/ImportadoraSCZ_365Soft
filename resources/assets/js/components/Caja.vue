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
    <Panel>
      <template #header>
        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="pi pi-bars panel-icon"></i>
            <h4 class="panel-title" style="margin: 0;">GESTION DE CARTERA</h4>
          </div>
          <Button icon="pi pi-plus" :label="mostrarLabel ? 'Nuevo' : ''" class="p-button-secondary p-button-sm"
            @click="abrirModal('caja', 'registrar')" />
        </div>
      </template>

      <DataTable :key="idrol" :value="arrayCaja" :paginator="false" responsiveLayout="scroll"
        class="p-datatable-gridlines p-datatable-sm tabla-caja">
        <Column header="Sucursal" style="min-width: 130px;">
          <template #body="slotProps">
            <div :class="[
              'celda-sucursal',
              slotProps.data.estado ? 'sucursal-activa' : 'sucursal-inactiva'
            ]">
              {{ slotProps.data.nombre_sucursal }}
            </div>
          </template>
        </Column>

        <Column field="fechaApertura" header="Fecha Apertura" style="min-width: 130px;" />
        <Column field="fechaCierre" header="Fecha Cierre" style="min-width: 130px;" />
        <Column field="saldoInicial" header="Saldo Inicial" style="min-width: 110px; text-align: right;" />
        <Column field="ventasContado" header="Cobros Efectivo" style="min-width: 120px; text-align: right;" />
        <Column field="ventasQR" header="Cobros QR" style="min-width: 100px; text-align: right;" />
        <Column field="saldototalventas" header="Cobros Totales" style="min-width: 120px; text-align: right;" />
        <Column field="depositos" header="Depósitos Extras" style="min-width: 120px; text-align: right;" />
        <Column field="salidas" header="Salidas Extras" style="min-width: 120px; text-align: right;" />
        <Column field="saldoCaja" header="Saldo Caja" style="min-width: 120px; text-align: right;" />
        <Column field="saldoFaltante" header="Saldo Faltante" style="min-width: 120px; text-align: right;" />
        <Column field="saldoSobrante" header="Saldo Sobrante" style="min-width: 120px; text-align: right;" />
        <Column v-if="puedeMostrarMontoArqueo" field="monto_arqueo" header="Monto Arqueo" style="min-width: 120px; text-align: right;" />
        
        <Column header="Acciones" style="min-width: 200px; text-align: center;">
          <template #body="slotProps">
            <div v-if="slotProps.data.estado">
              <Button icon="pi pi-file-pdf" class="p-button-danger p-button-sm btn-mini"
                  @click="generarReporte(slotProps.data.id)" v-tooltip.top="'Reporte'" />
              <template v-if="slotProps.data.id !== idCajaBotonesSecundarios">
                <Button icon="pi pi-plus" class="p-button-primary p-button-sm btn-mini"
                  @click="abrirModal2('cajaDepositar', 'depositar', slotProps.data)" v-tooltip.top="'Depositar'" />
                <Button icon="pi pi-minus" class="p-button-danger p-button-sm btn-mini"
                  @click="abrirModal3('cajaRetirar', 'retirar', slotProps.data)" v-tooltip.top="'Retirar'" />
                <Button icon="pi pi-eye" class="p-button-warning p-button-sm btn-mini"
                  @click="abrirModal4('cajaVer', 'ver', slotProps.data.id)" />
                <Button icon="pi pi-calculator" class="p-button-success p-button-sm btn-mini"
                  @click="abrirModal5('arqueoCaja', 'contar', slotProps.data.id)" v-tooltip.top="'Arqueo'" />
              </template>
              <template v-else>
                <Button icon="pi pi-eye" class="p-button-warning p-button-sm btn-mini"
                  @click="abrirModal4('cajaVer', 'ver', slotProps.data.id)" v-tooltip.top="'Ver'" />
                <Button icon="pi pi-lock" class="p-button-danger p-button-sm btn-mini"
                  @click="cerrarCaja(slotProps.data.id)" v-tooltip.top="'Cerrar'" />
              </template>
            </div>
            <div v-else>
              <Button icon="pi pi-file-pdf" class="p-button-danger p-button-sm btn-mini"
                  @click="generarReporte(slotProps.data.id)" v-tooltip.top="'Reporte'" />
              <Button icon="pi pi-eye" class="p-button-warning p-button-sm btn-mini"
                @click="abrirModal4('cajaVer', 'ver', slotProps.data.id)" v-tooltip.top="'Ver'" />
            </div>
          </template>
        </Column>
      </DataTable>
      <Paginator :rows="6" :totalRecords="pagination.total" :first="(pagination.current_page - 1) * 6"
        @page="onPageChange" />
    </Panel>

    <Dialog :visible.sync="modal" :modal="true" :closable="false"
      :containerStyle="{ width: '400px', borderRadius: '10px' }">
      <!-- Header personalizado -->
      <template #header>
        <div class="dialog-header">
          <i class="pi pi-wallet header-icon"></i>
          <span class="header-title">{{ tituloModal }}</span>
        </div>
      </template>

      <!-- Contenido -->
      <form @submit.prevent="registrarCaja">
        <div class="p-field">
          <label class="label-input">Saldo Inicial</label>
          <InputText v-model="saldoInicial" placeholder="0.00" class="input-full" />
        </div>

        <div v-show="errorCaja" class="div-error">
          <div class="text-error">
            <div v-for="error in errorMostrarMsjCaja" :key="error">{{ error }}</div>
          </div>
        </div>

        <div class="d-flex gap-2 justify-content-end modal-footer-buttons">
          <Button v-if="tipoAccion == 1" label="Guardar" icon="pi pi-check" class="p-button-success btn-sm"
            type="submit" />
          <Button label="Cerrar" icon="pi pi-times" class="p-button-danger btn-sm" @click="cerrarModal()"
            type="button" />
        </div>
      </form>
    </Dialog>


    <!-- Dialog Depósitos -->
    <Dialog :visible.sync="modal2" :modal="true" :closable="false"
      :containerStyle="{ width: '400px', borderRadius: '10px' }">
      <!-- Header personalizado -->
      <template #header>
        <div class="dialog-header">
          <i class="pi pi-dollar header-icon"></i>
          <span class="header-title">{{ tituloModal2 }}</span>
        </div>
      </template>

      <!-- Contenido -->
      <form @submit.prevent="depositar" class="p-fluid">
        <div class="p-mb-3">
          <label class="label-input mb-2">Forma de Pago</label>
          <div class="btn-group pago-buttons">
            <button type="button" class="btn pago-btn" :class="{ 'active-btn': tipoPagoCuota === 'efectivo' }"
              @click="tipoPagoCuota = 'efectivo'">
              <i class="fa fa-money mr-2"></i>
              Efectivo
            </button>
            <!--
            <button type="button" class="btn pago-btn" :class="{ 'active-btn': tipoPagoCuota === 'banco' }"
              @click="tipoPagoCuota = 'banco'">
              <i class="fa fa-university mr-2"></i>
              Banco
            </button>
            -->
          </div>
          <!-- 🔽 DROPDOWN DE BANCOS -->
          <div v-if="tipoPagoCuota === 'banco'" class="mt-3 fade-in">
            <label class="label-input">Seleccionar Banco</label>

            <Dropdown v-model="bancoSeleccionado" :options="bancos" optionLabel="nombre_banco"
              placeholder="Seleccione un banco" class="w-full custom-dropdown" @change="onBancoSelect">
              <!-- OPCIONES -->
              <template #option="slotProps">
                <div class="banco-opcion">
                  <img :src="getBankUrl(slotProps.option.nombre_banco)" class="banco-logo" />
                  <div class="banco-detalles">
                    <div class="cuenta">{{ slotProps.option.nombre_cuenta }}</div>
                    <div class="numero">{{ slotProps.option.numero_cuenta }}</div>
                    <div class="tipo">{{ slotProps.option.tipo_cuenta }}</div>
                  </div>
                </div>
              </template>

              <!-- VALOR SELECCIONADO -->
              <template #value="slotProps">
                <div v-if="slotProps.value" class="banco-value">
                  <img :src="getBankUrl(slotProps.value.nombre_banco)" class="banco-logo" />
                  <span class="cuenta">{{ slotProps.value.nombre_cuenta }}</span>
                </div>
                <span v-else>-</span>
              </template>

            </Dropdown>
          </div>
        </div>
        <!-- Importe (obligatorio) -->
        <div class="p-field">
          <label class="label-input">
            Importe <span class="text-required">*</span>
          </label>
          <InputText v-model="depositos" placeholder="0.00" class="input-full" />
        </div>

        <!-- Descripción (opcional) -->
        <div class="p-field">
          <label for="descripcion" class="optional-field">
            <i class="pi pi-info-circle optional-icon"></i>
            Descripción
          </label>
          <InputText v-model="Desdepositos" placeholder="Escribe una descripción" class="input-full" />
        </div>

        <!-- Errores -->
        <div v-show="errorCaja" class="div-error">
          <div class="text-error">
            <div v-for="error in errorMostrarMsjCaja" :key="error">{{ error }}</div>
          </div>
        </div>
      </form>

      <!-- Footer -->
      <template #footer>
        <div class="d-flex gap-2 justify-content-end modal-footer-buttons">
          <Button label="Cerrar" icon="pi pi-times" class="p-button-danger btn-sm" @click="cerrarModal2()"
            type="button" />
          <Button v-if="tipoAccion == 2" label="Depositar" icon="pi pi-arrow-right" class="p-button-success btn-sm"
            @click="depositar()" type="button" />
        </div>
      </template>
    </Dialog>


    <!-- Dialog Retiros -->
    <Dialog :visible.sync="modal3" :modal="true" :closable="false"
      :containerStyle="{ width: '400px', borderRadius: '10px' }">
      <!-- Header personalizado -->
      <template #header>
        <div class="dialog-header">
          <i class="pi pi-wallet header-icon"></i>
          <span class="header-title">{{ tituloModal3 }}</span>
        </div>
      </template>

      <!-- Contenido -->
      <form @submit.prevent="retirar" class="p-fluid">
        <div class="p-mb-3">
          <label class="label-input mb-2">Forma de Pago</label>
          <div class="btn-group pago-buttons">
            <button type="button" class="btn pago-btn" :class="{ 'active-btn': tipoPagoCuota === 'efectivo' }"
              @click="tipoPagoCuota = 'efectivo'">
              <i class="fa fa-money mr-2"></i>
              Efectivo
            </button>
            <!--
            <button type="button" class="btn pago-btn" :class="{ 'active-btn': tipoPagoCuota === 'banco' }"
              @click="tipoPagoCuota = 'banco'">
              <i class="fa fa-university mr-2"></i>
              Banco
            </button>
            -->
          </div>
          <!-- 🔽 DROPDOWN DE BANCOS -->
          <div v-if="tipoPagoCuota === 'banco'" class="mt-3 fade-in">
            <label class="label-input">Seleccionar Banco</label>

            <Dropdown v-model="bancoSeleccionado" :options="bancos" optionLabel="nombre_banco"
              placeholder="Seleccione un banco" class="w-full custom-dropdown" @change="onBancoSelect">
              <!-- OPCIONES -->
              <template #option="slotProps">
                <div class="banco-opcion">
                  <img :src="getBankUrl(slotProps.option.nombre_banco)" class="banco-logo" />
                  <div class="banco-detalles">
                    <div class="cuenta">{{ slotProps.option.nombre_cuenta }}</div>
                    <div class="numero">{{ slotProps.option.numero_cuenta }}</div>
                    <div class="tipo">{{ slotProps.option.tipo_cuenta }}</div>
                  </div>
                </div>
              </template>

              <!-- VALOR SELECCIONADO -->
              <template #value="slotProps">
                <div v-if="slotProps.value" class="banco-value">
                  <img :src="getBankUrl(slotProps.value.nombre_banco)" class="banco-logo" />
                  <span class="cuenta">{{ slotProps.value.nombre_cuenta }}</span>
                </div>
                <span v-else>-</span>
              </template>

            </Dropdown>
          </div>
        </div>
        <!-- Importe (obligatorio) -->
        <div class="p-field">
          <label class="label-input">
            Importe <span class="text-required">*</span>
          </label>
          <InputText v-model="salidas" placeholder="0.00" class="input-full" />
        </div>

        <!-- Descripción (opcional) -->
        <div class="p-field">
          <label for="descripcion" class="optional-field">
            <i class="pi pi-info-circle optional-icon"></i>
            Descripción
          </label>
          <InputText v-model="Dessalidas" placeholder="Escribe una descripción" class="input-full" />
        </div>

        <!-- Errores -->
        <div v-show="errorCaja" class="div-error">
          <div class="text-error">
            <div v-for="error in errorMostrarMsjCaja" :key="error">{{ error }}</div>
          </div>
        </div>
      </form>

      <!-- Footer -->
      <template #footer>
        <div class="d-flex gap-2 justify-content-end modal-footer-buttons">
          <Button label="Cerrar" icon="pi pi-times" class="p-button-danger btn-sm" @click="cerrarModal3()"
            type="button" />
          <Button v-if="tipoAccion == 3" label="Retirar" icon="pi pi-arrow-right" class="p-button-success btn-sm"
            @click="retirar()" type="button" />
        </div>
      </template>
    </Dialog>

    <!-- Dialog Ver Transacciones -->
    <Dialog :visible.sync="modal4" :modal="true" :closable="false"
      :containerStyle="{ width: '600px', borderRadius: '10px' }">
      <!-- Header personalizado -->
      <template #header>
        <div class="dialog-header">
          <i class="pi pi-list header-icon"></i>
          <span class="header-title">{{ tituloModal4 }}</span>
        </div>
      </template>

      <!-- Contenido -->
      <div class="p-fluid">
        <TransaccionExtra :data="extra" :key="extraKey" />
      </div>

      <!-- Footer -->
      <template #footer>
        <div class="d-flex gap-2 justify-content-end modal-footer-buttons">
          <Button label="Cerrar" icon="pi pi-times" class="p-button-danger btn-sm" @click="cerrarModal4()"
            type="button" />
        </div>
      </template>
    </Dialog>

    <!-- Dialog Arqueo de Caja -->
    <Dialog :visible.sync="modal5" :modal="true" :closable="false"
      :containerStyle="{ width: '400px', borderRadius: '10px' }">
      <!-- Header personalizado -->
      <template #header>
        <div class="dialog-header">
          <i class="pi pi-lock header-icon"></i>
          <span class="header-title">{{ tituloModal5 }}</span>
        </div>
      </template>

      <!-- Contenido -->
      <form @submit.prevent="guardarMontoCierre" class="p-fluid">
        <div class="p-field">
          <label class="label-input">
            Monto Total de Cierre (Bs) <span class="text-required">*</span>
          </label>
          <InputNumber v-model="montoCierre" mode="decimal" :minFractionDigits="2" placeholder="Ingrese el monto total"
            class="input-number-full" />
        </div>

        <!-- Errores -->
        <div v-show="errorCaja" class="div-error">
          <div class="text-error">
            <div v-for="error in errorMostrarMsjCaja" :key="error">{{ error }}</div>
          </div>
        </div>
      </form>

      <!-- Footer -->
      <template #footer>
        <div class="d-flex gap-2 justify-content-end modal-footer-buttons">
          <Button label="Cancelar" icon="pi pi-times" class="p-button-danger btn-sm" @click="cerrarModal5()"
            type="button" />
          <Button v-if="tipoAccion == 5" label="Guardar" icon="pi pi-check" class="p-button-success btn-sm"
            @click="guardarMontoCierre()" type="button" />
        </div>
      </template>
    </Dialog>
  </main>
</template>

<script>
import Panel from "primevue/panel";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import Tag from "primevue/tag";
import Paginator from "primevue/paginator";
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import TransaccionExtra from "./Tables/TransaccionExtra.vue";
import Swal from "sweetalert2";
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
import Tooltip from 'primevue/tooltip';
import Dropdown from "primevue/dropdown";

export default {
  components: {
    Panel,
    DataTable,
    Column,
    Button,
    Dialog,
    InputText,
    InputNumber,
    Tag,
    Paginator,
    TabView,
    TabPanel,
    ToastService,
    Toast,
    Dropdown,
    TransaccionExtra,
  },
  directives: {
    'tooltip': Tooltip
  },
  data() {
    return {
      tipoPagoCuota: "efectivo",
      bancoSeleccionado: null,
      idbanco: null,
      bancos: [],
      mostrarLabel: true,
      isLoading: false,
      idrol: 0,
      id: 0,
      idsucursal: 0,
      nombre_sucursal: "",
      idusuario: 0,
      usuario: "",
      fechaApertura: "",
      fechaCierre: "",
      saldoInicial: "",
      depositos: "",
      Desdepositos: "",
      salidas: "",
      Dessalidas: "",
      ventasContado: "",
      ventasCredito: "",
      comprasContado: "",
      comprasCredito: "",
      saldoFaltante: "",
      PagoCuotaEfectivo: "",
      saldoCaja: "",
      arqueo_id: 0,
      billete200: 0,
      billete100: 0,
      billete50: 0,
      billete20: 0,
      billete10: 0,
      totalBilletes: 0,
      moneda5: 0,
      moneda2: 0,
      moneda1: 0,
      moneda050: 0,
      moneda020: 0,
      moneda010: 0,
      totalMonedas: 0,
      arrayCaja: [],
      arrayTransacciones: [],
      ArrayIngresos: [],
      ArrayEgresos: [],
      egreso: null,
      montoCierre: 0,
      ingreso: null,
      extra: null,
      modal: 0,
      modal2: 0,
      modal3: 0,
      modal4: 0,
      modal5: 0,
      tituloModal: "",
      tituloModal2: "",
      tituloModal3: "",
      tituloModal4: "",
      tituloModal5: "",
      tipoAccion: 0,
      arqueoRealizado: false,
      errorCaja: 0,
      errorMostrarMsjCaja: [],
      pagination: {
        total: 0,
        current_page: 0,
        per_page: 0,
        last_page: 0,
        from: 0,
        to: 0,
      },
      offset: 3,
      arraySucursal: [],
      arrayUser: [],
      buscar: "",
      criterio: "",
      idCajaBotonesSecundarios: null,
      extraKey: 0,
    };
  },
  computed: {
    isActived: function () {
      return this.pagination.current_page;
    },
    puedeMostrarMontoArqueo: function () {
      // Solo mostrar para roles 1 (admin) y 4 (supervisor)
      return this.idrol === 1 || this.idrol === 4;
    },
    //Calcula los elementos de la paginación
    pagesNumber: function () {
      if (!this.pagination.to) {
        return [];
      }

      var from = this.pagination.current_page - this.offset;
      if (from < 1) {
        from = 1;
      }

      var to = from + this.offset * 2;
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
generarReporte(idCaja) {
  Swal.fire({
    title: 'Seleccione el tipo de reporte',
    text: 'Elija una opción para generar el reporte',
    icon: 'question',
    showCancelButton: true,
    showDenyButton: true,
    showConfirmButton: true,
    confirmButtonText: 'Efectivo',
    denyButtonText: 'QR',
    cancelButtonText: 'Completo',
    reverseButtons: true
  }).then((result) => {
    let tipo = 'completo';
    if (result.isConfirmed) tipo = 'efectivo';
    else if (result.isDenied) tipo = 'qr';

    // Descargar PDF automáticamente
    const url = `/reporte/caja/${idCaja}?tipo=${tipo}`;
    const link = document.createElement('a');
    link.href = url;
    link.download = `reporte_caja_${idCaja}.pdf`; // nombre del archivo
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  });
},

    onBancoSelect(e) {
      this.idbanco = e.value.id; // 🔥 ahora sí tienes el id REAL
      console.log("Banco seleccionado:", e.value);
    },
    getBankCodeByName(bankName) {
      const mapa = [
        { code: 'BNB', name: 'Banco Nacional de Bolivia S.A.' },
        { code: 'BME', name: 'Banco Mercantil Santa Cruz S.A.' },
        { code: 'BIS', name: 'Banco Bisa S.A.' },
        { code: 'BCR', name: 'Banco de Crédito de Bolivia S.A.' },
        { code: 'BEC', name: 'Banco Económico S.A.' },
        { code: 'BGA', name: 'Banco Ganadero S.A.' },
        { code: 'BSO', name: 'Banco Solidario S.A.' },
        { code: 'BIE', name: 'Banco para el Fomento a Iniciativas Económicas S.A.' },
        { code: 'BFO', name: 'Banco Fortaleza S.A.' },
        { code: 'BPR', name: 'Banco Prodem S.A.' },
        { code: 'BDM', name: 'Banco de Desarrollo Productivo S.A.M.' },
        { code: 'BUN', name: 'Banco Unión S.A.' },
        { code: 'PCO', name: 'Banco PYME de la Comunidad S.A.' },
        { code: 'PEF', name: 'Banco PYME Ecofuturo S.A.' },
        // 👉 agrega tus otros bancos según tus imágenes
      ];

      bankName = bankName.toLowerCase();

      for (let b of mapa) {
        if (b.name.toLowerCase() === bankName) {
          return b.code;
        }
      }

      return "default"; // si no coincide
    },
    getBankUrl(bankName) {
      const code = this.getBankCodeByName(bankName);
      return `/img/bancos/${code}.png`;
    },
    async cargarBancos() {
      try {
        const res = await axios.get('/bancos/selectBancos');

        this.bancos = Array.isArray(res.data)
          ? res.data.map(b => ({
            id: b.id,
            nombre_cuenta: b.nombre_cuenta,
            nombre_banco: b.nombre_banco,
            numero_cuenta: b.numero_cuenta,
            tipo_cuenta: b.tipo_cuenta
          }))
          : [];

      } catch (error) {
        console.error('Error al cargar bancos', error);
        this.bancos = [];
      }
    },
    onPageChange(event) {
      // PrimeVue paginator: event.page is 0-based
      const newPage = event.page + 1;
      this.cambiarPagina(newPage, this.buscar, this.criterio);
    },
    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
    },
    guardarMontoCierre() {
      this.idCajaBotonesSecundarios = this.id;
      this.cerrarModal5();
      this.$toast.add({
        severity: "success",
        summary: "Monto Guardado",
        detail: "Monto de cierre registrado localmente",
        life: 2500,
      });
    },
    async listarCaja(page, buscar, criterio) {
      let me = this;
      try {
        const url =
          "/caja?page=" + page + "&buscar=" + buscar + "&criterio=" + criterio;
        const response = await axios.get(url);
        const respuesta = response.data;
        me.arrayCaja = respuesta.cajas.data;
        me.pagination = respuesta.pagination;
      } catch (error) {
        console.error("Error al listar cajas:", error);
        Swal.fire("Error", "No se pudieron cargar las cajas", "error");
      }
    },

    cambiarPagina(page, buscar, criterio) {
      let me = this;
      me.pagination.current_page = page;
      me.listarCaja(page, buscar, criterio);
    },
    async registrarCaja() {
      if (this.validarCaja()) {
        return;
      }

      let me = this;
      try {
        this.isLoading = true; // Activar loading
        let formData = new FormData();
        formData.append("saldoInicial", this.saldoInicial);

        await axios.post("/caja/registrar", formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        });

        me.cerrarModal();
        await me.listarCaja(1, "", "id");
        this.$toast.add({
          severity: "success",
          summary: "Caja Aperturada",
          detail: "Caja aperturada de forma satisfactoria!",
          life: 2500,
        });
      } catch (error) {
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudo aperturar la caja",
          life: 3000,
        });
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },

    async depositar() {
      let me = this;
      try {
        this.isLoading = true; // Activar loading
        await axios.put("/caja/depositar", {
          depositos: this.depositos,
          id: this.id,
          transaccion: this.Desdepositos + "  (movimiento de ingreso )",
          idbanco: this.bancoSeleccionado ? this.bancoSeleccionado.id : null,
        });

        me.cerrarModal2();
        await me.listarCaja(1, "", "id");
        this.$toast.add({
          severity: "success",
          summary: "Deposito Extra",
          detail: "Transacción de caja registrada satisfactoriamente",
          life: 2500,
        });
      } catch (error) {
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudo realizar el depósito",
          life: 3000,
        });
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },

    async retirar() {
      let me = this;
      try {
        this.isLoading = true; // Activar loading
        await axios.put("/caja/retirar", {
          salidas: this.salidas,
          transaccion: this.Dessalidas + " (movimiento de egreso )",
          id: this.id,
          idbanco: this.bancoSeleccionado ? this.bancoSeleccionado.id : null,
        });

        me.cerrarModal3();
        await me.listarCaja(1, "", "id");
        this.$toast.add({
          severity: "success",
          summary: "Retiro Extra",
          detail: "Transacción de caja registrada satisfactoriamente",
          life: 2500,
        });
      } catch (error) {
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudo realizar el retiro",
          life: 3000,
        });

      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },
    calcularTotalBilletes() {
      const billete200 = parseFloat(this.billete200) || 0;
      const billete100 = parseFloat(this.billete100) || 0;
      const billete50 = parseFloat(this.billete50) || 0;
      const billete20 = parseFloat(this.billete20) || 0;
      const billete10 = parseFloat(this.billete10) || 0;

      this.totalBilletes =
        billete200 * 200 +
        billete100 * 100 +
        billete50 * 50 +
        billete20 * 20 +
        billete10 * 10;
    },

    calcularTotalMonedas() {
      const moneda5 = parseFloat(this.moneda5) || 0;
      const moneda2 = parseFloat(this.moneda2) || 0;
      const moneda1 = parseFloat(this.moneda1) || 0;
      const moneda050 = parseFloat(this.moneda050) || 0;
      const moneda020 = parseFloat(this.moneda020) || 0;
      const moneda010 = parseFloat(this.moneda010) || 0;

      this.totalMonedas =
        moneda5 * 5 +
        moneda2 * 2 +
        moneda1 * 1 +
        moneda050 * 0.5 +
        moneda020 * 0.2 +
        moneda010 * 0.1;
    },
    async cerrarCaja(id) {
      // Obtener el saldoCaja de la caja seleccionada
      const caja = this.arrayCaja.find(c => c.id === id);
      if (!caja) {
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudo encontrar la caja",
          life: 3000,
        });
        return;
      }

      const saldoCajaCalculado = caja.saldoCaja; // Usar el saldoCaja del registro
      
      try {
        const result = await Swal.fire({
          title: "¿Está seguro de cerrar la caja?",
          html: `<div style="text-align: left; padding: 10px;">
                   <p><strong>Saldo Caja (Efectivo):</strong> Bs ${parseFloat(saldoCajaCalculado).toFixed(2)}</p>
                   <p style="font-size: 0.9rem; color: #666; margin-top: 8px;">Este monto se utilizará como base para el cierre.</p>
                 </div>`,
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#22c55e",
          cancelButtonColor: "#ef4444",
          confirmButtonText: "Aceptar",
          cancelButtonText: "Cancelar",
          reverseButtons: true,
          customClass: {
            confirmButton: "swal2-confirm-lineanew",
            cancelButton: "swal2-cancel-lineanew",
          },
        });

        if (result.value) {
          this.isLoading = true; // Activar loading
          let me = this;
          await axios.put("/caja/cerrar", {
            id: id,
            saldoFaltante: saldoCajaCalculado, // Usar el saldoCaja calculado
          });

          await me.listarCaja(1, "", "id");
          this.$toast.add({
            severity: "success",
            summary: "Cierre de Caja",
            detail: "La caja fue cerrada con éxito",
            life: 2500,
          });
        }
      } catch (error) {
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudo cerrar la caja",
          life: 3000,
        });
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },

    cajaAbierta() {
      for (let i = 0; i < this.arrayCaja.length; i++) {
        if (this.arrayCaja[i].estado) {
          return true;
        }
      }
      return false;
    },

    validarCaja() {
      this.errorCaja = 0;
      this.errorMostrarMsjCaja = [];

      if (!this.saldoInicial)
        this.errorMostrarMsjCaja.push("El saldo inicial no puede estar vacío.");

      if (this.errorMostrarMsjCaja.length) this.errorCaja = 1;

      return this.errorCaja;
    },
    cerrarModal() {
      this.modal = 0;
      this.tituloModal = "";
      this.idsucursal = 0;
      this.sucursal = "";
      this.saldoInicial = "";
      this.errorCaja = 0;
      this.errorMostrarMsjCaja = [];
    },

    cerrarModal2() {
      this.modal2 = 0;
      this.depositos = "";
      this.Desdepositos = "";
    },

    cerrarModal3() {
      this.modal3 = 0;
      this.salidas = "";
      this.Dessalidas = "";
    },

    cerrarModal4() {
      this.modal4 = 0;
      this.arrayTransacciones = [];
      console.log("entro aqui a cerrar: ", this.arrayTransacciones);
    },

    cerrarModal5() {
      this.modal5 = 0;
    },

    abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case "caja": {
          switch (accion) {
            case "registrar": {
              if (this.cajaAbierta()) {
                Swal.fire(
                  "Ya existe una caja abierta!",
                  "Por favor realice el cierre de la caja e intente de nuevo.",
                  "error"
                );
                return;
              }

              this.modal = 1;
              this.tituloModal = "Apertura de Caja";
              this.saldoInicial = "";
              this.mostrarBotonesSecundarios = false;

              this.tipoAccion = 1;
              break;
            }
          }
        }
      }
    },

    abrirModal2(modelo, accion, data = []) {
      switch (modelo) {
        case "cajaDepositar": {
          switch (accion) {
            case "depositar": {
              this.cargarBancos();
              this.bancoSeleccionado = null;
              this.tipoPagoCuota = "efectivo";
              this.modal2 = 1;
              this.tituloModal2 = "Depositar Dinero";
              this.id = data["id"];
              this.tipoAccion = 2;
              break;
            }
          }
        }
      }
    },

    abrirModal3(modelo, accion, data = []) {
      switch (modelo) {
        case "cajaRetirar": {
          switch (accion) {
            case "retirar": {
              this.cargarBancos();
              this.bancoSeleccionado = null;
              this.modal3 = 1;
              this.tipoPagoCuota = "efectivo";
              this.tituloModal3 = "Retirar Dinero";
              this.id = data["id"];
              this.tipoAccion = 3;
              break;
            }
          }
        }
      }
    },

    abrirModal4(modelo, accion, id) {
      switch (modelo) {
        case "cajaVer": {
          switch (accion) {
            case "ver": {
              this.modal4 = 1;
              this.tituloModal4 = "Transacciones Caja";

              let me = this;
              var url = "/transacciones/" + id;
              axios
                .get(url)
                .then(function (response) {
                  var respuesta = response.data;

                  console.log(respuesta);
                  me.arrayTransacciones = respuesta.transacciones.data;
                  // me.pagination= respuesta.pagination;
                  me.ArrayEgresos = respuesta.ingresos;
                  me.ArrayIngresos = respuesta.ventas.data;
                  console.log("esto es vnentas: ", me.arrayTransacciones);

                  me.egreso = respuesta.ingresos;
                  me.ingreso = respuesta.ventas;
                  me.extra = respuesta.transacciones;
                  me.extraKey++; // Esto forzará la recreación del componente
                })
                .catch(function (error) {
                  console.log(error);
                });

              break;
            }
          }
        }
      }
    },

    abrirModal5(modelo, accion, id) {
      switch (modelo) {
        case "arqueoCaja": {
          switch (accion) {
            case "contar": {
              this.modal5 = 1;
              this.tituloModal5 = "Arqueo de Caja";
              this.id = id;
              this.montoCierre = 0;
              this.tipoAccion = 5;
              break;
            }
          }
        }
      }
    },
  },

  watch: {
    billete200: "calcularTotalBilletes",
    billete100: "calcularTotalBilletes",
    billete50: "calcularTotalBilletes",
    billete20: "calcularTotalBilletes",
    billete10: "calcularTotalBilletes",
    moneda5: "calcularTotalMonedas",
    moneda2: "calcularTotalMonedas",
    moneda1: "calcularTotalMonedas",
    moneda050: "calcularTotalMonedas",
    moneda020: "calcularTotalMonedas",
    moneda010: "calcularTotalMonedas",
  },

  async mounted() {
    this.handleResize();
    window.addEventListener("resize", this.handleResize);
    try {
      // Obtener el rol del usuario autenticado
      const userResponse = await axios.get('/usuario-autenticado');
      console.log('Usuario obtenido:', userResponse.data);
      this.idrol = userResponse.data.idrol || 0;
      console.log('idrol asignado:', this.idrol);
      
      await this.listarCaja(1, this.buscar, this.criterio);
    } catch (error) {
      console.error("Error en la carga inicial:", error);
      swal("Error", "Error al cargar los datos de caja", "error");
    }
  },
  watch: {
    data: {
      handler(newData) {
        if (newData && Array.isArray(newData.data)) {
          this.items = newData.data;
          this.perPage = newData.per_page || 0;
          this.currentPage = 1;
        } else {
          this.items = [];
          this.perPage = 0;
          this.currentPage = 1;
        }
      },
      deep: true,
      immediate: true,
    },
  },
};
</script>
<style scoped>
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


.input-full {
  width: 100%;
  /* 🔹 ocupa todo el ancho */
  font-size: 0.8rem;
  padding: 6px 8px;
  border-radius: 6px;
  box-sizing: border-box;
  /* evita que el padding rompa el ancho */
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

.tabla-caja {
  width: 100%;
  white-space: nowrap;
  /* evita salto de columnas */
  overflow-x: auto;
}

.tabla-caja .p-datatable-wrapper {
  overflow-x: auto;
}

.tabla-caja th,
.tabla-caja td {
  text-align: center;
  vertical-align: middle;
  font-size: 0.85rem;
  padding: 0.5rem;
}

/* Estilo para la celda de Sucursal */
.celda-sucursal {
  font-weight: 600;
  border-radius: 4px;
  padding: 0.4rem;
}

/* ✅ Caja activa (estado true) */
.sucursal-activa {
  background-color: #d1f7d1;
  color: #2e7d32;
}

/* ❌ Caja cerrada (estado false) */
.sucursal-inactiva {
  background-color: #ffd6d6;
  color: #c62828;
}


/* --- Para pantallas pequeñas --- */
@media screen and (max-width: 768px) {
  .tabla-caja {
    font-size: 0.75rem;
  }

  .tabla-caja th,
  .tabla-caja td {
    padding: 0.3rem;
  }

  .tabla-caja .p-button.btn-mini {
    transform: scale(0.9);
    margin: 0 1px;
  }
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

.input-container {
  position: relative;
  padding-bottom: 20px;
  /* Aumentado de 8px a 12px para dar espacio al error */
  margin-bottom: 8px;
  /* Agregado margen inferior pequeño */
}

.input-container .p-inputtext {
  width: 100%;
  margin-bottom: 0;
  /* Eliminar margen inferior si existe */
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

.search-bar {
  flex-grow: 1;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  min-width: 0;
  margin-right: 1rem;
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
  font-size: 0.75rem;
}

.p-dialog-mask {
  z-index: 9990 !important;
}

.p-dialog {
  z-index: 9990 !important;
}

/* SweetAlert z-index para que aparezca por encima de los diálogos */
>>>.swal2-container {
  z-index: 99999 !important;
}

>>>.swal2-popup {
  z-index: 99999 !important;
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
    padding: 0.35rem 0.5rem 0.35rem 2.5rem !important;
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
    padding-bottom: 20px;
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
    font-size: 0.5rem;
    padding: 0.1rem 0.2rem;
  }

  /* Espacios aún más compactos en móviles extra pequeños */
  .input-container {
    padding-bottom: 20px;
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
  padding: 0 1.5rem 1.5rem 1.5rem;
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
