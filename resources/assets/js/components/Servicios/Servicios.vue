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
          <i class="pi pi-bars"></i>
          <h4 class="panel-title">SERVICIOS</h4>
        </div>
      </template>

       <div class="toolbar-container">
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
           <InputText
              v-model="buscar"
              placeholder="Texto a buscar"
              class="p-inputtext-sm"
              @keyup="buscarArticulo"
            />
          </span>
        </div>
        <div class="toolbar">
          <Button
            :label="mostrarLabel ? 'Nuevo' : ''"
            icon="pi pi-plus"
            class="p-button-secondary p-button-sm"
            @click="abrirModal('articulo', 'registrar')"
          />
          <Button
            :label="mostrarLabel ? 'Reporte' : ''"
            icon="pi pi-file"
            class="p-button-success p-button-sm"
            @click="cargarReporte"
          />
          <Button
            :label="mostrarLabel ? 'Importar' : ''"
            icon="pi pi-upload"
            class="p-button-help p-button-sm"
            @click="abrirDialogos('Importar')"
          />
        </div>
      </div>

      <DataTable
        :value="paginatedItems"
        class="p-datatable-gridlines p-datatable-sm"
        responsiveLayout="scroll"
      >
        <Column
          v-for="(column, index) in computedColumns"
          :key="index"
          :field="column.field"
          :header="column.header"
        >
          <template #body="slotProps">
            <span v-if="column.type === 'button'">
              <div class="botones-opciones">
                <Button
                  icon="pi pi-pencil"
                  class="btn-icon p-button-warning"
                                  style="width: 28px; height: 28px"

                  @click="abrirModal('articulo', 'actualizar', slotProps.data)"
                />
                <Button
                  v-if="slotProps.data.condicion"
                  icon="pi pi-ban"
                  class="btn-icon p-button-danger"
                                  style="width: 28px; height: 28px"

                  @click="desactivarArticulo(slotProps.data.id)"
                />
                <Button
                  v-else
                  icon="pi pi-check-circle"
                  class="btn-icon p-button-success"
                                  style="width: 28px; height: 28px"

                  @click="activarArticulo(slotProps.data.id)"
                />
              </div>
            </span>
            <span v-else-if="column.type === 'dynamicPrice'">
              {{
                (
                  slotProps.data[column.field] * parseFloat(monedaPrincipal[0])
                ).toFixed(2)
              }}
              {{ monedaPrincipal[1] }}
            </span>
             <span v-else-if="column.field === 'maximo_descuento'">
              {{ Number(slotProps.data[column.field]).toFixed(2) }} BS
            </span>
            <span v-else-if="column.type === 'image'">
              <img
                :src="
                  'img/articulo/' +
                    slotProps.data.fotografia +
                    '?t=' +
                    new Date().getTime()
                "
                width="50"
                height="50"
                v-if="slotProps.data.fotografia"
                ref="imagen"
              />
              <img
                :src="'img/articulo/' + 'defecto.jpg'"
                width="50"
                height="50"
                v-else
                ref="imagen"
              />
            </span>
            <span
              v-else-if="column.type === 'badge'"
              style="text-align: center;"
            >
              <span
                v-if="slotProps.data.precio_variable"
                class="badge badge-success"
                style="center"
                >Si</span
              >
              <span v-else class="badge badge-danger" style="center">No</span>
            </span>
            <span v-else>
              {{ slotProps.data[column.field] }}
            </span>
          </template>
        </Column>
      </DataTable>
      <Paginator
        :rows="rowsPerPage"
        :totalRecords="arrayArticulo.length"
        :first="first"
        @page="onPageChange"
      />
    </Panel>
    <!-- MODAL REGISTRAR PRODUCTO -->
    <Dialog
      :visible.sync="dialogVisible"
      :modal="true"
      header="REGISTRAR SERVICIO"
      :closable="true"
      @hide="closeDialog"
      :containerStyle="{ width: '800px' }"
    >
      <TabView>
        <TabPanel header="Datos">
          <div class="form-group row">
            <div class="col-md-6">
              <div>
                <label class="font-weight-bold" for="nombreProducto"
                  >Concepto <span class="text-danger">*</span></label
                >
                <InputText
                  id="nombreProducto"
                  v-model="datosFormulario.nombre"
                  placeholder="Ej. Consulta Médica General"
                  class="form-control p-inputtext-sm"
                  :class="{ 'p-invalid': errores.nombre }"
                  @input="validarCampo('nombre')"
                />
                <small class="p-error" v-if="errores.nombre"
                  ><strong>{{ errores.nombre }}</strong></small
                >
              </div>
            </div>

            <div class="col-md-6">
              <label class="font-weight-bold" for="linea"
                >Categoria<span class="text-danger">*</span></label
              >
              <div class="p-inputgroup">
                <InputText
                  id="linea"
                  v-model="lineaSeleccionado.nombre"
                  placeholder="Seleccione una línea"
                  class="form-control p-inputtext-sm bold-input"
                  disabled
                  :class="{ 'p-invalid': errores.idcategoria }"
                />
                <Button
                  label="..."
                  class="p-button-primary p-button-sm"
                  @click="abrirDialogos('Lineas')"
                />
              </div>
              <small class="p-error" v-if="errores.idcategoria"
                ><strong>{{ errores.idcategoria }}</strong></small
              >
            </div>
          </div>

          <div class="form-group row">
            <div class="col-md-12">
              <div>
                <label class="font-weight-bold" for="descripcion"
                  >Descripción del Servicio</label
                >
                <Textarea
                  id="descripcion"
                  v-model="datosFormulario.descripcion"
                  placeholder="Ej. Atención medica general a todo público"
                  rows="3"
                  class="form-control p-inputtext-sm"
                />
              </div>
            </div>
          </div>
        </TabPanel>

        <TabPanel header="Precios">
          <div
            v-for="(precio, index) in precios"
            :key="precio.id"
            class="p-grid p-ai-center p-mb-2 mobile-responsive"
          >
            <div class="p-col-12 custom-precios">
              <label class="p-mr-2 p-text-bold" style="width: 100%;"
                >PRECIO {{ precio.nombre_precio }}:</label
              >
            </div>
            <div class="p-col-12 p-md-6 custom-precios">
              <div class="p-inputgroup p-mr-2" style="width: 100%;">
                <InputNumber
                  v-if="index === 0"
                  placeholder="Precio"
                  v-model="precio_uno"
                  mode="decimal"
                  :min="0"
                  :minFractionDigits="2"
                  :maxFractionDigits="2"
                  class="p-inputtext-sm"
                />
                <InputNumber
                  v-if="index === 1"
                  placeholder="Precio"
                  v-model="precio_dos"
                  mode="decimal"
                  :min="0"
                  :minFractionDigits="2"
                  :maxFractionDigits="2"
                  class="p-inputtext-sm"
                />
                <InputNumber
                  v-if="index === 2"
                  placeholder="Precio"
                  v-model="precio_tres"
                  mode="decimal"
                  :min="0"
                  :minFractionDigits="2"
                  :maxFractionDigits="2"
                  class="p-inputtext-sm"
                />
                <InputNumber
                  v-if="index === 3"
                  placeholder="Precio"
                  v-model="precio_cuatro"
                  mode="decimal"
                  :min="0"
                  :minFractionDigits="2"
                  :maxFractionDigits="2"
                  class="p-inputtext-sm"
                />
                <span class="p-inputgroup-addon">{{ monedaPrincipal[1] }}</span>
              </div>
            </div>
          </div>
          <div class="form-group row">
            <div class="col-md-6">
              <label class="font-weight-bold" for="maximoDescuento"
                >Máximo descuento</label
              >
              <InputNumber
                id="maximoDescuento"
                v-model="maximo_descuento"
                mode="decimal"
                :min="0"
                :minFractionDigits="2"
                :maxFractionDigits="2"
                :max="precio_uno || 0"
                :step="0.01"
                class="p-inputtext-sm"
                placeholder="Máximo descuento"
              />
            </div>
          </div>
        </TabPanel>
      </TabView>
      <template #footer>
                <div class="d-flex gap-2 justify-content-end modal-footer-buttons">

        <Button
          label="Cerrar"
          icon="pi pi-times"
          class="p-button-danger p-button-sm"
          @click="cerrarModal"
        />
        <Button
          v-if="tipoAccion == 1"
          class="p-button-success p-button-sm"
          label="Guardar"
          icon="pi pi-check"
          @click="enviarFormulario()"
        />
        <Button
          v-if="tipoAccion == 2"
          class="p-button-warning p-button-sm"
          label="Actualizar"
          icon="pi pi-check"
          @click="enviarFormulario()"
        />
              </div>

      </template>
    </Dialog>

    <!-- MODALES DINÁMICOS -->
    <DialogProveedores
      v-if="mostrarDialogoProveedores"
      :visible.sync="mostrarDialogoProveedores"
      @close="cerrarDialogos('Proveedores')"
      @proveedor-seleccionado="manejarProveedorSeleccionado"
    />
    <DialogCategoria
      v-if="mostrarDialogoLineas"
      :visible.sync="mostrarDialogoLineas"
      @close="cerrarDialogos('Lineas')"
      @linea-seleccionado="manejarLineaSeleccionado"
    />
    <DialogMarcas
      v-if="mostrarDialogoMarcas"
      :visible.sync="mostrarDialogoMarcas"
      @close="cerrarDialogos('Marcas')"
      @marca-seleccionado="manejarMarcaSeleccionado"
    />
    <DialogIndustrias
      v-if="mostrarDialogoIndustrias"
      :visible.sync="mostrarDialogoIndustrias"
      @close="cerrarDialogos('Industrias')"
      @industria-seleccionado="manejarIndustriaSeleecionado"
    />
    <DialogGrupos
      v-if="mostrarDialogoGrupos"
      :visible.sync="mostrarDialogoGrupos"
      @close="cerrarDialogos('Grupos')"
      @grupo-seleccionado="manejarGrupoSeleccionado"
    />
    <DialogMedidas
      v-if="mostrarDialogoMedidas"
      :visible.sync="mostrarDialogoMedidas"
      @close="cerrarDialogos('Medidas')"
      @medida-seleccionado="manejarMedidaSeleccionado"
    />
    <DialogAlmacenes
      v-if="mostrarDialogoAlmacen"
      :visible.sync="mostrarDialogoAlmacen"
      @close="cerrarDialogos('Almacen')"
      @almacen-seleccionado="manejarAlmacenSeleccionado"
    />
    <ImportarExcelNewView
      v-if="mostrarDialogoImportar"
      :visible.sync="mostrarDialogoImportar"
      @cerrar="cerrarDialogos('Importar')"
    />
  </main>
