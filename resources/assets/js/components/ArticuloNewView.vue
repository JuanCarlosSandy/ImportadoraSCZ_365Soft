<template>
  <main class="main">
    <Toast :breakpoints="{ '920px': { width: '100%', right: '0', left: '0' } }" style="padding-top: 10px;"
      appendTo="body" :baseZIndex="99999"></Toast>
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
          <h4 class="panel-title">PRODUCTOS</h4>
        </div>
      </template>
      <div class="toolbar-container">
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText v-model="buscar" placeholder="Texto a buscar" class="p-inputtext-sm" @keyup="buscarArticulo" />
          </span>
        </div>
        <div class="toolbar">
          <Button v-if="idrol !== 2" :label="mostrarLabel ? 'Nuevo' : ''" icon="pi pi-plus"
            class="p-button-secondary p-button-sm" @click="abrirModal('articulo', 'registrar')" />
          <Button :label="mostrarLabel ? 'Reporte' : ''" icon="pi pi-file" class="p-button-success p-button-sm"
            @click="descargarReporteExcel()" />
          <Button :label="mostrarLabel ? 'Importar' : ''" icon="pi pi-upload" class="p-button-help p-button-sm"
            @click="abrirDialogos('Importar')" />
        </div>
      </div>
      <DataTable :value="arrayArticulo" class="p-datatable-gridlines p-datatable-sm tabla-pro"
        responsiveLayout="scroll">
        <Column v-for="(column, index) in computedColumns" :key="index" :field="column.field" :header="column.header">
          <template #body="slotProps">
            <span v-if="column.type === 'button' && idrol !== 2"
              class="d-flex align-items-center justify-content-center gap-1">
              <Button v-if="column.field === 'acciones'" icon="pi pi-pencil" class="p-button-warning btn-mini"
                @click="abrirModal('articulo', 'actualizar', slotProps.data)" v-tooltip.top="'Editar'"/>
              <Button v-if="column.field === 'acciones' && slotProps.data.condicion" icon="pi pi-ban"
                class="p-button-danger btn-mini" @click="desactivarArticulo(slotProps.data.id)" v-tooltip.top="'Desactivar'"/>
              <Button v-if="column.field === 'acciones' && !slotProps.data.condicion" icon="pi pi-check-circle"
                class="p-button-success btn-mini" @click="activarArticulo(slotProps.data.id)" v-tooltip.top="'Activar'"/>
              <Button v-if="column.field === 'detalles'" label="Ver Detalles" icon="pi pi-info-circle"
                class="p-button-info btn-mini" @click="mostrarDetalles(slotProps.data)" v-tooltip.top="'Ver'"/>
            </span>
            <span v-else-if="column.field === 'codigo'" :class="slotProps.data.descuento > 0 ? 'codigo-descuento' : ''"
              :title="slotProps.data.codigo">
              {{ slotProps.data.codigo }}
            </span>
            <span v-else-if="column.type === 'dynamicPrice'">
              {{
                (
                  slotProps.data[column.field] * parseFloat(monedaPrincipal[0])
                ).toFixed(2)
              }}
              {{ monedaPrincipal[1] }}
            </span>
            <span v-else-if="column.type === 'image'">
              <img :src="'img/articulo/' +
                slotProps.data.fotografia +
                '?t=' +
                new Date().getTime()
                " width="50" height="50" v-if="slotProps.data.fotografia" ref="imagen" />
              <img :src="'img/articulo/' + 'defecto.jpg'" width="50" height="50" v-else ref="imagen" />
            </span>
            <span v-else-if="column.type === 'badge'" style="text-align: center;">
              <span v-if="slotProps.data.condicion" class="badge badge-success" style="center">Si</span>
              <span v-else class="badge badge-danger" style="center">No</span>
            </span>
            <span v-else>
              {{ slotProps.data[column.field] }}
            </span>
          </template>
        </Column>
      </DataTable>
      <Paginator :rows="pagination.per_page" :totalRecords="pagination.total"
        :first="(pagination.current_page - 1) * pagination.per_page" @page="onPageChange" />
    </Panel>
    <!-- MODAL REGISTRAR PRODUCTO -->
    <Dialog :visible.sync="dialogVisible" :modal="true" header="Datos del Producto" :closable="false"
      :closeOnEscape="true" @hide="closeDialog" :containerStyle="dialogContainerStyle" class="responsive-dialog">
      <form>
        <TabView v-model:activeIndex="activeTab">
          <!-- 🟢 TAB 1: DATOS DEL ARTÍCULO -->
          <TabPanel header="Datos del Artículo">

            <div class="form-group row">
              <div class="col-md-6">
                <div>
                  <label for="nombre" class="required-field">
                    <span class="required-icon">*</span>
                    Nombre del Producto
                  </label>
                  <InputText id="nombreProducto" v-model="datosFormulario.nombre" placeholder="Ej. Vaso Wiskero4 pts"
                    class="form-control p-inputtext-sm input-full" :class="{ 'input-error': errores.nombre }"
                    @input="validarCampo('nombre')" autocomplete="off"/>
                </div>
              </div>
              <div class="col-md-6">
                <label for="descripcion" class="optional-field">
                  <i class="pi pi-info-circle optional-icon"></i>
                  Codigo de Barras
                  <span class="p-tag p-tag-secondary tag-opcional">Opcional</span>
                </label>
                <InputText id="codigo" v-model="datosFormulario.codigo_alfanumerico" placeholder="Ej. 1000001101011"
                  class="form-control p-inputtext-sm input-full" autocomplete="off"/>
                <div class="barcode-container mt-4">
                  <barcode :value="datosFormulario.codigo_alfanumerico" :options="{ format: 'EAN-13' }"></barcode>
                </div>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-12">
                <div>
                  <label for="nombre" class="required-field">
                    <span class="required-icon">*</span>
                    Codigo del Producto
                  </label>
                  <InputText id="codigo" v-model="datosFormulario.codigo" placeholder="Ej. 309C"
                    class="form-control p-inputtext-sm input-full" :class="{ 'input-error': errores.codigo }"
                    @input="validarCampo('codigo')" autocomplete="off"/>
                </div>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-12">
                <div>
                  <label for="descripcion" class="optional-field">
                    <i class="pi pi-info-circle optional-icon"></i>
                    Descripción
                    <span class="p-tag p-tag-secondary tag-opcional">Opcional</span>
                  </label>
                  <Textarea id="descripcion" v-model="datosFormulario.descripcion"
                    placeholder="Ej. 4 docenas de vasos wiskeros" rows="3" class="form-control p-inputtext-sm" />
                </div>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <label for="nombre" class="required-field">
                  <span class="required-icon">*</span>
                  Proveedor
                </label>
                <div class="p-inputgroup ">
                  <InputText id="proveedor" v-model="proveedorSeleccionado.nombre" placeholder="Seleccione un proveedor"
                    class="form-control p-inputtext-sm bold-input input-full" disabled
                    :class="{ 'input-error': errores.idproveedor }" @input="validarCampo('codigo')" />
                  <Button label="..." class="p-button-primary p-button-sm btn-sm"
                    @click="abrirDialogos('Proveedores')" />
                </div>
              </div>

              <div class="col-md-6">
                <label for="nombre" class="required-field">
                  <span class="required-icon">*</span>
                  Categoria
                </label>
                <div class="p-inputgroup">
                  <InputText id="linea" v-model="lineaSeleccionado.nombre" placeholder="Seleccione una Categoria"
                    class="form-control p-inputtext-sm bold-input input-full" disabled
                    :class="{ 'input-error': errores.idcategoria }" />
                  <Button label="..." class="p-button-primary p-button-sm btn-sm" @click="abrirDialogos('Lineas')" />
                </div>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <div>
                  <label for="nombre" class="required-field">
                    <span class="required-icon">*</span>
                    Medida de Producto
                  </label>

                  <InputText id="nombreProducto" v-model="datosFormulario.descripcion_fabrica"
                    placeholder="Ej. Doc (Docenas)" class="form-control p-inputtext-sm input-full"
                    :class="{ 'input-error': errores.descripcion_fabrica }"
                    @input="validarCampo('descripcion_fabrica')"autocomplete="off" />
                </div>
              </div>

              <div class="col-md-6">
                <label for="unidadEnvase" class="required-field">
                  <span class="required-icon">*</span>

                  <!-- 🔥 Aquí el label dinámico -->
                  {{ datosFormulario.descripcion_fabrica
                    ? datosFormulario.descripcion_fabrica + ' x Caja'
                    : 'Unidades x Caja' }}

                </label>

                <div class="p-inputgroup">
                  <InputNumber id="unidadEnvase" v-model="datosFormulario.unidad_envase" placeholder="Ej: 24"
                    class="p-inputtext-sm input-number-full" :class="{ 'input-error': errores.unidad_envase }"
                    @input="validarCampo('unidad_envase')" autocomplete="off"/>
                </div>
              </div>

            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <label for="nombre" class="required-field">
                  <span class="required-icon">*</span>
                  Costo Compra
                </label>

                <div class="p-inputgroup">
                  <InputNumber id="preciounitario" v-model="datosFormulario.precio_costo_unid" placeholder="Ej: 12.50"
                    locale="es-ES" class="p-inputtext-sm bold-input input-number-full" mode="decimal"
                    :minFractionDigits="2" :maxFractionDigits="2" :class="{ 'input-error': errores.precio_costo_unid }"
                    @value-change="onPrecioPaqChange" @keydown.native="convertirPuntoComa" autocomplete="off"/>

                  <!-- 🔥 Aquí se agrega el texto “Bs” al final -->
                  <span class="p-inputgroup-addon addon-precio">Bs</span>
                </div>
              </div>


              <div class="col-md-6">
                <label for="nombre" class="required-field">
                  <span class="required-icon">*</span>
                  Stock Minimo (Cajas)
                </label>
                <div class="p-inputgroup">
                  <InputNumber id="stockMinimo" v-model="datosFormulario.stock" placeholder="Ej: 10"
                    class="p-inputtext-sm input-number-full" :class="{ 'input-error': errores.stock }"
                    @input="validarCampo('stock')" autocomplete="off" />
                </div>
              </div>
            </div>

            <div class="p-col-12" style="margin-top: 20px; margin-bottom: 10px;">
                <h5 style="font-weight: bold; color: #495057; border-bottom: 1px solid #dee2e6; padding-bottom: 8px;">
                    <i class="pi pi-tags"></i> Precios de Venta
                </h5>
            </div>

            <div v-for="(precio, index) in precios" :key="precio.id" class="p-grid p-ai-start p-mb-3 mobile-responsive">
              <div class="p-col-12">
                <label class="required-field">{{ precio.nombre_precio }}:</label>
              </div>

              <div class="p-col-12">
                <div class="p-grid">
                  <div class="p-col-12 p-md-6">
                    <div class="p-inputgroup" style="width: 100%;">
                      <InputNumber v-model.number="precio.valor" placeholder="Precio" mode="decimal" locale="es-ES"
                        :min="0" :useGrouping="false" :allowEmpty="true" :minFractionDigits="2" :maxFractionDigits="2"
                        class="p-inputtext-sm w-full input-number-full" @keydown.native="convertirPuntoComa"
                        @input="onPrecioChange(precio)" :class="{ 'input-error': precioError(precio) }" />
                      <span class="p-inputgroup-addon addon-precio">{{ monedaPrincipal[1] }}</span>
                    </div>
                  </div>

                  <div class="p-col-12 p-md-6">
                    <div class="p-inputgroup" style="width: 100%;">
                      
                      <InputNumber 
                        v-model.number="precio.porcentaje" 
                        mode="decimal" 
                        :min="0" 
                        :max="1000"
                        :useGrouping="false" 
                        :allowEmpty="true" 
                        :minFractionDigits="2" 
                        :maxFractionDigits="2"
                        placeholder="0.00"
                        class="p-inputtext-sm w-full input-number-full" 
                        @focus="onPorcentajeFocus(precio)"
                        @input="onPorcentajeInput(precio)"
                        @keydown.native="convertirPuntoComa" 
                      />
                      
                      <span class="p-inputgroup-addon addon-precio">%</span>
                    </div>
                  </div>
                </div>

                <div v-if="precio.errorVenta" class="p-error-precio"
                  style="display: block; margin-top: 4px; font-size: 0.85rem;">
                  ⚠️ El precio de venta es menor al costo de compra.
                </div>
              </div>
            </div>
            <div class="tag-extra">
              <i class="pi pi-cog"></i> Parámetros extras
            </div>
            <div v-show="tipoAccion == 1" class="form-group row align-items-center">
              <div class="col-md-4 switch-container d-flex align-items-center">
                <label class="required-field mr-2 mb-0" for="switchstock">Agregar a Stock</label>
                <InputSwitch id="switchstock" v-model="agregarStock" class="p-inputtext-sm" />
              </div>

              <div v-if="agregarStock" class="col-md-4">
                <label class="required-field" for="cantidadStock">Stock en Cajas <span
                    class="text-danger">*</span></label>
                <InputNumber id="cantidadStock" v-model="unidadStock" placeholder="Ej: 10"
                  class="p-inputtext-sm input-number-full" mode="decimal"
                  :class="{ 'input-error': erroresinventario.unidadStock }"
                  @input="validarCampoInventario('unidadStock')" autocomplete="off"/>
              </div>

              <div v-if="agregarStock" class="col-md-4">
                <label class="required-field" for="almacen">Almacén <span class="text-danger">*</span></label>
                <div class="p-inputgroup">
                  <InputText id="almacen" v-model="almacenSeleccionado.nombre_almacen"
                    placeholder="Seleccione un almacen" class="form-control p-inputtext-sm bold-input input-full"
                    :class="{ 'input-error': erroresinventario.AlmacenSeleccionado }" disabled />
                  <Button label="..." class="p-button-primary p-button-sm btn-sm" @click="abrirDialogos('Almacen')" />
                </div>
              </div>
            </div>

          </TabPanel>
          <TabPanel v-if="tipoAccion === 2" header="Descuento">
            <!-- fila: descuento (6) + fecha (6) -->
            <div class="form-group row align-items-center">
              <div class="col-12 col-md-6">
                <label for="descuento">Descuento (%)</label>
                <InputNumber id="descuento" ref="descuentoInput" v-model.number="datosFormulario.descuento"
                  mode="decimal" :min="0" :max="100" :minFractionDigits="2" :maxFractionDigits="2" suffix="%"
                  class="w-100" @input="validarDescuento" />
              </div>

              <div class="col-12 col-md-6">
                <label for="fecha_venc_descuento">Fecha de Vencimiento</label>
                <input type="date" id="fecha_venc_descuento" v-model="datosFormulario.fecha_venc_descuento"
                  class="form-control" :min="new Date().toISOString().split('T')[0]" placeholder="dd/mm/yyyy" />
                <small v-if="esFechaVencida" class="text-danger d-block mt-1">
                  ⚠️ El descuento ha vencido el {{ datosFormulario.fecha_venc_descuento }}.
                </small>
              </div>
            </div>

            <!-- fila: botones -->
            <div class="row g-2 mt-2">
              <div class="col-12 col-md-3">
                <Button label="Guardar" icon="pi pi-save" class="p-button-success p-button-sm w-100"
                  @click="guardarDescuento" :disabled="limpiandoDescuento ||
                    esFechaVencida ||
                    !datosFormulario.fecha_venc_descuento ||
                    datosFormulario.descuento <= 0
                    " />
              </div>

              <div class="col-12 col-md-3" v-if="datosFormulario.descuento > 0 || datosFormulario.fecha_venc_descuento">
                <Button label="Quitar Descuento" icon="pi pi-times" class="p-button-danger p-button-sm w-100"
                  @click="limpiarDescuento" />
              </div>
            </div>
          </TabPanel>


        </TabView>
      </form>
      <template #footer>
        <Button label="Cerrar" icon="pi pi-times" class="p-button-danger p-button-sm" @click="cerrarModal" />
        <Button v-if="tipoAccion == 1" class="p-button-success p-button-sm" label="Guardar" icon="pi pi-check"
          @click="enviarFormulario()" />
        <Button v-if="tipoAccion == 2" class="p-button-warning p-button-sm" label="Actualizar" icon="pi pi-check"
          @click="enviarFormulario()" />
      </template>
    </Dialog>
    <Dialog :visible.sync="dialogDetallesVisible" :modal="true" header="Detalles del Producto" :closable="false"
      @hide="cerrarDialogDetalles" :containerStyle="dialogContainerStyleDestalle" class="responsive-dialog">
      <div v-if="articuloSeleccionado" class="detalle-articulo-dialog">
        <div class="detalle-articulo-card">
          <div class="detalle-header">
            <i class="pi pi-info-circle icon-header"></i>
            <span class="detalle-titulo">{{ articuloSeleccionado.nombre }}</span>
          </div>

          <div class="detalle-body">
            <!-- Código de Barras -->
            <div class="detalle-row" style="text-align: center; margin: 10px 0;">
              <template v-if="articuloSeleccionado.codigo_alfanumerico">
                <barcode :value="articuloSeleccionado.codigo_alfanumerico"
                  :options="{ format: 'EAN-13', width: 2.5, height: 40, displayValue: true, fontSize: 12 }"></barcode>
              </template>
              <template v-else>
                <span style="font-style: italic; color: #888;">Sin código de barras</span>
              </template>
            </div>

            <!-- Descripción -->
            <div class="detalle-row">
              <span class="detalle-label">
                <i class="pi pi-align-left"></i> Descripción:
              </span>

              <span :class="mostrarDescripcion(articuloSeleccionado.descripcion).clase || 'detalle-value'">
                <i v-if="mostrarDescripcion(articuloSeleccionado.descripcion).clase === 'no-registrado'"
                  class="pi pi-exclamation-triangle"></i>

                {{ mostrarDescripcion(articuloSeleccionado.descripcion).texto }}
              </span>
            </div>

            <!-- Stock mínimo -->
            <div class="detalle-row detalle-stock">
              <span class="detalle-label"><i class="pi pi-box"></i> Stock mínimo:</span>
              <span class="badge-stock">{{ articuloSeleccionado.stock }} Cajas</span>
            </div>
            <!-- Medida Producto -->
            <div class="detalle-row detalle-stock">
              <span class="detalle-label"><i class="pi pi-box"></i> Medida de Producto:</span>
              <span class="badge-medida">{{ articuloSeleccionado.descripcion_fabrica }}</span>
            </div>
            <!-- Unidades x Caja -->
            <div class="detalle-row detalle-stock">
              <span class="detalle-label">
                <i class="pi pi-box"></i>
                {{ articuloSeleccionado.descripcion_fabrica ? articuloSeleccionado.descripcion_fabrica + ' x Caja:' :
                'Unidades x Caja:' }}
              </span> <span class="badge-uxc">{{ articuloSeleccionado.unidad_envase }}</span>
            </div>
            <!-- Descuento -->
            <div class="detalle-row mt-3" v-if="articuloSeleccionado.descuento && articuloSeleccionado.descuento > 0">
              <span class="detalle-label"><i class="pi pi-percentage"></i> Descuento:</span>
              <span class="detalle-value text-success fw-bold">
                {{ articuloSeleccionado.descuento }}%
              </span>
            </div>

            <!-- Fecha de vencimiento -->
            <div class="detalle-row" v-if="articuloSeleccionado.fecha_venc_descuento">
              <span class="detalle-label"><i class="pi pi-calendar"></i> Fecha de vencimiento del descuento:</span>
              <span class="detalle-value" :class="{
                'text-danger fw-bold': new Date(articuloSeleccionado.fecha_venc_descuento) < new Date(),
                'text-primary': new Date(articuloSeleccionado.fecha_venc_descuento) >= new Date()
              }">
                {{ new Date(articuloSeleccionado.fecha_venc_descuento).toLocaleDateString('es-BO') }}
              </span>
            </div>

            <!-- Aviso si el descuento venció -->
            <div
              v-if="articuloSeleccionado.fecha_venc_descuento && new Date(articuloSeleccionado.fecha_venc_descuento) < new Date()"
              class="alert alert-warning mt-2 p-2 text-center" style="font-size: 0.9rem;">
              ⚠️ El descuento ha vencido.
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Cerrar" icon="pi pi-times" class="p-button-danger p-button-sm btn-sm"
          @click="cerrarDialogDetalles" />
      </template>
    </Dialog>

    <!-- MODALES DINÁMICOS -->
    <DialogProveedores v-if="mostrarDialogoProveedores" :visible.sync="mostrarDialogoProveedores"
      @close="cerrarDialogos('Proveedores')" @proveedor-seleccionado="manejarProveedorSeleccionado" />
    <DialogLineas v-if="mostrarDialogoLineas" :visible.sync="mostrarDialogoLineas" @close="cerrarDialogos('Lineas')"
      @linea-seleccionado="manejarLineaSeleccionado" />
    <DialogMarcas v-if="mostrarDialogoMarcas" :visible.sync="mostrarDialogoMarcas" @close="cerrarDialogos('Marcas')"
      @marca-seleccionado="manejarMarcaSeleccionado" />
    <DialogIndustrias v-if="mostrarDialogoIndustrias" :visible.sync="mostrarDialogoIndustrias"
      @close="cerrarDialogos('Industrias')" @industria-seleccionado="manejarIndustriaSeleecionado" />
    <DialogGrupos v-if="mostrarDialogoGrupos" :visible.sync="mostrarDialogoGrupos" @close="cerrarDialogos('Grupos')"
      @grupo-seleccionado="manejarGrupoSeleccionado" />
    <DialogMedidas v-if="mostrarDialogoMedidas" :visible.sync="mostrarDialogoMedidas" @close="cerrarDialogos('Medidas')"
      @medida-seleccionado="manejarMedidaSeleccionado" />
    <DialogAlmacenes v-if="mostrarDialogoAlmacen" :visible.sync="mostrarDialogoAlmacen"
      @close="cerrarDialogos('Almacen')" @almacen-seleccionado="manejarAlmacenSeleccionado" />
    <ImportarExcelNewView v-if="mostrarDialogoImportar" :visible.sync="mostrarDialogoImportar"
      @cerrar="cerrarDialogos('Importar')" />
  </main>
