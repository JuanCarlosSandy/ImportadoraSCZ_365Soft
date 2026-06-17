<template>
  <main class="main">
    <div class="loading-overlay" v-if="isLoading">
      <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">LOADING...</div>
      </div>
    </div>
    <Toast :breakpoints="{ '920px': { width: '100%', right: '0', left: '0' } }" style="padding-top: 10px;"
      appendTo="body" :baseZIndex="99999"></Toast>
    <Panel v-if="vistaActual === 'formulario'">
      <template #header>
        <div class="panel-header">
          <i class="pi pi-list mr-2"></i>
          <h4 class="panel-title">CONTROL DE INVENTARIO</h4>
        </div>
      </template>

      <form @submit.prevent="enviarFormulario">
        <!-- PARTE 1: ALMACÉN Y PROVEEDOR -->
        <div class="row mt-3">
          <div class="col-md-6">
            <div class="form-group">
              <label class="font-weight-bold" for="almacen">
                Almacén <span class="text-danger">*</span>
              </label>
              <Dropdown id="almacen" v-model="idAlmacenSeleccionado" :options="arrayAlmacenes"
                optionLabel="nombre_almacen" optionValue="id" placeholder="Seleccione un almacén" class="dropdown-full"
                @change="limpiarProductosSeleccionados" />
            </div>
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <Button label="Agregar Producto" icon="pi pi-plus" class="p-button-success w-100 btn-sm-input"
              @click="abrirDialogoProductos" :disabled="!idAlmacenSeleccionado" />
          </div>
          <!--<div class="col-md-6">
            <div class="form-group">
              <label class="font-weight-bold">Proveedor</label>
              <div class="input-con-desplegable">
                <div class="p-inputgroup">
                  <input type="text" v-model="proveedorSeleccionado.nombre" @input="buscarProveedores($event)"
                    @keydown.down="moverSeleccionProveedor('abajo')" @keydown.up="moverSeleccionProveedor('arriba')"
                    @keydown.enter="seleccionarProveedorConEnter" placeholder="Buscar proveedor..."
                    class="p-inputtext p-component input-full" />
                  <Button v-if="proveedorSeleccionado.nombre" icon="pi pi-times" class="p-button-danger p-button-sm"
                    @click="limpiarProveedorSeleccionado" style="margin-left: 5px;" />
                </div>
                <ul v-if="mostrarDesplegableProveedor && proveedoresFiltrados.length > 0" class="desplegable-simple">
                  <li v-for="(proveedor, index) in proveedoresFiltrados" :key="proveedor.id"
                    @click="seleccionarProveedor(proveedor)"
                    :class="{ seleccionado: index === indiceSeleccionadoProveedor }">
                    {{ proveedor.nombre }}
                  </li>
                </ul>
              </div>
            </div>
          </div>-->
        </div>

        <!-- Tabla de Productos Seleccionados -->
        <div class="row mt-3">
          <div class="col-md-12">
            <label class="font-weight-bold">Productos Seleccionados para Ajuste</label>
            <DataTable :value="productosSeleccionados" class="p-datatable-sm p-datatable-gridlines tabla-pro"
              responsiveLayout="scroll">

              <Column field="codigo" header="Codigo">
                <template #body="slotProps">
                  <div>
                    {{ slotProps.data.codigo }}
                  </div>
                </template>
              </Column>

              <Column field="nombre" header="Producto">
                <template #body="slotProps">
                  <div>
                    {{ slotProps.data.nombre }}
                  </div>
                </template>
              </Column>

              <Column field="nombre_proveedor" header="Proveedor">
                <template #body="slotProps">
                  <div>
                    {{ slotProps.data.nombre_proveedor || 'Sin proveedor' }}
                  </div>
                </template>
              </Column>

              <Column header="Stock Real">
                <template #body="slotProps">
                  <InputText type="number" v-model="slotProps.data.stock_real"
                    class="form-control text-center font-weight-bold input-full" placeholder="0" :min="0"
                    @input="calcularDiferencia(slotProps.data)"
                    @keydown.tab.prevent="moverFoco(slotProps.index, $event, 'stock_real')"
                    :ref="'stock_real-' + slotProps.index" />
                </template>
              </Column>

              <Column header="Acciones">
                <template #body="slotProps">
                  <Button icon="pi pi-trash" class="p-button-danger p-button-sm btn-mini"
                    @click="eliminarProducto(slotProps.index)" title="Eliminar producto" />
                </template>
              </Column>
            </DataTable>

            <!-- Resumen de Totales -->
            <div class="mt-3 p-3" style="background-color: #f8f9fa; border-radius: 5px;">
              <div class="row" style="display: flex; justify-content: space-between;">
                <div class="col-md-4">
                  <strong><i class="pi pi-shopping-cart"></i> Productos: {{ productosSeleccionados.length }}</strong>
                </div>
                <div class="col-md-4">
                  <strong><i class="pi pi-box"></i> Total Unidades: {{ totalUnidadesAjuste }}</strong>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Botones de Acción -->
        <div class="row mt-3">

          <!-- Izquierda (antes estaba el PDF) -->
          <div class="col-md-6 d-flex justify-content-start">
            <!-- Aquí ya no va nada -->
          </div>

          <!-- Derecha (cancelar y procesar) -->
          <div class="col-md-6 d-flex justify-content-end">
            <Button label="Cancelar" icon="pi pi-times" class="p-button-danger mr-2 btn-sm"
              @click="confirmarCancelar" />

            <Button label="Guardar Control" icon="pi pi-check" class="p-button-success btn-sm" @click="enviarFormulario"
              :disabled="!puedeEnviarFormulario()" :loading="isLoading" />
          </div>

        </div>

      </form>
    </Panel>

    <Panel v-if="vistaActual === 'tabla'">
      <template #header>
        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="pi pi-file-o panel-icon"></i>
            <h4 class="panel-title" style="margin: 0;">CONTROL DE INVENTARIO</h4>
          </div>
        </div>
      </template>
      <div class="info-tip">
          <i class="pi pi-info-circle"></i>
          <span>
            Filtre los registros por un rango de fechas y tambien seleccionando el almacen correspondiente.
          </span>
        </div>
      <div class="mt-3">
        <div class="toolbar-container" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;">

          <div style="flex: 1 1 150px;">
            <label class="label-fecha" style="font-size: 11px; font-weight: bold; display: block; margin-bottom: 4px;">
              Desde
            </label>
            <input type="date" v-model="fechaInicio" class="p-inputtext p-component p-inputtext-sm input-date-full"
              style="width: 100%;" @change="listarControles()" />
          </div>

          <div style="flex: 1 1 150px;">
            <label class="label-fecha" style="font-size: 11px; font-weight: bold; display: block; margin-bottom: 4px;">
              Hasta
            </label>
            <input type="date" v-model="fechaFin" class="p-inputtext p-component p-inputtext-sm input-date-full"
              style="width: 100%;" @change="listarControles()" />
          </div>

          <div style="flex: 1 1 180px;">
            <label class="label-fecha"
              style="font-size: 11px; font-weight: bold; display: block; margin-bottom: 4px;">Almacén</label>
            <Dropdown v-model="idAlmacen" :options="arrayAlmacenes" optionLabel="nombre_almacen" optionValue="id"
              placeholder="Todos" showClear class="dropdown-full" style="width: 100%;" @change="listarControles()"/>
          </div>

          <div class="toolbar" style="padding-bottom: 2px;">
            <Button :label="mostrarLabel ? 'Limpiar' : ''" icon="pi pi-refresh" @click="resetFiltros"
              class="p-button-warning p-button-sm btn-sm-input" title="Restablecer filtros" />
            <Button v-if="rolUsuario == 4" label="Llaves de Acceso" icon="pi pi-key"
              class="p-button-primary p-button-sm btn-sm-input" @click="abrirDialogLlaves" />
            <Button label="Nuevo Ajuste" icon="pi pi-plus" class="p-button-secondary p-button-sm btn-sm-input"
              @click="validarAcceso" />
          </div>

        </div>
        <DataTable :value="arrayDatosControl" class="p-datatable-sm p-datatable-gridlines tabla-pro"
          responsiveLayout="scroll">

          <!-- 🔘 ACCIONES -->
          <Column header="ACCIONES" style="width: 120px; text-align: center;">
            <template #body="slotProps">
              <Button icon="pi pi-eye" class="p-button-info btn-mini"
                @click="verDetalleControl(slotProps.data.id)" v-tooltip.top="'Ver detalle'" />
              <Button icon="pi pi-file-pdf" class="p-button-danger btn-mini"
                @click="descargarPdf(slotProps.data.id)" v-tooltip.top="'Descargar PDF'" />
              <Button icon="pi pi-file-excel" class="p-button-success btn-mini"
                @click="descargarExcel(slotProps.data.id)" v-tooltip.top="'Descargar Excel'" />
            </template>
          </Column>

          <!-- 🏬 ALMACÉN -->
          <Column header="ALMACÉN">
            <template #body="slotProps">
              {{ slotProps.data.almacen ? slotProps.data.almacen.nombre_almacen : '' }}
            </template>
          </Column>

          <!-- 👤 RESPONSABLE -->
          <Column header="RESPONSABLE">
            <template #body="slotProps">
              {{ slotProps.data.usuario ? slotProps.data.usuario.usuario : '' }}
            </template>
          </Column>

          <!-- 📅 FECHA -->
          <Column header="FECHA">
            <template #body="slotProps">
              {{ formatearFecha(slotProps.data.fechahora) }}
            </template>
          </Column>

          <!-- 🔄 ESTADO -->
          <Column header="ESTADO" style="text-align: center;">
            <template #body="slotProps">
              <span :class="getEstadoClaseGeneral(slotProps.data.estado)" style="padding: 5px 10px;">
                {{ getEstadoTextoGeneral(slotProps.data.estado) }}
              </span>
            </template>
          </Column>

        </DataTable>

        <Paginator :rows="pagination.per_page" :totalRecords="pagination.total"
          :first="(pagination.current_page - 1) * pagination.per_page" @page="onPageChange" />
      </div>
    </Panel>

    <Sidebar :visible.sync="dialogoProductosVisible" position="right" header="Agregar Productos" :style="{
      width: '90vw',
      maxWidth: '800px',
      marginTop: '78px',
      zIndex: 1050    // 👈 aquí lo aumentamos
    }" appendTo="body" @show="onSidebarShow" @hide="limpiarBusqueda">
      <div class="search-and-buttons p-mb-3 p-d-flex p-flex-column p-flex-md-row p-gap-2">
        <div class="search-bar p-flex-grow-1">
          <span class="p-input-icon-left p-w-full">
            <i class="pi pi-search" />
            <InputText ref="inputBusqueda" v-model="buscarA" class="form-control p-w-full input-full"
              placeholder="Texto a buscar" @keyup="filtrarProductos" />
          </span>
        </div>
        <div class="p-d-flex p-gap-2">
          <Button label="Reset" icon="pi pi-refresh" @click="resetBusquedaProductos"
            class="p-button-help p-button-sm btn-sm" title="Limpiar" :disabled="!buscarA" />
        </div>
      </div>

      <!-- Tabla de productos -->
      <div class="table-responsive" style="margin-top: 2%">
        <DataTable :value="arrayBuscador" class="p-datatable-sm p-datatable-gridlines" responsiveLayout="scroll">
          <!-- Columnas de la tabla (sin cambios) -->
          <Column header="Seleccionar" style="width: 12%">
            <template #body="slotProps">
              <Button icon="pi pi-plus" class="p-button-primary p-button-sm btn-mini "
                @click="seleccionarProducto(slotProps.data)"
                :disabled="productosSeleccionados.some(p => p.id === slotProps.data.id)"
                :title="productosSeleccionados.some(p => p.id === slotProps.data.id) ? 'Ya seleccionado' : 'Agregar producto'" />
            </template>
          </Column>

          <Column field="codigo" header="Código">
            <template #body="slotProps">
              <div>
                {{ slotProps.data.codigo }}
              </div>
            </template>
          </Column>

          <Column field="nombre" header="Producto">
            <template #body="slotProps">
              <div>
                <strong>{{ slotProps.data.nombre }}</strong>
              </div>
            </template>
          </Column>

          <!-- NUEVA COLUMNA -->
          <Column header="Stock Real">
            <template #body="slotProps">

              <!-- INPUT -->
              <InputText v-if="!productosSeleccionados.some(p => p.id === slotProps.data.id)" type="number"
                v-model="slotProps.data.stock_ingresado" class="form-control text-center input-full" placeholder="0"
                min="0" />

              <!-- TAG -->
              <Tag v-else severity="success" value="Ya agregado" class="tag-mini" />

            </template>
          </Column>
        </DataTable>
      </div>

      <!-- Paginador -->
      <Paginator :rows="pagination.per_page" :totalRecords="pagination.total"
        :first="(pagination.current_page - 1) * pagination.per_page" @page="(event) => {
          const page = Math.floor(event.first / event.rows) + 1;
          listarProducto(page, buscarA, criterioA, idAlmacenSeleccionado, false, proveedorSeleccionado ? proveedorSeleccionado.id : null);
        }" class="p-mt-3" />
    </Sidebar>

    <Dialog :visible.sync="dialogoEscaneoVisible" modal header="Escaneo de Código de Barras"
      :style="{ width: '95vw', maxWidth: '500px' }" :closable="true" @hide="cerrarEscaneo" class="responsive-dialog">
      <div style="position: relative; width: 100%; height: 80vh; background-color: #000;">
        <p style="position: absolute; top: 10px; left: 0; right: 0; color: white; text-align: center; z-index: 100;">
          Apunta la cámara al código de barras
        </p>
        <div id="escaneo-camara" style="width: 100%; height: 100%;"></div>
      </div>
    </Dialog>

    <!-- MODAL PARA SELECCIONAR MOTIVOS -->
    <Dialog :visible.sync="modal2" modal :header="tituloModal2" :closable="true" @hide="cerrarModal2"
      class="responsive-dialog" :containerStyle="dialogContainerStyle">
      <!-- Contenido del modal -->
      <div class="toolbar-container">
        <div class="search-bar">
          <span class="p-input-icon-left">
            <i class="pi pi-search" />
            <InputText v-model="buscarA" @keyup="listarMotivo(1, buscarA, criterioA)" class="form-control"
              placeholder="Buscar motivo..." />
          </span>
        </div>
        <div class="toolbar">
          <Button label="Reset" icon="pi pi-refresh" @click="resetBusquedaMotivos" class="p-button-help p-button-sm"
            title="Limpiar" />
          <!-- Botón para añadir nuevo motivo -->
          <Button label="Nuevo Motivo" icon="pi pi-plus" class="p-button-secondary p-button-sm"
            @click="abrirModalNuevoMotivo" />
        </div>
      </div>

      <div class="table-responsive">
        <DataTable :value="arrayBuscador" class="p-datatable-sm p-datatable-gridlines" responsiveLayout="scroll">
          <!-- Columnas de la tabla de motivos -->
          <Column header="Seleccionar" style="width: 15%">
            <template #body="slotProps">
              <Button icon="pi pi-check" class="p-button-success p-button-sm" @click="seleccionar(slotProps.data)" />
            </template>
          </Column>
          <Column field="nombre" header="Motivo de Baja" />
        </DataTable>
      </div>

      <Paginator :rows="pagination.per_page" :totalRecords="pagination.total"
        :first="(pagination.current_page - 1) * pagination.per_page" @page="onPageChange" />

      <template #footer>
        <Button label="Cerrar" icon="pi pi-times" class="p-button-danger p-button-sm" @click="cerrarModal2"
          type="button" />
      </template>
    </Dialog>

    <!-- MODAL PARA REGISTRAR NUEVO MOTIVO -->
    <Dialog :visible.sync="modal3" modal :header="tituloModal3" :closable="true" @hide="cerrarModal3"
      class="responsive-dialog" :containerStyle="dialogContainerStyle">
      <!-- Contenido del modal -->
      <div v-if="tituloModal2 !== 'Proveedors'">
        <form class="form-horizontal">
          <div v-if="tituloModal2 !== 'Grupos' && tituloModal2 !== 'Lineas'" class="form-group row">
            <label class="col-md-3 form-control-label" for="text-input">
              Nombre
            </label>
            <div class="col-md-9">
              <InputText type="text" v-model="nombre" class="form-control1" placeholder="Ingrese nombre del motivo" />
            </div>
          </div>
        </form>
      </div>
      <template #footer>
        <Button label="Cerrar" icon="pi pi-times" class="p-button-secondary p-button-sm" @click="cerrarModal3"
          type="button" />
        <Button v-if="tipoAccion2 == 5" class="p-button-primary p-button-sm" label="Guardar" icon="pi pi-check"
          @click="registrarMarca" type="button" />
        <Button v-if="tipoAccion2 == 6" class="p-button-primary p-button-sm" label="Actualizar" icon="pi pi-check"
          @click="actualizarMarca" type="button" />
      </template>
    </Dialog>

    <Dialog :visible.sync="mostrarModalDetalle" :modal="true" :containerStyle="dialogContainerStyleDetalle"
      :closable="false" :closeOnEscape="true">
      <template slot="header">
        <div style="display: flex; align-items: center; gap: 10px;">
          <i class="pi pi-briefcase" style="font-size: 1.3rem;"></i>
          <span style="font-weight: bold; font-size: 1.1rem;">
            Detalle de Control de Inventario
          </span>
        </div>
      </template>
      <div class="info-control">

        <div class="info-item">
          <i class="pi pi-building"></i>
          <div>
            <small>Almacén</small>
            <strong>
              {{ controlSeleccionado.almacen ? controlSeleccionado.almacen.nombre_almacen : '' }}
            </strong>
          </div>
        </div>

        <div class="info-item">
          <i class="pi pi-user"></i>
          <div>
            <small>Responsable</small>
            <strong>
              {{ controlSeleccionado.usuario ? controlSeleccionado.usuario.usuario : '' }}
            </strong>
          </div>
        </div>

        <div class="info-item">
          <i class="pi pi-calendar"></i>
          <div>
            <small>Fecha</small>
            <strong>
              {{ formatearFecha(controlSeleccionado.fechahora) }}
            </strong>
          </div>
        </div>

      </div>

      <div class="resumen-control">

        <div class="resumen-item total">
          <span>{{ resumen.total }}</span>
          <small>Productos</small>
        </div>

        <div class="resumen-item verificados">
          <span>{{ resumen.verificados }}</span>
          <small>Verificados</small>
        </div>

        <div class="resumen-item pendientes">
          <span>{{ resumen.pendientes }}</span>
          <small>Pendientes</small>
        </div>

        <div class="resumen-item anulados">
          <span>{{ resumen.anulados }}</span>
          <small>Anulados</small>
        </div>

      </div>

      <!-- TABLA DETALLE -->
      <DataTable :value="controlSeleccionado.detalles" class="p-datatable-sm p-datatable-gridlines tabla-pro"
        responsiveLayout="scroll">

        <!-- 📦 CODIGO -->
        <Column header="CÓDIGO">
          <template slot="body" slot-scope="slotProps">
            {{ slotProps.data.articulo ? slotProps.data.articulo.codigo : '' }}
          </template>
        </Column>

        <!-- 📦 ARTICULO -->
        <Column header="ARTÍCULO">
          <template slot="body" slot-scope="slotProps">
            {{ slotProps.data.articulo ? slotProps.data.articulo.nombre : '' }}
          </template>
        </Column>

        <!-- 📊 STOCK SISTEMA -->
        <Column v-if="Number(rolUsuario) === 4" header="STOCK SISTEMA ANTERIOR" style="text-align: center;">
          <template slot="body" slot-scope="slotProps">
            {{ slotProps.data.stocksistema }}
          </template>
        </Column>

        <!-- 📊 STOCK SISTEMA ACTUAL -->
        <Column v-if="Number(rolUsuario) === 4" header="STOCK SISTEMA ACTUAL" style="text-align: center;">
          <template slot="body" slot-scope="slotProps">
            {{ slotProps.data.stock_actual }}
          </template>
        </Column>

        <!-- 📊 STOCK FISICO -->
        <Column header="STOCK FÍSICO" style="text-align: center;">
          <template slot="body" slot-scope="slotProps">

            <!-- ✏️ MODO EDICIÓN -->
            <template v-if="filaEditando === slotProps.data.id">

              <InputNumber v-model="stockFisicoEditado" mode="decimal" :minFractionDigits="2" :maxFractionDigits="2"
                inputStyle="width:100px; text-align:center;" class="input-number-full" />

            </template>

            <!-- 👁️ NORMAL -->
            <template v-else>
              {{ slotProps.data.stockfisico }}
            </template>

          </template>
        </Column>

        <!-- 📉 DIFERENCIA -->
        <Column v-if="Number(rolUsuario) === 4" header="DIFERENCIA" style="text-align: center;">
          <template slot="body" slot-scope="slotProps">
            <span :style="getColorDiferencia(slotProps.data)">
              {{ calcularDiferenciaDialog(slotProps.data) }}
            </span>
          </template>
        </Column>

        <Column header="ESTADO" style="text-align: center;">
          <template slot="body" slot-scope="slotProps">
            <span :class="getEstadoClase(slotProps.data)" style="padding: 5px 10px;">
              {{ getEstadoTexto(slotProps.data) }}
            </span>
          </template>
        </Column>

        <!-- 🔘 ACCIONES -->
        <Column header="ACCIONES" style="text-align: center;">
          <template slot="body" slot-scope="slotProps">

            <div style="display: flex; justify-content: center; align-items: center;">

              <!-- 🔥 SI ES ADMIN (ROL 4) -->
              <template v-if="Number(rolUsuario) === 4">

                <template v-if="slotProps.data.estado == 1">

                  <!-- ✏️ SI ESTÁ EDITANDO -->
                  <template v-if="filaEditando === slotProps.data.id">

                    <Button icon="pi pi-check" class="p-button-success btn-mini"
                      @click="guardarEdicion(slotProps.data)" v-tooltip.top="'Guardar'" />

                    <Button icon="pi pi-times" class="p-button-secondary btn-mini" style="margin-left: 5px;"
                      @click="cancelarEdicion()" v-tooltip.top="'Cancelar'" />

                  </template>

                  <!-- 👁️ NORMAL -->
                  <template v-else>

                    <!-- ✏️ EDITAR -->
                    <Button icon="pi pi-pencil" class="p-button-primary btn-mini"
                      @click="editarStockFisico(slotProps.data)" v-tooltip.top="'Editar stock físico'" />

                    <!-- 🔵 SIN DIFERENCIA -->
                    <template v-if="sinDiferencia(slotProps.data)">

                      <Button icon="pi pi-arrow-right" class="p-button-help btn-mini"
                        style="margin-left: 5px;" @click="confirmarPasarEstado(slotProps.data)"
                        v-tooltip.top="'Pasar estado'" />

                    </template>

                    <!-- 🟡 CON DIFERENCIA -->
                    <template v-else>

                      <Button icon="pi pi-refresh" class="p-button-warning btn-mini"
                        style="margin-left: 5px;" @click="ajustarProducto(slotProps.data)"
                        v-tooltip.top="'Ajustar stock'" />

                      <Button icon="pi pi-times" class="p-button-danger btn-mini" style="margin-left: 5px;"
                        @click="confirmarCancelacion(slotProps.data)" v-tooltip.top="'Cancelar ajuste'" />

                    </template>

                  </template>

                </template>

                <template v-else>
                  <i :class="{
                    'pi pi-check-circle text-success': slotProps.data.estado == 2,
                    'pi pi-times-circle text-danger': slotProps.data.estado == 0,
                    'pi pi-info-circle text-info': slotProps.data.estado == 3
                  }" v-tooltip.top="slotProps.data.estado == 2 ? 'Verificado' : 'Anulado'" />
                </template>

              </template>

              <!-- 🔒 SI NO ES ADMIN -->
              <template v-else>

                <i v-if="slotProps.data.estado == 1" class="pi pi-clock text-warning" v-tooltip.top="'Pendiente'" />

                <i v-else-if="slotProps.data.estado == 2" class="pi pi-check-circle text-success"
                  v-tooltip.top="'Verificado'" />

                <i v-else-if="slotProps.data.estado == 3" class="pi pi-info-circle text-info"
                  v-tooltip.top="'Sin diferencia'" />

                <i v-else class="pi pi-times-circle text-danger" v-tooltip.top="'Anulado'" />

              </template>

            </div>

          </template>
        </Column>

      </DataTable>
      <template slot="footer">
        <div style="display: flex; justify-content: flex-end;">
          <Button label="Cerrar" icon="pi pi-times" class="p-button-danger btn-sm"
            @click="mostrarModalDetalle = false" />
        </div>
      </template>
    </Dialog>

    <Dialog :visible.sync="mostrarDialogLlaves" :modal="true" :containerStyle="dialogContainerStyleLlave"
      class="responsive-dialog" :closable="false">
      <template #header>
        <div class="dialog-header">
          <i class="pi pi-key header-icon"></i>
          <span class="header-title">Gestión de Llaves de Acceso</span>
        </div>
      </template>

      <div class="grid-llaves">

        <!-- 🟦 COLUMNA IZQUIERDA (FORMULARIO) -->
        <div class="col-form">

          <h6 class="titulo-seccion">Crear Llave</h6>

          <!-- 👤 USUARIO -->
          <div class="campo">
            <label for="nombre" class="required-field">
              <span class="required-icon">*</span>
              Seleccione un Usuario
            </label>
            <Dropdown v-model="formLlave.idusuario" :options="usuarios" optionLabel="usuario" optionValue="id"
              placeholder="Seleccione usuario" class="dropdown-full" />
          </div>

          <!-- 📅 FECHA FIN -->
          <div class="campo">
            <label for="nombre" class="required-field">
              <span class="required-icon">*</span>
              Fecha de expiración de la llave
            </label>
            <input type="date" v-model="formLlave.fechafin" :min="fechaMinima"
              class="p-inputtext p-component input-date-full" />
          </div>

          <!-- 🔑 CLAVE -->
          <div class="campo">
            <label for="nombre" class="required-field">
              <span class="required-icon">*</span>
              Ingrese una clave segura para el usuario
            </label>
            <InputText type="password" v-model="formLlave.clave" placeholder="Ingrese clave" class="input-full" />
          </div>

          <!-- 🔁 CONFIRMAR -->
          <div class="campo">
            <label for="nombre" class="required-field">
              <span class="required-icon">*</span>
              Confirmar Clave
            </label>
            <InputText type="password" v-model="formLlave.confirmar" placeholder="Repita clave" class="input-full" />
          </div>

          <!-- 🔘 BOTÓN -->
          <Button label="Guardar" icon="pi pi-save" class="p-button-success p-button-sm btn-sm" @click="guardarLlave" />

        </div>

        <!-- 🟩 COLUMNA DERECHA (TABLA) -->
        <div class="col-tabla">

          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">

            <h6 class="titulo-seccion">Llaves Registradas</h6>

            <Button label="Eliminar vencidas" icon="pi pi-trash" class="p-button-danger p-button-sm btn-sm"
              @click="confirmarEliminarVencidas" />

          </div>

          <DataTable :value="llaves" class="p-datatable-sm p-datatable-gridlines">

            <Column header="Usuario">
              <template slot="body" slot-scope="slotProps">
                {{ slotProps.data.usuario ? slotProps.data.usuario.usuario : '' }}
              </template>
            </Column>
            <Column header="Llave" style="text-align: center;">
              <template slot="body" slot-scope="slotProps">
                <span>
                  {{
                    llavesVisibles.includes(slotProps.data.id)
                      ? slotProps.data.llave
                      : '••••••'
                  }}
                </span>
              </template>
            </Column>
            <Column header="Fecha Fin" style="text-align: center;">
              <template slot="body" slot-scope="slotProps">
                <span :class="getClaseFecha(slotProps.data.fechafin)">
                  {{ slotProps.data.fechafin }}
                </span>
              </template>
            </Column>
            <Column header="ACCIONES" style="text-align: center; width: 120px;">
              <template slot="body" slot-scope="slotProps">
                <!-- 👁 VER / OCULTAR -->
                <Button :icon="llavesVisibles.includes(slotProps.data.id) ? 'pi pi-eye-slash' : 'pi pi-eye'"
                  class="p-button-secondary btn-mini" @click="toggleVerLlave(slotProps.data.id)"
                  v-tooltip.top="llavesVisibles.includes(slotProps.data.id) ? 'Ocultar' : 'Ver llave'" />
                <Button icon="pi pi-trash" class="p-button-danger btn-mini"
                  @click="confirmarEliminarLlave(slotProps.data)" v-tooltip.top="'Eliminar llave'" />

              </template>
            </Column>

          </DataTable>

        </div>

      </div>
      <template #footer>
        <Button label="Cancelar" icon="pi pi-times" class="p-button-danger btn-sm"
          @click="mostrarDialogLlaves = false" />
      </template>
    </Dialog>

    <Dialog :visible.sync="mostrarDialogClave" :modal="true" :closable="false" :containerStyle="dialogContainerStyle">
      <template #header>
        <div class="dialog-header">
          <i class="pi pi-key header-icon"></i>
          <span class="header-title">Verificación de Acceso</span>
        </div>
      </template>

      <div style="text-align: center;">

        <h6 class="titulo-seccion">Ingrese su clave de acceso</h6>

        <div class="input-password-container">

          <InputText :type="mostrarClave ? 'text' : 'password'" v-model="claveAcceso" placeholder="Clave"
            class="p-inputtext-sm input-full" style="width: 100%; padding-right: 35px;" />

          <i :class="mostrarClave ? 'pi pi-eye-slash' : 'pi pi-eye'" class="icono-ojo"
            @click="mostrarClave = !mostrarClave"></i>

        </div>

        <Button label="Verificar" icon="pi pi-check" class="p-button-success p-button-sm btn-sm"
          @click="verificarClave" />

      </div>
      <template #footer>
        <Button label="Cancelar" icon="pi pi-times" class="p-button-danger btn-sm"
          @click="mostrarDialogClave = false" />
      </template>

    </Dialog>
  </main>