</template>

<script>
import Panel from "primevue/panel";
import Paginator from "primevue/paginator";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Dialog from "primevue/dialog";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Textarea from "primevue/textarea";
import InputNumber from "primevue/inputnumber";
import ImagePreview from "primevue/imagepreview";
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import DialogProveedores from "../../components/modales/DialogProveedores.vue";
import DialogCategoria from "../../components/modales/DialogCategoriaServicio.vue";
import DialogMarcas from "../../components/modales/DialogMarcas.vue";
import DialogIndustrias from "../../components/modales/DialogIndustrias.vue";
import DialogGrupos from "../../components/modales/DialogGrupos.vue";
import DialogMedidas from "../../components/modales/DialogMedidas.vue";
import DialogAlmacenes from "../../components/modales/DialogAlmacenes.vue";
import Dropdown from "primevue/dropdown";
import InputSwitch from "primevue/inputswitch";
import Calendar from "primevue/calendar";
import VueBarcode from "vue-barcode";
import {
  esquemaArticulos,
  esquemaInventario,
} from "../../constants/validations";
import ImportarExcelNewView from "../../components/Servicios/ImportarExcelServicio.vue";
import Swal from "sweetalert2";

export default {
  components: {
    TabView,
    TabPanel,
    Panel,
    Button,
    InputText,
    Dialog,
    DataTable,
    Column,
    Textarea,
    InputNumber,
    ImagePreview,
    Dropdown,
    InputSwitch,
    Calendar,
    Paginator,
    barcode: VueBarcode,
    DialogProveedores,
    DialogCategoria,
    DialogMarcas,
    DialogIndustrias,
    DialogGrupos,
    DialogMedidas,
    DialogAlmacenes,
    ImportarExcelNewView,
  },
  data() {
    return {     
      mostrarLabel: true,
      isLoading: false,
      criterio: "nombre",
      buscar: "",
      arrayArticulo: [], // Datos del artículo
      dialogVisible: false,
      agregarStock: false,
      fechaVencimientoAlmacen: null,
      unidadStock: null,
      datosFormulario: {
        precio_variable: false,
        nombre: "",
        descripcion: "",
        unidad_envase: 0,
        precio_costo_unid: 0,
        precio_costo_paq: 0,
        precio_venta: 0,
        precio_uno: 0,
        precio_dos: 0,
        precio_tres: 0,
        precio_cuatro: 0,
        stock: 0,
        costo_compra: 0,
        codigo: "",
        codigo_alfanumerico: "",
        descripcion_fabrica: "",
        idcategoria: null,
        idmarca: null,
        idindustria: null,
        idgrupo: null,
        idproveedor: null,
        idmedida: null,
        maximo_descuento: null,
      },
      datosFormularioInventario: {
        AlmacenSeleccionado: null,
        fechaVencimientoAlmacen: null,
        unidadStock: null,
      },
      errores: {},
      erroresinventario: {},
      tipo_stock: "",
      tipoEnvase: [
        { valor: "paquetes", etiqueta: "Paquetes" },
        { valor: "unidades", etiqueta: "Unidades" },
      ],
      mostrarDialogoProveedores: false,
      mostrarDialogoLineas: false,
      mostrarDialogoMarcas: false,
      mostrarDialogoIndustrias: false,
      mostrarDialogoGrupos: false,
      mostrarDialogoMedidas: false,
      mostrarDialogoAlmacen: false,
      mostrarDialogoImportar: false,
      proveedorSeleccionado: [],
      lineaSeleccionado: [],
      marcaSeleccionado: [],
      industriaSeleccionado: [],
      grupoSeleccionado: [],
      medidaSeleccionado: [],
      almacenSeleccionado: "Almacen Principal",
      precios: [],
      precio_uno: 0,
      precio_dos: 0,
      precio_tres: 0,
      precio_cuatro: 0,
      monedaPrincipal: [],
      maximo_descuento: 0,

      //CONFIGURACIONES
      mostrarSaldosStock: "",
      mostrarProveedores: "",
      mostrarCostos: "",
      rolUsuario: "",
      articulo_id: 0,
      idcategoria: 0,
      idmarca: 0,
      idindustria: 0,
      idproveedor: 0,
      idgrupo: 0,
      codigoProductoSin: 0,
      idmedida: 0,
      nombreLinea: "",
      nombre_categoria: "",
      nombre_proveedor: "",
      //id:'',//aumente 7 julio
      codigo: "",
      nombre: "",
      nombre_producto: "",
      nombreProductoVacio: false,
      codigoVacio: false,
      unidad_envaseVacio: false,
      precio_costo_unidVacio: false,
      precio_costo_paqVacio: false,
      precio_ventaVacio: false,
      costo_compraVacio: false,
      stockVacio: false,
      descripcionVacio: false,
      fotografiaVacio: false,
      lineaseleccionadaVacio: false,
      marcaseleccionadaVacio: false,
      industriaseleccionadaVacio: false,
      proveedorseleccionadaVacio: false,
      gruposeleccionadaVacio: false,
      medidaseleccionadaVacio: false,
      unidad_envase: 0,
      precio_costo_unid: 0,
      precio_costo_paq: 0,
      fotoMuestra: null,
      tipoAccion: 0,
      minDate: null,
      idarticulo: 0,
      // Paginación frontend
      first: 0,
      rowsPerPage: 10,
      offset: 3,
      headers: [
        { field: "acciones", header: "Acciones", type: "button" },
        { field: "codigo", header: "CODIGO" },
        { field: "nombre", header: "SERVICIO" },
        { field: "descripcion", header: "DESCRIPCION" },
        { field: "nombre_categoria", header: "CATEGORIA" },
                { field: "maximo_descuento", header: "Maximo Descuento" },
      ],
    };
  },
  computed: {
    imagen() {
      return this.fotoMuestra;
    },
    paginatedItems() {
      // Obtener elementos a mostrar en la página actual (frontend pagination)
      return this.arrayArticulo.slice(this.first, this.first + this.rowsPerPage);
    },

    computedColumns() {
      const dynamicColumns = this.precios.map((precio, index) => ({
        field: `precio_${["uno", "dos", "tres", "cuatro"][index]}`,
        header: `PRECIO ${precio.nombre_precio}`,
        type: "dynamicPrice",
      }));
      const index =
        this.headers.findIndex(
          (header) => header.field === "nombre_categoria"
        ) + 1;
      const result = [
        ...this.headers.slice(0, index),
        ...dynamicColumns,
        ...this.headers.slice(index),
      ];
      console.log("RESULTS COMPUTED ", index);
      return result;
    },
  },
  methods: {
        handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
    },
    cargarReporte() {
      Swal.fire({
  title: "Selecciona el tipo de reporte",
  icon: "question",
  showDenyButton: true,
  showCancelButton: true,
  confirmButtonText: "PDF",
  cancelButtonText: "EXCEL",
  denyButtonText: "VOLVER",
  customClass: {
    confirmButton: "swal2-cancel-lineanew",
    cancelButton: "swal2-confirm-lineanew",
    denyButton: "swal2-volver-lineanew",
  },
  buttonsStyling: false,
      }).then((result) => {
        if (result.isConfirmed) {
          window.open("/servicio/pdf", "_blank"); // PDF
        } else if (result.dismiss === Swal.DismissReason.cancel) {
          window.open("/servicio/excel", "_blank"); // Excel
        }
      });
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
          duration: 4000,
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
          duration: 4000,
        }
      );
    },
    handleDateChange(date) {
      // Verifica si date es un objeto Date válido
      if (date instanceof Date && !isNaN(date)) {
        this.fechaVencimientoAlmacen = this.formatDateToYMD(date);
        console.log("fecha ", this.fechaVencimientoAlmacen);
      } else {
        console.error("La fecha seleccionada no es válida:", date);
      }
    },
    formatDateToYMD(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0"); // Los meses son indexados desde 0
      const day = String(date.getDate()).padStart(2, "0");
      return `${year}-${month}-${day}`;
    },
    closeDialog() {
      this.dialogVisible = false;
    },
    obtenerFotografia(event) {
      let file = event.target.files[0];

      let fileType = file.type;
      // Validar si el archivo es una imagen en formato PNG o JPG
      if (fileType !== "image/png" && fileType !== "image/jpeg") {
        alert("Por favor, seleccione una imagen en formato PNG o JPG.");
        return;
      }

      this.fotografia = file;
      this.mostrarFoto(file);
    },
    mostrarFoto(file) {
      let reader = new FileReader();
      reader.onload = (file) => {
        this.fotoMuestra = file.target.result;
      };
      reader.readAsDataURL(file);
    },
    manejarProveedorSeleccionado(proveedor) {
      this.proveedorSeleccionado = proveedor;
      this.validarCampo("idproveedor");
    },
    manejarLineaSeleccionado(linea) {
      this.lineaSeleccionado = linea;
      this.validarCampo("idcategoria");
    },
    manejarMarcaSeleccionado(marca) {
      this.marcaSeleccionado = marca;
      this.validarCampo("idmarca");
    },
    manejarIndustriaSeleecionado(industria) {
      this.industriaSeleccionado = industria;
      this.validarCampo("idindustria");
    },
    manejarGrupoSeleccionado(grupo) {
      this.grupoSeleccionado = grupo;
      this.validarCampo("idgrupo");
    },
    manejarMedidaSeleccionado(medida) {
      this.medidaSeleccionado = medida;
      this.validarCampo("idmedida");
    },
    manejarAlmacenSeleccionado(almacen) {
      this.almacenSeleccionado = almacen;
      if (this.agregarStock == true) {
        this.validarCampoInventario("AlmacenSeleccionado");
      }
    },
    abrirDialogos(dialogo) {
      switch (dialogo) {
        case "Proveedores":
          this.mostrarDialogoProveedores = true;
          break;
        case "Lineas":
          this.mostrarDialogoLineas = true;
          break;
        case "Marcas":
          this.mostrarDialogoMarcas = true;
          break;
        case "Industrias":
          this.mostrarDialogoIndustrias = true;
          break;
        case "Grupos":
          this.mostrarDialogoGrupos = true;
          break;
        case "Medidas":
          this.mostrarDialogoMedidas = true;
          break;
        case "Almacen":
          this.mostrarDialogoAlmacen = true;
          this.dialogVisible = false;
          break;
        case "Importar":
          this.mostrarDialogoImportar = true;
          break;
      }
      this.dialogVisible = false;
    },
    cerrarDialogos(dialogo) {
      switch (dialogo) {
        case "Proveedores":
          this.mostrarDialogoProveedores = false;
          break;
        case "Lineas":
          this.mostrarDialogoLineas = false;
          break;
        case "Marcas":
          this.mostrarDialogoMarcas = false;
          break;
        case "Industrias":
          this.mostrarDialogoIndustrias = false;
          break;
        case "Grupos":
          this.mostrarDialogoGrupos = false;
          break;
        case "Medidas":
          this.mostrarDialogoMedidas = false;
          break;
        case "Almacen":
          this.mostrarDialogoAlmacen = false;
          break;
        case "Importar":
          this.mostrarDialogoImportar = false;
          this.listarServicio(1, "", "nombre");
          break;
      }
      this.dialogVisible = true;
    },
      listarPrecio() {
          let me = this;
          me.isLoading = true; // Activar loader
          
          var url = "/precios";
          return axios.get(url)
              .then(function(response) {
                  var respuesta = response.data;
                  me.precios = respuesta.precio.data;
              })
              .catch(function(error) {
                  console.log(error);
              })
              .finally(() => {
                  me.isLoading = false; // Desactivar loader
              });
      },
    buscarArticulo() {
      this.listarServicio(1, this.buscar);
    },
    asignarCampos() {
      this.datosFormulario.idcategoria = this.lineaSeleccionado.id;
      this.datosFormulario.idmedida = this.medidaSeleccionado.id;
    },
    asignarCamposPrecios() {
      this.datosFormulario.precio_venta = this.convertDolar(
        this.datosFormulario.precio_venta
      );

      this.datosFormulario.precio_uno = this.convertDolar(this.precio_uno);
      this.datosFormulario.precio_dos = this.convertDolar(this.precio_dos);
      this.datosFormulario.precio_tres = this.convertDolar(this.precio_tres);
      this.datosFormulario.precio_cuatro = this.convertDolar(
        this.precio_cuatro
      );
    },

    convertDolar(precio) {
      return precio / parseFloat(this.monedaPrincipal);
    },
    async validarCampo(campo) {
      this.asignarCampos();
      try {
        await esquemaArticulos.validateAt(campo, this.datosFormulario);
        this.errores[campo] = null;
      } catch (error) {
        this.errores[campo] = error.message;
      }
    },
    async validarCampoInventario(campo) {
      try {
        await esquemaInventario.validateAt(
          campo,
          this.datosFormularioInventario
        );
        this.erroresinventario[campo] = null;
      } catch (error) {
        this.erroresinventario[campo] = error.message;
      }
    },
    async enviarFormulario() {
      this.asignarCampos();
      this.asignarCamposPrecios();
      // Asegurar que maximo_descuento se pase correctamente
      this.datosFormulario.maximo_descuento = this.maximo_descuento;
      console.log("UNIDAD STOCK ", this.unidadStock);
      console.log("ALMACEN ", this.AlmacenSeleccionado);
      console.log("agregar ", this.agregarStock);

      if (this.agregarStock === true) {
        console.log("Asignando valores adicionales al formulario");
      }

      console.log("DATOS FORMULARIO ANTES DE VALIDAR: ", this.datosFormulario);
      console.log(
        "DATOS FORMULARIO ANTES DE VALIDAR: ",
        this.datosFormularioInventario
      );
      console.log("Formulario>: ", this.datosFormulario)
      const camposFaltantes = [];
      if (!this.datosFormulario.nombre) camposFaltantes.push('Concepto del servicio');
      if (!this.datosFormulario.idcategoria) camposFaltantes.push('Categoria del servicio');
      if (!this.datosFormulario.precio_uno) camposFaltantes.push('Precio 1');
      if (!this.datosFormulario.maximo_descuento) camposFaltantes.push('Maximo descuento');

      let validacionExitosa = true;
      let validacionInventarioExitosa = true;

      try {
        await esquemaArticulos.validate(this.datosFormulario, {
          abortEarly: false,
        });
        console.log("Validación de esquemaArticulos exitosa");
      } catch (error) {
        validacionExitosa = false;
        const erroresValidacion = {};
        error.inner.forEach((e) => {
          erroresValidacion[e.path] = e.message;
        });
        this.errores = erroresValidacion;
        console.log("Errores en esquemaArticulos: ", this.errores);
      }
      if (camposFaltantes.length > 0) {
        Swal.fire({
          title: '¡Campos obligatorios!',
          html: `Debe completar los siguientes campos:<br><ul style="text-align:left;">${camposFaltantes.map(c => `<li>${c}</li>`).join('')}</ul>`,
          icon: 'warning',
        });
        return;
      }

      if (this.tipoAccion != 2 && this.agregarStock == true) {
        try {
          await esquemaInventario.validate(this.datosFormularioInventario, {
            abortEarly: false,
          });
          console.log("Validación de esquemaInventario exitosa");
        } catch (error) {
          validacionInventarioExitosa = false;
          const erroresValidacionInventario = {};
          error.inner.forEach((e) => {
            erroresValidacionInventario[e.path] = e.message;
          });

          this.erroresinventario = erroresValidacionInventario;
          console.log("Errores en esquemaInventario: ", this.erroresinventario);
        }
      }

      if (this.tipoAccion == 2) {
        // Actualización del artículo
        try {
          this.datosFormulario.fotografia = this.fotografia;
          if (this.tipo_stock == "paquetes") {
            this.datosFormulario.stock =
              this.datosFormulario.unidad_envase * this.datosFormulario.stock;
            console.log("paquetes ", this.datosFormulario.stock);
          }
          await this.actualizarArticulo(this.datosFormulario);
          console.log("Actualización de artículo exitosa");
        } catch (error) {
          console.error("Error al actualizar el artículo: ", error);
        }
      } else if (validacionExitosa || validacionInventarioExitosa) {
        // Registro del artículo
        console.log("TIPO STOCK ", this.tipo_stock);
        this.datosFormulario.fotografia = this.fotografia;
        if (this.tipo_stock == "paquetes") {
          this.datosFormulario.stock =
            this.datosFormulario.unidad_envase * this.datosFormulario.stock;
          console.log("paquetes ", this.datosFormulario.stock);
        }

        try {
          await this.registrarArticulo(this.datosFormulario);
          console.log("Registro de artículo exitoso", this.datosFormulario);
        } catch (error) {
          console.error("Error al registrar el artículo: ", error);
        }
      }
    },
    obtenerConfiguracionTrabajo() {
          let me = this;
          me.isLoading = true; // Activar loader
          
          return axios.get("/configuracion")
              .then((response) => {
                  console.log(response);
              })
              .catch((error) => {
                  console.error('Error al obtener configuración de trabajo:', error);
              })
              .finally(() => {
                  me.isLoading = false; // Desactivar loader
              });
      },
      datosConfiguracion() {
          let me = this;
          me.isLoading = true; // Activar loader
          
          return axios.get("/configuracion")
              .then(function(response) {
                  var respuesta = response.data;
                  me.mostrarSaldosStock = respuesta.configuracionTrabajo.mostrarSaldosStock;
                  me.mostrarCostos = respuesta.configuracionTrabajo.mostrarCostos;
                  me.mostrarProveedores = respuesta.configuracionTrabajo.mostrarProveedores;
                  me.monedaPrincipal = [
                      respuesta.configuracionTrabajo.valor_moneda_principal,
                      respuesta.configuracionTrabajo.simbolo_moneda_principal
                  ];
              })
              .catch(function(error) {
                  console.log(error);
              })
              .finally(() => {
                  me.isLoading = false; // Desactivar loader
              });
      },
    calculatePages: function(paginationObject, offset) {
      if (!paginationObject.to) {
        return [];
      }

      var from = paginationObject.current_page - offset;
      if (from < 1) {
        from = 1;
      }

      var to = from + offset * 2;
      if (to >= paginationObject.last_page) {
        to = paginationObject.last_page;
      }

      var pagesArray = [];
      while (from <= to) {
        pagesArray.push(from);
        from++;
      }
      return pagesArray;
    },
    calcularPrecioCostoUnid() {
      if (
        this.datosFormulario.unidad_envase &&
        this.datosFormulario.precio_costo_paq
      ) {
        this.datosFormulario.precio_costo_unid =
          this.datosFormulario.precio_costo_paq /
          this.datosFormulario.unidad_envase;
        this.datosFormulario.precio_costo_unidVacio = false;
        this.validarCampo("precio_costo_unid");
      }
    },
    calcularPrecioCostoPaq() {
      if (
        this.datosFormulario.unidad_envase &&
        this.datosFormulario.precio_costo_unid
      ) {
        this.datosFormulario.precio_costo_paq =
          this.datosFormulario.precio_costo_unid *
          this.datosFormulario.unidad_envase;
        this.datosFormulario.precio_costo_paqVacio = false;
        this.validarCampo("precio_costo_paq");
      }
    },
    calcularPrecioP(precio_costo_unid, porcentage) {
      const margenG = precio_costo_unid * (porcentage / 100);
      const precioP = parseFloat(precio_costo_unid) + parseFloat(margenG);
      return precioP.toFixed(2);
    },
      listarServicio(page, buscar, criterio) {
          let me = this;
          me.isLoading = true; // Activar loader
          
          var url = "/servicio?buscar=" + buscar + "&criterio=" + criterio;
          return axios.get(url)
              .then(function(response) {
                  var respuesta = response.data;
                  me.arrayArticulo = respuesta.articulos;
                  me.first = 0; // Reiniciar a la primera página
              })
              .catch(function(error) {
                  console.log(error);
              })
              .finally(() => {
                  me.isLoading = false; // Desactivar loader
              });
      },
    onPageChange(event) {
      this.first = event.first;
      this.rowsPerPage = event.rows;
    },
    calcularPrecioValorMoneda(precio) {
      return Number((precio * parseFloat(this.monedaPrincipal)).toFixed(2));
    },
    registrarArticulo(data) {
      let me = this;
      var formulario = new FormData();
      for (var key in data) {
        if (data.hasOwnProperty(key)) {
          formulario.append(key, data[key]);
        }
      }

      axios
        .post("/servicio/registrar", formulario, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })
        .then(function(response) {
          var respuesta = response.data;
          me.idarticulo = respuesta.idArticulo;
          console.log("respuesta = ", me.idarticulo);
          me.cerrarModal();
          me.listarServicio(1, "", "nombre");
          me.toastSuccess("Articulo registrado correctamente");
          console.log("stock ???", me.agregarStock);
          if (me.agregarStock == true) {
            let arrayArticulos = [
              {
                idarticulo: me.idarticulo,
                idalmacen: 1,
                cantidad: me.unidadStock,
                fecha_vencimiento: me.fechaVencimientoAlmacen,
              },
            ];
            console.log("registrar inventario qefqe", arrayArticulos);
            return axios.post("/inventarios/registrar", {
              inventarios: arrayArticulos,
            });
          }
        })
        .then(function(response) {
          if (response) {
            console.log(response.data);
          }
        })
        .catch(function(error) {
          console.error(error);
          me.toastError("Hubo un error al registrar el articulo o inventario");
        });
    },
    actualizarArticulo(data) {
      var formulario = new FormData();
      let me = this;
      for (var key in data) {
        if (data.hasOwnProperty(key)) {
          formulario.append(key, data[key]);
        }
      }

      axios
        .post("/servicio/actualizar", formulario, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })
        .then(function(response) {
          //alert("Datos actualizados con éxito");
          //console.log("datos actuales",formData);
          var respuesta = response.data;
          console.log("respuesta = ", respuesta);
          console.log("foto ", data);
          me.cerrarModal();
          me.listarServicio(1, "", "nombre");
          me.toastSuccess("Articulo actualizado correctamente");
        })
        .catch(function(error) {
          console.log(error);
          me.toastError("No se puedo actualizar el articulo");
        });
    },
    desactivarArticulo(id) {
      Swal.fire({
        title: "¿Está seguro de ELIMINAR este artículo?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Aceptar!",
        cancelButtonText: "Cancelar",
                  customClass: {
            confirmButton: "swal2-confirm-lineanew",
            cancelButton: "swal2-cancel-lineanew",
          },
        reverseButtons: true,
      }).then((result) => {
        if (result.isConfirmed) {
          let me = this;
          axios
            .put("/articulo/desactivar", {
              id: id,
            })
            .then(function(response) {
              me.listarServicio(1, "", "nombre");
              Swal.fire(
                "Desactivado!",
                "El registro ha sido desactivado con éxito.",
                "success"
              );
            })
            .catch(function(error) {
              console.log(error);
            });
        }
      });
    },
    activarArticulo(id) {
      Swal.fire({
        title: "¿Está seguro de activar este artículo?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Aceptar!",
        cancelButtonText: "Cancelar",
        reverseButtons: true,
      }).then((result) => {
        if (result.isConfirmed) {
          let me = this;
          axios
            .put("/articulo/activar", {
              id: id,
            })
            .then(function(response) {
              me.listarServicio(1, "", "nombre");
              Swal.fire(
                "Activado!",
                "El registro ha sido activado con éxito.",
                "success"
              );
            })
            .catch(function(error) {
              console.log(error);
            });
        }
      });
    },
    validarArticulo() {
      this.errorArticulo = 0;
      this.errorMostrarMsjArticulo = [];
      if (!this.nombre_producto) this.errorMostrarMsjArticulo.push("");
      if (!this.precio_venta) this.errorMostrarMsjArticulo.push("");

      if (this.errorMostrarMsjArticulo.length) this.errorArticulo = 1;

      return this.errorArticulo;
    },
    cerrarModal() {
      this.dialogVisible = false;
      this.tituloModal = "";
      this.codigo = "";
      this.nombre_producto = "";
      this.precio_venta = "";
      this.precio_costo_unid = "";
      this.precio_costo_paq = "";
      this.stock = "";
      this.descripcion = "";
      this.fotografia = ""; //Pasando el valor limpio de la referencia
      this.fotoMuestra = null;
      this.lineaSeleccionado = [];
      this.marcaSeleccionado = [];
      this.industriaSeleccionado = [];
      this.proveedorSeleccionado = [];
      this.grupoSeleccionado = [];
      this.medidaSeleccionado = [];
      this.fechaVencimientoSeleccion = false;
      this.errorArticulo = 0;
      this.idmedida = 0;
      this.costo_compra = "";
      this.precio_uno = "";
      this.precio_dos = "";
      this.precio_tres = "";
      this.precio_cuatro = "";
    },
    abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case "articulo": {
          switch (accion) {
            case "registrar": {
              this.dialogVisible = true;
              this.tituloModal = "Registrar Servicio";
              this.agregarStock = false;
              this.tipoAccion = 1;
              this.fotografia = "";

              this.datosFormulario = {
                precio_variable: false,
                nombre: "",
                descripcion: "",
                precio_venta: null,
                precio_uno: null,
                precio_dos: null,
                precio_tres: null,
                precio_cuatro: null,
                codigo: "",
                idcategoria: null,
                maximo_descuento: null,
              };
              this.maximo_descuento = null;
              this.errores = {};
              break;
            }
            case "actualizar": {
              console.log("DATA ACTUALIZAR", data);
              this.agregarStock = false;
              this.dialogVisible = true;
              this.tituloModal = "Actualizar Artículo";
              this.tipoAccion = 2;
              this.datosFormulario = {
                nombre: data["nombre"],
                descripcion: data["descripcion"],
                precio_venta: this.calcularPrecioValorMoneda(
                  data["precio_venta"],
                ),
                precio_variable: data["precio_variable"] == 1,
                precio_uno: 0,
                precio_dos: 0,
                precio_tres: 0,
                precio_cuatro: 0,
                codigo: data["codigo"],
                idcategoria: null,
                idmedida: data["idmedida"],
                id: data["id"],
                maximo_descuento: (data["maximo_descuento"] !== undefined && data["maximo_descuento"] !== null && !isNaN(Number(data["maximo_descuento"]))) ? Number(data["maximo_descuento"]) : null,
              };
              this.maximo_descuento = (data["maximo_descuento"] !== undefined && data["maximo_descuento"] !== null && !isNaN(Number(data["maximo_descuento"]))) ? Number(data["maximo_descuento"]) : null;
              this.errores = {};
              this.idmedida = data["idmedida"];

              this.lineaSeleccionado = {
                nombre: data["nombre_categoria"],
                id: data["idcategoria"],
              };

              this.medidaSeleccionado = {
                descripcion_medida: data["descripcion_medida"],
                id: data["idmedida"],
              };

              this.precio_uno = this.calcularPrecioValorMoneda(
                data["precio_uno"]
              );
              this.precio_dos = this.calcularPrecioValorMoneda(
                data["precio_dos"]
              );
              this.precio_tres = this.calcularPrecioValorMoneda(
                data["precio_tres"]
              );
              this.precio_cuatro = this.calcularPrecioValorMoneda(
                data["precio_cuatro"]
              );

              break;
            }
          }
        }
      }
    },
    calcularPrecio(precio, index) {
      if (
        isNaN(this.datosFormulario.precio_costo_unid) ||
        isNaN(parseFloat(precio.porcentage))
      ) {
        return;
      }
      const margen_ganancia =
        parseFloat(this.datosFormulario.precio_costo_unid) *
        (parseFloat(precio.porcentage) / 100);
      const precio_publico =
        parseFloat(this.datosFormulario.precio_costo_unid) + margen_ganancia;
      console.log("precio publico", typeof precio_publico);
      if (index === 0) {
        this.precio_uno = Number(parseFloat(precio_publico).toFixed(2));
      } else if (index === 1) {
        this.precio_dos = Number(parseFloat(precio_publico).toFixed(2));
      } else if (index === 2) {
        this.precio_tres = Number(parseFloat(precio_publico).toFixed(2));
      } else if (index === 3) {
        this.precio_cuatro = Number(parseFloat(precio_publico).toFixed(2));
      }
    },
    recuperarIdRol() {
        let me = this;
        me.isLoading = true; // Activar loader
        
        return new Promise((resolve) => {
            this.rolUsuario = window.userData.rol;
            me.isLoading = false; // Desactivar loader
            resolve();
        });
    },
  },