</template>

<script>
import 'primeicons/primeicons.css';
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
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
import DialogProveedores from "./modales/DialogProveedores.vue";
import DialogLineas from "./modales/DialogLineas.vue";
import DialogMarcas from "./modales/DialogMarcas.vue";
import DialogIndustrias from "./modales/DialogIndustrias.vue";
import DialogGrupos from "./modales/DialogGrupos.vue";
import DialogMedidas from "./modales/DialogMedidas.vue";
import DialogAlmacenes from "./modales/DialogAlmacenes.vue";
import Dropdown from "primevue/dropdown";
import InputSwitch from "primevue/inputswitch";
import Calendar from "primevue/calendar";
import VueBarcode from "vue-barcode";
import { esquemaArticulos, esquemaInventario } from "../constants/validations";
import ImportarExcelNewView from "./productos/ImportarExcelNewView.vue";
import Swal from "sweetalert2";
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import Tooltip from 'primevue/tooltip';

export default {
  components: {
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
    ToastService,
    Toast,
    barcode: VueBarcode,
    DialogProveedores,
    DialogLineas,
    DialogMarcas,
    DialogIndustrias,
    DialogGrupos,
    DialogMedidas,
    DialogAlmacenes,
    ImportarExcelNewView,
    TabView,
    TabPanel,
  },directives: {
    'tooltip': Tooltip
  },
  data() {
    return {
      windowWidth: window.innerWidth,
      limpiandoDescuento: false,
      activeTab: 0,
      dialogDetallesVisible: false,
      mostrarLabel: true,
      articuloSeleccionado: null,
      idrol: null,
      isLoading: false,
      criterio: "nombre",
      buscar: "",
      arrayArticulo: [], // Datos del artículo
      dialogVisible: false,
      agregarStock: false,
      fechaVencimientoSeleccion: false,
      fechaVencimientoAlmacen: '2099-12-31',
      unidadStock: null,
      datosFormulario: {
        descuento: 0,
        fecha_venc_descuento: null,
        nombre: "",
        descripcion: "",
        nombre_generico: "",
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
      },
      datosFormularioInventario: {
        AlmacenSeleccionado: null,
        fechaVencimientoAlmacen: '2099-12-31',
        unidadStock: null,
      },
      errores: {},
      erroresinventario: {},
      tipo_stock: "unidades",
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
      precios: [
        { id: 1, nombre_precio: 'por Unidad',  valor: 0, porcentaje: 0, errorVenta: false }, 
        { id: 2, nombre_precio: 'por Docena',  valor: 0, porcentaje: 0, errorVenta: false }, 
        { id: 3, nombre_precio: 'por Paquete', valor: 0, porcentaje: 0, errorVenta: false }, 
        { id: 4, nombre_precio: 'Especial',    valor: 0, porcentaje: 0, errorVenta: false }, 
      ],
      precio_uno: null,
      precio_dos: null,
      precio_tres: null,
      precio_cuatro: null,
      monedaPrincipal: [],

      //CONFIGURACIONES
      mostrarSaldosStock: "",
      mostrarProveedores: "",
      mostrarCostos: "",
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
      nombre_generico: "",
      nombreProductoVacio: false,
      codigoVacio: false,
      unidad_envaseVacio: false,
      nombre_genericoVacio: false,
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
      pagination: {
        total: 0,
        current_page: 1,
        per_page: 10,
        last_page: 0,
        from: 0,
        to: 0,
      },
      offset: 3,
      headers: [
        { field: "acciones", header: "Acciones", type: "button" },
        { field: "codigo", header: "CODIGO" },
        { field: "nombre", header: "NOMBRE PRODUCTO" },
        { field: "nombre_proveedor", header: "PROVEEDOR" },
        { field: "nombre_categoria", header: "CATEGORIA" },
        { field: "precio_costo_unid", header: "COSTO COMPRA" },
        { field: "detalles", header: "Detalles", type: "button" },
      ],
    };
  },
  watch: {
    'datosFormulario.precio_costo_unid'(nuevoCosto) {
      if (isNaN(nuevoCosto) || nuevoCosto === null) return;
      this.onCostoChange();
    },
    fechaVencimientoSeleccion(val) {
      this.setFechaPorDefecto();
    }
  },
  computed: {
    esFechaVencida() {
      if (!this.datosFormulario.fecha_venc_descuento) return false;

      const hoy = new Date();
      const fechaDescuento = new Date(this.datosFormulario.fecha_venc_descuento);

      hoy.setHours(0, 0, 0, 0);
      fechaDescuento.setHours(0, 0, 0, 0);

      return fechaDescuento < hoy;
    },
    imagen() {
      return this.fotoMuestra;
    },
    paginatedItems() {
      // Obtener elementos a mostrar en la página actual
      return this.arrayArticulo.slice(
        this.pagination.from - 1,
        this.pagination.to
      );
    },
    pagesNumber() {
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

    fechaPorDefecto() {
      let defaultDate;
      if (!this.fechaVencimientoSeleccion) {
        defaultDate = new Date(2099, 11, 31); // 31/12/2099
        this.fechaVencimientoAlmacen = "2099-12-31"; // Formato YYYY-MM-DD
      } else {
        defaultDate = new Date();
        this.fechaVencimientoAlmacen = this.formatDateToYMD(defaultDate); // Formato YYYY-MM-DD
      }
      return defaultDate;
    },
    setFechaPorDefecto() {
      if (!this.fechaVencimientoSeleccion) {
        // Fecha "fija" cuando está desactivado
        this.fechaVencimientoAlmacen = "2099-12-31";
      } else {
        // Fecha actual cuando está activado
        this.fechaVencimientoAlmacen = this.formatDateToYMD(new Date());
      }
    },
    computedColumns() {
      const dynamicColumns = this.precios.map((precio, index) => ({
        field: `precio_${["uno", "dos", "tres", "cuatro"][index]}`,
        header: `PRECIO ${precio.nombre_precio}`,
        type: "dynamicPrice",
      }));
      const index =
        this.headers.findIndex(
          (header) => header.field === "precio_costo_unid"
        ) + 1;
      const result = [
        ...this.headers.slice(0, index),
        ...dynamicColumns,
        ...this.headers.slice(index),
      ];
      console.log("RESULTS COMPUTED ", index);
      return result;
    },
    dialogContainerStyle() {
      if (window.innerWidth <= 480) {
        return { width: "95vw", maxWidth: "95vw", margin: "0 auto" };
      } else if (window.innerWidth <= 768) {
        return { width: "90vw", maxWidth: "90vw", margin: "0 auto" };
      } else if (window.innerWidth <= 1024) {
        return { width: "85vw", maxWidth: "900px", margin: "0 auto" };
      } else {
        return { width: "800px", maxWidth: "90vw", margin: "0 auto" };
      }
    },
    dialogContainerStyleDestalle() {
      const width = this.windowWidth;

      if (width <= 480) {
        return { width: "95vw", maxWidth: "400px", margin: "0 auto" };
      } else if (width <= 768) {
        return { width: "85vw", maxWidth: "500px", margin: "0 auto" };
      } else if (width <= 1024) {
        return { width: "70vw", maxWidth: "600px", margin: "0 auto" };
      } else {
        return { width: "400px", maxWidth: "45vw", margin: "0 auto" };
      }
    },
  },
  methods: {
    onPrecioPaqChange(e) {
      this.datosFormulario.precio_costo_paq = e.value;

      // limpiamos o activamos error
      this.validarCampo("precio_costo_paq");
    },
    precioError(precio) {
      return (
        precio.valor === null ||
        precio.valor === undefined ||
        precio.valor === ''
      );
    },
    mostrarDescripcion(valor) {
      if (!valor || valor === "null" || valor === null) {
        return {
          texto: 'No registrado',
          clase: 'no-registrado'
        };
      }
      return { texto: valor, clase: '' };
    },
    evitarReformateo(event, callback) {
      const valor = event.target.value;

      // Si el valor está en un estado intermedio como "10," o "10,."
      if (valor.endsWith(',') || valor.endsWith(',.')) {
        return; // ← No ejecutar validaciones
      }

      // Ejecutar la validación original
      callback();
    },
    convertirPuntoComa(event) {
      if (event.key === '.') {
        event.preventDefault();
        const input = event.target;

        // Si ya tiene coma, solo mover cursor a la parte decimal
        if (input.value.includes(',')) {
          const pos = input.value.indexOf(',') + 1;
          input.setSelectionRange(pos, pos);
          return;
        }

        const start = input.selectionStart;
        const end = input.selectionEnd;

        // Insertar una sola coma SI no existe
        const nuevoValor =
          input.value.substring(0, start) + ',' + input.value.substring(end);

        input.value = nuevoValor;

        // Colocar cursor después de la coma
        input.setSelectionRange(start + 1, start + 1);

        // Actualizar modelo
        input.dispatchEvent(new Event("input"));
      }
    },

    async guardarDescuento() {
      try {
        const { descuento, fecha_venc_descuento, id } = this.datosFormulario;

        // ⚠️ VALIDACIONES (TOAST DE ADVERTENCIA)
        if (descuento <= 0) {
          this.$toast.add({
            severity: "warn",
            summary: "Atención",
            detail: "El descuento debe ser mayor a 0%.",
            life: 3000,
          });
          return;
        }

        if (!fecha_venc_descuento) {
          this.$toast.add({
            severity: "warn",
            summary: "Atención",
            detail: "Debe seleccionar una fecha de vencimiento.",
            life: 3000,
          });
          return;
        }

        const payload = { id, descuento, fecha_venc_descuento };

        await axios.put(`/articulo/actualizar-descuento/${id}`, payload);

        // 🟢 TOAST DE ÉXITO
        this.$toast.add({
          severity: "success",
          summary: "Descuento guardado",
          detail: "El descuento fue aplicado correctamente.",
          life: 2000,
        });

        // 🔄 Refrescar tabla
        this.listarArticulo(
          this.pagination.current_page,
          this.buscar || "",
          this.criterio || "nombre"
        );

      } catch (error) {
        console.error(error);

        // 🔴 TOAST DE ERROR
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudo guardar el descuento.",
          life: 3500,
        });
      }
    },

    async limpiarDescuento() {
      try {
        this.limpiandoDescuento = true;

        const payload = {
          id: this.datosFormulario.id,
          descuento: 0,
          fecha_venc_descuento: null,
        };

        await axios.put(`/articulo/actualizar-descuento/${payload.id}`, payload);

        this.datosFormulario.descuento = 0;
        this.datosFormulario.fecha_venc_descuento = null;

        // 🔵 TOAST DE ÉXITO
        this.$toast.add({
          severity: "success",
          summary: "Descuento eliminado",
          detail: "El descuento se eliminó correctamente.",
          life: 2500,
        });

        this.limpiandoDescuento = false;

        this.listarArticulo(
          this.pagination.current_page,
          this.buscar || "",
          this.criterio || "nombre"
        );

      } catch (error) {
        console.error("❌ Error al limpiar descuento:", error);
        this.limpiandoDescuento = false;

        // 🔴 TOAST DE ERROR
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudo eliminar el descuento.",
          life: 4000,
        });
      }
    },

    calcularVentasDesdeCosto() {
      if (this.datosFormulario.precio_costo_unid && this.datosFormulario.precio_costo_unid > 0) {
        const costo = parseFloat(this.datosFormulario.precio_costo_unid);

        // 🔹 Tomamos los porcentajes actuales (ya cargados)
        const venta1 = this.precios.find((p) => p.id === 1);
        const venta2 = this.precios.find((p) => p.id === 2);

        // 🔹 Calculamos los precios de venta según el porcentaje
        if (venta1) {
          venta1.valor = parseFloat((costo + (costo * venta1.porcentaje / 100)).toFixed(2));
        }
        if (venta2) {
          venta2.valor = parseFloat((costo + (costo * venta2.porcentaje / 100)).toFixed(2));
        }
      }
    },
    async cargarPreciosGlobales() {
      try {
        const response = await axios.get("/configuracion/porcentajes");
        const preciosConfig = response.data;

        const venta1 = preciosConfig.find((p) => p.nombre_precio === "VENTA 1");
        const venta2 = preciosConfig.find((p) => p.nombre_precio === "VENTA 2");
        const venta3 = preciosConfig.find((p) => p.nombre_precio === "VENTA 3"); 

        return {
          venta1: venta1 ? venta1.porcentage : 0,
          venta2: venta2 ? venta2.porcentage : 0,
          venta3: venta3 ? venta3.porcentage : 0,
        };
      } catch (error) {
        console.error("❌ Error al cargar precios globales:", error);
        return { venta1: 0, venta2: 0, venta3: 0 };
      }
    },

    calcularPrecio(porcentaje) {
      const costo = Number(this.datosFormulario.precio_costo_unid) || 0;
      const porc = Number(porcentaje);

      if (isNaN(porc) || porc < 0) return costo; // 👈 si es inválido o negativo, retorna el costo
      return parseFloat((costo + (costo * porc / 100)).toFixed(2));
    },

    calcularPorcentaje(precioVenta) {
      const costo = Number(this.datosFormulario.precio_costo_unid) || 0;
      const venta = Number(precioVenta);

      if (costo <= 0 || isNaN(venta) || venta <= costo) return 0;

      const porcentaje = ((venta - costo) / costo) * 100;
      return parseFloat(porcentaje.toFixed(2));
    },

    onPorcentajeFocus(precio, event) {
      if (!precio.porcentaje || precio.porcentaje === 0) {
         precio.porcentaje = null;
      } 
      else {
         if (event && event.target) {
            event.target.select();
         }
      }
    },

    onPorcentajeInput(precio) {
       precio.valor = this.calcularPrecio(precio.porcentaje);
       const costo = Number(this.datosFormulario.precio_costo_unid) || 0;
       precio.errorVenta = precio.valor < costo;
       this.sincronizarPrecios(precio);
    },

    onPrecioChange(precio) {
       const costo = Number(this.datosFormulario.precio_costo_unid) || 0;
       precio.porcentaje = this.calcularPorcentaje(precio.valor);
       precio.errorVenta = precio.valor < costo;
       this.sincronizarPrecios(precio);
    },

    sincronizarPrecios(precio) {
      const valorNumerico = Number(precio.valor);

      switch (precio.id) {
        case 1:
          this.precio_uno = valorNumerico;
          this.datosFormulario.precio_uno = valorNumerico; 
          break;
        case 2:
          this.precio_dos = valorNumerico;
          this.datosFormulario.precio_dos = valorNumerico;
          break;
        case 3:
          this.precio_tres = valorNumerico;
          this.datosFormulario.precio_tres = valorNumerico;
          break;
        case 4:
          this.precio_cuatro = valorNumerico;
          this.datosFormulario.precio_cuatro = valorNumerico;
          break;
      }
    },

    onCostoChange() {
      this.validarCampo('precio_costo_unid');

      const costo = parseFloat(this.datosFormulario.precio_costo_unid || 0);
      if (costo <= 0) return;

      const venta1 = this.precios.find(p => p.id === 1); // Unidad
      const venta2 = this.precios.find(p => p.id === 2); // Docena
      const venta3 = this.precios.find(p => p.id === 3); // Paquete 

      // --- 1. RECALCULAR COSTOS ---

      // Unidad
      if (venta1 && venta1.porcentaje > 0 && (!venta1.valor || venta1.valor === 0)) {
        venta1.valor = parseFloat((costo + (costo * venta1.porcentaje / 100)).toFixed(2));
      }
      // Docena
      if (venta2 && venta2.porcentaje > 0 && (!venta2.valor || venta2.valor === 0)) {
        venta2.valor = parseFloat((costo + (costo * venta2.porcentaje / 100)).toFixed(2));
      }
      // Paquete 
      if (venta3 && venta3.porcentaje > 0 && (!venta3.valor || venta3.valor === 0)) {
        venta3.valor = parseFloat((costo + (costo * venta3.porcentaje / 100)).toFixed(2));
      }

      // --- 2. RECALCULAR PORCENTAJES ---

      // Unidad
      if (venta1 && venta1.valor > 0) {
        let porc1 = ((venta1.valor - costo) / costo) * 100;
        venta1.porcentaje = parseFloat((porc1 < 0 ? 0 : porc1).toFixed(2));
        venta1.errorVenta = venta1.valor < costo;
      }

      // Docena
      if (venta2 && venta2.valor > 0) {
        let porc2 = ((venta2.valor - costo) / costo) * 100;
        venta2.porcentaje = parseFloat((porc2 < 0 ? 0 : porc2).toFixed(2));
        venta2.errorVenta = venta2.valor < costo;
      }

      // Paquete 
      if (venta3 && venta3.valor > 0) {
        let porc3 = ((venta3.valor - costo) / costo) * 100;
        venta3.porcentaje = parseFloat((porc3 < 0 ? 0 : porc3).toFixed(2));
        venta3.errorVenta = venta3.valor < costo;
      }

      console.log("✅ Costo:", costo, "| UNIDAD:", venta1.valor, "| DOCENA:", venta2.valor, "| PAQUETE:", venta3.valor);
    },
    mostrarDetalles(articulo) {
      this.articuloSeleccionado = articulo;
      this.dialogDetallesVisible = true;
    },
    cerrarDialogDetalles() {
      this.dialogDetallesVisible = false;
      this.articuloSeleccionado = null;
    },
    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
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
          this.dialogVisible = true;
          break;
        case "Lineas":
          this.mostrarDialogoLineas = false;
          this.dialogVisible = true;
          break;
        case "Marcas":
          this.mostrarDialogoMarcas = false;
          this.dialogVisible = true;
          break;
        case "Industrias":
          this.mostrarDialogoIndustrias = false;
          this.dialogVisible = true;
          break;
        case "Grupos":
          this.mostrarDialogoGrupos = false;
          this.dialogVisible = true;
          break;
        case "Medidas":
          this.mostrarDialogoMedidas = false;
          this.dialogVisible = true;
          break;
        case "Almacen":
          this.mostrarDialogoAlmacen = false;
          this.dialogVisible = true;
          break;
        case "Importar":
          this.mostrarDialogoImportar = false;
          this.listarArticulo(1, this.buscar);
          break;
      }
    },
    listarPrecio() {
      let me = this;
      var url = "/precios";
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.precios = respuesta.precio.data;
          //me.precioCount = me.arrayBuscador.length;
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    async buscarArticulo() {
      try {
        if (this.searchTimeout) {
          clearTimeout(this.searchTimeout);
        }

        this.searchTimeout = setTimeout(async () => {
          this.isLoading = true; // Activar loading
          await this.listarArticulo(1, this.buscar);
          setTimeout(() => {
            this.isLoading = false; // Desactivar loading
          }, 500);
        }, 300);
      } catch (error) {
        console.error("Error en la búsqueda:", error);
        this.isLoading = false;
      }
    },
    asignarCamposPrecios() {
      const precioUnitario = this.precios.find(p => p.id === 1);
      const precioDocena   = this.precios.find(p => p.id === 2);
      const precioPaquete  = this.precios.find(p => p.id === 3);
      const precioEspecial = this.precios.find(p => p.id === 4);

      this.datosFormulario.precio_uno = this.convertDolar(precioUnitario ? precioUnitario.valor : 0);
      this.datosFormulario.precio_dos = this.convertDolar(precioDocena ? precioDocena.valor : 0);
      
      this.datosFormulario.precio_tres = this.convertDolar(precioPaquete ? precioPaquete.valor : 0);
      
      this.datosFormulario.precio_cuatro = this.convertDolar(precioEspecial ? precioEspecial.valor : 0);
      this.datosFormulario.precio_costo_unid = this.convertDolar(this.datosFormulario.precio_costo_unid);
      
      if(!this.datosFormulario.precio_costo_paq || this.datosFormulario.precio_costo_paq == 0) {
           this.datosFormulario.precio_costo_paq = this.datosFormulario.precio_costo_unid * this.datosFormulario.unidad_envase;
      } else {
           this.datosFormulario.precio_costo_paq = this.convertDolar(this.datosFormulario.precio_costo_paq);
      }

      this.datosFormulario.precio_venta = this.datosFormulario.precio_uno; // Normalmente el precio venta base es el precio 1
      this.datosFormulario.costo_compra = this.convertDolar(this.datosFormulario.costo_compra);
    },
    asignarCamposInventario() {
      this.datosFormularioInventario.AlmacenSeleccionado = this.almacenSeleccionado.id;
      this.datosFormularioInventario.unidadStock = this.unidadStock * this.datosFormulario.unidad_envase;
      this.datosFormularioInventario.fechaVencimientoAlmacen = this.fechaVencimientoAlmacen;
    },
    convertDolar(precio) {
      return precio / parseFloat(this.monedaPrincipal);
    },
    asignarCampos() {
      this.datosFormulario.idcategoria = this.lineaSeleccionado.id;
      this.datosFormulario.idproveedor = this.proveedorSeleccionado.id;
      this.datosFormulario.idmedida = this.medidaSeleccionado ? this.medidaSeleccionado.id : 1;

      if (this.fechaVencimientoSeleccion == false) {
        this.datosFormulario.fechaVencimientoSeleccion = "0";
      } else {
        this.datosFormulario.fechaVencimientoSeleccion = "1";
      }
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
      this.asignarCamposInventario();
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
    validarDescuento() {
      this.datosFormulario.descuento = Number(this.datosFormulario.descuento);
      if (this.datosFormulario.descuento < 0) this.datosFormulario.descuento = 0;
      else if (this.datosFormulario.descuento > 100) this.datosFormulario.descuento = 100;
    },
    async enviarFormulario() {
      try {
        this.isLoading = true; // Activar loading
        this.asignarCampos();
        this.asignarCamposPrecios();

        if (this.agregarStock === true) {
          this.asignarCamposInventario();
        }

        let validacionExitosa = true;
        let validacionInventarioExitosa = true;

        try {
          await esquemaArticulos.validate(this.datosFormulario, {
            abortEarly: false,
          });
        } catch (error) {
          validacionExitosa = false;
          const erroresValidacion = {};
          error.inner.forEach((e) => {
            erroresValidacion[e.path] = e.message;
          });
          this.errores = erroresValidacion;
        }

        if (this.tipoAccion != 2 && this.agregarStock == true) {
          try {
            await esquemaInventario.validate(this.datosFormularioInventario, {
              abortEarly: false,
            });
          } catch (error) {
            validacionInventarioExitosa = false;
            const erroresValidacionInventario = {};
            error.inner.forEach((e) => {
              erroresValidacionInventario[e.path] = e.message;
            });
            this.erroresinventario = erroresValidacionInventario;
          }
        }

        if (this.tipoAccion == 2) {
          await this.actualizarArticulo(this.datosFormulario);
        } else if (validacionExitosa || validacionInventarioExitosa) {
          if (this.tipo_stock == "paquetes") {
            this.datosFormulario.stock =
              this.datosFormulario.unidad_envase * this.datosFormulario.stock;
          }
          await this.registrarArticulo(this.datosFormulario);
        }
      } catch (error) {
        console.error("Error en el envío del formulario:", error);
        this.toastError("Hubo un error al procesar el formulario");
      } finally {
        setTimeout(() => {
          this.isLoading = false; // Desactivar loading
        }, 500);
      }
    },
    obtenerConfiguracionTrabajo() {
      // Utiliza Axios para realizar la solicitud al backend
      axios
        .get("/configuracion")
        .then((response) => {
          console.log(response);
        })
        .catch((error) => {
          console.error("Error al obtener configuración de trabajo:", error);
        });
    },
    datosConfiguracion() {
      let me = this;
      var url = "/configuracion";

      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.mostrarSaldosStock =
            respuesta.configuracionTrabajo.mostrarSaldosStock;
          me.mostrarCostos = respuesta.configuracionTrabajo.mostrarCostos;
          me.mostrarProveedores =
            respuesta.configuracionTrabajo.mostrarProveedores;

          me.monedaPrincipal = [
            respuesta.configuracionTrabajo.valor_moneda_principal,
            respuesta.configuracionTrabajo.simbolo_moneda_principal,
          ];
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    calculatePages: function (paginationObject, offset) {
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
    listarArticulo(page, buscar, criterio) {
      let me = this;
      var url =
        "/articulo?page=" +
        page +
        "&buscar=" +
        buscar +
        "&criterio=" +
        criterio;
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arrayArticulo = respuesta.articulos.data;
          me.pagination = respuesta.pagination;
          me.idrol = respuesta.idrol; // 👈 guardar idrol
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    cambiarPagina(page, buscar, criterio) {
      // Actualiza la página actual
      this.pagination.current_page = page;
      // Envía la petición para visualizar la data de esa página
      this.listarArticulo(page, buscar, criterio);
    },
    onPageChange(event) {
      const page = Math.floor(event.first / this.pagination.per_page) + 1;
      this.cambiarPagina(page, this.buscar, this.criterio);
    },
    async descargarArchivoReporte(url, nombreArchivo) {
      try {
        Swal.fire({
          title: 'Generando reporte...',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        const response = await axios.get(url, {
          responseType: 'blob'
        });

        const blob = new Blob([response.data]);
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = nombreArchivo;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        Swal.close();
      } catch (error) {
        Swal.close();
        Swal.fire('ERROR AL GENERAR EL REPORTE', '', 'error');
      }
    },
    async descargarReporteExcel() {
      await this.descargarArchivoReporte('/articulo/reporteExcel', 'articulos.xlsx');
    },
    cambiarPagina(page, buscar, criterio) {
      let me = this;
      me.pagination.current_page = page;
      me.listarArticulo(page, buscar, criterio);
    },
    calcularPrecioValorMoneda(precio) {
      const tasa = Array.isArray(this.monedaPrincipal)
        ? parseFloat(this.monedaPrincipal[0]) || 1
        : parseFloat(this.monedaPrincipal) || 1;

      const valor = parseFloat(precio) || 0;
      return Number((valor * tasa).toFixed(2));
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
        .post("/articulo/registrar", formulario, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })
        .then(function (response) {
          var respuesta = response.data;
          me.idarticulo = respuesta.idArticulo;

          // 🔵 Toast de éxito
          me.$toast.add({
            severity: "success",
            summary: "Registrado",
            detail: "El artículo fue registrado correctamente.",
            life: 2500,
          });

          me.cerrarModal();
          me.listarArticulo(1, me.buscar);

          // Verificar si se debe registrar inventario
          if (me.agregarStock === true) {
            let arrayArticulos = [
              {
                idarticulo: me.idarticulo,
                idalmacen: me.almacenSeleccionado.id,
                cantidad: me.unidadStock,
                fecha_vencimiento: me.fechaVencimientoAlmacen,
              },
            ];

            return axios.post("/inventarios/registrar", {
              inventarios: arrayArticulos,
            });
          }
        })
        .then(function (response) {
          if (response) {
            console.log("Inventario registrado:", response.data);
          }
        })
        .catch(function (error) {

          // 🔴 Error específico 409 con mensaje desde servidor
          if (error.response && error.response.status === 409 && error.response.data && error.response.data.message) {
            me.$toast.add({
              severity: "error",
              summary: "Error",
              detail: error.response.data.message,
              life: 3000,
            });
          }
          else {
            // 🔴 Error general
            me.$toast.add({
              severity: "error",
              summary: "Error",
              detail: "Hubo un error al registrar el artículo o el inventario.",
              life: 3000,
            });
          }

          console.log(error);
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
        .post("/articulo/actualizar", formulario, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })
        .then(function (response) {
          console.log("respuesta = ", response.data);

          // 🔵 Toast de éxito
          me.$toast.add({
            severity: "success",
            summary: "Actualizado",
            detail: "El artículo fue actualizado correctamente.",
            life: 2500,
          });

          me.cerrarModal();
          me.listarArticulo(1, me.buscar);
        })
        .catch(function (error) {
          console.log(error);

          // 🔴 Toast de error
          me.$toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudo actualizar el artículo.",
            life: 3000,
          });
        });
    },

    async desactivarArticulo(id) {
      try {
        const result = await Swal.fire({
          title: "¿Está seguro de ELIMINAR este artículo?",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#22c55e",
          cancelButtonColor: "#ef4444",
          confirmButtonText: "Aceptar!",
          cancelButtonText: "Cancelar",
          reverseButtons: true,
          customClass: {
            confirmButton: "swal2-confirm-articulonew",
            cancelButton: "swal2-cancel-articulonew",
          },
        });

        if (result.value) {
          this.isLoading = true;

          await axios.put("/articulo/desactivar", { id: id });
          await this.listarArticulo(1, this.buscar);

          // 🔵 TOAST DE ÉXITO
          this.$toast.add({
            severity: "success",
            summary: "Eliminado",
            detail: "El artículo fue eliminado correctamente.",
            life: 2500,
          });
        }

      } catch (error) {
        console.error("Error al desactivar:", error);

        // 🔴 TOAST DE ERROR
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudo eliminar el artículo.",
          life: 3500,
        });

      } finally {
        this.isLoading = false;
      }
    },

    activarArticulo(id) {
      Swal.fire({
        title: "¿Está seguro de activar este artículo?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#22c55e",
        cancelButtonColor: "#ef4444",
        confirmButtonText: "Aceptar!",
        cancelButtonText: "Cancelar",
        reverseButtons: true,
        customClass: {
          confirmButton: "swal2-confirm-articulonew",
          cancelButton: "swal2-cancel-articulonew",
        },
      }).then((result) => {
        if (result.value) {
          let me = this;

          axios
            .put("/articulo/activar", {
              id: id,
            })
            .then(function (response) {
              me.listarArticulo(1, me.buscar);
              me.toastSuccess("El registro ha sido activado con éxito.");
            })
            .catch(function (error) {
              console.log(error);
            });
        } else if (
          // Read more about handling dismissals
          result.dismiss === Swal.DismissReason.cancel
        ) {
        }
      });
    },
    validarArticulo() {
      this.errorArticulo = 0;
      this.errorMostrarMsjArticulo = [];
      if (!this.unidad_envase) this.errorMostrarMsjArticulo.push("");
      if (!this.codigo) this.errorMostrarMsjArticulo.push("");
      if (!this.nombre_producto) this.errorMostrarMsjArticulo.push("");
      if (!this.nombre_generico) this.errorMostrarMsjArticulo.push("");
      if (!this.precio_costo_unid) this.errorMostrarMsjArticulo.push("");
      if (!this.precio_costo_paq) this.errorMostrarMsjArticulo.push("");
      if (!this.descripcion) this.errorMostrarMsjArticulo.push("");
      if (!this.stock) this.errorMostrarMsjArticulo.push("");
      if (!this.precio_venta) this.errorMostrarMsjArticulo.push("");
      if (!this.costo_compra) this.errorMostrarMsjArticulo.push("");
      if (!this.fotografia) this.errorMostrarMsjArticulo.push("");

      if (this.errorMostrarMsjArticulo.length) this.errorArticulo = 1;

      return this.errorArticulo;
    },
    cerrarModal() {
      this.dialogVisible = false;
      this.tituloModal = "";
      this.codigo = "";
      this.nombre_producto = "";
      this.nombre_generico = "";
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
      this.descuento = 0;
      this.fecha_venc_descuento = null;
    },
    async abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case "articulo": {
          switch (accion) {
            case "registrar": {
              this.cerrarModal();

              this.$nextTick(async () => {
                this.dialogVisible = true;
                this.tituloModal = "Registrar Artículo";
                this.agregarStock = false;
                this.tipoAccion = 1;
                this.fotografia = "";

                const preciosGlobales = await this.cargarPreciosGlobales();

                this.precios = [
  {
    id: 1,
    nombre_precio: "por Unidad",
    valor: 0,
    porcentaje: parseFloat(preciosGlobales.venta1) || 0, // 👈 Forzamos a Numero
    errorVenta: false,
  },
  {
    id: 2,
    nombre_precio: "por Docena",
    valor: 0,
    porcentaje: parseFloat(preciosGlobales.venta2) || 0, // 👈 Forzamos a Numero
    errorVenta: false,
  },
  {
    id: 3,
    nombre_precio: "por Paquete",
    valor: 0,
    porcentaje: parseFloat(preciosGlobales.venta3) || 0, // 👈 Forzamos a Numero
    errorVenta: false,
  },
];

                this.datosFormulario = {
                  nombre: "",
                  descripcion: "",
                  nombre_generico: "",
                  unidad_envase: null,
                  precio_costo_unid: null,
                  precio_costo_paq: null,
                  precio_venta: null,
                  precio_uno: null,
                  precio_dos: null,
                  precio_tres: null,
                  precio_cuatro: null,
                  stock: null,
                  costo_compra: null,
                  codigo: "",
                  codigo_alfanumerico: "",
                  descripcion_fabrica: "",
                  idcategoria: null,
                  idmarca: null,
                  idindustria: null,
                  idgrupo: null,
                  idproveedor: null,
                  idmedida: null,
                };

                this.errores = {};
                this.fechaVencimientoSeleccion = false;
              });

              break;
            }
            case "actualizar": {
              this.cerrarModal();

              // ✅ Recargamos datos frescos desde backend
              axios.get(`/articulo/detalle/${data.id}`).then((response) => {
                const articulo = response.data;

                this.$nextTick(() => {
                  console.log("DATA ACTUALIZAR (refrescado)", articulo);
                  this.agregarStock = false;
                  this.dialogVisible = true;
                  this.tituloModal = "Actualizar Artículo";
                  this.tipoAccion = 2;

                  this.datosFormulario = {
                    nombre: articulo.nombre,
                    descripcion: articulo.descripcion,
                    nombre_generico: articulo.nombre_generico,
                    unidad_envase: articulo.unidad_envase,
                    descuento: articulo.descuento || 0,
                    fecha_venc_descuento: articulo.fecha_venc_descuento
                      ? articulo.fecha_venc_descuento.split("T")[0]
                      : null,

                    precio_costo_unid: this.calcularPrecioValorMoneda(articulo.precio_costo_unid),
                    precio_costo_paq: this.calcularPrecioValorMoneda(articulo.precio_costo_paq),
                    precio_venta: this.calcularPrecioValorMoneda(articulo.precio_venta),
                    precio_uno: 0, 
                    precio_dos: 0,
                    precio_tres: 0, 
                    precio_cuatro: 0,
                    stock:
                      this.tipo_stock == "paquetes" && articulo.unidad_envase > 0
                        ? articulo.stock / articulo.unidad_envase
                        : articulo.stock,
                    costo_compra: this.calcularPrecioValorMoneda(articulo.costo_compra),
                    codigo: articulo.codigo,
                    codigo_alfanumerico: articulo.codigo_alfanumerico || "",
                    descripcion_fabrica: articulo.descripcion_fabrica || "",
                    idcategoria: null,
                    idmarca: null,
                    idindustria: null,
                    idgrupo: null,
                    idproveedor: null,
                    idmedida: articulo.idmedida,
                    id: articulo.id,
                  };

                  this.precio_uno = Number(this.calcularPrecioValorMoneda(articulo.precio_uno)) || 0;
                  this.precio_dos = Number(this.calcularPrecioValorMoneda(articulo.precio_dos)) || 0;
                  this.precio_tres = Number(this.calcularPrecioValorMoneda(articulo.precio_tres)) || 0;
                  this.precio_cuatro = Number(this.calcularPrecioValorMoneda(articulo.precio_cuatro)) || 0;

                  this.errores = {};
                  this.idmedida = articulo.idmedida;
                  this.fotografia = articulo.fotografia;
                  this.fotoMuestra = articulo.fotografia
                    ? "img/articulo/" + articulo.fotografia
                    : null;

                  // Asignación de objetos seleccionados (Selects)
                  this.industriaSeleccionado = { nombre: articulo.nombre_industria, id: articulo.idindustria };
                  this.lineaSeleccionado = { nombre: articulo.nombre_categoria, id: articulo.idcategoria };
                  this.marcaSeleccionado = { nombre: articulo.nombre_marca, id: articulo.idmarca };
                  this.proveedorSeleccionado = { nombre: articulo.nombre_proveedor, id: articulo.idproveedor };
                  this.grupoSeleccionado = { nombre_grupo: articulo.nombre_grupo, id: articulo.idgrupo };
                  this.medidaSeleccionado = { descripcion_medida: articulo.descripcion_medida, id: articulo.idmedida };

                  this.precios = [
                    {
                      id: 1,
                      nombre_precio: "por Unidad",
                      valor: this.precio_uno,
                      porcentaje: this.calcularPorcentaje(this.precio_uno),
                      errorVenta: false,
                    },
                    {
                      id: 2,
                      nombre_precio: "por Docena",
                      valor: this.precio_dos,
                      porcentaje: this.calcularPorcentaje(this.precio_dos),
                      errorVenta: false,
                    },
                    {
                      id: 3,
                      nombre_precio: "por Paquete",
                      valor: this.precio_tres,
                      porcentaje: this.calcularPorcentaje(this.precio_tres),
                      errorVenta: false,
                    }
                  ];

                  if (this.precio_cuatro > 0) {
                    this.precios.push({
                      id: 4,
                      nombre_precio: "Especial",
                      valor: this.precio_cuatro,
                      porcentaje: this.calcularPorcentaje(this.precio_cuatro),
                      errorVenta: false,
                    });
                  }

                  this.$forceUpdate();
                  this.fechaVencimientoSeleccion = articulo.vencimiento === 1;
                });
              });

              break;
            }
            case "registrarInd": {
              this.modal = 1;
              this.tituloModal = "Registrar Industria";
              this.nombre = "";
              this.tipoAccion = 3;
              break;
            }
          }
        }
      }
    },
    calcularPrecio(porcentaje) {
      const costo = Number(this.datosFormulario.precio_costo_unid) || 0;
      const porc = Number(porcentaje);
      if (isNaN(porc) || porc < 0) return costo;

      const precioVenta = costo + (costo * porc / 100);
      return parseFloat(precioVenta.toFixed(2));
    },
  },

  async mounted() {
    window.addEventListener("resize", this.onResize);
    this.handleResize();
    window.addEventListener("resize", this.handleResize);
    try {
      await Promise.all([
        this.datosConfiguracion(),
        this.listarArticulo(1, this.buscar),
        this.listarPrecio(),
      ]);

      let today = new Date();
      let tomorrow = new Date(today);
      tomorrow.setDate(today.getDate() + 1);
      this.minDate = tomorrow;
    } catch (error) {
      console.error("Error en la carga inicial:", error);
    }
  },
  beforeUnmount() {
    window.removeEventListener("resize", this.handleResize);
    window.removeEventListener("resize", this.onResize);
  },
};
</script>