</template>


<script>
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import Dropdown from "primevue/dropdown";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dialog from "primevue/dialog";
import Paginator from "primevue/paginator";
import { esquemaArticulos, esquemaInventario } from "../constants/validations";
import VueBarcode from "vue-barcode";
import Panel from "primevue/panel";
import Sidebar from 'primevue/sidebar';
import AutoComplete from 'primevue/autocomplete';
import Calendar from "primevue/calendar";
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
import Swal from "sweetalert2";
import Tooltip from 'primevue/tooltip';
import Tag from 'primevue/tag';

export default {
  components: {
    Button,
    InputText,
    InputNumber,
    Dropdown,
    DataTable,
    Column,
    Dialog,
    Paginator,
    barcode: VueBarcode,
    Panel,
    Sidebar,
    AutoComplete,
    Calendar,
    ToastService,
    Toast,
    Tag
  }, directives: {
    'tooltip': Tooltip
  },
  data() {
    return {
      filaEditando: null,
      stockFisicoEditado: null,

      mostrarClave: false,
      mostrarDialogClave: false,
      claveAcceso: '',
      llavesVisibles: [],
      mostrarDialogLlaves: false,
      usuarios: [],
      llaves: [],

      formLlave: {
        idusuario: null,
        clave: '',
        confirmar: '',
        fechafin: ''
      },
      mostrarModalDetalle: false,
      controlSeleccionado: {
        detalles: [],
        usuario: {},
        almacen: {}
      },
      modo_ajuste: 'unidad',
      mostrarLabel: true,
      buscar: "",
      isLoading: false,
      idAlmacenSeleccionado: null,
      categoriaSeleccionada: "",

      tipo_stock: "paquetes",
      idarticulo: 0,
      fechaVencimientoSeleccion: "0",
      arrayAlmacenes: [],
      AlmacenSeleccionado: null,
      productosSeleccionados: [],
      datosFormulario: {
        cantidad: 0,
        idtipobaja: null,
        producto: null,
        idAlmacenSeleccionado: null,
      },

      stock_actual: 0,
      stock_restante: 0,

      errores: {},
      monedaPrincipal: [],

      criterioA: "nombre",
      buscarA: "",
      tituloModal2: "",
      industriaseleccionada: [],
      productoseleccionado: [],
      proveedorseleccionada: [],
      gruposeleccionada: [],
      nombre_grupo: "",

      modal2: false,
      idcategoria: 0,
      idmarca: 0,
      idindustria: 0,
      idproveedor: 0,
      idgrupo: 0,
      idmedida: 0,
      nombre_categoria: "",
      nombre_proveedor: "",
      //id:'',//aumente 7 julio
      codigo: "",
      nombre: "",
      nombre_generico: "",
      //validaion para inputs

      lineaseleccionadaVacio: false,
      marcaseleccionadaVacio: false,
      //aumente esto
      unidad_envase: 0,
      precio_costo_unid: 0,
      precio_costo_paq: 0,
      //hasta aqui
      precios: [],
      precio: "", //aumente 5julio

      //aumento 13_junio
      precio_uno: 0,
      precio_dos: 0,
      precio_tres: 0,
      precio_cuatro: 0,
      //hasta aqui

      //--------hasta aqui-----------------
      //--grupo--
      nombre_grupo: "",
      //---hasta aqui
      precio_venta: 0,
      costo_compra: 0,

      stock: 5,
      descripcion: "",
      fotografia: "",
      fotoMuestra: null,
      arrayDatosControl: [],
      arrayBuscador: [],
      modal: 0,

      tituloModal: "",
      tipoAccion: 0,
      tipoAccion2: 0,
      //------registro industia, marcas--
      modal3: false,
      tituloModal3: "",
      marca_id: 0,
      condicion: 1,
      nombre_industria: "",
      //--------hasta aqui---
      pagination: this.createPaginationObject(),
      offset: {
        pagination: 3,
      },
      criterio: "articulos.nombre", // Por defecto
      categoria: "", // Nueva categoría seleccionada

      //CONFIGURACIONES
      mostrarSaldosStock: "",
      mostrarProveedores: "",
      mostrarCostos: "",
      rolUsuario: "",

      descripcion_medida: "",
      medidaseleccionada: [],
      // NUEVO REFACTORIZACION AJUSTES
      dialogoProductosVisible: false,
      proveedorSeleccionado: { nombre: '' },
      proveedoresFiltrados: [],
      mostrarDesplegableProveedor: false,
      indiceSeleccionadoProveedor: -1,
      dialogoEscaneoVisible: false,
      indiceFoco: -1,
      vistaActual: 'tabla',

      fechaInicio: null,
      fechaFin: null,
      idAlmacen: null,
    };
  },
  computed: {
    fechaMinima() {
      const hoy = new Date();
      const year = hoy.getFullYear();
      const month = String(hoy.getMonth() + 1).padStart(2, '0');
      const day = String(hoy.getDate()).padStart(2, '0');

      return `${year}-${month}-${day}`; // formato YYYY-MM-DD
    },
    resumen() {
      const detalles = this.controlSeleccionado.detalles || [];

      let total = detalles.length;
      let verificados = 0;
      let pendientes = 0;
      let anulados = 0;

      detalles.forEach(d => {
        if (d.estado == 2) verificados++;
        else if (d.estado == 1) pendientes++;
        else if (d.estado == 0) anulados++;
      });

      return {
        total,
        verificados,
        pendientes,
        anulados
      };
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
    dialogContainerStyleDetalle() {
      if (window.innerWidth <= 480) {
        return { width: "95vw", maxWidth: "95vw", margin: "0 auto" };
      } else if (window.innerWidth <= 768) {
        return { width: "90vw", maxWidth: "90vw", margin: "0 auto" };
      } else if (window.innerWidth <= 1024) {
        return { width: "85vw", maxWidth: "1000px", margin: "0 auto" };
      } else {
        return { width: "1200px", maxWidth: "95vw", margin: "0 auto" };
      }
    },
    dialogContainerStyleLlave() {
      if (window.innerWidth <= 480) {
        return { width: "95vw", maxWidth: "95vw", margin: "0 auto" };
      } else if (window.innerWidth <= 768) {
        return { width: "90vw", maxWidth: "90vw", margin: "0 auto" };
      } else if (window.innerWidth <= 1024) {
        return { width: "85vw", maxWidth: "1000px", margin: "0 auto" };
      } else {
        return { width: "1200px", maxWidth: "95vw", margin: "0 auto" };
      }
    },
    isActived: function () {
      return this.pagination.current_page;
    },
    pagesNumber: function () {
      return this.calculatePages(this.pagination, this.offset.pagination);
    },

    totalUnidadesAjuste() {
      return this.productosSeleccionados.reduce((total, producto) => {
        const cantidad = parseInt(producto.cantidad_ajuste) || 0;
        return total + (cantidad > 0 ? cantidad : 0);
      }, 0);
    },

    hayProductosSeleccionados() {
      return this.productosSeleccionados.length > 0;
    }
  },
  //añadido en fecha 14/03/25
  // Corrección de los watchers
  watch: {
    buscar: {
      handler: function (val) {
        if (this._debounceBuscar) clearTimeout(this._debounceBuscar);
        this._debounceBuscar = setTimeout(() => {
          this.listarControles(1, val, "");
        }, 400);
      },
    },
    // Para el producto seleccionado
    productoseleccionado: {
      handler(newVal) {
        if (newVal && newVal.id) {
          // Actualizar el ID del producto en datosFormulario
          this.datosFormulario.producto = newVal.id;
          // Llamar a obtenerStock
          this.obtenerStock();
        }
      },
      deep: true,
    },

    // Para el almacén seleccionado
    idAlmacenSeleccionado: function (newVal) {
      this.AlmacenSeleccionado = newVal; // Sincronizar ambas variables
      this.datosFormulario.idAlmacenSeleccionado = newVal; // Mantener siempre actualizado el id en el formulario

      if (newVal !== this.idAlmacenAnterior) {
        this.limpiarProductosSeleccionados();
        this.idAlmacenAnterior = newVal;
      }

      if (newVal && this.productoseleccionado && this.productoseleccionado.id) {
        this.obtenerStock();
      }
    },

    // Para la cantidad
    "datosFormulario.cantidad": function (newVal) {
      this.actualizarStock();
    },
  },

  methods: {
    async guardarEdicion(det) {

      try {

        this.isLoading = true;

        await axios.put(
          `/controlinventario/editarStockFisico/${det.id}`,
          {
            stockfisico: this.stockFisicoEditado
          }
        );

        // actualizar valor local
        det.stockfisico = this.stockFisicoEditado;

        this.$toast.add({
          severity: 'success',
          summary: 'Correcto',
          detail: 'Stock físico actualizado',
          life: 3000
        });

        this.filaEditando = null;
        this.stockFisicoEditado = null;

      } catch (error) {

        console.error(error);

        this.$toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'No se pudo actualizar',
          life: 3000
        });

      } finally {

        this.isLoading = false;

      }

    },
    cancelarEdicion() {

      this.filaEditando = null;
      this.stockFisicoEditado = null;

    },
    editarStockFisico(det) {

      this.filaEditando = det.id;
      this.stockFisicoEditado = parseFloat(det.stockfisico);

    },
    sinDiferencia(det) {
      return (
        parseFloat(det.stockfisico) - parseFloat(det.stock_actual)
      ) === 0;
    },
    async confirmarPasarEstado(detalle) {
      setTimeout(async () => {

        const result = await Swal.fire({
          title: '¿Está seguro?',
          text: '¿Desea marcar este registro como SIN DIFERENCIA?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Sí, continuar',
          cancelButtonText: 'No',
          allowOutsideClick: false,
          allowEscapeKey: false,
          backdrop: true,
          didOpen: (modal) => {
            modal.parentElement.style.zIndex = '99999';
            const backdrop = document.querySelector('.swal2-container');
            if (backdrop) {
              backdrop.style.zIndex = '99998';
            }
          }
        });

        if (result.isConfirmed) {

          try {
            // 🔥 ACTIVAR TU LOADING GLOBAL
            this.isLoading = true;

            await this.pasarDetalle(detalle);

          } catch (error) {
            console.error(error);
          } finally {
            // 🔥 DESACTIVAR LOADING
            this.isLoading = false;
          }

        } else {
        }

      }, 100);
    },
    async pasarDetalle(detalle) {
      try {

        const resp = await axios.put(
          `/detalle-controlinventario/pasarEstado/${detalle.id}`
        );

        this.toastSuccess('Estado actualizado correctamente');

        // 🔥 SI EL CONTROL CAMBIÓ → RECARGAR LISTA
        if (resp.data.control_actualizado) {
          await this.listarControles(1, "", "");
        }

        // 🔄 REFRESCAR DETALLE
        await this.verDetalleControl(this.controlSeleccionado.id);

      } catch (error) {

        console.error("Error:", error);

        this.toastError('No se pudo actualizar el estado');

      }
    },
    descargarPdf(id) {
      window.open(`/controlinventario/pdf/${id}`, '_blank');
    },
    descargarExcel(id) {
      window.open(`/controlinventario/excel/${id}`, '_blank');
    },
    async confirmarEliminarVencidas() {

      const result = await Swal.fire({
        title: '¿Está seguro?',
        text: '¿Desea eliminar todas las llaves vencidas?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        allowOutsideClick: false,
        allowEscapeKey: false,
        backdrop: true,
        didOpen: (modal) => {
          modal.parentElement.style.zIndex = '99999';
          const backdrop = document.querySelector('.swal2-container');
          if (backdrop) {
            backdrop.style.zIndex = '99998';
          }
        }
      });

      if (result.isConfirmed) {
        await this.eliminarLlavesVencidas();
      }
    },
    async eliminarLlavesVencidas() {
      try {

        this.isLoading = true;

        await axios.delete('/llaves/eliminar-vencidas');
        this.toastSuccess('Llaves vencidas eliminadas correctamente');

        // 🔄 refrescar tabla
        this.listarLlaves();

      } catch (error) {
        console.error(error);
        this.toastError('Error', 'No se pudieron eliminar las llaves vencidas');
      } finally {
        this.isLoading = false;
      }
    },

    async verificarClave() {

      if (!this.claveAcceso) {
        this.toastWarning('Ingrese la clave');
        return;
      }

      try {

        this.isLoading = true;

        const response = await axios.post('/llaves/verificar', {
          clave: this.claveAcceso
        });

        if (response.data.valido) {

          this.$toast.add({
            severity: 'success',
            summary: 'Acceso concedido',
            detail: 'Puede continuar',
            life: 2000
          });

          this.mostrarDialogClave = false;
          this.claveAcceso = '';
          this.vistaActual = 'formulario';

        } else {

          this.$toast.add({
            severity: 'error',
            summary: 'Error',
            detail: response.data.mensaje || 'Clave incorrecta',
            life: 2000
          });

        }

      } catch (error) {
        console.error(error);
      } finally {
        this.isLoading = false;
      }
    },
    validarAcceso() {

      if (this.rolUsuario == 4) {
        this.vistaActual = 'formulario';
        return;
      }

      // 🔒 pedir clave
      this.mostrarDialogClave = true;
    },
    toggleVerLlave(id) {
      const index = this.llavesVisibles.indexOf(id);

      if (index === -1) {
        this.llavesVisibles.push(id); // mostrar
      } else {
        this.llavesVisibles.splice(index, 1); // ocultar
      }
    },
    getClaseFecha(fechafin) {

      if (!fechafin) return '';

      const hoy = new Date();
      const fechaFin = new Date(fechafin);

      // quitar horas para comparación limpia
      hoy.setHours(0, 0, 0, 0);
      fechaFin.setHours(0, 0, 0, 0);

      const diferenciaDias = (fechaFin - hoy) / (1000 * 60 * 60 * 24);

      if (diferenciaDias < 0) {
        return 'badge badge-danger'; // 🔴 vencido
      }

      if (diferenciaDias <= 2) {
        return 'badge badge-warning'; // 🟡 por vencer
      }

      return ''; // normal
    },
    async confirmarEliminarLlave(llave) {

      const nombreUsuario = llave.usuario ? llave.usuario.usuario : '';

      const result = await Swal.fire({
        title: '¿Está seguro?',
        text: `¿Desea eliminar la llave de ${nombreUsuario}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        allowOutsideClick: false,
        allowEscapeKey: false,
        backdrop: true,
        didOpen: (modal) => {
          modal.parentElement.style.zIndex = '99999';
          const backdrop = document.querySelector('.swal2-container');
          if (backdrop) {
            backdrop.style.zIndex = '99998';
          }
        }
      });

      if (result.isConfirmed) {
        await this.eliminarLlave(llave.id);
      }
    },
    async eliminarLlave(id) {
      try {

        this.isLoading = true;

        await axios.delete(`/llaves/${id}`);

        this.$toast.add({
          severity: 'success',
          summary: 'Éxito',
          detail: 'Llave eliminada correctamente',
          life: 2000
        });

        // 🔄 refrescar tabla
        this.listarLlaves();

      } catch (error) {
        console.error(error);

        this.$toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'No se pudo eliminar la llave',
          life: 3000
        });

      } finally {
        this.isLoading = false;
      }
    },
    async guardarLlave() {

      if (!this.formLlave.idusuario) {
        this.toastWarning('Seleccione usuario');
        return;
      }

      if (!this.formLlave.clave) {
        this.toastWarning('Ingrese clave');
        return;
      }

      if (this.formLlave.clave !== this.formLlave.confirmar) {
        this.toastError('Las claves no coinciden');
        return;
      }

      if (!this.formLlave.fechafin) {
        this.toastWarning('Seleccione fecha de expiración');
        return;
      }
      try {
        await axios.post('/llaves', {
          idusuario: this.formLlave.idusuario,
          llave: this.formLlave.clave,
          fechafin: this.formLlave.fechafin
        });
        this.toastSuccess('Llave creada correctamente');

        this.formLlave = { idusuario: null, clave: '', confirmar: '' };

        this.listarLlaves();

      } catch (error) {

        console.error(error);

        let mensaje = 'Error al registrar';

        if (error.response && error.response.data) {

          if (error.response.data.error) {
            mensaje = error.response.data.error;
          }

          // 🔥 errores de validación Laravel (422)
          if (error.response.data.errors) {
            mensaje = Object.values(error.response.data.errors)[0][0];
          }
        }

        this.toastError(mensaje);
      }
    },
    async listarUsuarios() {
      try {
        const response = await axios.get('/usuario/selectUsuario');
        this.usuarios = response.data.usuarios;
      } catch (error) {
        console.error("Error al cargar usuarios", error);
      }
    },

    async listarLlaves() {
      const res = await axios.get('/llaves');
      this.llaves = res.data;
    },
    abrirDialogLlaves() {
      this.mostrarDialogLlaves = true;
      this.listarUsuarios();
      this.listarLlaves();
    },
    toastSuccess(mensaje) {
      this.$toast.add({
        severity: "success",
        summary: "Éxito",
        detail: mensaje,
        life: 2000,
      });
    },
    toastError(mensaje) {
      this.$toast.add({
        severity: "error",
        summary: "Error",
        detail: mensaje,
        life: 2000,
      });
    },
    toastWarning(mensaje) {
      this.$toast.add({
        severity: "warn",
        summary: "Advertencia",
        detail: mensaje,
        life: 2000,
      });
    },
    toastInfo(mensaje) {
      this.$toast.add({
        severity: "info",
        summary: "Información",
        detail: mensaje,
        life: 2000,
      });
    },
    getEstadoTextoGeneral(estado) {
      if (estado == 1) return 'NO VERIFICADO';
      if (estado == 2) return 'VERIFICADO';
      if (estado == 0) return 'ANULADO';
      return '';
    },

    getEstadoClaseGeneral(estado) {
      if (estado == 1) return 'badge badge-warning';   // 🟡
      if (estado == 2) return 'badge badge-success';   // 🟢
      if (estado == 0) return 'badge badge-danger';    // 🔴
      return 'badge badge-secondary';
    },
    async confirmarCancelacion(detalle) {
      setTimeout(async () => {

        const result = await Swal.fire({
          title: '¿Está seguro?',
          text: '¿Desea cancelar este ajuste?',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Sí, cancelar',
          cancelButtonText: 'No',
          allowOutsideClick: false,
          allowEscapeKey: false,
          backdrop: true,
          didOpen: (modal) => {
            modal.parentElement.style.zIndex = '99999';
            const backdrop = document.querySelector('.swal2-container');
            if (backdrop) {
              backdrop.style.zIndex = '99998';
            }
          }
        });

        if (result.isConfirmed) {

          try {
            // 🔥 ACTIVAR TU LOADING GLOBAL
            this.isLoading = true;

            await this.cancelarDetalle(detalle);

          } catch (error) {
            console.error(error);
          } finally {
            // 🔥 DESACTIVAR LOADING
            this.isLoading = false;
          }

        } else {
        }

      }, 100);
    },
    async cancelarDetalle(detalle) {
      try {

        const resp = await axios.put(
          `/detalle-controlinventario/cancelar/${detalle.id}`
        );

        this.toastSuccess('Detalle cancelado correctamente');

        // 🔥 SI EL CONTROL CAMBIÓ → RECARGAR LISTA
        if (resp.data.control_actualizado) {
          await this.listarControles(1, "", "");
        }

        // 🔄 REFRESCAR DETALLE
        await this.verDetalleControl(this.controlSeleccionado.id);

      } catch (error) {

        console.error("Error:", error);

        this.toastError('No se pudo cancelar el detalle');

      }
    },
    getEstadoClase(det) {
      const diferencia = parseFloat(det.stockfisico) - parseFloat(det.stock_actual);

      if (diferencia === 0 && det.estado == 1) {
        return 'badge badge-info';
      }

      if (det.estado == 1) return 'badge badge-warning';
      if (det.estado == 2) return 'badge badge-success';
      if (det.estado == 3) return 'badge badge-info';
      if (det.estado == 0) return 'badge badge-danger';

      return 'badge badge-secondary';
    },
    getEstadoTexto(det) {
      const diferencia = parseFloat(det.stockfisico) - parseFloat(det.stock_actual);

      if (diferencia === 0 && det.estado == 1) {
        return 'SIN DIFERENCIA';
      }

      if (det.estado == 1) return 'NO AJUSTADO';
      if (det.estado == 2) return 'AJUSTADO';
      if (det.estado == 3) return 'SIN DIFERENCIA';
      if (det.estado == 0) return 'CANCELADO';

      return '';
    },
    async ajustarProducto(detalle) {

      // calcular diferencia
      const diferencia = detalle.stockfisico - detalle.stock_actual;

      if (diferencia === 0) {
        this.$toast.add({
          severity: 'info',
          summary: 'Sin cambios',
          detail: 'No hay diferencia para ajustar',
          life: 2000
        });
        return;
      }

      const result = await Swal.fire({
        title: '¿Ajustar inventario?',
        text: `¿Desea ajustar el stock del producto "${detalle.articulo.nombre}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, ajustar',
        cancelButtonText: 'No',
        allowOutsideClick: false,
        allowEscapeKey: false,
        backdrop: true,
        didOpen: (modal) => {
          modal.parentElement.style.zIndex = '99999';
          const backdrop = document.querySelector('.swal2-container');
          if (backdrop) {
            backdrop.style.zIndex = '99998';
          }
        }
      });

      if (result.isConfirmed) {
        try {
          this.isLoading = true;

          const resp = await this.registrarAjusteInventario(detalle, diferencia);

          // 🔄 SI CAMBIÓ EL CONTROL → RECARGAR LISTA
          if (resp.control_actualizado) {
            await this.listarControles(1, "", "");
          }

          this.$toast.add({
            severity: 'success',
            summary: 'Éxito',
            detail: 'Stock ajustado correctamente',
            life: 2500
          });

          // refrescar detalle
          await this.verDetalleControl(this.controlSeleccionado.id);

        } catch (error) {
          console.error(error);

          this.$toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo ajustar el stock',
            life: 3000
          });

        } finally {
          this.isLoading = false;
        }
      }
    },
    async registrarAjusteInventario(detalle, diferencia) {

      const data = {
        cantidad: Math.abs(diferencia),
        producto: detalle.idarticulo,
        idAlmacenSeleccionado: this.controlSeleccionado.idalmacen,
        tipo_movimiento: diferencia > 0 ? 'entrada' : 'salida',
        iddetalle: detalle.id // 👈 importante para actualizar estado
      };

      const response = await axios.post('/ajusteinventario/registrar', data);

      return response.data; // 👈 importante
    },
    getColorDiferencia(det) {
      const diff = det.stockfisico - det.stock_actual;

      if (diff > 0) return { color: 'green', fontWeight: 'bold' };
      if (diff < 0) return { color: 'red', fontWeight: 'bold' };
      return { color: 'gray' };
    },
    calcularDiferenciaDialog(det) {
      return (det.stockfisico - det.stock_actual).toFixed(2);
    },
    formatearFecha(fecha) {
      if (!fecha) return '';

      const f = new Date(fecha);
      const dia = String(f.getDate()).padStart(2, '0');
      const mes = String(f.getMonth() + 1).padStart(2, '0');
      const anio = f.getFullYear();

      return `${dia}/${mes}/${anio}`;
    },
    async verDetalleControl(id) {
      this.isLoading = true; // 🔥 ACTIVAR LOADING

      try {
        const response = await axios.get(`/controlinventario/${id}`);
        const data = response.data;

        console.log("DETALLE:", data);

        this.controlSeleccionado = data;
        this.mostrarModalDetalle = true; // 👉 aquí se abre el dialog

      } catch (error) {
        console.error("Error al obtener detalle:", error);
      } finally {
        this.isLoading = false; // 🔥 SIEMPRE se apaga (éxito o error)
      }
    },
    async registrarControlInventario(datosParaEnviar) {
      try {
        this.isLoading = true;

        await axios.post("/controlinventario", datosParaEnviar, {
          headers: {
            "Content-Type": "application/json",
          },
        });

        this.cerrarModal();
        await this.listarControles(); // crea este método si no lo tienes

        this.toastSuccess("Control de inventario registrado correctamente");

      } catch (error) {
        console.error("Error:", error);
        throw error;

      } finally {
        this.isLoading = false;
      }
    },
    async enviarFormulario() {

      const productosAProcesar = this.productosSeleccionados.filter(p => {
        return p.stock_real >= 0;
      });

      if (productosAProcesar.length === 0) {
        this.toastError("No hay diferencias de stock para registrar.");
        return;
      }

      try {
        this.isLoading = true;

        const data = {
          idalmacen: this.idAlmacenSeleccionado,
          detalles: productosAProcesar.map(p => {
            return {
              idarticulo: p.id,
              stocksistema: parseFloat(p.stock_actual_unidades),
              stockfisico: parseFloat(p.stock_real)
            };
          })
        };

        await this.registrarControlInventario(data);

        setTimeout(() => {
          this.vistaActual = 'tabla';
          this.productosSeleccionados = [];
        }, 1500);

      } catch (error) {
        console.error("Error:", error);
        let msg = "Error al procesar.";

        if (error.response && error.response.data && error.response.data.error) {
          msg = error.response.data.error;
        }

        this.$toast.add({
          severity: 'error',
          summary: 'Error',
          detail: msg,
          life: 5000
        });

      } finally {
        this.isLoading = false;
      }
    },
    toggleModoAjuste(producto) {
      producto.modo_ajuste =
        producto.modo_ajuste === 'unidad' ? 'caja' : 'unidad';

      // Recalcular todo al cambiar modo
      this.calcularDiferencia(producto);
    },
    resetBusqueda() {
      this.buscar = "";
      this.listarControles(1, this.buscar || "", "", this.categoria);
    },

    resetBusquedaProductos() {
      this.buscarA = '';
      let idProveedor = null;
      if (this.proveedorSeleccionado && this.proveedorSeleccionado.id) {
        idProveedor = this.proveedorSeleccionado.id;
      }
      this.listarProducto(1, '', this.criterioA, this.idAlmacenSeleccionado, false, idProveedor);
    },

    resetBusquedaMotivos() {
      this.buscarA = "";
      this.listarMotivo(1, "", this.criterioA);
    },

    enfocarInputBusqueda() {
      this.$nextTick(() => {
        if (this.$refs.inputBusqueda && this.$refs.inputBusqueda.$el) {
          this.$refs.inputBusqueda.$el.focus();
        } else {
          console.error('No se encontró el elemento input');
        }
      });
    },

    onSidebarShow() {
      this.actualizarListaProductos();
      this.enfocarInputBusqueda();
    },

    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
    },
    onPageChange(event) {
      const page = Math.floor(event.first / event.rows) + 1;
      this.cambiarPagina(page);
    },
    asignarCampos() {
      this.datosFormulario.producto = this.productoseleccionado.id;
      this.datosFormulario.idAlmacenSeleccionado = this.idAlmacenSeleccionado;
    },
    async validarCampo() {
      this.asignarCampos();
    },

    guardarYVolver() {
      this.enviarFormulario();
      this.vistaActual = 'tabla';
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
    createPaginationObject() {
      return {
        total: 0,
        current_page: 0,
        per_page: 0,
        last_page: 0,
        from: 0,
        to: 0,
      };
    },

    //-------------hasta qui calcular -----------
    seleccionar(selected) {
      if (this.tituloModal2 == "Productos") {
        if (selected.condicion == 1) {
          this.agregarProductoSeleccionado(selected);
        } else if (selected.condicion == 0) {
          this.advertenciaInactiva("Productos");
        }
      }

      this.arrayBuscador = [];
      this.modal2 = false;
    },

    cerrarModal2() {
      this.arrayBuscador = [];
      this.modal2 = false;
      this.buscarA = "";
    },

    confirmarCancelar() {
      this.$swal.fire({
        title: '¿Está seguro que desea cancelar?',
        text: 'Se perderán todos los cambios.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No'
      }).then((result) => {
        if (result.isConfirmed) {
          this.reiniciarFormulario();
        }
      });
    },

    reiniciarFormulario() {
      this.idAlmacenSeleccionado = null;
      this.proveedorSeleccionado = { nombre: '' };
      this.productosSeleccionados = [];
      this.buscarA = '';
      this.arrayBuscador = [];
      this.vistaActual = 'tabla';
    },

    listarProducto(page, buscar, criterio, idAlmacen, todos, idProveedor) {
      let me = this;
      let url = `/articuloAjusteInven?page=${page}&buscar=${buscar}&criterio=${criterio}&idAlmacen=${idAlmacen}`;
      if (idProveedor) {
        url += `&idProveedor=${idProveedor}`;
      }
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arrayBuscador = respuesta.articulos.data;
          me.pagination = respuesta.pagination;
        })
        .catch(function (error) {
          console.log("Error al listar los productos:", error);
        });
    },

    actualizarListaProductos() {
      const page = 1;
      const buscar = this.buscarA || "";
      const criterio = this.criterioA || "nombre";
      const idAlmacen = this.idAlmacenSeleccionado;
      const idProveedor = this.proveedorSeleccionado ? this.proveedorSeleccionado.id : null;

      this.listarProducto(page, buscar, criterio, idAlmacen, false, idProveedor);
    },

    seleccionarCategoria(categoria) {
      this.categoriaSeleccionada = categoria; // Almacenar la categoría seleccionada
      this.listarProducto(
        1,
        this.buscarA,
        this.criterioA,
        this.idAlmacenSeleccionado
      ); // Actualizar la lista con la nueva categoría
    },

    selectAlmacen() {
      let me = this;
      var url = "/almacen/selectAlmacen";
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arrayAlmacenes = respuesta.almacenes;
        })
        .catch(function (error) {
          console.log(error);
        });
    },


    abrirDialogoProductos() {
      this.dialogoProductosVisible = true;
      let idProveedor = null;
      if (this.proveedorSeleccionado && this.proveedorSeleccionado.id) {
        idProveedor = this.proveedorSeleccionado.id;
      }
      this.listarProducto(1, this.buscarA, this.criterioA, this.idAlmacenSeleccionado, false, idProveedor);
    },


    //FALTA CODIGO PARA QUE FUNCIONE EL ESCANEO DEL CODIGO DE BARRAS

    esDispositivoMovil() {

    },


    iniciarEscaneo() {

    },

    iniciarQuagga() {

    },

    onDetected(result) {

    },

    cerrarEscaneo() {

    },

    abrirModal2(titulo) {
      if (titulo === "Motivo") {
        this.listarMotivo(1, "", "nombre");
        this.modal2 = true;
        this.tituloModal2 = titulo;
        this.marcaseleccionadaVacio = false;
      } else if (titulo === "Productos") {
        if (!this.idAlmacenSeleccionado) {
          swal({
            icon: "warning",
            title: "Almacén no seleccionado",
            text: "Primero seleccione un almacén válido",
          });
          return;
        }
        this.listarProducto(
          1,
          this.buscarA,
          this.criterioA,
          this.idAlmacenSeleccionado
        );
        this.modal2 = true;
        this.tituloModal2 = titulo;
        this.lineaseleccionadaVacio = false;
      }
    },

    async agregarProductoSeleccionado(producto) {
      const productoExistente = this.productosSeleccionados.find(p => p.id === producto.id);

      if (productoExistente) {
        this.toastError("Este producto ya está en la lista");
        return;
      }

      try {
        const stockData = await this.obtenerStockProducto(producto.id, this.idAlmacenSeleccionado);

        const productoConStock = {
          ...producto,
          stock_actual: stockData.stock_actual || 0,
          cantidad_ajuste: 0,
          stock_restante: stockData.stock_actual || 0
        };

        this.productosSeleccionados.push(productoConStock);

      } catch (error) {
        console.error("Error al obtener stock del producto:", error);
        this.toastError("Error al obtener información del producto");
      }
    },

    async obtenerStockProducto(productoId, almacenId) {
      try {
        const response = await axios.get("/ajuste-inventario/obtenerStock", {
          params: {
            producto: productoId,
            almacen: almacenId,
          },
        });
        return response.data;
      } catch (error) {
        console.error("Error al obtener stock:", error);
        return { stock_actual: 0 };
      }
    },

    actualizarStockRestante(producto) {
      const cantidad = parseInt(producto.cantidad_ajuste) || 0;
      producto.stock_restante = Math.max(0, producto.stock_actual - cantidad);
    },

    eliminarProducto(index) {
      const producto = this.productosSeleccionados[index];
      this.productosSeleccionados.splice(index, 1);
    },

    limpiarProductosSeleccionados() {
      if (this.productosSeleccionados.length > 0) {
        this.productosSeleccionados = [];
      }
    },

    puedeEnviarFormulario() {
      if (!this.idAlmacenSeleccionado) return false;
      if (this.productosSeleccionados.length === 0) return false;

      const hayCambios = this.productosSeleccionados.some(p => {

        return p.cantidad_ajuste !== null &&
          p.cantidad_ajuste !== '' &&
          p.cantidad_ajuste !== undefined;

      });

      if (!hayCambios) return false;

      const hayErroresStock = this.productosSeleccionados.some(p => {
        const cantidad = parseFloat(p.cantidad_ajuste) || 0;
        const stockActual = parseFloat(p.stock_actual) || 0;
        if (!p.es_aumento && cantidad > p.stock_actual_unidades) return true;
        return false;
      });

      return !hayErroresStock;
    },

    validarFormularioMultiple() {
      if (!this.idAlmacenSeleccionado) return false;
      if (this.productosSeleccionados.length === 0) return false;

      // TODOS los productos deben tener cantidad > 0 y válida
      return this.productosSeleccionados.every(producto => {
        const cantidad = parseInt(producto.cantidad_ajuste) || 0;
        return cantidad > 0 && cantidad <= producto.stock_actual;
      });
    },

    async registrarAjusteMultiple(datosParaEnviar) {
      try {
        this.isLoading = true;

        const response = await axios.post("/ajuste/registrar-multiple", datosParaEnviar, {
          headers: {
            "Content-Type": "application/json",
          },
        });

        this.cerrarModal();
        await this.listarControles(1, "", "");
        this.toastSuccess("Ajuste de Inventario registrado correctamente");
      } catch (error) {
        console.error("Error:", error);
        throw error;
      } finally {
        this.isLoading = false;
      }
    },

    formatearPrecio(precio) {
      if (!precio) return "0.00";
      return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB'
      }).format(precio);
    },

    almacenSeleccionado() { },

    listarAlmacenes(page, buscar, criterio) {
      let me = this;
      var url = `/almacen?page=${page}&buscar=${buscar}&criterio=${criterio}`;
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arrayBuscador = respuesta.almacenes.data;
          me.pagination = respuesta.pagination;
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    async listarControles(page = 1, buscar = "", criterio = "") {
      try {
        this.isLoading = true;

        let url = `/controlinventario?page=${page}&buscar=${buscar}&criterio=${criterio}`;

        if (this.fechaInicio) url += `&fechaInicio=${this.fechaInicio}`;
        if (this.fechaFin) url += `&fechaFin=${this.fechaFin}`;
        if (this.idAlmacen) url += `&idAlmacen=${this.idAlmacen}`;

        const response = await axios.get(url);
        const respuesta = response.data;

        this.arrayDatosControl = respuesta.data;
        this.pagination = respuesta.pagination;

      } catch (error) {
        console.error("Error al listar controles:", error);
      } finally {
        this.isLoading = false;
      }
    },

    generarReporte(tipo) {
      if (!this.fechaInicio || !this.fechaFin) {
        swal("Atención", "Por favor selecciona un rango de fechas válido.", "warning");
        return;
      }

      const fInicio = this.formatDate(this.fechaInicio);
      const fFin = this.formatDate(this.fechaFin);
      const almacen = this.idAlmacen ? this.idAlmacen : '';
      const busqueda = this.buscar ? this.buscar : '';
      let url = `/ajusteinv/reporte/${tipo}?fechaInicio=${fInicio}&fechaFin=${fFin}&idAlmacen=${almacen}&buscar=${busqueda}`;
      window.open(url, '_blank');
    },

    formatDate(date) {
      if (!date) return '';
      if (typeof date === 'string') return date;

      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    },

    filtrarProductos() {
      this.listarProducto(1, this.buscarA, this.criterioA, this.idAlmacenSeleccionado, false, this.proveedorSeleccionado ? this.proveedorSeleccionado.id : null);
    },

    limpiarBusqueda() {
      this.buscarA = "";
      this.resetBusquedaProductos();
    },

    filtrarPorCategoria(categoria) {
      this.categoria = categoria;
      this.listarControles(1, this.buscar, "");
    },

    listarMarca(page, buscar, criterio) {
      let me = this;
      var url =
        "/marca?page=" + page + "&buscar=" + buscar + "&criterio=" + criterio;

      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;

          me.arrayBuscador = respuesta.marcas.data;
          me.pagination = respuesta.pagination;
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    listarMotivo(page, buscar, criterio) {
      let me = this;
      var url =
        "/motivos?page=" + page + "&buscar=" + buscar + "&criterio=" + criterio;
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          console.log(respuesta);
          me.arrayBuscador = respuesta.motivos.data;
          me.pagination = respuesta.pagination;
        })
        .catch(function (error) {
          console.log("ERRORES", error);
        });
    },

    buscarProveedores(event) {
      const query = event.target.value;
      if (!query.trim().length) {
        this.proveedoresFiltrados = [];
        this.mostrarDesplegableProveedor = false;
        return;
      }
      axios.get(`/proveedor/selectNombreProveedor?filtro=${query}`)
        .then(response => {
          this.proveedoresFiltrados = response.data.proveedores;
          this.mostrarDesplegableProveedor = this.proveedoresFiltrados.length > 0;
        })
        .catch(error => {
          console.error("Error al buscar proveedores:", error);
        });
    },

    moverSeleccionProveedor(direccion) {
      if (!this.mostrarDesplegableProveedor || this.proveedoresFiltrados.length === 0) return;
      if (direccion === 'abajo') {
        this.indiceSeleccionadoProveedor = (this.indiceSeleccionadoProveedor + 1) % this.proveedoresFiltrados.length;
      } else if (direccion === 'arriba') {
        this.indiceSeleccionadoProveedor = (this.indiceSeleccionadoProveedor - 1 + this.proveedoresFiltrados.length) % this.proveedoresFiltrados.length;
      }
    },

    seleccionarProveedorConEnter() {
      if (this.indiceSeleccionadoProveedor >= 0 && this.indiceSeleccionadoProveedor < this.proveedoresFiltrados.length) {
        this.seleccionarProveedor(this.proveedoresFiltrados[this.indiceSeleccionadoProveedor]);
      }
    },

    seleccionarProveedor(proveedor) {
      this.proveedorSeleccionado = { id: proveedor.id, nombre: proveedor.nombre };
      this.mostrarDesplegableProveedor = false;
    },

    limpiarProveedorSeleccionado() {
      this.proveedorSeleccionado = { id: null, nombre: '' };
      this.mostrarDesplegableProveedor = false;
      this.actualizarListaProductos();
    },

    resetBusquedaProductos() {
      this.buscarA = '';
      let idproveedor = null;
      if (this.proveedorSeleccionado && this.proveedorSeleccionado.id) {
        idproveedor = this.proveedorSeleccionado.id;
      }
      this.listarProducto(1, '', this.criterioA, this.idAlmacenSeleccionado, false, idproveedor);
    },

    resetBusquedaMotivos() {
      this.buscarA = '';
      this.listarMotivo(1, '', this.criterioA);
    },

    resetFiltros() {
      this.buscar = '';
      this.idAlmacen = null;
      this.establecerFechasPorDefecto();
      this.listarControles(1, this.buscar, this.criterio);
    },

    establecerFechasPorDefecto() {
      const date = new Date();

      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');

      // Primer día del mes
      this.fechaInicio = `${year}-${month}-01`;

      // Último día del mes
      const ultimoDia = new Date(year, date.getMonth() + 1, 0).getDate();
      this.fechaFin = `${year}-${month}-${String(ultimoDia).padStart(2, '0')}`;
    },

    agregarTodosProductos() {
      const productosFiltrados = this.arrayBuscador.filter(
        producto => !this.productosSeleccionados.some(p => p.id === producto.id)
      );

      productosFiltrados.forEach(producto => {
        // Obtener la fecha de vencimiento más cercana
        const fechaVencimientoMasCercana = producto.fechas_vencimiento && producto.fechas_vencimiento.length > 0
          ? producto.fechas_vencimiento.reduce((prev, curr) =>
            new Date(prev.fecha_vencimiento) < new Date(curr.fecha_vencimiento) ? prev : curr
          )
          : null;

        this.productosSeleccionados.push({
          ...producto,
          fecha_vencimiento_seleccionada: fechaVencimientoMasCercana,
          cantidad_ajuste: 0,
          stock_restante: producto.stock_total,
          stock_actual: producto.stock_total,
          stock_real: 0
        });
      });

      this.$toast.add({ severity: 'success', summary: 'Éxito', detail: 'Productos agregados' });
    },

    seleccionarProducto(producto) {

      this.buscarA = '';
      this.filtrarProductos();

      const productoExistente = this.productosSeleccionados.find(
        p => p.id === producto.id
      );

      if (!productoExistente) {

        let stockTotal = parseFloat(producto.stock_total_unidades) || 0;
        let stockCajas = parseFloat(producto.stock_total_cajas) || 0;
        let stockSueltas = parseFloat(producto.stock_total_unidades_sueltas) || 0;

        let stockRealIngresado = parseFloat(producto.stock_ingresado) || 0;

        this.productosSeleccionados.push({
          ...producto,

          cantidad_ajuste: 0,

          stock_actual_unidades: stockTotal,
          stock_actual_cajas: stockCajas,
          stock_actual_unidades_sueltas: stockSueltas,

          modo_ajuste: 'unidad',
          es_paquete: false,
          es_aumento: true,

          stock_real: stockRealIngresado,

          stock_restante: stockTotal
        });

        this.$toast.add({
          severity: 'success',
          summary: 'Agregado',
          detail: 'Producto listo para ajustar',
          life: 1000
        });

      } else {

        this.$toast.add({
          severity: 'warn',
          summary: 'Atención',
          detail: 'El producto ya está en la lista'
        });

      }

      this.enfocarInputBusqueda();

    },

    cambiarPagina(page) {
      let me = this;
      //Actualiza la página actual
      me.pagination.current_page = page;
      //Envia la petición para visualizar la data de esa página
      me.listarControles(page, me.buscar || "", "", me.categoria);
    },

    cambiarPaginaMarca(page, buscar, criterio) {
      let me = this;
      //Actualiza la página actual
      me.pagination.current_page = page;
      me.listarMotivo(page, buscar, criterio);
      //Envia la petición para visualizar la data de esa página
    },
    cambiarPaginaLinea(page, buscar, criterio, idAlmacenSeleccionado) {
      let me = this;
      //Actualiza la página actual
      me.pagination.current_page = page;
      me.listarProducto(page, buscar, criterio, idAlmacenSeleccionado);
      //Envia la petición para visualizar la data de esa página
    },

    // Método para obtener el stock actual 14/03/25
    async obtenerStock() {
      try {
        this.isLoading = true; // Activar loading
        const productoId =
          this.datosFormulario.producto ||
          (this.productoseleccionado ? this.productoseleccionado.id : null);
        const almacenId =
          this.idAlmacenSeleccionado || this.AlmacenSeleccionado;

        if (!productoId || !almacenId) {
          this.stock_actual = 0;
          this.stock_restante = 0;
          return;
        }

        const response = await axios.get("/ajuste-inventario/obtenerStock", {
          params: {
            producto: productoId,
            almacen: almacenId,
          },
        });

        this.stock_actual = response.data.stock_actual;
        this.actualizarStock();
      } catch (error) {
        console.error("Error al obtener el stock:", error);
        console.error(
          "Detalles del error:",
          error.response ? error.response.data : error.message
        );
        this.stock_actual = 0;
        this.stock_restante = 0;
      } finally {
        setTimeout(() => {
          this.isLoading = false;
        }, 500);
      }
    },
    actualizarStock() {
      const cantidad = parseInt(this.datosFormulario.cantidad) || 0;
      this.stock_restante = Math.max(0, this.stock_actual - cantidad);
    },

    calcularDiferencia(producto) {
      if (producto.stock_real === undefined || producto.stock_real === null || producto.stock_real === '') {
        this.$set(producto, 'cantidad_ajuste', 0);
        return;
      }

      const envase = Number(producto.unidad_envase) || 1;
      const inputUsuario = Number(producto.stock_real);
      const stockActualUnidades = Number(producto.stock_actual_unidades);

      let stockFisicoEnUnidades = 0;

      if (producto.modo_ajuste === 'caja') {
        stockFisicoEnUnidades = inputUsuario * envase;
      } else {
        stockFisicoEnUnidades = inputUsuario;
      }

      const diferencia = stockFisicoEnUnidades - stockActualUnidades;

      if (diferencia >= 0) {
        this.$set(producto, 'es_aumento', true);
        this.$set(producto, 'cantidad_ajuste', diferencia);
      } else {
        this.$set(producto, 'es_aumento', false);
        this.$set(producto, 'cantidad_ajuste', Math.abs(diferencia));
      }

      this.$set(producto, 'stock_restante', stockFisicoEnUnidades);
    },

    moverFoco(index, event, tipoCampo) {
      event.preventDefault();
      const totalProductos = this.productosSeleccionados.length;
      let nuevoIndice = index + 1;

      if (nuevoIndice >= totalProductos) {
        nuevoIndice = 0;
      }

      this.$nextTick(() => {
        let inputRef;
        if (tipoCampo === 'stock_real') {
          inputRef = this.$refs[`cantidad_ajuste-${nuevoIndice}`];
        } else if (tipoCampo === 'cantidad_ajuste') {
          inputRef = this.$refs[`stock_real-${nuevoIndice}`];
        }

        if (inputRef && inputRef[0]) {
          const inputElement = inputRef[0].$el.querySelector('input');
          if (inputElement) {
            inputElement.focus();
          }
        }
      });
    },

    calcularPrecioValorMoneda(precio) {
      return (precio * parseFloat(this.monedaPrincipal)).toFixed(2);
    },

    async registrarAjuste(data) {
      let me = this;
      try {
        this.isLoading = true; // Activar loading
        var formulario = new FormData();
        for (var key in data) {
          if (data.hasOwnProperty(key)) {
            formulario.append(key, data[key]);
          }
        }

        const response = await axios.post("/ajuste/registrar", formulario, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        });

        me.idarticulo = response.data.id;
        me.cerrarModal();
        await me.listarControles(1, "", "");
      } catch (error) {
        console.error(error);
        me.toastError("Error al registrar el ajuste de inventario");
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },

    advertenciaInactiva(nombre) {
      swal({
        title: "Opción Inactiva",
        text: "No puede seleccionar esta opción porque está inactiva.",
        type: "warning",
        confirmButtonColor: "#3085d6",
        confirmButtonText: "Aceptar",
        confirmButtonClass: "btn btn-success",
        buttonsStyling: false,
      }).then(() => {
        this.abrirModal2(nombre);
      });
    },

    //#################registro industria############
    registrarMarca() {
      let me = this;

      axios
        .post("/motivo/registrar", {
          nombre: this.nombre,
        })
        .then(function (response) {
          me.cerrarModal3();
          //me.modal3=0;
          me.listarMotivo(1, "", "id");
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    cerrarModal() {
      this.modal = 0;
      this.tituloModal = "";
      this.cantidad = 0;
      this.idtipobaja = "";
      this.producto = "";
      this.buscarA = "";
      this.productosSeleccionados = [];
    },

    abrirModal(modelo, accion, data = []) {
      switch (modelo) {
        case "articulo": {
          switch (accion) {
            case "registrar": {
              this.modal = 1;
              this.tituloModal = "AJUSTE DE INVENTARIO";
              this.tipoAccion = 1;
              this.fotografia = "";

              this.datosFormulario = {
                cantidad: 0,
                idtipobaja: null,
                producto: null,
                idAlmacenSeleccionado: null,
              };

              this.idAlmacenSeleccionado = null;
              this.categoriaSeleccionada = "";
              this.productoseleccionado = [];
              this.buscarA = "";

              this.productosSeleccionados = [];

              this.errores = {};
              break;
            }
          }
        }
      }
    },

    cerrarModal3() {
      this.modal3 = 0;
      this.tituloModal3 = "";
      this.nombre = "";
    },

    abrirModal3(modelo3, accion3, data = []) {
      switch (modelo3) {
        case "Marca": {
          switch (accion3) {
            case "registrarMar": {
              this.modal3 = 1;
              this.tituloModal3 = "Registrar Motivo de Bajass";
              this.nombre = "";
              this.tipoAccion2 = 5;
              break;
            }
            case "actualizar": {
              this.modal3 = 1;
              this.tituloModal3 = "Actualizar marca";
              this.tipoAccion2 = 6;
              this.marca_id = data["id"];
              this.nombre = data["nombre"];
              this.condicion = data["condicion"];
              break;
            }
          }
        }
      }
    },

    abrirModalNuevoMotivo() {
      this.tituloModal3 = "Registrar Nuevo Motivo";
      this.tipoAccion2 = 5;
      this.nombre = "";
      this.modal3 = true;
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
    recuperarIdRol() {
      this.rolUsuario = window.userData.rol;
    },
  },
  async mounted() {
    this.handleResize();
    window.addEventListener("resize", this.handleResize);
    this.establecerFechasPorDefecto();
    try {
      this.isLoading = true;

      await Promise.all([
        this.selectAlmacen(),
        this.recuperarIdRol(),
        this.datosConfiguracion(),
        this.obtenerConfiguracionTrabajo(),
        this.listarControles(1, this.buscar, ""),
      ]);
    } catch (error) {
      console.error("Error en la carga inicial:", error);
      swal("Error", "Error al cargar los datos iniciales", "error");
    } finally {
      this.isLoading = false;
    }
  },
  beforeUnmount() {
    window.removeEventListener("resize", this.handleResize);
  },
};
</script>
<style scoped>
.info-tip {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  padding: 8px 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 12px;
  color: #475569;
}

.info-tip i {
  color: #3b82f6;
  font-size: 14px;
  flex-shrink: 0;
}
.icono-ojo:hover {
  color: #111827;
}

.input-password-container {
  position: relative;
  width: 100%;
  margin-bottom: 10px;
}

.icono-ojo {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  color: #6b7280;
  font-size: 1rem;
}

.titulo-seccion {
  margin-bottom: 15px;
  font-weight: 600;
  color: #374151;
  border-left: 4px solid #3B82F6;
  padding-left: 10px;
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

.grid-llaves {
  display: flex;
  gap: 20px;
}

.col-form {
  width: 35%;
}

.col-tabla {
  width: 65%;
}

.campo {
  margin-bottom: 10px;
  display: flex;
  flex-direction: column;
}

.campo label {
  font-size: 0.8rem;
  margin-bottom: 3px;
}

.resumen-control {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
  gap: 10px;
}

.resumen-item {
  flex: 1;
  text-align: center;
  padding: 3px 1px;
  /* 🔥 menos padding */
  border-radius: 6px;
  color: white;
}

.resumen-item span {
  display: block;
  font-size: 0.9rem;
  font-weight: 600;
  line-height: 0.3;
  /* 🔥 clave */
  margin-bottom: 0px;
  /* 🔥 controla separación */
  margin-top: 6px;
  /* 🔥 controla separación */

}

.resumen-item small {
  font-size: 0.65rem;
  line-height: 1;
  /* 🔥 clave */
  margin: 0;
  /* 🔥 elimina espacio extra */
}

/* 🎨 colores */
.resumen-item.total {
  background: #6c757d;
}

.resumen-item.verificados {
  background: #28a745;
}

.resumen-item.pendientes {
  background: #ffc107;
  color: black;
}

.resumen-item.anulados {
  background: #dc3545;
}

/* 🔹 Botones pequeños */
.btn-sm {
  font-size: 0.8rem;
  padding: 0.4rem 0.7rem;
  border-radius: 6px;
  line-height: 1.1;
}

.btn-sm .pi {
  font-size: 0.75rem;
  margin-right: 4px;
}

/* 🔹 Botones pequeños inputs */
.btn-sm-input {
  font-size: 0.8rem;
  padding: 0.5rem 0.9rem;
  border-radius: 6px;
  line-height: 1.1;
}

.btn-sm-input .pi {
  font-size: 0.65rem;
  margin-right: 4px;
}

.info-control {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 15px;
}

.info-item {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 10px;
  background: #f8f9fa;
  padding: 10px;
  border-radius: 8px;
}

.info-item i {
  font-size: 1.5rem;
  color: #6c757d;
}

.info-item small {
  display: block;
  font-size: 0.75rem;
  color: #6c757d;
}

.info-item strong {
  font-size: 0.9rem;
}

.p-d-flex .p-button-sm {
  margin: 0 1%;
}

.form-control input {
  width: 100%;
  padding: 0.375rem 0.75rem;
  font-size: 1rem;
  line-height: 1.5;
  color: #495057;
  background-color: #fff;
  border: 1px solid #ced4da;
  border-radius: 0.25rem;
}

.form-control input:focus {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.mr-2 {
  margin-right: 10px;
}

#escaneo-camara {
  width: 100%;
  height: 400px;
  background-color: #000;
}

.input-con-desplegable {
  position: relative;
  width: 100%;
}

.input-con-desplegable {
  position: relative;
  width: 100%;
}

.desplegable-simple {
  position: absolute;
  background: white;
  width: 100%;
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #ddd;
  border-radius: 4px;
  z-index: 1000;
  list-style: none;
  padding: 0;
  margin: 0;
}

.desplegable-simple li {
  padding: 8px 10px;
  cursor: pointer;
}

.desplegable-simple li:hover,
.desplegable-simple li.seleccionado {
  background-color: #f0f0f0;
}

/* Estilos para el botón "Agregar Producto" */
.d-flex.align-items-end {
  display: flex;
  align-items: flex-end;
  padding-bottom: 0.5rem;
}

/* Estilos para el Panel */
.panel-header {
  padding: 1rem;
}


.p-sidebar {
  position: fixed !important;
  top: 0 !important;
  bottom: 0 !important;
  right: 0 !important;
  height: 100vh !important;
  width: auto !important;
  max-width: 800px !important;
  background: var(--surface-overlay, #fff);
  box-shadow: -2px 0 12px rgba(0, 0, 0, 0.3);
  z-index: 9992 !important;
  /* 👈 encima del modal (9990) */
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.p-sidebar-mask {
  position: fixed !important;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.55);
  display: flex;
  justify-content: flex-end;
  align-items: stretch;
  pointer-events: auto;
  z-index: 9991 !important;
  transition: background 0.3s ease;
}

.p-sidebar-mask.p-component-overlay {
  pointer-events: auto;
}

body.p-overflow-hidden {
  overflow: hidden !important;
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
  margin: 0;
  padding-left: 5px;
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
}

.responsive-dialog>>>.p-dialog-header {
  padding: 0.75rem 1.5rem;
  font-size: 1.1rem;
}

.responsive-dialog>>>.p-dialog-footer {
  padding: 0.5rem 1.5rem;
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
}

>>>.p-fluid .p-field {
  margin-bottom: 0.25rem;
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

/* Form Grid Responsive */
>>>.p-formgrid.p-grid {
  margin: 0;
}

>>>.p-formgrid .p-field {
  padding: 0.5rem;
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

  >>>.p-formgrid .p-field.p-col-12.p-md-6 {
    width: 100% !important;
    flex: 0 0 100% !important;
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
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.5rem 1rem;
    font-size: 1rem;
  }

  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.4rem 1rem;
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

  >>>.p-formgrid .p-field {
    padding: 0.25rem;
    margin-bottom: 0.4rem !important;
  }

  >>>.p-formgrid .p-field label {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
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
  >>>.p-inputnumber-input,
  >>>.p-multiselect,
  >>>.p-inputtextarea {
    font-size: 0.9rem;
    padding: 0.5rem;
  }

  >>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
    min-width: auto !important;
  }

  .toolbar>>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

  .search-bar .p-inputtext-sm {
    padding: 0.35rem 0.5rem 0.35rem 2.5rem !important;
    font-size: 0.85rem !important;
  }

  .p-sidebar {
    width: 100vw !important;
    max-width: 100% !important;
  }

  /* Buscador */
  .search-bar {
    width: 100%;
    margin-bottom: 1rem;
  }

  .search-bar .p-input-icon-left {
    width: 100%;
  }

  .search-bar .form-control {
    width: 100%;
  }

  /* Botones del toolbar */
  .toolbar {
    flex-direction: column;
    gap: 0.75rem;
  }

  .toolbar>div {
    width: 100%;
    display: flex;
    justify-content: space-between;
  }

  .toolbar .p-d-flex.p-gap-2 {
    justify-content: flex-end;
  }

  /* Botones de Exportar PDF, Cancelar y Procesar Ajuste */
  .row.mt-3 [class*="col-"] {
    flex: 0 0 100%;
    max-width: 100%;
    margin-bottom: 0.75rem;
  }

  .row.mt-3 .d-flex.justify-content-start,
  .row.mt-3 .d-flex.justify-content-end {
    justify-content: center !important;
  }

  .row.mt-3 .p-button {
    width: 100%;
    margin-bottom: 0.5rem;
  }

  .search-and-buttons {
    flex-direction: column !important;
  }

  .search-and-buttons .p-d-flex.p-gap-2 {
    width: 100%;
    justify-content: space-between;
  }

  .search-and-buttons .p-button {
    flex: 1;
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
  }

  .responsive-dialog>>>.p-dialog-header {
    padding: 0.4rem 0.75rem;
    font-size: 0.95rem;
  }

  .responsive-dialog>>>.p-dialog-footer {
    padding: 0.3rem 0.75rem;
    justify-content: flex-end;
  }

  .responsive-dialog>>>.p-dialog-footer .p-button {
    width: auto;
    margin-bottom: 0.25rem;
  }

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

  .toolbar>>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
  }

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

  >>>.p-formgrid .p-field {
    padding: 0.2rem;
    margin-bottom: 0.3rem !important;
  }

  >>>.p-formgrid .p-field label {
    font-size: 0.85rem;
  }

  .required-icon {
    font-size: 0.7rem;
  }

  .optional-icon {
    font-size: 0.55rem;
  }

  >>>.p-inputtext,
  >>>.p-dropdown,
  >>>.p-inputnumber-input,
  >>>.p-multiselect,
  >>>.p-inputtextarea {
    font-size: 0.85rem;
    padding: 0.4rem;
  }

  >>>.p-tag {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
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

.swal2-container {
  position: fixed !important;
  z-index: 999999 !important;
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

body,
html {
  transform: none !important;
  perspective: none !important;
  filter: none !important;
}
</style>