mounted() {
      this.handleResize();
    window.addEventListener("resize", this.handleResize);
    this.isLoading = true; // Activar loader al inicio
    Promise.all([
        this.recuperarIdRol(),
        this.datosConfiguracion(),
        this.obtenerConfiguracionTrabajo(),
        this.listarServicio(1, this.buscar, this.criterio),
        this.listarPrecio()
    ])
    .catch(error => {
        console.error('Error en la inicialización:', error);
    })
    .finally(() => {
        this.isLoading = false; // Desactivar loader
        let today = new Date();
        let tomorrow = new Date(today);
        tomorrow.setDate(today.getDate() + 1);
        this.minDate = tomorrow;
    });
},
};
</script>

<style scoped>
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

>>> .p-datatable.p-datatable-gridlines .p-datatable-tbody > tr > td {
  text-align: center;
}
.bold-input {
  font-weight: bold;
}
/*Panel*/
.ingreso-panel {
  margin-bottom: 1rem;
}
.panel-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
}
.panel-icon {
  color: #000000;
  font-size: 1.2rem;
}
.panel-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #1f2937;
}

/* Panel Content Spacing */
>>> .p-panel .p-panel-content {
  padding: 1rem;
}
>>> .p-panel .p-panel-header {
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
}
>>> .p-panel .p-panel-header .p-panel-title {
  font-weight: 600;
}