<style scoped>
.tag-extra {
  display: inline-block;
  background: #e0f2ff;
  color: #0369a1;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 10px;
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

.input-date-full {
  width: 100%;
  padding: 6px 8px;
  font-size: 0.85rem;
  border-radius: 6px;
  border: 1px solid #ced4da;
  box-sizing: border-box;
}

.input-date-full:focus {
  border-color: #6c9ffe;
  outline: none;
}

/* 🔹 Addon al mismo tamaño del input */
.addon-precio {
  font-size: 0.8rem;
  /* igual al input */
  padding: 6px 8px;
  /* igual al input */
  border-radius: 0 6px 6px 0;
  /* borde derecho redondeado */
  height: 100%;
  /* igualar altura */
  display: flex;
  align-items: center;
  /* centrar verticalmente */
  box-sizing: border-box;
}

/* Asegurar que el contenedor no se rompa */
.p-inputgroup-addon {
  min-width: 40px;
  /* opcional, para que no se comprima demasiado */
}

.p-inputgroup-addon {
  padding: 0 !important;
  /* eliminamos padding por defecto */
}

.addon-precio {
  padding: 3px 12px !important;
  /* igual al input interno */
  height: 33px;
  /* si tu input termina midiendo 38px (común en PrimeVue) */
}

.tabla-pro {
  width: 100%;
  white-space: nowrap;
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
  gap: 0.4rem;
  font-weight: 500;
  color: #6c757d;
}

.optional-icon {
  color: #17a2b8;
  font-size: 0.7rem;
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

>>>.p-datatable.p-datatable-gridlines .p-datatable-tbody>tr>td {
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
  padding: 0.5rem;
}

.responsive-dialog>>>.p-dialog-header {
  padding: 0.5rem 0.9rem;
  font-size: 1.1rem;
}

.responsive-dialog>>>.p-dialog-footer {
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

>>>.p-dropdown .p-dropdown-trigger {
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

/* Tablet Styles */
@media (max-width: 1024px) {
  .responsive-dialog>>>.p-dialog {
    margin: 0.5rem;
    max-height: 90vh;
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
    max-height: 90vh;
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
}

/* Extra Small Mobile */
@media (max-width: 480px) {
  .toolbar .p-button .p-button-label {
    display: none;
  }

  .responsive-dialog>>>.p-dialog {
    margin: 0.1rem;
    max-height: 90vh;
  }

  .responsive-dialog>>>.p-dialog-content {
    padding: 0.5rem;
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.5rem 0.75rem;
    font-size: 0.95rem;
  }

  /* Footer mantiene botones alineados a la derecha, no ocupan todo el ancho */
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

.p-dialog-mask {
  z-index: 9990 !important;
}

.p-dialog {
  z-index: 9990 !important;
}

.swal-zindex {
  z-index: 9995 !important;
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

.detalle-value {
  color: #1e293b;
  font-weight: 500;
  word-break: break-word;
}

.detalle-dialog .p-dialog {
  width: 450px !important;
  max-width: 450px;
  height: 580px !important;
  max-height: 580px;
  border-radius: 10px;
}

.detalle-dialog .p-dialog-content {
  height: calc(100% - 60px);
  overflow-y: auto;
}

.detalle-articulo-dialog {
  height: 100%;
  overflow: hidden;
}

.detalle-articulo-card {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 10px 20px;
}

.detalle-header {
  display: flex;
  align-items: center;
  gap: 8px;
  border-bottom: 1px solid #ddd;
  padding-bottom: 5px;
  margin-bottom: 10px;
}

.icon-header {
  font-size: 1.3rem;
  color: #007ad9;
}

.detalle-titulo {
  font-weight: bold;
  font-size: 1.2rem;
}

.detalle-body {
  flex: 1;
  overflow-y: auto;
  padding-right: 10px;
}

.detalle-row {
  margin: 10px 0;
}

/* --- STOCK MÍNIMO --- */
.detalle-stock {
  display: flex;
  align-items: center;
  gap: 8px;
}

.detalle-label {
  font-weight: bold;
  color: #555;
}

.footer-center {
  display: flex;
  justify-content: center;
  padding-top: 10px;
}

.p-error-precio {
  color: #ddc239;
  /* rojo elegante */
  display: block;
  margin-top: 4px;
  font-size: 0.85rem;
}

.modern-date {
  border: 1px solid #0d6efd;
  border-radius: 0.6rem;
  padding: 8px 12px;
  font-size: 0.95rem;
  color: #212529;
  background-color: #f8faff;
  transition: all 0.25s ease;
  box-shadow: 0 1px 3px rgba(13, 110, 253, 0.15);
}

/* Efecto al enfocar */
.modern-date:focus {
  border-color: #0b5ed7;
  outline: none;
  box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.25);
  background-color: #fff;
}

/* 🔹 Ícono del calendario (Chrome, Edge, Safari) */
.modern-date::-webkit-calendar-picker-indicator {
  color: #0d6efd;
  background: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%230d6efd' viewBox='0 0 24 24'%3E%3Cpath d='M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2zm0 16H5V9h14v11zM7 11h5v5H7v-5z'/%3E%3C/svg%3E") no-repeat center;
  background-size: 1rem;
  opacity: 0.7;
  cursor: pointer;
  transition: 0.3s ease;
}

.modern-date::-webkit-calendar-picker-indicator:hover {
  opacity: 1;
  transform: scale(1.1);
}

/* 🔹 Borde rojo si hay error (por ejemplo, con clase .is-invalid) */
.modern-date.is-invalid {
  border-color: #dc3545 !important;
  box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, 0.25);
}

.text-danger-fecha {
  color: #dc3545;
  font-weight: 500;
}

.modern-date {
  border-radius: 8px !important;
  border: 1px solid #d1d5db !important;
  transition: 0.2s;
}

.modern-date:focus {
  border-color: #0d6efd !important;
  box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.25);
}

.detalle-label {
  font-weight: 600;
  color: #555;
  margin-right: 6px;
}

.detalle-value {
  color: #222;
}

.badge-stock {
  background: #e0f3ff;
  color: #0d6efd;
  font-weight: 600;
  border-radius: 50px;
  padding: 4px 10px;
  font-size: 0.85rem;
  display: inline-block;
}

.badge-medida {
  background: #f8f4c3;
  color: #fdb10d;
  font-weight: 600;
  border-radius: 50px;
  padding: 4px 10px;
  font-size: 0.85rem;
  display: inline-block;
}

.badge-uxc {
  background: #ffe0fa;
  color: #fd0df1;
  font-weight: 600;
  border-radius: 50px;
  padding: 4px 10px;
  font-size: 0.85rem;
  display: inline-block;
}

.alert-warning {
  background-color: #fff3cd;
  color: #856404;
  border-radius: 8px;
  border: 1px solid #ffeeba;
}

.codigo-descuento {
  background-color: #53d070 !important;
  color: #fff !important;
  padding: 4px 8px;
  border-radius: 8px;
  font-weight: 600;
  display: inline-block;
  /* evita deformaciones */
  white-space: nowrap;
  /* impide que el texto salte de línea */
  text-overflow: ellipsis;
  /* corta con "..." si es muy largo */
  overflow: hidden;
  /* oculta el exceso de texto */
  max-width: 110px;
  /* ajusta el ancho visible */
  text-align: center;
  /* centra el contenido */
  vertical-align: middle;
}

.codigo-descuento:hover {
  background-color: #40b75e !important;
}

.codigo-descuento[title] {
  cursor: pointer;
}
</style>