/* Responsive Dialog Styles */
.responsive-dialog >>> .p-dialog {
  margin: 0.75rem;
  max-height: 90vh;
  overflow-y: auto;
}

.responsive-dialog >>> .p-dialog-content {
  overflow-x: auto;
  padding: 1rem;
}

.responsive-dialog >>> .p-dialog-header {
  padding: 1rem 1.5rem;
  font-size: 1.1rem;
}

.responsive-dialog >>> .p-dialog-footer {
  padding: 0.75rem 1.5rem;
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

.form-group {
  margin-bottom: 15px;
}

>>> .p-dropdown .p-dropdown-trigger {
  width: 2rem;
}
.switch-container {
  display: flex;
  align-items: center;
  justify-content: space-evenly;
}
.custom-precios {
  display: flex;
  justify-content: space-evenly;
  align-items: center;
}

/* Estilos para el código de barras */
.barcode-container {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  overflow-x: auto;
}

/* En desktop, mantener el código de barras a la derecha */
@media (min-width: 769px) {
  .barcode-container {
    justify-content: flex-start;
    width: 250px;
  }
}

/* En móvil, centrar el código de barras */
@media (max-width: 768px) {
  .barcode-container {
    justify-content: center;
    margin: 1rem auto;
    width: 100%;
  }
}

/* DataTable Responsive */
>>> .p-datatable {
  font-size: 0.9rem;
}

>>> .p-datatable .p-datatable-tbody > tr > td {
  padding: 0.5rem;
  word-break: break-word;
}

>>> .p-datatable .p-datatable-thead > tr > th {
  padding: 0.75rem 0.5rem;
  font-size: 0.85rem;
}

/* Tablet Styles */
@media (max-width: 1024px) {
  .responsive-dialog >>> .p-dialog {
    margin: 0.5rem;
    max-height: 95vh;
  }

  >>> .p-datatable {
    font-size: 0.85rem;
  }
}

/* Mobile Styles */
@media (max-width: 768px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }
  .responsive-dialog >>> .p-dialog {
    margin: 0.25rem;
    max-height: 98vh;
  }

  .responsive-dialog >>> .p-dialog-content {
    padding: 0.75rem;
  }

  .responsive-dialog >>> .p-dialog-header {
    padding: 0.75rem 1rem;
    font-size: 1rem;
  }

  .responsive-dialog >>> .p-dialog-footer {
    padding: 0.5rem 1rem;
    justify-content: flex-end;
  }

  .toolbar-container {
    gap: 0.5rem;
  }

  >>> .p-datatable {
    font-size: 0.8rem;
  }

  >>> .p-datatable .p-datatable-tbody > tr > td {
    padding: 0.4rem 0.3rem;
  }

  >>> .p-datatable .p-datatable-thead > tr > th {
    padding: 0.5rem 0.3rem;
    font-size: 0.75rem;
  }

  /* Ajustar botones en móviles */
  >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
    min-width: auto !important;
  }

  /* Ajustar botón "Nuevo" para que coincida con otros botones */
  .toolbar >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  /* Reducir altura del input buscador */
  .search-bar .p-inputtext-sm {
    padding: 0.35rem 0.5rem 0.35rem 2.5rem !important;
    font-size: 0.85rem !important;
  }
}

/* Extra Small Mobile */
@media (max-width: 480px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }
  .responsive-dialog >>> .p-dialog {
    margin: 0.1rem;
    max-height: 99vh;
  }

  .responsive-dialog >>> .p-dialog-content {
    padding: 0.5rem;
  }

  .responsive-dialog >>> .p-dialog-header {
    padding: 0.5rem 0.75rem;
    font-size: 0.95rem;
  }

  /* Footer mantiene botones alineados a la derecha, no ocupan todo el ancho */
  .responsive-dialog >>> .p-dialog-footer {
    padding: 0.5rem 0.75rem;
    justify-content: flex-end;
  }

  .responsive-dialog >>> .p-dialog-footer .p-button {
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
  .toolbar >>> .p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  /* Reducir más la altura del input buscador en móviles pequeños */
  .search-bar .p-inputtext-sm {
    padding: 0.3rem 0.5rem 0.3rem 2.5rem !important;
    font-size: 0.8rem !important;
  }

  >>> .p-datatable {
    font-size: 0.75rem;
  }

  >>> .p-datatable .p-datatable-tbody > tr > td {
    padding: 0.3rem 0.2rem;
  }

  >>> .p-datatable .p-datatable-thead > tr > th {
    padding: 0.4rem 0.2rem;
    font-size: 0.7rem;
  }
}

/* Paginator Responsive */
@media (max-width: 768px) {
  >>> .p-paginator {
    flex-wrap: wrap !important;
    justify-content: center;
    font-size: 0.85rem;
    padding: 0.5rem;
  }

  >>> .p-paginator .p-paginator-page,
  >>> .p-paginator .p-paginator-next,
  >>> .p-paginator .p-paginator-prev,
  >>> .p-paginator .p-paginator-first,
  >>> .p-paginator .p-paginator-last {
    min-width: 32px !important;
    height: 32px !important;
    font-size: 0.85rem !important;
    padding: 0 6px !important;
    margin: 2px !important;
  }
}

@media (max-width: 480px) {
  >>> .p-paginator {
    font-size: 0.8rem;
    padding: 0.4rem;
  }

  >>> .p-paginator .p-paginator-page,
  >>> .p-paginator .p-paginator-next,
  >>> .p-paginator .p-paginator-prev,
  >>> .p-paginator .p-paginator-first,
  >>> .p-paginator .p-paginator-last {
    min-width: 28px !important;
    height: 28px !important;
    font-size: 0.8rem !important;
    padding: 0 4px !important;
    margin: 1px !important;
  }
}

/* Action Buttons in DataTable */
>>> .p-datatable .p-button {
  margin-right: 0.25rem;
}

@media (max-width: 768px) {
  >>> .p-datatable .p-button {
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
.p-dialog-mask {
  z-index: 9990 !important;
}
.p-dialog {
  z-index: 9990 !important;
}
.swal-zindex {
  z-index: 9995 !important;
}
.addon-small {
  font-size: 1em;
}
@media (max-width: 768px) {
  .addon-small {
    font-size: 0.85em !important;
  }
}
@media (max-width: 480px) {
  .addon-small {
    font-size: 0.75em !important;
  }
}

/* Ajustar el tamaño del cuadrado del addon para que coincida con los inputs pequeños */
@media (max-width: 768px) {
  .p-inputgroup-addon,
  .addon-small.p-inputgroup-addon {
    padding: 0.25rem 0.5rem !important;
height: 2.0rem !important;
    min-width: 2rem !important;
    font-size: 0.85em !important;
    line-height: 1.2 !important;
  }
}
@media (max-width: 480px) {
  .p-inputgroup-addon,
  .addon-small.p-inputgroup-addon {
    padding: 0.15rem 0.35rem !important;
    height: 2.0rem !important;
    min-width: 1.6rem !important;
    font-size: 0.75em !important;
    line-height: 1.1 !important;
  }
}
</style>

<style>
.swal2-confirm-articulonew {
  background-color: #22c55e !important;
  border-color: #22c55e !important;
  color: #fff !important;
}
.swal2-cancel-articulonew {
  background-color: #ef4444 !important;
  border-color: #ef4444 !important;
  color: #fff !important;
}
.modal-footer-buttons {
  padding-top: 1rem;
}
</style>