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
    <Panel class="ingreso-panel">
      <template #header>
        <div class="panel-header">
          <i class="pi pi-shopping-cart panel-icon"></i>
          <h4 class="panel-title">VENTAS</h4>
        </div>
      </template>

      <template v-if="listado == 1">
        <div class="d-flex align-items-center mb-2 justify-content-between">
          <div class="d-flex align-items-center">
            <!--<span :class="['badge',
            estadoFactVisual === 'online' ? 'bg-success' : 'bg-secondary',
            'd-flex', 'align-items-center']"
            style="font-size: 0.85rem; padding: 0.3em 0.7em; min-width: 120px; justify-content: center; gap: 0.4em;">
            <i v-if="cargandoFactVisual" class="pi pi-spin pi-spinner" style="font-size: 1em;"></i>
            <i v-else-if="estadoFactVisual === 'online'" class="pi pi-check" style="font-size: 1em;"></i>
            <i v-else class="pi pi-times" style="font-size: 1em;"></i>
            {{
              cargandoFactVisual ? 'FACTURACION ONLINE' :
                (estadoFactVisual === 'online' ? 'FACTURACION ONLINE' : 'FACTURACION OFFLINE')
            }}
          </span>
          <button @click="ejecutarSecuencial" class="btn btn-light btn-sm ms-2"
            style="margin-left: 8px; padding: 2px 7px; font-size: 0.9em; border-radius: 4px; border: 1px solid #ccc;">
            <i class="pi pi-refresh"></i>
          </button>-->
          </div>
          <div class="d-flex align-items-center gap-1">
            <!-- 
            <button :class="['btn', 'btn-sm', filtroVentasActivo === 'contado' ? 'btn-primary' : 'btn-outline-primary']"
              @click="filtroVentasActivo = 'contado'; listarVenta(1, buscar, criterio, 1)">
              CONTADO
            </button>
            
            <button :class="['btn', 'btn-sm', filtroVentasActivo === 'credito' ? 'btn-primary' : 'btn-outline-primary']"
              @click="filtroVentasActivo = 'credito'; listarVenta(1, buscar, criterio, 2)">
              CRÉDITO
            </button> -->


            <!--<button :class="['btn', 'btn-sm', filtroVentasActivo === 'factura' ? 'btn-primary' : 'btn-outline-primary']"
            style="margin-left: 8px; min-width: 70px; font-size: 0.85em; padding: 2px 10px;"
            @click="filtroVentasActivo = 'factura'; listarVentaF(1, buscar, criterio);">FACTURA</button>
            <button :class="['btn', 'btn-sm', filtroVentasActivo === 'recibo' ? 'btn-primary' : 'btn-outline-primary']"
              style="margin-left: 4px; min-width: 70px; font-size: 0.85em; padding: 2px 10px;"
              @click="filtroVentasActivo = 'recibo'; listarVentaR(1, buscar, criterio);">RECIBO</button>-->
            <button :class="['btn', 'btn-sm', filtroVentasActivo === 'todos' ? 'btn-primary' : 'btn-outline-primary']"
              style="margin-left: 4px; min-width: 70px; font-size: 0.85em; padding: 2px 10px;"
              @click="filtroVentasActivo = 'todos'; listarVenta(1, buscar, criterio);">TODOS</button>

          </div>
        </div>

        <!--<span class="badge bg-secondary" id="comunicacionSiat" style="color: white; display:none;"
        v-show="mostrarElementos">Desconectado</span>
      <span class="badge bg-secondary" id="cuis" style="display:none;" v-show="mostrarElementos">CUIS:
        Inexistente</span>
      <span class="badge bg-secondary" id="cufd" style="display:none;" v-show="mostrarElementos">No existe cufd
        vigente</span>
      <span class="badge bg-secondary" id="direccion" style="display:none;" v-show="mostrarDireccion">No hay dirección
        registrada</span>
      <span class="badge bg-primary" id="cufdValor" style="display:none;" v-show="mostrarCUFD">No hay CUFD</span>-->
        <div class="filtros-superadmin p-mb-3" v-if="idrol == 4" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 15px;">        
          
          <div class="field">
            <label for="filtroSucursal" style="display:block; font-size: 12px; font-weight: bold;">Sucursal</label>
            <select v-model="filtroSucursal" @change="buscarVenta" class="p-inputtext p-component p-inputtext-sm">
              <option value="">Todas las sucursales</option>
              <option v-for="suc in arraySucursales" :key="suc.id" :value="suc.id">
                {{ suc.nombre }}
              </option>
            </select>
          </div>

          <div class="field">
            <label for="fechaInicio" style="display:block; font-size: 12px; font-weight: bold;">Fecha Inicio</label>
            <input type="date" v-model="fechaInicio" @change="buscarVenta" class="p-inputtext p-component p-inputtext-sm" style="height: 35px;" />
          </div>

          <div class="field">
            <label for="fechaFin" style="display:block; font-size: 12px; font-weight: bold;">Fecha Fin</label>
            <input type="date" v-model="fechaFin" @change="buscarVenta" class="p-inputtext p-component p-inputtext-sm" style="height: 35px;" />
          </div>

          <div class="field">
            <Button @click="limpiarFiltros" label="Todos / Limpiar" icon="pi pi-filter-slash" class="p-button-secondary p-button-sm" style="height: 35px;" />
          </div>
        </div>

        <div class="toolbar-container" style="margin-top: 0; padding-top: 0;">
          <div class="search-bar">
            <span class="p-input-icon-left">
              <i class="pi pi-search" />
              <InputText v-model="buscar" @input="buscarVenta" placeholder="Texto a buscar" class="p-inputtext-sm" />
            </span>
          </div>
          <div class="toolbar">
            <Button @click="abrirTipoVenta" :label="mostrarLabel ? 'Nuevo' : ''" icon="pi pi-plus" class="p-button-primary p-button-sm" />
          </div>
        </div>
        <div>
          <DataTable :value="arrayVenta" paginator :rows="10" responsiveLayout="scroll"
            class="p-datatable-gridlines p-datatable-sm tabla-venta">
            <Column header="Opciones">
              <template #body="slotProps">
                <!-- Botón para ver venta -->
                <Button icon="pi pi-eye" @click="verVenta(slotProps.data.id)" class="p-button-sm p-mr-1 btn-mini"
                  :style="{
                    backgroundColor: slotProps.data.descuento_total > 0 ? 'yellow' : 'green',
                    borderColor: slotProps.data.descuento_total > 0 ? 'yellow' : 'green',
                    color: slotProps.data.descuento_total > 0 ? 'black' : 'white',
                  }" v-tooltip.top="'Ver'" />

                <!--<Button v-if="slotProps.data.estado === '1' || slotProps.data.estado === '2'" icon="pi pi-pencil"
                  class="p-button-sm p-mr-1 btn-mini btn-negro" title="Editar" @click="editarVenta(slotProps.data)"
                  v-tooltip.top="'Editar'" />-->

                <!-- Botón eliminar si estado = 1 -->
                <template v-if="slotProps.data.estado === '1' || slotProps.data.estado === '2'">
                  <Button icon="pi pi-trash" v-if="slotProps.data.tipo_comprobante === 'RESIVO'"
                    @click="desactivarVenta(slotProps.data.id)" class="p-button-sm p-button-danger p-mr-1 btn-mini"
                    v-tooltip.top="'Desactivar'" />
                </template>

                <!-- Botones para RESIVO -->
                <Button icon="pi pi-print" v-if="slotProps.data.tipo_comprobante === 'RESIVO'"
                  @click="imprimirResivo(slotProps.data.id, slotProps.data.correo)"
                  class="p-button-sm p-button-primary p-mr-1 btn-mini" v-tooltip.top="'Recibo'" />
                <Button icon="pi pi-print" @click="imprimirRemision(slotProps.data.id, slotProps.data.correo)"
                  class="p-button-sm p-button-help p-mr-1 btn-mini" v-tooltip.top="'Remisión'" />
                <!--
                <template v-if="slotProps.data.idtipo_venta == 2 && slotProps.data.estado === '2'">
                  <Button label="Cobrar" icon="pi pi-wallet" class="p-button-sm p-button-warning p-mr-1 btn-mini"
                    @click="abrirModalCobro(slotProps.data)" />
                </template>
                -->

                <!-- Botones para FACTURA -->
                <template v-if="slotProps.data.tipo_comprobante === 'FACTURA'">
                  <Button icon="pi pi-check" @click="verificarFactura(slotProps.data.cuf, slotProps.data.numeroFactura)"
                    class="p-button-sm p-mr-1 btn-mini" v-tooltip.top="'Verificar'" />
                  <Button icon="pi pi-print" @click="imprimirFactura(slotProps.data.idFactura, slotProps.data.correo)"
                    class="p-button-sm p-button-primary p-mr-1 btn-mini" v-tooltip.top="'Imprimir'" />
                  <Button v-if="slotProps.data.estado === '1'" icon="pi pi-trash"
                    @click="abrirDialogAnularFactura(slotProps.data)"
                    class="p-button-sm p-button-danger p-mr-1 btn-mini" v-tooltip.top="'Anular Factura'" />
                  <!-- 🔸 BOTÓN PAGAR -->
                  <template v-if="slotProps.data.estado == '4' || slotProps.data.facturaValidada == 0"> <Button
                      label="Facturar" icon="pi pi-wallet" class="p-button-warning p-button-sm p-mr-1 btn-mini"
                      @click="abrirModalPago(slotProps.data.id)" />
                  </template>
                </template>

              </template>
            </Column>
            <Column field="nombre_sucursal" header="Sucursal"></Column>
            <Column field="usuario" header="Vendedor"></Column>
            <Column field="fecha_hora" header="Fecha y Hora" class="d-none d-md-table-cell"></Column>
            <Column field="num_comprobante" header="N° de Comprobante" class="d-none d-md-table-cell"></Column>
            <Column field="razonSocial" header="Cliente"></Column>
            <Column field="documentoid" header="Documento" class="d-none d-md-table-cell"></Column>
            <Column header="Total">
              <template #body="slotProps">
                <span class="font-weight-bold text-primary">
                  {{ (slotProps.data.total * parseFloat(monedaVenta[0])).toFixed(2) }}
                  {{ monedaVenta[1] }}
                </span>
              </template>
            </Column>
            <Column field="estado" header="Estado">
              <template #body="slotProps">
                <span v-if="slotProps.data.estado == 1"
                  style="background-color: #198754; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold;">
                  Registrado
                </span>

                <span v-else-if="slotProps.data.estado == 0"
                  style="background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold;">
                  Anulado
                </span>

                <span v-else style="color: gray;">
                  -
                </span>
              </template>
            </Column>

          </DataTable>
          <!--<Paginator :rows="10" :totalRecords="pagination.total" :first="(pagination.current_page - 1) * 10"
            @page="onPageChange" />-->
        </div>
      </template>
      <template v-else-if="listado == 2">
        <div class="detalle-venta-pro">
          <!-- ENCABEZADO -->
          <div class="detalle-header-pro">
            <div class="detalle-section-pro">
              <h3 class="detalle-titulo-pro">Detalle de Comprobante</h3>
              <p class="detalle-subtitulo-pro">Resumen completo de la venta registrada</p>
            </div>
            <div class="detalle-meta-pro">
              <div>
                <span class="label-pro">Tipo Comprobante</span>
                <p class="valor-pro">{{ tipo_comprobante }}</p>
              </div>
              <div>
                <span class="label-pro">N° Comprobante</span>
                <p class="valor-pro">#{{ num_comprobante }}</p>
              </div>
            </div>
          </div>

          <!-- CLIENTE -->
          <div class="detalle-cliente-pro">
            <span class="label-pro">Cliente</span>
            <p class="valor-pro">{{ cliente }}</p>
          </div>

          <!-- TABLA DE ARTÍCULOS -->
          <div class="detalle-tabla-pro">
            <DataTable :value="arrayDetalle" class="p-datatable-sm p-datatable-gridlines">
              <Column field="codigo" header="Codigo"></Column>
              <Column field="articulo" header="Producto"></Column>
              <!--<Column field="unidad_envase" header="Cant x Caja">
                <template #body="slotProps">
                  <span v-if="slotProps.data.modo_venta === 'caja'">
                    {{ slotProps.data.unidad_envase }}
                  </span>

                  <span v-else>-</span>
                </template>
</Column>-->

              <Column header="Precio Unit.">
                <template #body="slotProps">
                  {{ (slotProps.data.precio * parseFloat(monedaVenta[0])).toFixed(2) }}
                  {{ monedaVenta[1] }}
                </template>
              </Column>
              <Column field="cantidad" header="Cant Vendida">
                <template #body="slotProps">
                  <span :style="{
                    backgroundColor:
                      slotProps.data.modo_venta === 'caja'
                        ? '#0d6efd'
                        : slotProps.data.modo_venta === 'docena'
                          ? '#6f42c1'
                          : '#198754',
                    color: 'white',
                    padding: '4px 8px',
                    borderRadius: '4px',
                    fontWeight: 'bold'
                  }">
                    {{
                      slotProps.data.cantidad + ' ' +
                      (
                        slotProps.data.modo_venta === 'caja'
                          ? (slotProps.data.cantidad == 1 ? 'caja' : 'cajas')
                          : slotProps.data.modo_venta === 'docena'
                            ? (slotProps.data.cantidad == 1 ? 'docena' : 'docenas')
                            : (slotProps.data.cantidad == 1 ? 'unidad' : 'unidades')
                      )
                    }}
                  </span>
                </template>
              </Column>
              <Column header="Subtotal sin Descuento">
                <template #body="slotProps">
                  {{ (slotProps.data.subtotal_sin_descuento * parseFloat(monedaVenta[0])).toFixed(2) }}
                  {{ monedaVenta[1] }}
                </template>
              </Column>

              <Column header="Descuento por Producto">
                <template #body="slotProps">
                  {{ slotProps.data.descuento }}
                  {{ monedaVenta[1] }}
                </template>
              </Column>

              <Column header="Descuento Total">
                <template #body="slotProps">
                  {{ slotProps.data.descuento_total_producto }}
                  {{ monedaVenta[1] }}
                </template>
              </Column>

              <Column field="subtotal" header="Subtotal">
                <template #body="slotProps">
                  {{ (slotProps.data.subtotal * parseFloat(monedaVenta[0])).toFixed(2) }}
                  {{ monedaVenta[1] }}
                </template>
              </Column>
            </DataTable>
          </div>
          <div v-if="idtipo_venta == 2 && cuotas && cuotas.length > 0" class="mt-4">
            <h4 class="mb-3">Cuotas del Crédito</h4>
            <!--
            <Button
              label="Modificar monto de cuota"
              icon="pi pi-pencil"
              class="p-button-sm p-button-warning mb-3"
              @click="toggleEditarCuotas"
            />
            -->
            <DataTable :value="cuotas" class="p-datatable-sm p-datatable-gridlines">

              <Column field="numero_cuota" header="# Cuota"></Column>

              <Column field="fecha_pago" header="Fecha Pago">
                <template #body="slotProps">

                  <!-- MODO EDICIÓN -->
                  <div v-if="editarCuotas && slotProps.data.idtipo_pago !== 5">
                    <input type="date" class="form-control form-control-sm" style="max-width: 160px"
                      v-model="slotProps.data.fecha_pago" />
                  </div>


                  <!-- MODO LECTURA -->
                  <div v-else>
                    {{ slotProps.data.fecha_pago ? slotProps.data.fecha_pago.substring(0, 10) : '-' }}
                  </div>

                </template>
              </Column>
              <Column field="precio_cuota" header="Monto Cuota">
                <template #body="slotProps">

                  <!-- MODO EDICIÓN -->
                  <div v-if="editarCuotas && slotProps.data.idtipo_pago !== 5">
                    <InputNumber v-model="slotProps.data.precio_cuota" mode="decimal" :min="0" :minFractionDigits="2"
                      :maxFractionDigits="2" class="p-inputtext-sm w-8rem"
                      :class="{ 'p-invalid': cuotaExcedida(slotProps.data) }"
                      @blur="validarMontoCuota(slotProps.data)" />
                    <span class="ms-1">{{ monedaVenta[1] }}</span>
                    <small v-if="cuotaExcedida(slotProps.data)" class="text-danger d-block mt-1">
                      Monto excede el saldo
                    </small>
                  </div>

                  <!-- MODO LECTURA -->
                  <div v-else>
                    {{ slotProps.data.precio_cuota }} {{ monedaVenta[1] }}
                  </div>

                </template>
              </Column>

              <Column field="saldo_restante" header="Saldo Restante">
                <template #body="slotProps">
                  {{ slotProps.data.saldo_restante }} {{ monedaVenta[1] }}
                </template>
              </Column>

              <Column field="estado" header="Estado">
                <template #body="slotProps">
                  <!-- Liquidación con descuento -->
                  <span v-if="slotProps.data.idtipo_pago === 5 && slotProps.data.descuento > 0" class="badge bg-info">
                    Liquidado {{ slotProps.data.descuento }} {{ monedaVenta[1] }}
                  </span>

                  <!-- Cancelado: última cuota que liquida todo (saldo = 0) -->
                  <span
                    v-else-if="slotProps.data.saldo_restante == 0 || slotProps.data.saldo_restante === '0' || slotProps.data.saldo_restante === '0.00'"
                    class="badge bg-primary">
                    Cancelado
                  </span>

                  <!-- Pagado: cuota pagada pero aún queda saldo -->
                  <span v-else-if="slotProps.data.estado === 'Pagado' || slotProps.data.saldo_restante > 0"
                    class="badge bg-success">
                    Pagado
                  </span>

                  <!-- Otros estados -->
                  <span v-else class="badge bg-warning">
                    {{ slotProps.data.estado }}
                  </span>
                </template>
              </Column>
              <Column header="Tipo de Pago">
                <template #body="slotProps">

                  <!-- MODO EDICIÓN -->
                  <div v-if="editarCuotas" class="d-flex flex-column gap-1">

                    <!-- SELECT TIPO DE PAGO -->
                    <Dropdown v-if="slotProps.data.idtipo_pago !== 5" v-model="slotProps.data.idtipo_pago"
                      :options="tiposPagoOptions" optionLabel="label" optionValue="value" placeholder="Seleccione"
                      class="p-inputtext-sm" />

                    <!-- TEXTO FIJO CUANDO ES LIQUIDACIÓN -->
                    <span v-else class="badge bg-info p-2 text-center">
                      🔒 Liquidación
                    </span>
                    <Dropdown v-if="slotProps.data.idtipo_pago === 7" v-model="slotProps.data.bancoSeleccionado"
                      :options="bancosOptions" dataKey="id" placeholder="Seleccione un banco"
                      class="w-full custom-dropdown mt-1" @change="onBancoSelectCuota(slotProps.data)">
                      <!-- OPTION -->
                      <template #option="slotPropsOpt">
                        <div class="banco-opcion">
                          <img :src="getBankUrl(slotPropsOpt.option.nombre_banco)" class="banco-logo" />
                          <div class="banco-detalles">
                            <div class="cuenta">{{ slotPropsOpt.option.nombre_cuenta }}</div>
                            <div class="numero">{{ slotPropsOpt.option.numero_cuenta }}</div>
                            <div class="tipo">{{ slotPropsOpt.option.tipo_cuenta }}</div>
                          </div>
                        </div>
                      </template>

                      <!-- VALUE -->
                      <template #value="slotPropsVal">
                        <div v-if="slotPropsVal.value" class="banco-value">
                          <img :src="getBankUrl(slotPropsVal.value.nombre_banco)" class="banco-logo" />
                          <span class="cuenta">{{ slotPropsVal.value.nombre_cuenta }}</span>
                        </div>
                        <span v-else>-</span>
                      </template>
                    </Dropdown>


                  </div>

                  <!-- MODO LECTURA -->
                  <div v-else>
                    <span v-if="slotProps.data.idtipo_pago === 1" class="badge bg-success">
                      💵 Efectivo
                    </span>

                    <span v-else-if="slotProps.data.idtipo_pago === 5" class="badge bg-info">
                      🔒 Liquidación
                    </span>

                    <span v-else-if="slotProps.data.idtipo_pago === 7" class="badge bg-primary">
                      🏦 {{ slotProps.data.nombre_cuenta || 'Cuenta bancaria' }}
                    </span>

                    <span v-else class="badge bg-dark">
                      No definido
                    </span>
                  </div>

                </template>
              </Column>
              <!-- COLUMNA ELIMINAR (solo en modo edición) -->
              <Column v-if="editarCuotas" header="Eliminar" style="width: 80px; text-align: center;">
                <template #body="slotProps">
                  <Button v-if="slotProps.data.idtipo_pago !== 5" icon="pi pi-trash"
                    class="p-button-sm p-button-danger btn-mini" title="Eliminar cuota"
                    @click="abrirModalEliminarCuota(slotProps.data)" />
                  <span v-else class="text-muted">-</span>
                </template>
              </Column>
            </DataTable>
            <Button v-if="editarCuotas" label="Guardar cambios" icon="pi pi-check"
              class="p-button-sm p-button-success mt-3" @click="guardarMontosCuotas" />
          </div>

          <!-- RESUMEN DE TOTALES -->
          <div class="detalle-resumen-pro">
            <div class="resumen-linea-pro">
              <span>SubTotal General</span>
              <strong>{{ (subtotalVista * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}</strong>
            </div>
            <div class="resumen-linea-pro">
              <span>Descuento Adicional</span>
              <strong>{{ (descuentoAdicionalvista * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1]
              }}</strong>
            </div>
            <div class="resumen-linea-pro total-final-pro">
              <span>Total Neto</span>
              <strong>{{ (total * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}</strong>
            </div>
          </div>

          <!-- BOTÓN -->
          <div class="detalle-footer-pro">
            <Button @click="ocultarDetalle()" label="Cerrar" icon="pi pi-times" severity="danger"
              class="p-button-danger p-button-sm btn-mini" />
          </div>
        </div>
      </template>
    </Panel>

    <template>
      <Dialog :visible.sync="modal2" :containerStyle="dialogContainerStyle" :modal="true" :closable="false"
        :closeOnEscape="false" class="responsive-dialog">
        <template #header>
          <div class="modal-header">
            <h5 class="modal-title">Detalle de Venta</h5>
            <button class="close-button" @click="modal2 = false">
              <i class="pi pi-times"></i>
            </button>
          </div>
        </template>
        <div class="p-fluid">


          <div v-if="step === 2" class="step-content p-fluid">
            <div class="p-grid p-formgrid align-items-start">

              <div :class="tipoAccion2 === 2 ? 'p-col-12' : 'p-col-12 p-md-6 d-flex flex-column justify-content-start'">

                <h5 class="mb-3" style="font-size: 1.5rem; font-weight: bold; text-align: center; margin-bottom: 1rem;">
                  DATOS DEL CLIENTE
                </h5>

                <div style="width: 100%; padding-top: 0.5rem;">

                  <div class="p-mb-3" style="margin-bottom: 1.5rem; position: relative;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                      <label class="label-input">
                        Documento del Cliente <span class="text-required">*</span>
                      </label>
                      <button type="button" class="btn btn-sm btn-outline-primary" @click="alternarTipoDocumento"
                        style="font-size: 0.8rem; padding: 2px 8px; border-radius: 6px;">
                        {{ tipoDocumentoTexto }}
                      </button>
                    </div>
                    <div class="input-con-desplegable">
                      <div class="p-inputgroup">
                        <InputText ref="inputDocumentoCliente" id="documento" v-model="documento" class="input-full"
                          @input="buscarClientePorDocumento" @keydown.down="moverSeleccionCliente('abajo')"
                          @keydown.up="moverSeleccionCliente('arriba')"
                          @keydown.enter="seleccionarClienteConEnter($event)"
                          placeholder="Buscar cliente por documento o nombre" autocomplete="off"
                          style="margin-top: 2px" />
                      </div>

                      <ul v-if="mostrarDesplegableCliente" class="desplegable-simple"
                        style="position: absolute; z-index: 1000; background: white; border: 1px solid #ccc; width: 100%; max-height: 200px; overflow-y: auto; margin-top: 2px; border-radius: 4px; padding: 0;">
                        <li v-for="(cliente, index) in resultadosClientes" :key="cliente.id"
                          @click="seleccionarCliente(cliente)"
                          :class="{ seleccionado: index === indiceSeleccionadoCliente }"
                          style="padding: 8px; cursor: pointer; list-style: none;">
                          {{ cliente.nombre }} - {{ cliente.num_documento }}
                        </li>
                      </ul>
                    </div>
                  </div>

                  <div class="p-mb-3" style="margin-bottom: 1.5rem; position: relative;">
                    <label class="label-input">
                      Razón Social <span class="text-required">*</span>
                    </label>
                    <span class="p-float-label">
                      <InputText ref="inputNombreCliente" id="nombreCliente" v-model="nombreCliente" class="input-full"
                        :disabled="!nombreClienteEditable" @input="mensajeRazonSocial = false" autocomplete="off"
                        style="margin-top: 2px" />
                    </span>
                    <span v-if="nombreClienteEditable && (!nombreCliente || nombreCliente.trim() === '')"
                      style="color: #FFA500; font-size: 0.75rem; position: absolute; top: 70%; left: 12px; transform: translateY(-50%); pointer-events: none;">
                      Ingrese la razón social del cliente
                    </span>
                  </div>

                  <div class="p-mb-3" style="margin-bottom: 1.5rem;">
                    <label class="optional-field">
                      <i class="pi pi-phone optional-icon"></i>
                      Teléfono <span class="optional-tag">Opcional</span>
                    </label>
                    <span class="p-float-label">
                      <InputText id="telefonoCliente" v-model="telefonoCliente" :disabled="!telefonoClienteEditable"
                        autocomplete="off" type="tel" maxlength="15" class="input-full" style="margin-top: 2px" />
                    </span>
                  </div>

                  <div class="p-mb-3" style="margin-bottom: 1.5rem; position: relative;">
                    <label class="optional-field">
                      <i class="pi pi-map-marker optional-icon"></i>
                      Ubicación <span class="optional-tag">Opcional</span>
                    </label>

                    <div class="input-con-desplegable">
                      <InputText v-model="direccionCliente" :disabled="!direccionClienteEditable" class="input-full"
                        :class="{ 'p-invalid': errores.direccion }" placeholder="Ej: Av. Heroinas esq. Ayacucho"
                        @input="buscarDireccion" @keydown.down="moverSeleccionDireccion('abajo')"
                        @keydown.up="moverSeleccionDireccion('arriba')" @keydown.enter="seleccionarDireccionEnter"
                        style="margin-top: 2px" />

                      <ul v-if="mostrarDesplegableDireccion" class="desplegable-simple"
                        style="position: absolute; z-index: 1000; background: white; border: 1px solid #ccc; width: 100%; max-height: 200px; overflow-y: auto; margin-top: 2px; border-radius: 4px; padding: 0;">
                        <li v-for="(dir, index) in direccionesFiltradas" :key="dir" @click="seleccionarDireccion(dir)"
                          :class="{ seleccionado: index === indiceDireccionSeleccionada }"
                          style="padding: 8px; cursor: pointer; list-style: none;">
                          {{ dir }}
                        </li>
                      </ul>
                    </div>
                  </div>

                  <div v-if="tipoAccion2 === 2" class="d-flex justify-content-center mt-3">
                    <Button label="Actualizar Venta" icon="pi pi-check" class="p-button-success p-button-sm"
                      @click="actualizarVenta" />
                  </div>

                </div>
              </div>

              <div v-if="tipoAccion2 !== 2" class="p-col-12 p-md-6 d-flex flex-column justify-content-start">



                <div v-if="tipoVenta === 'contado'">
                  <div class="d-flex justify-content-center mb-1">
                    <div class="form-group">
                      <div class="btn-group">
                        <button class="btn btn-primary" @click="opcionPago = 'efectivo'">
                          <i class="fa fa-money mr-2" aria-hidden="true"></i>
                          Efectivo
                        </button>
                        <button class="btn btn-primary" @click="opcionPago = 'qr'">
                          <i class="fa fa-qrcode mr-2" aria-hidden="true"></i>
                          QR
                        </button>
                      </div>
                    </div>
                  </div>

                  <div v-if="opcionPago === 'efectivo'" class="mt-2">
                    <div class="card mb-2" style="font-size: 0.875rem;">
                      <div class="card-body">
                        <div class="form-group mb-3">
                          <label for="descuentoTotal" class="font-weight-bold">
                            <span class="mr-2">Bs</span> Descuento al Total:
                          </label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text">{{ monedaVenta[1] }}</span>
                            </div>
                            <input type="number" class="form-control" id="descuentoTotal" v-model="descuentoAdicional"
                              placeholder="Ingrese el descuento"
                              :disabled="permitir_descuento != 1 && !habilitacionpromocion" />
                          </div>
                        </div>

                        <div class="form-group mb-3">
                          <label for="montoEfectivo" class="font-weight-bold">
                            <i class="fa fa-money mr-2"></i> Monto Recibido:
                          </label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text">{{ monedaVenta[1] }}</span>
                            </div>
                            <input type="number" class="form-control" id="montoEfectivo" v-model="recibido"
                              placeholder="Ingrese el monto recibido" />
                          </div>
                        </div>

                        <div class="form-group">
                          <label for="cambioRecibir" class="font-weight-bold">
                            <i class="fa fa-exchange mr-2"></i> Cambio a Entregar:
                          </label>
                          <input type="text" class="form-control bg-light" id="cambioRecibir" :value="(
                            recibido - calcularTotal * parseFloat(monedaVenta[0])
                          ).toFixed(2)" readonly />
                        </div>
                      </div>
                    </div>

                    <div class="card" style="font-size: 0.75rem;">
                      <div class="card-body">
                        <h5 class="mb-2 text-center text-md-left" style="font-size: 0.95rem;">
                          Detalle de Venta
                        </h5>

                        <div v-if="saldoFavorCliente > 0" class="alert alert-success py-1 mb-2"
                          style="font-size: 0.8rem;">
                          Saldo a Favor: {{ saldoFavorCliente.toFixed(2) }}
                        </div>

                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                          <div class="d-flex align-items-center">
                            <i class="fa fa-money mr-2" style="font-size: 0.75rem;"></i>
                            <span style="font-size: 0.85rem;">Total a Pagar:</span>
                            <span class="font-weight-bold ml-2 h5 mb-0" style="font-size: 0.95rem;">
                              {{ (Math.max(0, (calcularTotal * parseFloat(monedaVenta[0])) -
                                saldoFavorCliente)).toFixed(2) }}
                              {{ monedaVenta[1] }}
                            </span>
                          </div>

                          <div class="d-flex flex-column flex-md-column mt-2 mt-md-0 text-center">
                            <div class="d-flex flex-row justify-content-center mb-1">
                              <!--<button class="btn btn-light mr-2" @click="aplicarDescuentoRecibo(1, 1)">
                                <img src="/img/logoPrincipal.png" alt="Recibo" class="img-fluid" style="height: 24px;" />
                              </button>-->
                              <button type="button" @click="aplicarDescuentoRecibo(1, 1)" class="btn btn-success">
                                <i class="fa fa-check mr-2"></i> Registrar Pago
                              </button>
                            </div>
                            <!--<small style="color: #777; font-size: 0.75rem;">
                              Click en el botón verde para Factura o en la imagen para Recibos
                            </small>-->
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-else-if="opcionPago === 'qr'" class="mt-2">
                    <div class="card">
                      <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                          <div class="mb-3 mb-md-0">
                            <h5 class="mb-1" style="font-size: 1rem;">
                              🧾 Detalle de Venta
                            </h5>
                            <label class="mb-0 text-muted">Total a pagar:</label>
                            <div class="font-weight-bold text-primary" style="font-size: 1.1rem;">
                              {{ (Math.max(0, (calcularTotal * parseFloat(monedaVenta[0])) -
                                saldoFavorCliente)).toFixed(2) }}
                              {{ monedaVenta[1] }}
                            </div>
                          </div>

                          <button class="btn btn-primary" @click="generarQr">
                            <i class="fa fa-qrcode mr-2"></i> Generar QR
                          </button>
                        </div>

                        <div class="d-flex flex-row flex-md-row mt-2 mt-md-0 justify-content-center">
                          <!--<button class="btn btn-light mr-2" @click="aplicarDescuentoRecibo(1, 7)">
                            <img src="/img/logoPrincipal.png" alt="Recibo" class="img-fluid" style="height: 24px;" />
                          </button>-->
                          <button type="button" @click="aplicarDescuentoRecibo(1, 7)" class="btn btn-success">
                            <i class="fa fa-check mr-2"></i> Registrar Pago
                          </button>
                        </div>
                        <div class="text-center mt-1">
                          <!--<small style="color: #777; font-size: 0.75rem;">
                            Click en el botón verde para Factura o en la imagen para Recibos
                          </small>-->
                        </div>
                      </div>
                    </div>
                  </div>

                </div>

                <div v-else-if="opcionPago === 'qr'" style="margin-top: -5px;">
                  <div class="card mb-2">
                    <div class="card-body">
                      <h5 class="mb-3" style="font-size: 0.95rem;">
                        Detalle de Venta
                      </h5>

                      <div v-if="saldoFavorCliente > 0" class="alert alert-success py-2 mb-2"
                        style="font-size: 0.8rem;">
                        <i class="fa fa-gift mr-1"></i>
                        <strong>Saldo a Favor:</strong> {{ saldoFavorCliente.toFixed(2) }} {{ monedaVenta[1] }}
                      </div>

                      <div class="d-flex flex-column">
                        <div class="d-flex align-items-center">
                          <i class="fa fa-money mr-2" style="font-size: 0.75rem;"></i>
                          <span style="font-size: 0.85rem;">Total a Pagar:</span>
                          <span class="font-weight-bold ml-2" style="font-size: 0.95rem;">{{
                            Math.max(0, (calcularTotal * parseFloat(monedaVenta[0])) - saldoFavorCliente).toFixed(2)
                          }}
                            {{ monedaVenta[1] }}</span>
                        </div>
                        <small v-if="saldoFavorCliente > 0" class="text-muted">
                          (Subtotal: {{ (calcularTotal * parseFloat(monedaVenta[0])).toFixed(2) }} - Saldo a favor:
                          {{
                            saldoFavorCliente.toFixed(2) }})
                        </small>
                      </div>
                    </div>
                  </div>


                  <div class="d-flex flex-wrap justify-content-center">

                    <button type="button" @click="aplicarDescuentoRecibo(1, 7)" class="btn btn-success">
                      <i class="fa fa-check mr-2"></i> Registrar Pago
                    </button>
                  </div>
                </div>
              </div>
              <div v-if="tipoVenta === 'credito'">
                <div class="d-flex justify-content-center mb-3">
                  <div class="btn-selector">
                    <button class="btn-selector-btn" :class="{ active: opcionPago === 'efectivo' }"
                      @click="opcionPago = 'efectivo'">
                      <i class="fa fa-money mr-2"></i>
                      Efectivo
                    </button>

                    <button class="btn-selector-btn" :class="{ active: opcionPago === 'qr' }"
                      @click="opcionPago = 'qr'">
                      <i class="fa fa-qrcode mr-2"></i>
                      Banco
                    </button>
                  </div>
                </div>
                <div v-if="opcionPago === 'efectivo'" class="mt-2">
                  <div class="card mb-2" style="font-size: 0.8rem;">
                    <div class="card-body d-flex flex-column">

                      <!-- 🔹 Descuento al Total -->
                      <div v-if="permitir_bonificacion == 1 || permitir_descuento == 1" class="form-group mb-3">
                        <label for="descuentoTotal" class="label-input">
                          <i class="fa fa-percent mr-1"></i> Descuento al Total
                        </label>

                        <div class="input-group input-group-sm custom-input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text addon-small">%</span>
                          </div>

                          <input type="number" id="descuentoTotal" class="form-control input-uniforme"
                            v-model="descuentoAdicional" :disabled="permitir_descuento != 1 && !habilitacionpromocion"
                            placeholder="Ingrese el % de descuento" min="0" max="100" />
                        </div>
                      </div>

                      <div class="form-group mb-3">
                        <label for="montoEfectivo" class="label-input">
                          <i class="fa fa-money mr-1"></i> Monto Recibido
                        </label>
                        <div class="input-group input-group-sm custom-input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text addon-small">{{ monedaVenta[1] }}</span>
                          </div>
                          <input type="number" id="montoEfectivo" class="form-control input-uniforme" v-model="recibido"
                            placeholder="Ingrese el monto recibido" />
                        </div>
                      </div>

                      <div class="form-group mb-0">
                        <label for="cambioRecibir" class="label-input">
                          <i class="fa fa-exchange mr-1"></i> Saldo Total
                        </label>
                        <input type="text" id="cambioRecibir" class="form-control input-cambio bg-light"
                          :value="Math.max(0, (calcularTotal * parseFloat(monedaVenta[0])) - saldoFavorCliente) - recibido"
                          readonly />
                      </div>
                    </div>
                  </div>

                  <div class="card" style="font-size: 0.75rem;">
                    <div class="card-body">
                      <h5 class="mb-2 text-center text-md-left" style="font-size: 0.95rem;">
                        Detalle de Venta
                      </h5>

                      <div v-if="saldoFavorCliente > 0" class="alert alert-success py-2 mb-2"
                        style="font-size: 0.8rem;">
                        <i class="fa fa-gift mr-1"></i>
                        <strong>Saldo a Favor:</strong> {{ saldoFavorCliente.toFixed(2) }} {{ monedaVenta[1] }}
                      </div>

                      <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                        <div class="d-flex flex-column">
                          <div class="d-flex align-items-center">
                            <i class="fa fa-money mr-2" style="font-size: 0.75rem;"></i>
                            <span style="font-size: 0.85rem;">Total a Pagar:</span>
                            <span class="font-weight-bold ml-2 h5 mb-0" style="font-size: 0.95rem;">{{
                              Math.max(0, (calcularTotal * parseFloat(monedaVenta[0])) - saldoFavorCliente).toFixed(2)
                            }}
                              {{ monedaVenta[1] }}</span>
                          </div>
                          <small v-if="saldoFavorCliente > 0" class="text-muted">
                            (Subtotal: {{ (calcularTotal * parseFloat(monedaVenta[0])).toFixed(2) }} - Saldo a favor:
                            {{
                              saldoFavorCliente.toFixed(2) }})
                          </small>
                        </div>
                        <div class="d-flex flex-row flex-md-row mt-2 mt-md-0">

                          <button type="button" @click="aplicarDescuentoRecibo(2, 1)" class="btn btn-success">
                            <i class="fa fa-check mr-2"></i> Registrar Pago
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-else-if="opcionPago === 'qr'" style="margin-top: -5px;">
                  <div class="card mb-2">
                    <div class="card-body">
                      <h5 class="mb-3" style="font-size: 0.95rem;">
                        Detalle de Venta
                      </h5>

                      <div v-if="saldoFavorCliente > 0" class="alert alert-success py-2 mb-2"
                        style="font-size: 0.8rem;">
                        <i class="fa fa-gift mr-1"></i>
                        <strong>Saldo a Favor:</strong> {{ saldoFavorCliente.toFixed(2) }} {{ monedaVenta[1] }}
                      </div>

                      <div class="d-flex flex-column">
                        <div class="d-flex align-items-center">
                          <i class="fa fa-money mr-2" style="font-size: 0.75rem;"></i>
                          <span style="font-size: 0.85rem;">Total a Pagar:</span>
                          <span class="font-weight-bold ml-2" style="font-size: 0.95rem;">{{
                            Math.max(0, (calcularTotal * parseFloat(monedaVenta[0])) - saldoFavorCliente).toFixed(2)
                          }}
                            {{ monedaVenta[1] }}</span>
                        </div>
                        <small v-if="saldoFavorCliente > 0" class="text-muted">
                          (Subtotal: {{ (calcularTotal * parseFloat(monedaVenta[0])).toFixed(2) }} - Saldo a favor:
                          {{
                            saldoFavorCliente.toFixed(2) }})
                        </small>
                      </div>
                    </div>
                  </div>

                  <!--<button class="btn btn-primary mb-2" @click="generarQr">Generar QR</button>-->

                  <div class="d-flex flex-wrap justify-content-center">
                    <button class="btn btn-light mr-2 mb-2 mb-md-0" @click="aplicarDescuentoRecibo(2)">
                      <img src="/img/logoPrincipal.png" alt="Botón Imagen" class="img-fluid" style="height: 24px;">
                    </button>
                    <button type="button" @click="aplicarDescuentoRecibo(2, 7)" class="btn btn-success">
                      <i class="fa fa-check mr-2"></i> Registrar Pago
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        </div>
        <InputText v-model="idcliente" type="hidden" />
        <InputText v-model="tipo_documento" type="hidden" />
        <InputText v-model="complemento_id" type="hidden" />
        <InputText v-model="usuarioAutenticado" type="hidden" />
        <InputText v-model="puntoVentaAutenticado" type="hidden" />
        <InputText v-model="email" type="hidden" />
        <InputText v-model="num_comprob" type="hidden" disabled />
        </div>

        <div v-if="step === 1" class="step-content">
          <div class="p-fluid p-grid">


            <div class="p-col-12 p-md-4 mb-0 pb-0" style="margin-top: -17px !important;">
              <label for="tipo_documento" class="label-input">
                Almacén de trabajo <span class="text-required">*</span>
              </label>
              <Dropdown v-model="selectedAlmacen" :options="arrayAlmacenes" optionLabel="nombre_almacen"
                optionValue="id" placeholder="Seleccione" :disabled="arrayDetalle.length > 0"
                @change="getAlmacenProductos" class="dropdown-full input-height-fix" />
            </div>

            <div class="p-col-12 p-md-8 mb-0 pb-0">
              <label for="nombre" class="label-input"
                style="display: flex; justify-content: space-between; align-items: center;">
                Buscar Producto
                <span style="font-size: 0.85rem; color: #0d6efd;">
                  Combos/Ofertas Botón azul
                </span>
              </label>
              <div class="input-con-desplegable">
                <div class="p-inputgroup input-height-fix">
                  <InputText ref="inputCodigo" v-model="codigo" placeholder="Buscar por nombre, código o alfanumérico"
                    class="input-full" :disabled="!idAlmacen" @input="buscarArticulo"
                    @keydown.down="moverSeleccion('abajo')" @keydown.up="moverSeleccion('arriba')"
                    @keydown.enter="seleccionarConEnter" />
                  <Button icon="pi pi-search" class="btn-search-fix" @click="abrirModal" />
                </div>

                <ul v-if="mostrarDesplegable" class="desplegable-simple" style="max-height: 300px; overflow-y: auto;">
                  <li v-for="(articulo, index) in resultadosBusqueda" :key="articulo.id"
                    @click="seleccionarArticulo(articulo)" :class="{
                      'seleccionado': index === indiceSeleccionado,
                      'item-sin-stock': articulo.saldo_stock <= 0
                    }" style="padding: 8px 12px; border-bottom: 1px solid #eee; cursor: pointer;">

                    <div class="item-contenido" style="line-height: 1.4; font-size: 0.85rem;">
                      <span style="font-weight: bold; color: #333; font-size: 0.9rem;">
                        {{ articulo.nombre }}
                      </span>
                      <span style="color: #bbb; margin: 0 5px;">/</span>
                      <span class="text-muted">
                        {{ articulo.nombre_categoria || "Sin Cat." }}
                      </span>
                      <span style="color: #bbb; margin: 0 5px;">/</span>
                      <span class="text-primary font-weight-bold">
                        {{ Number(parseFloat(articulo.precio_uno).toFixed(2)) }} Bs.
                      </span>
                      <span style="color: #bbb; margin: 0 5px;">/</span>
                      <span
                        :class="articulo.saldo_stock > 0 ? 'text-success font-weight-bold' : 'text-danger font-weight-bold'">
                        Stock: {{ articulo.saldo_stock }}
                      </span>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div v-if="arrayDetalle.length === 0"
            style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 250px; padding: 1rem;">
            <i class="pi pi-shopping-cart"
              style="font-size: 2.5rem; color: #ccc; margin-bottom: 0.8rem; opacity: 0.6;"></i>
            <h4 style="color: #666; font-weight: 500; margin: 0; font-size: 1.1rem;">
              Carrito de ventas vacío
            </h4>
            <p style="color: #999; font-size: 0.9rem; margin-top: 0.5rem;">
              Agregue productos para comenzar
            </p>
          </div>

          <DataTable v-if="arrayDetalle.length > 0" :value="arrayDetalle" class="p-mt-3" responsiveLayout="scroll">
            <Column header="Opciones" style="width: 15%">
              <template #body="slotProps">
                <Button icon="pi pi-trash" class="p-button-danger p-button-sm btn-mini" @click="
                  slotProps.data.medida != 'KIT'
                    ? eliminarDetalle(slotProps.data.id)
                    : eliminarKit(slotProps.data.idkit)
                  " />
                <Button :label="getLabelModoVenta(slotProps.data.modoVenta)" class="p-button-info p-button-sm btn-mini"
                  style="margin-left: 5px" :disabled="slotProps.data.descripcion_fabrica == 1"
                  @click="cambiarModoVenta(slotProps.data)" />
              </template>
            </Column>
            <Column field="articulo" header="Producto" />
            <Column field="stock" header="Stock Actual" style="width: 15%">
              <template #body="slotProps">
                <div style="background-color: #007bff; color: white; padding: 4px; border-radius: 4px; text-align: center;">
                  <span v-if="slotProps.data.descripcion_fabrica == '1'">∞</span>
                  <span v-else>
                    {{
                      slotProps.data.modoVenta === 'caja'
                        ? slotProps.data.stock_cajas + ' Cajas'
                        : slotProps.data.modoVenta === 'docena'
                          ? (slotProps.data.stock / 12).toFixed(2) + ' Docenas'
                          : slotProps.data.stock + ' Unidades'
                    }}
                  </span>
                </div>
              </template>
            </Column>
            <Column field="unidad_envase" header="Cant x Caja" style="width: 10%" class="column-precio-unidad">
              <template #body="slotProps">
                <input type="text" class="form-control form-control-sm input-precio-unidad"
                  style="height: 32px; font-size: 0.875rem; padding: 0.25rem 0.3rem; text-align: center; width: 100%;"
                  :value="slotProps.data.descripcion_fabrica == 1 ? '-' : slotProps.data.unidad_envase" disabled />
              </template>
            </Column>
            <Column field="precioUnidad" header="Precio Unidad" style="width: 12%" class="column-precio-unidad">
              <template #body="slotProps">
                <div class="precio-wrapper" style="display: flex; gap: 5px; align-items: center;">
                  <input type="text" v-model.number="slotProps.data.precioseleccionado"
                    @input="actualizarDetalle(slotProps.index); guardarCambioPrecio(slotProps.data)" class="form-control form-control-sm input-precio-unidad"
                    :disabled="!slotProps.data.editandoPrecio && (permitir_cambioprecio == 0 && slotProps.data.descripcion_fabrica != '1')" />
                  <Button 
                    :icon="slotProps.data.editandoPrecio ? 'pi pi-check' : 'pi pi-pencil'" 
                    class="p-button-sm p-button-secondary btn-precio-toggle"
                    :class="slotProps.data.editandoPrecio ? 'p-button-success' : ''"
                    :title="slotProps.data.editandoPrecio ? 'Guardar precio' : 'Editar precio'" 
                    @click="toggleEditarPrecio(slotProps.data)" />
                </div>
              </template>
            </Column>
            <!--<Column header="Precio Venta" style="width: 130px; text-align: center;">
                <template #body="slotProps">
                  <div v-if="slotProps.data.unidad_envase >= 1" class="d-flex flex-column align-items-center">
                    <InputSwitch v-model="slotProps.data.es_paquete" @change="cambiarModoVenta(slotProps.data)"
                      style="transform: scale(0.8);" />
                    <small :style="{ color: slotProps.data.es_paquete ? '#2196F3' : '#689F38', fontWeight: 'bold' }">
                      {{ slotProps.data.es_paquete ? 'POR PAQUETE' : 'POR UNIDAD' }}
                    </small>
                  </div>
                  <div v-else>
                    <span class="badge badge-secondary">Unidad Única</span>
                  </div>
                </template>
              </Column>-->
            <Column field="unidades" header="Cantidad a Vender" style="width: 10%" class="column-unidades">
              <template #body="slotProps">
                <InputNumber v-model="slotProps.data.cantidad" :min="1" @input="actualizarDetalle(slotProps.index)"
                  class="p-inputtext-sm input-unidades" style="height: 32px;"
                  :ref="'inputCantidad_' + slotProps.index" />
              </template>
            </Column>
               <Column v-if="permitir_ofertas == 1" field="descuento" header="cuento por Cantidad (Bs)" style="width: 10%"
              class="column-descuento">
              <template #body="slotProps">
                <InputNumber v-model="slotProps.data.descuento" mode="decimal" :minFractionDigits="2"
                  :maxFractionDigits="2" :min="0" class="p-inputtext-sm" @keydown.native="convertirPuntoComa"
                  @input="actualizarDetalle(slotProps.index)" />
              </template>
            </Column>

            <Column field="total" header="Total" style="width: 15%">
              <template #body="slotProps">
                {{
                  (
                    (
                      slotProps.data.modoVenta === 'caja'
                        ? slotProps.data.precioseleccionado * slotProps.data.cantidad * slotProps.data.unidad_envase
                        : slotProps.data.modoVenta === 'docena'
                          ? slotProps.data.precioseleccionado * slotProps.data.cantidad * 12
                          : slotProps.data.precioseleccionado * slotProps.data.cantidad
                    ) - (parseFloat(slotProps.data.descuento || 0) * slotProps.data.cantidad)
                  * parseFloat(monedaVenta[0])
                  ).toFixed(2)
                }}
                {{ monedaVenta[1] }}
              </template>
            </Column>
          </DataTable>

          <div v-if="arrayDetalle.length > 0" class="p-grid p-mt-3">
            <div class="p-col-12 p-md-8"></div>
            <div class="p-col-12 p-md-4" style="text-align: right;">
              <h5>
                Total Neto:
                {{ (calcularTotal * parseFloat(monedaVenta[0])).toFixed(2) }}
                {{ monedaVenta[1] }}
              </h5>
            </div>
          </div>
        </div>
        <div class="buttons d-flex justify-content-center">
          <button class="btn btn-primary mr-2" @click="prevStep" :disabled="step === 1">
            Anterior
          </button>
          <button class="btn btn-primary" @click="validarYAvanzar" :disabled="step === 2 || arrayDetalle.length === 0">
            Siguiente
          </button>
        </div>
      </Dialog>
    </template>
    <template>
      <Dialog :visible.sync="modalCobro" :modal="true" :closable="true" :blockScroll="true"
        :containerStyle="{ width: '450px' }">

        <template v-if="ventaSeleccionada">

          <template slot="header">
            <div class="modal-header">
              <h5 class="modal-title">Cobro de Cuota</h5>
              <button class="close-button" @click="modalCobro = false">
                <i class="pi pi-times"></i>
              </button>
            </div>
          </template>

          <div class="p-fluid">

            <div class="p-mb-3">
              <strong>Cliente:</strong> {{ ventaSeleccionada.razonSocial }} <br>
              <strong>Documento:</strong> {{ ventaSeleccionada.documentoid }} <br>
              <strong>Total Crédito:</strong> {{ ventaSeleccionada.total }} <br>
              <strong>Saldo Actual:</strong> {{ ventaSeleccionada.saldo_restante }}
            </div>

            <div class="p-mb-3">
              <label class="label-input mb-2">Forma de Pago</label>
              <div class="btn-group pago-buttons">
                <button class="btn pago-btn" :class="{ 'active-btn': tipoPagoCuota === 'efectivo' }"
                  @click="tipoPagoCuota = 'efectivo'">
                  <i class="fa fa-money mr-2"></i>
                  Efectivo
                </button>
                <button class="btn pago-btn" :class="{ 'active-btn': tipoPagoCuota === 'banco' }"
                  @click="tipoPagoCuota = 'banco'">
                  <i class="fa fa-university mr-2"></i>
                  Banco
                </button>
                <button class="btn pago-btn"
                  :class="{ 'active-btn': tipoPagoCuota === 'descuento', 'descuento-btn': tipoPagoCuota === 'descuento' }"
                  @click="seleccionarDescuento">
                  <i class="pi pi-percentage mr-2"></i>
                  Descuento
                </button>

              </div>
              <!-- 🔽 DROPDOWN DE BANCOS -->
              <div v-if="tipoPagoCuota === 'banco'" class="mt-3 fade-in">
                <label class="label-input">Seleccionar Banco</label>
                <!--
                <Dropdown v-model="bancoSeleccionado" :options="bancos" optionLabel="nombre_banco"
                  placeholder="Seleccione un banco" class="w-full custom-dropdown" @change="onBancoSelect">
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
                  <template #value="slotProps">
                    <div v-if="slotProps.value" class="banco-value">
                      <img :src="getBankUrl(slotProps.value.nombre_banco)" class="banco-logo" />
                      <span class="cuenta">{{ slotProps.value.nombre_cuenta }}</span>
                    </div>
                    <span v-else>-</span>
                  </template>

                </Dropdown>
                -->

              </div>
            </div>
            <div class="p-mb-3">
              <label class="label-input">Monto a Pagar</label>
              <input type="number" class="form-control" v-model="recibidoCuota" min="0" placeholder="Ingrese monto" />
            </div>
          </div>

          <template slot="footer">
            <Button label="Registrar Pago" icon="pi pi-check" class="p-button-success" @click="registrarPagoCuota" />
          </template>

        </template>

      </Dialog>
    </template>

    <template>
      <Dialog :visible="modal" :containerStyle="dialogContainerStyle" style="padding-top: 5px;" :modal="true"
        :closable="false" class="responsive-dialog">
        <template #header>
          <h4>{{ tituloModal }}</h4>
        </template>
        <TabView>
          <TabPanel header="Combos/Ofertas">
            <div class="p-field p-col-12" style="width: 100%; margin: 0; padding: 0;">
              <div class="p-inputgroup" style="width: 100%;">
                <InputText id="buscarA" v-model="buscarA" placeholder="Texto a buscar"
                  @input="listarItemCompuesto(buscarA)" class="input-full" />
                <Button icon="pi pi-refresh" class="p-button-secondary p-button-sm" @click="
                  buscarA = '';
                listarItemCompuesto('');
                " type="button" :disabled="!buscarA" :style="{ minWidth: '36px' }" title="Limpiar" />
              </div>
            </div>
            <DataTable :value="arrayItemCompuesto" :paginator="true" :rows="10"
              class="p-mt-2 p-datatable-gridlines p-datatable-sm tabla-venta tabla-seleccionable"
              responsiveLayout="scroll" @row-click="seleccionarItem($event.data, 'itemcompuesto')">
              <Column header="Opciones" style="width: 80px">
                <template #body="slotProps">
                  <Button icon="pi pi-check" class="p-button-success p-button-sm btn-mini"
                    @click.stop="agregarDetalleModal(slotProps.data, 'itemcompuesto')" />
                  <Button icon="pi pi-eye" class="btn-icon p-button-primary btn-mini"
                    @click.stop="verCombosOfertas(slotProps.data.id)" v-tooltip.top="'Ver Combo'" />
                </template>
              </Column>
              <Column field="nombre" header="Descripcion" />
              <Column field="nombre_categoria" header="Categoría" class="d-none d-md-table-cell" />
              <Column header="Precio de Venta">
                <template #body="slotProps">
                  {{ (slotProps.data.precio_uno * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}
                </template>
              </Column>
            </DataTable>
          </TabPanel>
          <TabPanel header="Productos">
            <div class="p-field p-col-12" style="width: 100%; margin: 0; padding: 0; position: relative;">
              <div class="p-inputgroup" style="width: 100%; position: relative;">
                <InputText id="buscarA" v-model="buscarA" class="p-inputtext-sm" autocomplete="off"
                  style="width: 100%; margin: 0;" @input="listarArticulo(buscarA)" />
                <span v-if="buscarA.trim() === ''"
                  style="color: #FFA500; font-size: 0.75rem; position: absolute; left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none;">
                  Realice una búsqueda por nombre, proveedor, código de barra o código del producto
                </span>
                <Button icon="pi pi-refresh" class="p-button-secondary p-button-sm" @click="
                  buscarA = '';
                listarArticulo('');
                " type="button" :disabled="!buscarA" :style="{ minWidth: '36px' }" title="Limpiar" />
              </div>
            </div>
            <DataTable :value="arrayArticulo" :paginator="true" :rows="10"
              class="p-mt-2 p-datatable-gridlines p-datatable-sm tabla-seleccionable" responsiveLayout="scroll"
              @row-click="seleccionarItem($event.data, 'producto')">

              <Column header="Opciones" style="width: 120px">
                <template #body="slotProps">
                  <Button icon="pi pi-check" class="p-button-success p-button-sm btn-mini"
                    @click.stop="agregarDetalleModalProducto(slotProps.data)" />
                  <Button icon="pi pi-info-circle" class="p-button-info p-button-sm btn-mini"
                    @click.stop="verStockPorSucursal(slotProps.data)" />
                </template>
              </Column>

              <Column field="codigo" header="Código" style="width: 100px" />

              <Column field="nombre" header="Nombre" />

              <Column field="nombre_categoria" header="Categoría" class="d-none d-md-table-cell" />

              <Column field="contacto" header="Proveedor" class="d-none d-lg-table-cell" />

              <Column header="Precio Venta">
                <template #body="slotProps">
                  {{ (slotProps.data.precio_uno * parseFloat(monedaVenta[0])).toFixed(2) }} {{ monedaVenta[1] }}
                </template>
              </Column>

              <Column field="saldo_stock" header="Stock" class="d-none d-md-table-cell">
                <template #body="slotProps">
                  <span v-if="slotProps.data.descripcion_fabrica == '1'">∞</span>
                  <span v-else-if="slotProps.data.saldo_stock == 0" style="color: red; font-weight: bold;">
                    Sin stock
                  </span>
                  <span v-else>{{ slotProps.data.saldo_stock }}</span>
                </template>
              </Column>
            </DataTable>
          </TabPanel>

        </TabView>
        <template #footer>
          <Button label="Cerrar" icon="pi pi-times" @click="cerrarModal" class="p-button-secondary" />
          <Button v-if="tipoAccion === 1" label="Guardar" icon="pi pi-check" @click="registrarPersona" />
          <Button v-if="tipoAccion === 2" label="Actualizar" icon="pi pi-check" @click="actualizarPersona" />
        </template>
      </Dialog>
    </template>

    <template>
      <!-- DIALOG PARA PAGOS -->
      <Dialog :visible="modalPago" :containerStyle="dialogContainerStyle" style="padding-top: 35px;" :modal="true"
        :closable="false" class="responsive-dialog" @hide="cerrarModalPago">
        <template #header>
          <h3>Emisión / Pago - Venta #{{ idventaa }}</h3>
        </template>

        <TabView>
          <TabPanel header="Factura">
            <div class="container">
              <div class="row">
                <div class="col-md-12">
                  <div class="card shadow-sm">
                    <div class="card-body">
                      <div class="mb-3">
                        <h5 class="mb-0">Detalle de la Factura</h5>
                      </div>
                      <hr />
                      <div class="d-flex justify-content-between">
                        <span><i class="fa fa-money mr-2"></i> Total Factura:</span>
                        <span class="font-weight-bold h5">
                          {{ Number(totalReservaSeleccionada).toFixed(2) }} BS
                        </span>
                      </div>
                    </div>
                  </div>

                  <button type="button" @click="aplicarDescuento2" class="btn btn-success btn-block mt-3">
                    <i class="fa fa-paper-plane mr-2"></i> Enviar al SIAT / Pagar
                  </button>
                </div>
              </div>
            </div>
          </TabPanel>
        </TabView>

        <template #footer>
          <Button label="Cerrar" icon="pi pi-times" @click="cerrarModalPago" class="p-button-secondary" />
          <Button label="Procesar Pago" icon="pi pi-check" @click="aplicarDescuento2" />
        </template>
      </Dialog>

      <Dialog :visible.sync="dialogAnularVentaVisible" :modal="true" :closable="false"
        :containerStyle="{ width: '420px' }">
        <template #header>
          <h4 class="mb-0">
            <i class="pi pi-ban text-danger mr-2"></i> Anular Venta
          </h4>
        </template>
        <div v-if="ventaAnularSeleccionada" class="p-3">
          <div class="mb-4">
            <label class="font-weight-bold d-block mb-1">El pago se hizo en:</label>
            <div v-if="ventaAnularSeleccionada.idtipo_pago === 1">
              <Tag icon="pi pi-money-bill" severity="success" class="mr-2" value="Efectivo" />
              <span class="font-weight-bold">
                {{
                  (parseFloat(ventaAnularSeleccionada.efectivo_pago || 0) -
                    parseFloat(ventaAnularSeleccionada.cambio || 0)).toFixed(2)
                }}
              </span>
            </div>

            <div v-else-if="ventaAnularSeleccionada.idtipo_pago === 7">
              <Tag icon="pi pi-qrcode" severity="info" class="mr-2" value="QR" />
              <span class="font-weight-bold">
                {{ ventaAnularSeleccionada.qr_pago || ventaAnularSeleccionada.total }}
              </span>
            </div>

            <div v-else-if="ventaAnularSeleccionada.idtipo_pago === 13">
              <div class="mb-1">
                <Tag icon="pi pi-money-bill" severity="success" class="mr-2" value="Efectivo" />
                <span class="font-weight-bold">
                  {{
                    (parseFloat(ventaAnularSeleccionada.efectivo_pago || 0) -
                      parseFloat(ventaAnularSeleccionada.cambio || 0)).toFixed(2)
                  }}
                </span>
              </div>
              <div>
                <Tag icon="pi pi-qrcode" severity="info" class="mr-2" value="QR" />
                <span class="font-weight-bold">{{ ventaAnularSeleccionada.qr_pago }}</span>
              </div>
            </div>

            <div v-else class="text-danger">
              <i class="pi pi-exclamation-triangle mr-2"></i> Tipo de pago desconocido
            </div>
          </div>

          <div class="mb-3">
            <label class="font-weight-bold d-block mb-2">¿Cómo desea reponer caja?</label>
            <div class="form-check mb-2">
              <input type="radio" id="reponerEfectivo" class="form-check-input" value="efectivo"
                v-model="opcionReposicionCaja" />
              <label for="reponerEfectivo" class="form-check-label">Reposición en Efectivo</label>
            </div>

            <div class="form-check">
              <input type="radio" id="reponerQR" class="form-check-input" value="qr" v-model="opcionReposicionCaja" />
              <label for="reponerQR" class="form-check-label">Reposición por QR</label>
            </div>

            <div class="mt-2 text-muted" style="font-size: 0.85rem;">
              Solo puede marcar una opción.
            </div>
          </div>
        </div>
        <template #footer>
          <Button label="Cancelar" icon="pi pi-times" class="p-button-danger"
            @click="dialogAnularVentaVisible = false" />
          <Button label="Continuar" icon="pi pi-check" @click="continuarDesactivarVenta" class="p-button-success" />
        </template>
      </Dialog>

      <Dialog :visible.sync="dialogStockVisible" :modal="true" :closable="false" :dismissableMask="true" :header="null"
        :containerStyle="{ width: '480px', borderRadius: '14px', overflow: 'hidden', boxShadow: '0 4px 20px rgba(0,0,0,0.15)' }">
        <!-- 🔹 Encabezado personalizado -->
        <div
          style="background: linear-gradient(135deg, #1976d2, #42a5f5); color: white; padding: 1rem 1rem; display: flex; align-items: center; justify-content: space-between;">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="pi pi-box" style="font-size: 1.4rem;"></i>
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600;">
              Stock por Sucursal
            </h3>
          </div>
        </div>

        <!-- 🔹 Contenido principal -->
        <div style="padding: 1.5rem;">
          <div v-if="articuloSeleccionado" style="text-align: center; margin-bottom: 1rem;">
            <h4 style="margin: 0; color: #333; font-weight: 600;">
              {{ articuloSeleccionado.nombre }}
            </h4>
            <p style="margin: 0; color: #666; font-size: 0.85rem;">
              Código: {{ articuloSeleccionado.contacto }}
            </p>
          </div>

          <div v-if="stockPorSucursal.length">
            <table class="p-datatable p-component" style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
              <thead>
                <tr style="background: #f4f6f8; border-bottom: 2px solid #1976d2;">
                  <th style="padding: 8px 10px; text-align: left; font-weight: 600;">
                    Sucursal
                  </th>
                  <th style="padding: 8px 10px; text-align: right; font-weight: 600;">
                    Stock Total
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, i) in stockPorSucursal" :key="i" style="border-bottom: 1px solid #eee;">
                  <td style="padding: 8px 10px;">{{ item.sucursal }}</td>
                  <td style="padding: 8px 10px; text-align: right;">
                    <span :style="{
                      color:
                        item.total_stock > 10
                          ? '#2e7d32'
                          : item.total_stock > 0
                            ? '#f9a825'
                            : '#d32f2f',
                      fontWeight: '600'
                    }">
                      {{ item.total_stock }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="p-text-center" style="text-align: center; padding: 1.5rem;">
            <i class="pi pi-exclamation-triangle" style="color: #FFA500; font-size: 2rem;"></i>
            <p style="margin-top: 0.5rem; color: #555;">
              No hay registros de stock para este producto.
            </p>
          </div>
        </div>

        <!-- 🔹 Footer elegante -->
        <div style="background: #f9f9f9; padding: 1rem; text-align: right; border-top: 1px solid #eee;">
          <Button label="Cerrar" icon="pi pi-times" class="p-button-danger p-button-sm btn-mini"
            @click="dialogStockVisible = false" />
        </div>
      </Dialog>
    </template>

    <!-- MODAL CONFIRMAR ELIMINAR CUOTA -->
    <Dialog :visible.sync="modalConfirmarEliminarCuota" :modal="true" :closable="true"
      :containerStyle="{ width: '400px' }">
      <template #header>
        <div class="modal-header" style="display: flex; align-items: center; gap: 8px;">
          <i class="pi pi-exclamation-triangle" style="color: #f44336; font-size: 1.5rem;"></i>
          <h5 class="modal-title" style="margin: 0;">Confirmar Eliminación</h5>
        </div>
      </template>

      <div class="p-3">
        <p style="margin: 0; font-size: 1rem;">
          ¿Estás seguro de borrar esta cuota?
        </p>
        <div v-if="cuotaAEliminar" class="mt-3 p-3" style="background: #f8f9fa; border-radius: 6px;">
          <p style="margin: 0 0 5px 0;"><strong>Cuota #:</strong> {{ cuotaAEliminar.numero_cuota }}</p>
          <p style="margin: 0 0 5px 0;"><strong>Monto:</strong> {{ cuotaAEliminar.precio_cuota }} {{ monedaVenta[1] }}
          </p>
          <p style="margin: 0;">
            <strong>Tipo de Pago:</strong>
            <span v-if="cuotaAEliminar.idtipo_pago === 1">Efectivo</span>
            <span v-else-if="cuotaAEliminar.idtipo_pago === 7">Banco/QR</span>
            <span v-else>Otro</span>
          </p>
        </div>
        <p class="mt-3 text-danger" style="font-size: 0.85rem;">
          <i class="pi pi-info-circle"></i>
          Esta acción descontará el monto de la caja y no se puede deshacer.
        </p>
      </div>

      <template #footer>
        <Button label="Cancelar" icon="pi pi-times" class="p-button-secondary"
          @click="modalConfirmarEliminarCuota = false" />
        <Button label="Sí, eliminar" icon="pi pi-trash" class="p-button-danger" @click="confirmarEliminarCuota" />
      </template>
    </Dialog>
    <Dialog :visible.sync="showQrDialog" :modal="true" :closable="true" :containerStyle="dialogContainerStyleQR">
      <template #header>
        <div class="d-flex align-items-center gap-2" style="border-bottom: 2px solid #dee2e6; padding-bottom: 0.5rem;">
          <i class="pi pi-qrcode" style="font-size: 1.5rem; color: #000;"></i>
          <h3 class="font-weight-bold m-0" style="color: #000;">Pago con QR</h3>
        </div>
      </template>

      <div class="d-flex flex-column flex-md-row align-items-center justify-content-center gap-4 py-3 px-2"
        style="text-align: center;">
        <div class="qr-image-container">
          <img v-if="qrImage" :src="qrImage" alt="Código QR" class="img-fluid rounded border shadow-sm"
            style="max-width: 350px; width: 100%; height: auto;" />
        </div>

        <div class="qr-actions d-flex flex-column align-items-center w-100" style="max-width: 350px;">
          <div class="d-flex align-items-center mb-3 w-100 gap-3" v-if="qrImage" style="gap: 12px;">
            <Button label="Verificar Estado de Pago" icon="pi pi-sync" class="p-button-info w-100"
              @click="verificarEstado" />
            <div style="display: flex; align-items: center; gap: 8px; white-space: nowrap;">
              <InputSwitch v-model="autoVerificarQR" :true-value="true" :false-value="false"
                :aria-label="'Auto-verificar'" :style="{
                  zoom: '1.2'
                }" class="auto-verificar-switch" />
              <span style="font-size: 0.9em; font-weight: 500; color: #495057;">
                Auto
              </span>
            </div>
          </div>



          <div v-if="estadoTransaccion" class="card w-100 p-3 text-left border shadow-sm" style="background: #f8f9fa;">
            <div class="text-muted font-weight-bold mb-1">Estado actual:</div>
            <span :class="'badge badge-' + badgeSeverity" style="font-size: 0.95rem;">
              {{ estadoTransaccion.objeto.estadoActual }}
            </span>
          </div>
          <!-- Progress Bar y Contador -->
          <div v-if="autoVerificarQR && qrImage" class="w-100 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <small class="text-muted">Próxima verificación:</small>
              <small class="font-weight-bold text-info">{{ verificacionCountdown }}s</small>
            </div>
            <ProgressBar :value="((8 - verificacionCountdown) / 8) * 100" :showValue="false" style="height: 6px;" />
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Cerrar" icon="pi pi-times" @click="showQrDialog = false" class="p-button-danger" />
      </template>
    </Dialog>

    <Dialog :visible.sync="dialogVerCombo" :modal="true" :closable="false" :containerStyle="{ width: '800px' }"
      class="dialog-combo">
      <!-- HEADER PERSONALIZADO -->
      <template #header>
        <div class="dialog-header">
          <i class="pi pi-box icon-header"></i>
          <div>
            <h3 class="title">{{ nombreComboActual }}</h3>
            <span class="subtitle">Detalle de productos incluidos</span>
          </div>
        </div>
      </template>

      <!-- CONTENIDO -->
      <div class="dialog-content">
        <DataTable :value="productosSeleccionados" responsiveLayout="scroll" class="p-datatable-sm p-datatable-striped">
          <Column field="nombre" header="Producto"></Column>
          <Column field="nombre_categoria" header="Categoría"></Column>
          <Column field="cantidad" header="Cantidad" style="width: 120px; text-align:center">
            <template #body="slotProps">
              <span class="cantidad-badge">
                {{ slotProps.data.cantidad }}
              </span>
            </template>
          </Column>
        </DataTable>
      </div>

      <!-- FOOTER -->
      <template #footer>
        <div class="dialog-footer">
          <Button label="Cerrar" icon="pi pi-times" class="p-button-danger p-button-sm"
            @click="dialogVerCombo = false" />
        </div>
      </template>
    </Dialog>
  </main>
</template>

<script>
import Calendar from 'primevue/calendar';
import Dropdown from "primevue/dropdown";
import Swal from "sweetalert2";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Paginator from "primevue/paginator";
import Card from "primevue/card";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Panel from "primevue/panel";
import Steps from "primevue/steps";
import Dialog from "primevue/dialog";
import Message from "primevue/message";
import Tag from "primevue/tag";
import SelectButton from "primevue/selectbutton";
import InputNumber from "primevue/inputnumber";
import InputSwitch from "primevue/inputswitch";
import ProgressBar from "primevue/progressbar";
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
import Tooltip from 'primevue/tooltip';

export default {
  components: {
    Dropdown,
    DataTable,
    Column,
    Button,
    Paginator,
    Card,
    Calendar,
    InputText,
    Button,
    Panel,
    Steps,
    Message,
    Dialog,
    Tag,
    SelectButton,
    InputNumber,
    TabView,
    TabPanel,
    InputSwitch,
    ProgressBar,
    ToastService,
    Toast
  }, directives: {
    'tooltip': Tooltip
  },
  data() {
    return {
      autoVerificarQR: false,
      autoVerificarQRInterval: null,
      countdownInterval: null,
      verificacionCountdown: 8,
      showQrDialog: false,
      dialogVerCombo: false,
      nombreComboActual: '',
      productosSeleccionados: [],

      arrayItemCompuesto: [],
      clienteExistente: false,
      mostrarDesplegableDireccion: false,
      indiceDireccionSeleccionada: 0,
      debounceDireccion: null,
      // cliente
      direccionCliente: '',
      direccionClienteEditable: true,

      errores: {
        direccion: null,
        razonSocial: null
      },
      direccionesFiltradas: [],
      indiceDireccionSeleccionada: -1,
      clienteEncontrado: false,
      idcliente: null,

      documento: '',
      nombreCliente: '',
      telefonoCliente: '',
      direccionClienteEditable: true,
      nombreClienteEditable: true,
      telefonoClienteEditable: true,
      editarCuotas: false,
      modoVenta: "unidad", // inicia vendiendo en cajas
      bancoMap: {
        "banco nacional de bolivia": "BNB",
        "banco mercantil santa cruz": "BME",
        "banco económico": "BEC",
        "banco fondo financiero privado": "BFO",
        "banco ganadero": "BGA",
        "banco bis": "BIS",
        "banco bie": "BIE",
        "banco de crédito": "BCR",
        "banco unión": "BUN",
        "banco progreso": "BPR",
        "banco sol": "BSO",
        "banco de potosí": "PCO",
        "banco efectivo": "PEF",
      },
      tipoPagoCuota: null,
      recibidoCuota: 0,
      usarDescuento: false,
      idtipo_venta: null,  // 👈 NECESARIO
      cuotas: [], // 👈 NECESARIO
      bancoSeleccionado: null,
      idbanco: null,
      bancos: [],
      modalCobro: false,
      recibidoCuota: 0,
      tipoPagoCuota: "efectivo",

      creditoTotal: 0,
      totalComprasCliente: 0,
      saldoFavorCliente: 0,
      habilitacionpromocion: false,
      dialogStockVisible: false,
      stockPorSucursal: [],
      articuloSeleccionado: null,
      desdeModal: false, // 🔹 Nuevo flag

      tipoDocumentoTexto: "CI",

      mensajeRazonSocial: false, // 🔹 Nueva variable
      resultadosClientes: [],
      mostrarDesplegableCliente: false,
      indiceSeleccionadoCliente: -1,
      buscarTimeout: null,
      resultadosBusqueda: [],
      mostrarDesplegable: false,
      indiceSeleccionado: -1,
      debounceTimer: null,
      estadoFactVisual: 'cargando', // 'cargando', 'online', 'offline', 'incompleto'
      cargandoFactVisual: true,
      filtroVentasActivo: 'todos',

      dialogAnularVentaVisible: false,
      opcionReposicionCaja: 'efectivo', // 'efectivo' o 'qr'
      ventaAnularSeleccionada: null,
      modalConfirmarEliminarCuota: false,
      cuotaAEliminar: null,
      mostrarLabel: true,
      isLoading: false,
      opcionesPago: [
        { label: "Efectivo", value: "efectivo" },
        { label: "QR", value: "qr" },
      ],
      criterioOptions: [
        { label: "Nombre", value: "nombre" },
        { label: "Descripción", value: "descripcion" },
        { label: "Código", value: "codigo" },
      ],
      isDialogVisible: false,
      tipoComprobanteOptions: [
        { name: "RECIBO", code: "RESIVO" },
        { name: "FACTURA", code: "FACTURA" },
      ],
      opcionPago: "efectivo",
      tipoVenta: "contado",
      tipocompro: "Recibo",

      mostrarSpinner: false,
      selectedAlmacen: null,
      idrol: null,
      step: 1,
      modal2: false,
      modal: false,
      zIndexBase: 1050,
      puedeDescontar: false, // Por defecto no puede

      //qr
      alias: "",
      montoQR: 0,
      qrImage: "",
      aliasverificacion: "",
      estadoTransaccion: null,
      currency: "BOB", // Define tu moneda
      resivo: "",
      clienteDeudas: 0,
      arrayCuotas: [],
      arraySeleccionado: [],
      cuotaSeleccionada: null,
      modalCuotas: 0,

      tipo_pago: "",
      criterioKit: "nombre",
      buscarKit: "",

      mensajesKit: [],
      arrayArticulosKit: [],
      datosFormularioKit: [],
      modalDetalleKit: 0,
      arrayKit: [],

      arrayPreciosEspeciales: [],
      modalDetalle: 0,
      datosFormularioPE: [],
      arrayArticulosPE: [],

      arrayPromocion: [],
      unidadPaquete: 1,
      tipoVentaOptions: [
        { label: "Por unidad", value: 0 },
        { label: "Por paquete", value: 1 },
      ],

      monedaVenta: [],
      permitirDevolucion: "",
      saldosNegativos: 1,
      permitir_cambioprecio: false,
      permitir_bonificacion: false,
      permitir_descuento: false,
      permitir_ofertas: false,
      venta_id: 0,
      idcliente: 0,
      usuarioAutenticado: null,
      puntoVentaAutenticado: null,
      idsucursalAutenticado: null,
      cliente: "",
      email: "",
      nombreCliente: "",
      nombreClienteEditable: false,
      telefonoCliente: "",
      direccionClienteEditable: false,
      telefonoClienteEditable: false,
      telefonoCliente: "",
      telefonoClienteEditable: false,
      documento: "",
      tipo_documento: 1,
      complemento_id: "",
      descuentoAdicional: 0.0,
      descuentoAdicionalvista: "",
      descuentoTotalDetalle: "",
      subtotalVista: "",
      descuentoGiftCard: "",
      tipo_comprobante: "FACTURA",
      serie_comprobante: "",
      last_comprobante: 0,
      num_comprob: "",
      impuesto: 0.18,
      total: 0.0,
      totalImpuesto: 0.0,
      totalParcial: 0.0,
      arrayVenta: [],
      arrayCliente: [],
      arrayDetalle: [],
      arrayProductos: [],
      arrayFactura: [],
      listado: 1,
      tituloModal: "",
      tipoAccion: 0,
      errorVenta: 0,
      errorMostrarMsjVenta: [],
      pagination: {
        total: 0,
        current_page: 1,
        last_page: 0, // Asegúrate de actualizar este valor al obtener datos
      },
      offset: 3,
      criterio: "",
      buscar: "",
      criterioA: "nombre",
      buscarA: "",
      arrayArticulo: [],
      arraySeleccionado: [],

      idarticulo: 0,
      codigo: "",
      articulo: "",
      medida: "",
      codigoClasificador: "",
      codigoProductoSin: "",
      precio: 0,
      unidad_envase: 0,
      cantidad: 1,
      paquni: "",
      precioBloqueado: false,
      descuento: 0,
      descuentoProducto: 0,
      sTotal: 0,
      stock: 0,
      valorMaximoDescuento: "",
      mostrarDireccion: true,

      casosEspeciales: false,
      mostrarCampoCorreo: false,
      leyendaAl: "",
      codigoExcepcion: 0,
      mostrarSpinner: false,
      primer_precio_cuota: 0,
      numeroTarjeta: null,
      metodoPago: "",
      criterioVenta: "ci",
      //almacenes
      arrayAlmacenes: [],
      almacenSeleccionado: null,
      almacenPredeterminadoId: null,
      idAlmacen: null,
      //-----PRECIOS- AUMENTE 3/OCTUBRE--------
      precioseleccionado: "",
      //precio : '',
      arrayPrecios: [],
      nombre_precio: "",
      precio_uno: "",
      precio_dos: "",
      precio_tres: "",
      precio_cuatro: "",
      //-----MODAL 2---

      tituloModal2: "",
      tipoAccion2: "",

      modal3: 0,
      tituloModal3: "",
      tipoAccion3: "",

      recibido: 0,
      efectivo: 0,
      cambio: 0,
      faltante: 0,
      cantidadClientes: 0,
      idtipo_pago: "",
      idtipo_venta: 1,
      tiempo_diaz: "",
      numero_cuotas: "",
      cuotas: [], //---para almacenar las fechas
      estadocrevent: "activo",
      primera_cuota: "",
      habilitarPrimeraCuota: false,
      tipoPago: "EFECTIVO",
      mostrarElementos: true,
      mostrarCUFD: true,
      idPago: "",
      modalPago: false,
      idventaa: null,
      totalReservaSeleccionada: 0,
      totaldescuentoseleccionada: 0,
      ventaSeleccionada: null,
      tiposPago: [
        { label: '💵 Efectivo', value: 1 },
        { label: '🏦 Transferencia / QR', value: 7 },
      ],
      procesandoSeleccion: false,


      filtroSucursal: '',
      fechaInicio: '',
      fechaFin: new Date().toISOString().split('T')[0], 
      arraySucursales: [],
    };
  },

  watch: {
    cuotas: {
      deep: true,
      handler(cuotas) {
        cuotas.forEach(c => {
          if (c.idtipo_pago !== 7) {
            c.bancoSeleccionado = null;
            c.idbanco = null;
          }
        });
      }
    },
    editarCuotas(val) {
      if (val) {
        this.cargarBancos();
      }
    },
    autoVerificarQR(newVal) {
      if (newVal) {
        this.startAutoVerificarQR();
      } else {
        this.stopAutoVerificarQR();
      }
    },
    showQrDialog(newVal) {
      if (!newVal) {
        this.autoVerificarQR = false;
        this.stopAutoVerificarQR();
      }
    },

    estadoTransaccion(newVal) {
      if (newVal && newVal.objeto && newVal.objeto.estadoActual === "PAGADO") {
        this.autoVerificarQR = false;
      }
    },
    documento(newVal) {
      const clienteSeleccionado = this.resultadosClientes[this.indiceSeleccionadoCliente];
      if (!clienteSeleccionado || clienteSeleccionado.num_documento !== newVal) {
        this.nombreCliente = "";        // 🔹 Vaciar nombre
        this.emailCliente = "";         // 🔹 Vaciar email
        this.saldoFavorCliente = 0;     // 🔹 Resetear saldo a favor
        this.nombreClienteEditable = true;
        this.emailClienteEditable = true;
        this.indiceSeleccionadoCliente = -1;
        this.mostrarDesplegableCliente = false;
      }
    },
    codigo(newValue) {
      if (newValue && !this.desdeModal) {
        this.buscarArticulo();
      } else {
        this.desdeModal = false; // 🔹 Reset
      }
    },
    documento(newDocumento) {
      this.mostrarCampoCorreo =
        newDocumento === "99002" || newDocumento === "99003";
    },
  },
  computed: {
    dialogContainerStyleQR() {
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
    tiposPagoOptions() {
      return this.tiposPago;
    },
    bancosOptions() {
      return this.bancos;
    },
    totalCantidades() {
      return this.arrayArticulosKit.reduce((total, articulo) => {
        return total + parseInt(articulo.cantidad);
      }, 0);
    },

    dialogContainerStyle() {
      if (window.innerWidth <= 480) {
        return { width: "95vw", maxWidth: "95vw", margin: "0 auto" };
      } else if (window.innerWidth <= 768) {
        return { width: "90vw", maxWidth: "90vw", margin: "0 auto" };
      } else if (window.innerWidth <= 1024) {
        return { width: "85vw", maxWidth: "900px", margin: "0 auto" };
      } else {
        return { width: "1100px", maxWidth: "95vw", margin: "0 auto" };
      }
    },

    calcularTotal() {
      let resultado = 0.0;

      for (let i = 0; i < this.arrayDetalle.length; i++) {
        let detalle = this.arrayDetalle[i];

        // 🔹 Convertir a número y asignar valor por defecto
        const precio = Number(detalle.precioseleccionado) || 0;
        const cantidad = Number(detalle.cantidad) || 0;
        const unidadEnvase = Number(detalle.unidad_envase) || 1; // Si no tiene, asumimos 1
        const descuento = Number(detalle.descuento) || 0; // En Bs

        let totalDetalle = 0;

        if (detalle.modoVenta === "caja") {
          totalDetalle = (precio - descuento) * cantidad * unidadEnvase;
        } else if (detalle.modoVenta === "docena") {
          totalDetalle = (precio - descuento) * cantidad * 12;
        } else {
          totalDetalle = (precio - descuento) * cantidad;
        }

        // Evitar negativo
        if (totalDetalle < 0) totalDetalle = 0;

        resultado += totalDetalle;
      }

      // 🔹 Descuento adicional en Bs
      const descuentoAdicionalBs = Number(this.descuentoAdicional) || 0;
      resultado -= descuentoAdicionalBs;

      // 🔹 Evitar resultado negativo
      if (resultado < 0) resultado = 0;

      return resultado;
    },

    badgeSeverity() {
      if (
        this.estadoTransaccion &&
        this.estadoTransaccion.objeto.estadoActual === "PENDIENTE"
      ) {
        return "danger"; // Rojo para estado PENDIENTE
      } else if (
        this.estadoTransaccion &&
        this.estadoTransaccion.objeto.estadoActual === "PAGADO"
      ) {
        return "success"; // Verde para estado PAGADO
      } else {
        return "info"; // Otros estados
      }
    },
  },

  methods: {
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
    stopAutoVerificarQR() {
      if (this.autoVerificarQRInterval) {
        clearInterval(this.autoVerificarQRInterval);
        this.autoVerificarQRInterval = null;
      }
      if (this.countdownInterval) {
        clearInterval(this.countdownInterval);
        this.countdownInterval = null;
      }
      this.verificacionCountdown = 8;
    },
    startAutoVerificarQR() {
      if (this.autoVerificarQRInterval) return;

      // Inicializar el contador
      this.verificacionCountdown = 8;

      // Interval para verificar cada 8 segundos
      this.autoVerificarQRInterval = setInterval(() => {
        if (this.autoVerificarQR && this.showQrDialog) {
          this.verificarEstado();
          this.verificacionCountdown = 8; // Reiniciar countdown
        }
      }, 8000);

      // Interval para actualizar el countdown cada segundo
      this.countdownInterval = setInterval(() => {
        if (this.autoVerificarQR && this.showQrDialog && this.verificacionCountdown > 0) {
          this.verificacionCountdown--;
        }
      }, 1000);
    },
    seleccionarItem(data, tipo) {
      if (tipo === 'producto') {
        this.agregarDetalleModalProducto(data);
      } else if (tipo === 'itemcompuesto') {
        this.agregarDetalleModal(data, 'itemcompuesto');
      }
    },
    verCombosOfertas(id) {
      axios.get(`/itemcompuesto/${id}`).then((res) => {
        this.nombreComboActual = res.data.nombre_compuesto || 'Detalle del Combo';

        this.productosSeleccionados = (res.data.items || []).map(item => ({
          nombre: item.nombre,
          nombre_categoria: item.nombre_categoria || 'SIN CATEGORIA',
          cantidad: parseInt(item.cantidad || 1)
        }));

        this.dialogVerCombo = true;
      });
    },
    listarItemCompuesto(buscar, criterio) {
      let me = this;
      me.isLoading = true; // Activar loader

      var url = "/itemcompuesto?buscar=" + buscar + "&criterio=" + criterio;
      return axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arrayItemCompuesto = respuesta.articulos;
          me.first = 0; // Reiniciar a la primera página
        })
        .catch(function (error) {
          console.log(error);
        })
        .finally(() => {
          me.isLoading = false; // Desactivar loader
        });
    },
    asignarPrecioPorModo(detalle) {
      if (detalle.modoVenta === 'caja') {
        detalle.precioseleccionado = detalle.precio_tres;
      } else if (detalle.modoVenta === 'docena') {
        detalle.precioseleccionado = detalle.precio_dos;
      } else {
        detalle.precioseleccionado = detalle.precio_uno;
      }
    },
    getLabelModoVenta(modo) {
      switch (modo) {
        case 'caja':
          return 'Caja';
        case 'docena':
          return 'Docena';
        default:
          return 'Unidad';
      }
    },
    getEstadoText(estado, idtipo_venta, tipo_comprobante, saldo_restante = null) {
      // Si es crédito y el saldo restante es negativo, mostrar como Saldo a Favor
      if (idtipo_venta == 2 && parseFloat(saldo_restante) < 0) {
        return '💰 Saldo a Favor';
      }

      // Si es crédito y el saldo restante es 0, mostrar como Pagado
      if (idtipo_venta == 2 && (saldo_restante === 0 || saldo_restante === '0' || saldo_restante === '0.00')) {
        return 'Pagado';
      }

      if (estado === '1') {
        return 'Pagado';
      } else if (estado === '0') {
        return 'Anulado';
      } else if (estado === '2') {
        return idtipo_venta === 2 ? 'Crédito' : 'Pendiente';
      } else if (estado === '4') {
        return 'Vuelva a Intentarlo';
      } else if (estado === '5') {
        return 'Anulada';
      } else if (estado === '6') {
        return tipo_comprobante === "FACTURA" ? 'Presione en Facturar' : 'Cerrar Venta';
      } else if (estado === '7') {
        return 'Intente facturar de nuevo';
      } else {
        return 'Desconocido';
      }
    },

    getEstadoClass(estado, idtipo_venta, saldo_restante = null) {
      // Si es crédito y el saldo restante es negativo (saldo a favor)
      const esSaldoAFavor = idtipo_venta == 2 && parseFloat(saldo_restante) < 0;

      // Si es crédito y el saldo restante es 0, mostrar en verde
      const esCreditoPagado = idtipo_venta == 2 && (saldo_restante === 0 || saldo_restante === '0' || saldo_restante === '0.00');

      return {
        'estado-badge': true,          // clase base
        'estado-verde': estado === '1' || esCreditoPagado || esSaldoAFavor,  // Pagado, Crédito pagado o Saldo a Favor
        'estado-rojo': estado === '0' || estado === '5',    // Anulado / Anulada
        'estado-amarillo': estado === '2' && !esCreditoPagado && !esSaldoAFavor,  // Crédito / Pendiente (solo si no está pagado ni tiene saldo a favor)
      };
    },
    seleccionarDireccion(dir) {
      this.direccionCliente = dir;
      this.mostrarDesplegableDireccion = false;
      console.log("Dirección seleccionada:", this.direccionCliente);
    },
    moverSeleccionDireccion(direccion) {
      if (!this.mostrarDesplegableDireccion || this.direccionesFiltradas.length === 0) return;

      if (direccion === "abajo") {
        this.indiceDireccionSeleccionada =
          (this.indiceDireccionSeleccionada + 1) % this.direccionesFiltradas.length;
      } else if (direccion === "arriba") {
        this.indiceDireccionSeleccionada =
          (this.indiceDireccionSeleccionada - 1 + this.direccionesFiltradas.length)
          % this.direccionesFiltradas.length;
      }
    },
    seleccionarDireccionEnter() {
      if (
        this.indiceDireccionSeleccionada >= 0 &&
        this.indiceDireccionSeleccionada < this.direccionesFiltradas.length
      ) {
        this.seleccionarDireccion(this.direccionesFiltradas[this.indiceDireccionSeleccionada]);
      }
    },
    async buscarDireccion() {
      clearTimeout(this.debounceDireccion);

      this.debounceDireccion = setTimeout(async () => {

        if (!this.direccionCliente || !this.direccionCliente.trim()) {
          this.mostrarDesplegableDireccion = false;
          return;
        }

        try {
          const response = await axios.get(
            `/cliente/buscarDireccion?filtro=${this.direccionCliente}`
          );

          this.direccionesFiltradas = response.data.direcciones || [];

          this.mostrarDesplegableDireccion = this.direccionesFiltradas.length > 0;
          this.indiceDireccionSeleccionada = 0;
        } catch (error) {
          console.error("Error al buscar direcciones:", error);
          this.mostrarDesplegableDireccion = false;
        }

      }, 200);
    },
    syncBancoSeleccionado(cuota) {
      if (!cuota.idbanco) {
        cuota.bancoSeleccionado = null;
        return;
      }

      cuota.bancoSeleccionado =
        this.bancosOptions.find(b => b.id === cuota.idbanco) || null;
    },


    onBancoSelectCuota(cuota) {
      if (cuota.bancoSeleccionado) {
        cuota.idbanco = cuota.bancoSeleccionado.id;
        cuota.nombre_cuenta = cuota.bancoSeleccionado.nombre_cuenta;
      } else {
        cuota.idbanco = null;
        cuota.nombre_cuenta = null;
      }
    },

    async toggleEditarCuotas() {
      this.editarCuotas = !this.editarCuotas;

      if (this.editarCuotas) {
        // 1️⃣ Asegurar que los bancos estén cargados
        if (this.bancosOptions.length === 0) {
          await this.cargarBancos();
        }

        // 2️⃣ Formatear fechas para el input type="date" (YYYY-MM-DD)
        this.cuotas.forEach(c => {
          if (c.fecha_pago) {
            // Extraer solo la parte de fecha (YYYY-MM-DD)
            c.fecha_pago = c.fecha_pago.substring(0, 10);
          }
        });

        // 3️⃣ Sincronizar banco seleccionado por cuota
        this.cuotas.forEach(c => this.syncBancoSeleccionado(c));
      }
    },

    // =============================================
    // MÉTODOS PARA ELIMINAR CUOTA
    // =============================================
    abrirModalEliminarCuota(cuota) {
      this.cuotaAEliminar = cuota;
      this.modalConfirmarEliminarCuota = true;
    },

    async confirmarEliminarCuota() {
      if (!this.cuotaAEliminar) return;

      try {
        const response = await axios.post('/credito/eliminarCuota', {
          idcuota: this.cuotaAEliminar.id
        });

        if (response.data.success) {
          Swal.fire("Éxito", "Cuota eliminada correctamente", "success");

          // Cerrar modal
          this.modalConfirmarEliminarCuota = false;
          this.cuotaAEliminar = null;
          this.editarCuotas = false;
          // Recargar cuotas
          await this.cargarCuotas(this.idVentaSeleccionada);
          await this.verVenta(this.idVentaSeleccionada);

        } else {
          Swal.fire("Error", response.data.message || "No se pudo eliminar la cuota", "error");
        }

      } catch (error) {
        console.error("Error al eliminar cuota:", error);
        Swal.fire("Error", "Ocurrió un error al eliminar la cuota", "error");
      }
    },

    togglePrecio(index) {
      const item = this.arrayDetalle[index];
      if (!item) return;

      console.log("Antes del toggle:", item);

      if (item.usando_precio === 'uno') {
        item.precioseleccionado = item.precio_dos;
        item.usando_precio = 'dos';
      } else {
        item.precioseleccionado = item.precio_uno;
        item.usando_precio = 'uno';
      }

      this.actualizarDetalle(index);
    },
    async actualizarVenta() {
      console.log("Iniciando actualización de venta...");
      console.log("idventa:", this.ventaSeleccionada)
      console.log("array de productos:", this.arrayDetalle)
      try {
        const payload = {
          idventa: this.ventaSeleccionada.id,
          // CLIENTE
          cliente: {
            num_documento: this.documento,
            nombre: this.nombreCliente,
            telefono: this.telefonoCliente,
            tipo_documento: this.tipoDocumento
          },

          // DETALLES
          detalles: this.arrayDetalle.map(p => ({
            iddetalle: p.id ? p.id : null,
            idarticulo: p.idarticulo,
            cantidad: p.cantidad,
            precio: p.precioseleccionado,
            descuento: p.descuento,
            modo_venta: p.modoVenta
          })),

          total: Number(this.calcularTotal * parseFloat(this.monedaVenta[0])).toFixed(2)
        };

        console.log("Payload:", payload);

        const resp = await axios.post('/ventas/actualizar', payload);

        this.$toast.add({
          severity: "success",
          summary: "Venta Actualizada",
          detail: resp.data.message || "Los cambios fueron guardados correctamente."
        });
        this.listarVenta(1, this.buscar, this.criterio),
          this.modal2 = false;

      } catch (error) {
        console.error(error);
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudo actualizar la venta."
        });
      }
    },
    async editarVenta(ventaResumen) {
      // --- Acciones iniciales ---
      this.verificarAutorizacionDescuento();
      this.cargarBancos();
      this.scrollToTop();
      this.ventaSeleccionada = ventaResumen;
      this.tipoAccion2 = 2; // Modo edición
      this.modal2 = true;

      // Si formPago no existe, asegurar que exista
      if (!this.formPago) this.formPago = {};

      try {
        const id = ventaResumen.id || ventaResumen.idventa;
        if (!id) throw new Error("ID inválido");

        // ----------- LLAMADA AL BACKEND -----------
        const resp = await axios.get(`/ventas/obtener-completa/${id}`);

        const venta = resp.data.venta;
        const detalles = resp.data.detalles;

        // 🔹 Datos del cliente

        this.documento = resp.data.cliente.num_documento;
        this.nombreCliente = resp.data.cliente.nombre;
        this.telefonoCliente = resp.data.cliente.telefono;
        this.tipoDocumento = resp.data.cliente.tipo_documento;

        // 🔹 Bloquear edición del cliente cuando corresponda
        this.nombreClienteEditable = false;
        this.telefonoClienteEditable = false;
        this.direccionClienteEditable = false;

        // Si quieres bloquear también la búsqueda de cliente en edición:
        this.mostrarDesplegableCliente = false;
        // ----------- Paso 1: Detalles para el DataTable -----------
        this.arrayDetalle = detalles.map((item) => {

          const stockUnidad = Number(item.saldo_stock) || 0;
          const stockCaja = Number(item.saldo_stock_cajas) || 0;

          const precioUno = Number(item.precio_uno);
          const precioDos = Number(item.precio_dos);
          const precioActual = Number(item.precio);

          // 🔥 determinar cuál precio se usó
          let usandoPrecio = 'uno';
          if (precioDos && precioActual === precioDos) {
            usandoPrecio = 'dos';
          }

          return {
            id: item.id,
            idarticulo: item.idarticulo,
            articulo: item.articulo,
            unidad_envase: item.unidad_envase,

            cantidad: Number(item.cantidad) || 1,

            // 🔹 precios
            precio_uno: precioUno,
            precio_dos: precioDos,
            precioseleccionado: precioActual,
            usando_precio: usandoPrecio,

            modoVenta: item.modo_venta,
            descuento: item.descuento,
            subtotal: item.subtotal,

            saldo_stock: stockUnidad,
            saldo_stock_cajas: stockCaja,

            stocks: item.modo_venta === 'unidad' ? stockUnidad : stockCaja,
            stock_cajas: stockCaja,
            stock: stockUnidad,
            codigo_producto: item.codigo,
            editandoPrecio: false // 🔹 Estado para editar precio
          };
        });

        this.arrayProductos = this.arrayDetalle.map(det => ({
          id: det.id || ("nuevo_" + det.idarticulo),
          idarticulo: det.idarticulo,
          cantidad: det.cantidad,
          precioUnitario: det.precioseleccionado,
          descuento: det.descuento,
          modoVenta: det.modoVenta,
          subTotal: det.subtotal,
          saldo_stock: det.saldo_stock,
          saldo_stock_cajas: det.saldo_stock_cajas
        }));

        console.log("Array Productos para edición:", this.arrayProductos);
        console.log("Array Detalle para edición:", this.arrayDetalle);
        // ----------- Paso 2: Datos de pago -----------
        this.formPago.tipoVenta = venta.idtipo_venta || venta.tipoventa || 1;
        this.formPago.total = venta.total;

        // Recalcular totales si la función existe
        if (typeof this.actualizarTotales === "function") {
          this.actualizarTotales();
        }

      } catch (error) {
        console.error("Error al cargar venta:", error);
        if (this.$toast) {
          this.$toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudo cargar la venta para editar."
          });
        }
      }

      // ----------- ENFOQUE AUTOMÁTICO (COMPATIBLE CON VUE 2) -----------
      this.$nextTick(() => {
        setTimeout(() => {
          // Input de búsqueda
          if (this.$refs.inputCodigo && this.$refs.inputCodigo.$el) {
            this.$refs.inputCodigo.$el.focus();
          }

          // Cantidad del primer ítem
          const first = this.$refs["inputCantidad_0"];
          if (first && first.$el) {
            try {
              const input = first.$el.querySelector("input");
              if (input) input.focus();
            } catch (e) {
              console.warn("No se pudo enfocar el input de cantidad", e);
            }
          }

        }, 150);
      });
    },

    // Si tienes una función para recalcular totales
    actualizarTotales() {
      // ejemplo mínimo: recalcular calcularTotal si está usado en el template
      // Si ya tienes la reactividad, esto puede no ser necesario.
      this.$forceUpdate && this.$forceUpdate();
    },
    async guardarMontosCuotas() {
      try {

        const payload = this.cuotas.map(c => {
          let fechaHora = null;

          if (c.fecha_pago) {
            const now = new Date();
            const hora = now.toTimeString().substring(0, 8); // HH:mm:ss
            fechaHora = `${c.fecha_pago} ${hora}`;
          }

          return {
            ...c,
            fecha_pago: fechaHora
          };
        });

        await axios.post('/credito/actualizarCuotas', {
          cuotas: payload
        });

        Swal.fire("Éxito", "Cuotas actualizadas correctamente", "success");
        this.editarCuotas = false;
        await this.cargarCuotas(this.idVentaSeleccionada);
        await this.verVenta(this.idVentaSeleccionada);

      } catch (error) {
        Swal.fire("Error", "No se pudieron actualizar las cuotas", "error");
      }
    },

    async listarCuotas() {
      try {
        const response = await axios.get(`/credito/cuotas/${this.idcredito}`);

        this.cuotas = response.data.cuotas.map(c => ({
          ...c,

          // ✅ input type="date" necesita string "YYYY-MM-DD"
          fecha_pago: c.fecha_pago ? String(c.fecha_pago).substring(0, 10) : null,

          idtipo_pago: Number(c.idtipo_pago),
          idbanco: c.idbanco ? Number(c.idbanco) : null,
          bancoSeleccionado: null
        }));

      } catch (error) {
        console.error("Error al cargar cuotas:", error);
      }
    },




    recalcularSaldo(cuota) {
      // Si quieres recalcular saldo restante automáticamente:
      if (cuota.precio_cuota && cuota.precio_total) {
        cuota.saldo_restante = (cuota.precio_total - cuota.precio_cuota).toFixed(2);
      }
    },

    // Verificar si una cuota excede el saldo disponible
    cuotaExcedida(cuota) {
      if (!this.cuotas || this.cuotas.length === 0) return false;

      const monto = Number(cuota.precio_cuota || 0);
      const totalVenta = Number(this.total || 0);

      // Verificar si la cuota individual es mayor al total
      if (monto > totalVenta) return true;

      // Verificar si la suma de todas las cuotas excede el total
      const sumaCuotas = this.cuotas.reduce((acc, c) => {
        return acc + Number(c.precio_cuota || 0);
      }, 0);

      return sumaCuotas > totalVenta;
    },

    // Recalcular los saldos restantes de todas las cuotas
    recalcularSaldosCuotas() {
      if (!this.cuotas || this.cuotas.length === 0) return;

      const totalVenta = Number(this.total || 0);
      let saldoAcumulado = totalVenta;

      this.cuotas.forEach(cuota => {
        const montoCuota = Number(cuota.precio_cuota || 0) + Number(cuota.descuento || 0);
        saldoAcumulado -= montoCuota;
        if (saldoAcumulado < 0) saldoAcumulado = 0;
        cuota.saldo_restante = saldoAcumulado.toFixed(2);
      });
    },

    cambiarModoVenta(detalle) {
      if (detalle.modoVenta === 'unidad') {
        detalle.modoVenta = 'docena';
      } else if (detalle.modoVenta === 'docena') {
        detalle.modoVenta = 'caja';
      } else {
        detalle.modoVenta = 'unidad';
      }
      this.asignarPrecioPorModo(detalle);
      // Guardar el cambio de modo y precio en BD
      this.guardarCambioPrecio(detalle);
    },

    toggleEditarPrecio(detalle) {
      // Cambiar el estado de edición
      detalle.editandoPrecio = !detalle.editandoPrecio;

      // Si se está guardando (desactivando edición), llamar a guardarCambioPrecio
      if (!detalle.editandoPrecio) {
        this.guardarCambioPrecio(detalle);
      }
    },

    guardarCambioPrecio(detalle) {
      // Cancelar el debounce anterior si existe
      if (detalle._saveTimeout) {
        clearTimeout(detalle._saveTimeout);
      }

      // Establecer un nuevo debounce de 500ms
      detalle._saveTimeout = setTimeout(async () => {
        try {
          // Solo guardar si es una venta existente (tiene ID)
          if (!this.ventaSeleccionada || !this.ventaSeleccionada.id) {
            return;
          }

          const payload = {
            idventa: this.ventaSeleccionada.id,
            iddetalle: detalle.id,
            precio: detalle.precioseleccionado,
            modo_venta: detalle.modoVenta,
            cantidad: detalle.cantidad,
            descuento: detalle.descuento
          };

          await axios.post('/ventas/actualizar-detalle-precio', payload);

          // Mostrar notificación de éxito (opcional, puede ser muy frecuente)
          // this.$toast.add({
          //   severity: 'success',
          //   summary: 'Precio actualizado',
          //   detail: 'El precio ha sido guardado correctamente.',
          //   life: 2000
          // });
        } catch (error) {
          console.error('Error al guardar el precio:', error);
          this.$toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'No se pudo guardar el cambio de precio.',
            life: 3000
          });
        }
      }, 500);
    },

    seleccionarDescuento() {
      this.tipoPagoCuota = 'descuento';

      // Asignar saldo restante al input
      if (this.ventaSeleccionada && this.ventaSeleccionada.saldo_restante) {
        this.recibidoCuota = parseFloat(this.ventaSeleccionada.saldo_restante);
      }
    },

    validarMontoCuota(cuota) {
      const monto = Number(cuota.precio_cuota || 0);
      const totalVenta = Number(this.total || 0);

      // ❌ Cuota mayor al total de la venta
      if (monto > totalVenta) {
        this.$toast.add({
          severity: 'warn',
          summary: 'Monto inválido',
          detail: 'El monto de la cuota no puede ser mayor al total de la venta (' + totalVenta.toFixed(2) + ').',
          life: 3500
        });

        cuota.precio_cuota = totalVenta;
        this.recalcularSaldosCuotas();
        return;
      }

      // 🔢 Validar suma total de cuotas
      const sumaCuotas = this.cuotas.reduce((acc, c) => {
        return acc + Number(c.precio_cuota || 0);
      }, 0);

      if (sumaCuotas > totalVenta) {
        const excedente = (sumaCuotas - totalVenta).toFixed(2);
        this.$toast.add({
          severity: 'error',
          summary: 'Monto excedido',
          detail: `La suma de las cuotas (${sumaCuotas.toFixed(2)}) supera el total de la venta por ${excedente}.`,
          life: 4000
        });

        // Ajustar la cuota actual para que no exceda
        const nuevoMonto = monto - (sumaCuotas - totalVenta);
        cuota.precio_cuota = nuevoMonto > 0 ? nuevoMonto : 0;
      }

      // Recalcular saldos correctamente
      this.recalcularSaldosCuotas();
    },


    resetBanco() {
      this.bancoSeleccionado = null;
      this.idbanco = null;
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

    async registrarPagoCuota() {
      let me = this;

      if (this.recibidoCuota <= 0) {
        this.$toast.add({
          severity: 'warn',
          summary: 'Monto inválido',
          detail: 'Debe ingresar un monto mayor a cero.',
          life: 3000
        });
        return;
      }

      let payload = {
        idventa: this.ventaSeleccionada.id,
        monto: this.recibidoCuota,
        descuento: 0,
        idtipo_pago: null,
        idbanco: null,
      };

      // ⛔ Caso DESCUENTO
      if (this.tipoPagoCuota === 'descuento') {

        payload.idtipo_pago = 5;

      }

      // 💰 Caso EFECTIVO
      else if (this.tipoPagoCuota === 'efectivo') {
        payload.idtipo_pago = 1;
      }

      // 🏦 Caso BANCO
      else if (this.tipoPagoCuota === 'banco') {
        payload.idtipo_pago = 7;
        payload.idbanco = this.idbanco ? this.idbanco : null;
      }

      try {
        const res = await axios.post('/venta/abonar-cuota', payload);

        if (res.data && res.data.error) {
          swal('Advertencia', res.data.error, 'warning');
          return;
        }

        swal(
          'Registro exitoso!',
          this.tipoPagoCuota === 'descuento'
            ? 'Descuento aplicado correctamente'
            : 'Pago registrado con éxito',
          'success'
        );

        this.modalCobro = false;

        if (me.filtroVentasActivo === 'recibo') {
          me.listarVentaR(1, me.buscar, me.criterio);
        } else if (me.filtroVentasActivo === 'factura') {
          me.listarVentaF(1, me.buscar, me.criterio);
        } else {
          me.listarVenta(1, me.buscar, me.criterio);
        }

        this.recibidoCuota = 0;
        this.tipoPagoCuota = 'efectivo';

      } catch (error) {
        console.error(error);

        swal(
          'Error',
          'Ocurrió un problema al registrar el pago.',
          'error'
        );
      }
    },

    abrirModalCobro(venta) {
      this.ventaSeleccionada = venta;
      this.recibidoCuota = 0;
      this.tipoPagoCuota = "efectivo";
      this.modalCobro = true;
      this.bancoSeleccionado = null;
      this.cargarBancos();
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



    calcularSubtotal() {
      let subtotal = 0.0;

      for (let i = 0; i < this.arrayDetalle.length; i++) {
        let detalle = this.arrayDetalle[i];

        // 🔹 Calcular descuento como porcentaje
        const precio = parseFloat(detalle.precioseleccionado) || 0;
        const cantidad = parseFloat(detalle.cantidad) || 0;
        const porcentajeDescuento = parseFloat(detalle.descuento) || 0;

        const totalBruto = precio * cantidad;
        const montoDescuento = totalBruto * (porcentajeDescuento / 100);
        let totalDetalle = totalBruto - montoDescuento;

        if (totalDetalle < 0) totalDetalle = 0;

        subtotal += totalDetalle;
      }

      return subtotal;
    },
    handleClickFuera: function (event) {
      // 🔹 Solo hacer algo si el desplegable está visible
      if (!this.mostrarDesplegable) return;

      // 🔹 Referencia al input de búsqueda
      var buscador = this.$refs.inputCodigo
        ? this.$refs.inputCodigo.$el || this.$refs.inputCodigo
        : null;

      // 🔹 Referencia a la lista desplegable
      var lista = document.querySelector(".desplegable-simple");

      // 🔹 Si el clic fue dentro del buscador o dentro de la lista, no hacer nada
      if (
        (buscador && buscador.contains(event.target)) ||
        (lista && lista.contains(event.target))
      ) {
        return;
      }

      // 🔹 Cerrar el desplegable
      this.mostrarDesplegable = false;

      // 🔹 Enfocar el campo "cantidad" del primer artículo agregado
      var self = this;
      this.$nextTick(function () {
        if (self.arrayDetalle.length > 0) {
          // 🔹 Primer artículo (índice 0)
          var refInput = self.$refs["inputCantidad_0"];

          if (refInput && refInput.$el) {
            var inputElement = refInput.$el.querySelector("input");
            if (inputElement) {
              inputElement.focus();
              inputElement.select(); // opcional: seleccionar el valor
            }
          }
        }
      });
    },
    async verStockPorSucursal(articulo) {
      this.articuloSeleccionado = articulo;
      console.log("Artículo seleccionado para ver stock por sucursal:", articulo);
      try {
        const response = await axios.get(`/inventario/stockPorSucursal?idarticulo=${articulo.id}`);
        this.stockPorSucursal = response.data.stocks || [];
        this.dialogStockVisible = true;
      } catch (error) {
        console.error("Error al obtener stock por sucursal:", error);
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: "No se pudo obtener el stock por sucursal",
          life: 3000,
        });
      }
    },
    confirmarDesactivarFactura(venta, opcionReposicionCaja) {
      this.anularFactura(venta.cuf, opcionReposicionCaja, venta);
    },
    continuarDesactivarVenta() {
      if (!this.opcionReposicionCaja) {
        Swal.fire({
          icon: 'warning',
          title: 'Debe seleccionar un tipo de devolución',
          text: 'Tiene que marcar un tipo de devolución: EFECTIVO o QR'
        });
        return;
      }
      if (this.ventaAnularSeleccionada.tipo_comprobante === 'RESIVO') {
        this.confirmarDesactivarVenta(this.ventaAnularSeleccionada.id, this.opcionReposicionCaja);
      } else if (this.ventaAnularSeleccionada.tipo_comprobante === 'FACTURA') {
        this.confirmarDesactivarFactura(this.ventaAnularSeleccionada, this.opcionReposicionCaja);
      }
    },
    confirmarDesactivarVenta(id, tipoReposicion) {
      swal({
        title: "Esta seguro de anular esta venta?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Aceptar!",
        cancelButtonText: "Cancelar",
        confirmButtonClass: "btn btn-success",
        cancelButtonClass: "btn btn-danger",
        buttonsStyling: false,
        reverseButtons: true,
      }).then((result) => {
        if (result.value) {
          // Cierra el diálogo solo si confirma
          this.dialogAnularVentaVisible = false;
          let me = this;
          axios
            .put("/venta/desactivar", {
              id: id,
              tipo_reposicion: tipoReposicion // aquí envías la opción seleccionada
            })
            .then(function (response) {
              if (me.filtroVentasActivo === 'recibo') {
                me.listarVentaR(1, me.buscar, me.criterio);
              } else if (me.filtroVentasActivo === 'factura') {
                me.listarVentaF(1, me.buscar, me.criterio);
              } else {
                me.listarVenta(1, me.buscar, me.criterio);
              } swal(
                "Anulado!",
                "La venta ha sido anulado con éxito.",
                "success"
              );
            })
            .catch(function (error) {
              console.log(error);
            });
        }
        // Si cancela, el diálogo sigue abierto
      });
    },
    cambiarTipoventa(tipoventa, buscar, criterio) {
      this.tipocompro = tipoventa;
      console.log("estos es:", this.tipocompro);
      this.listarventaReporte(1, buscar, criterio);
    },

    listarventaReporte(page, buscar, criterio) {
      if (this.tipocompro === "Factura") {
        this.listarVentaF(page, buscar, criterio);
      } else if (this.tipocompro === "Recibo") {
        this.listarVentaR(page, buscar, criterio);
      } else {
        this.listarVenta(page, buscar, criterio);
      }
    },
    handleResize() {
      this.mostrarLabel = window.innerWidth > 768; // cambia según breakpoint deseado
    },

    mostrarStock(articulo) {
      console.log("Artículo recibido:", articulo);
      if (articulo.descripcion_fabrica == '1') {
        return "∞"; // o "Infinito" si prefieres texto
      }

      return articulo.saldo_stock == 0
        ? "No registrado en stock"
        : articulo.saldo_stock;
    },
    handleKeyPress(event) {
      // Detectar Shift + R
      if (event.shiftKey && event.key === "B") {
        this.abrirModal();
      }
    },

    async buscarVenta() {
      try {
        if (this.searchTimeout) {
          clearTimeout(this.searchTimeout);
        }
        this.searchTimeout = setTimeout(async () => {
          this.isLoading = true; // Activar loading al empezar la búsqueda
          try {
            if (this.filtroVentasActivo === 'recibo') {
              await this.listarVentaR(1, this.buscar, this.criterio);
            } else if (this.filtroVentasActivo === 'factura') {
              await this.listarVentaF(1, this.buscar, this.criterio);
            } else {
              await this.listarVenta(1, this.buscar, this.criterio);
            }
          } finally {
            setTimeout(() => {
              this.isLoading = false; // Desactivar loading después de un breve momento
            }, 500);
          }
        }, 300); // Pequeño delay antes de ejecutar la búsqueda
      } catch (error) {
        console.error("Error en la búsqueda:", error);
        this.isLoading = false;
      }
    },

    validarYAvanzar() {
      const errores = [];
      if (this.step === 2) {
        if (!this.idAlmacen) errores.push("Seleccione un almacén");
      }
      if (errores.length > 0) {
        const mensaje = errores.join("\n");
        swal("Campos incompletos", mensaje, "warning");
      } else {
        this.nextStep();
      }
    },

    nextStep() {
      if (this.step < 3) {
        this.step++;

        // Si se pasa al paso 2 → enfocar documento
        if (this.step === 2) {
          this.$nextTick(() => {
            if (this.$refs.inputDocumentoCliente && this.$refs.inputDocumentoCliente.$el) {
              this.$refs.inputDocumentoCliente.$el.focus();
            }
          });
        }
      }
    },
    prevStep() {
      if (this.step > 1) {
        this.step--;
      }
    },
    actualizarFechaHora() {
      const now = new Date();
      this.alias = now.toLocaleString();
    },
    verificarEstado() {
      this.isLoading = true;
      axios
        .post("/qr/verificarestado", {
          alias: this.aliasverificacion,
        })
        .then((response) => {
          this.estadoTransaccion = response.data;
        })
        .catch((error) => {
          console.error(error);
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    generarQr() {
      this.isLoading = true;
      this.actualizarFechaHora(); // Actualiza el alias siempre antes de generar QR
      this.aliasverificacion = this.alias;
      axios
        .post("/qr/generarqr", {
          alias: this.alias,
          monto: this.calcularTotal,
        })
        .then((response) => {
          const imagenBase64 = response.data.objeto.imagenQr;
          this.qrImage = `data:image/png;base64,${imagenBase64}`;
          this.showQrDialog = true; // Abrir el modal QR
        })
        .catch((error) => {
          console.error(error);
        })
        .finally(() => {
          this.isLoading = false;
        });
      // No vaciar alias aquí para evitar el bug
      this.montoQR = 0;
    },
    calcularPrecioUnitario(articulo) {
      if (
        this.totalCantidades >= this.datosFormularioPE.rango_inicio_r1 &&
        this.totalCantidades <= this.datosFormularioPE.rango_final_r1
      ) {
        return this.datosFormularioPE.precio_r1;
      } else if (
        this.totalCantidades >= this.datosFormularioPE.rango_inicio_r2 &&
        this.totalCantidades <= this.datosFormularioPE.rango_final_r2
      ) {
        return this.datosFormularioPE.precio_r2;
      } else if (
        this.totalCantidades >= this.datosFormularioPE.rango_inicio_r3 &&
        this.totalCantidades <= this.datosFormularioPE.rango_final_r3
      ) {
        return this.datosFormularioPE.precio_r3;
      } else {
        // Precio predeterminado si no está en ningún rango
        return articulo.precio_costo_unid;
      }
    },
    abrirTipoVenta() {
      this.verificarAutorizacionDescuento();
      this.cargarBancos();
      this.modal2 = true;
      this.cliente = this.nombreCliente;
      this.tipoAccion2 = 1;
      this.arrayDetalle = [];
      this.arrayProductos = [];
      this.documento = "";
      this.nombreCliente = "";
      this.telefonoCliente = "";
      this.saldoFavorCliente = 0;
      this.scrollToTop();
      this.$nextTick(() => {
        setTimeout(() => {
          if (this.$refs.inputCodigo && this.$refs.inputCodigo.$el) {
            this.$refs.inputCodigo.$el.focus();
          }
        }, 150);
      });
    },
    scrollToTop() {
      $("html, body").animate(
        {
          scrollTop: 0,
        },
        50
      );
    },
    obtenerDescuentoVigente(producto) {
      if (!producto.descuento || !producto.fecha_venc_descuento) {
        return 0;
      }

      const fechaVencimiento = new Date(producto.fecha_venc_descuento);
      const hoy = new Date();

      if (fechaVencimiento >= hoy) {
        return parseFloat(producto.descuento);
      } else {
        return 0;
      }
    },
    actualizarDetalle(index) {
      const det = this.arrayDetalle[index];
      if (!det) return;

      // Buscar el producto según idarticulo (seguro y estable)
      const prod = this.arrayProductos.find(p => p && p.idarticulo === det.idarticulo);
      if (!prod) {
        console.warn("Producto no encontrado para idarticulo:", det.idarticulo);
        return;
      }

      // Actualizar cantidad y precio
      prod.cantidad = det.cantidad;
      prod.precioUnitario = det.precioseleccionado;

      const subtotal = det.cantidad * (det.precioseleccionado - det.descuento); // Descuento por unidad
      const total = subtotal > 0 ? subtotal : 0; // Evitar negativo

      prod.subTotal = total;
      det.total = total;

      // Recalcular totales de toda la venta
      this.calcularTotal();
    },
    async verificarComunicacion() {
      try {
        const response = await axios.post("/venta/verificarComunicacion");
        if (response.data.RespuestaComunicacion.transaccion === true) {
          document.getElementById("comunicacionSiat").innerHTML =
            response.data.RespuestaComunicacion.mensajesList.descripcion;
          document.getElementById("comunicacionSiat").className =
            "badge bg-success";
        } else {
          document.getElementById("comunicacionSiat").innerHTML =
            "Desconectado";
          document.getElementById("comunicacionSiat").className =
            "badge bg-secondary";
        }
      } catch (error) {
        console.log(error);
      }
    },

    async cuis() {
      try {
        const response = await axios.post("/venta/cuis");
        if (response.data.RespuestaCuis.transaccion === false) {
          document.getElementById("cuis").innerHTML =
            "CUIS: " + response.data.RespuestaCuis.codigo;
          document.getElementById("cuis").className = "badge bg-primary";
        } else {
          document.getElementById("cuis").innerHTML = "CUIS: Inexistente";
          document.getElementById("cuis").className = "badge bg-secondary";
        }
      } catch (error) {
        console.log(error);
      }
    },
    async cufd() {
      try {
        const response = await axios.post("/venta/cufd");
        console.log("Respuesta Cufd: " + response.data);
        if (response.data.transaccion != false) {
          document.getElementById("cufd").innerHTML =
            "CUFD vigente: " + response.data.fechaVigencia.substring(0, 16);
          document.getElementById("direccion").innerHTML =
            response.data.direccion;
          document.getElementById("cufdValor").innerHTML = response.data.codigo;
          document.getElementById("cufd").className = "badge bg-info";
        } else {
          document.getElementById("cufd").innerHTML = "No existe CUFD vigente";
          document.getElementById("cufd").className = "badge bg-secondary";
        }
      } catch (error) {
        console.log(error);
      }
    },

    async ejecutarSecuencial() {
      try {
        this.cargandoFactVisual = true;
        await this.verificarComunicacion();
        await this.cuis();
        await this.cufd();
        this.actualizarEstadoFactVisual();
      } catch (error) {
        this.cargandoFactVisual = false;
        this.estadoFactVisual = 'offline';
        console.log("Error en la ejecución secuencial:", error);
      }
    },

    actualizarEstadoFactVisual() {
      // Lee los textos actuales de los badges (compatible con JS antiguo)
      var siatElem = document.getElementById('comunicacionSiat');
      var cuisElem = document.getElementById('cuis');
      var cufdElem = document.getElementById('cufd');
      var direccionElem = document.getElementById('direccion');
      var siat = siatElem ? siatElem.innerText : '';
      var cuis = cuisElem ? cuisElem.innerText : '';
      var cufd = cufdElem ? cufdElem.innerText : '';
      var direccion = direccionElem ? direccionElem.innerText : '';
      // Si cualquiera tiene error principal, es OFFLINE
      var offline = (
        siat.indexOf('Desconectado') !== -1 ||
        cuis.indexOf('Inexistente') !== -1 ||
        cufd.indexOf('No existe') !== -1 ||
        direccion.indexOf('No hay') !== -1
      );
      // Si todos están OK
      var ok = (
        siat && cuis && cufd && direccion &&
        siat.indexOf('Desconectado') === -1 &&
        cuis.indexOf('Inexistente') === -1 &&
        cufd.indexOf('No existe') === -1 &&
        direccion.indexOf('No hay') === -1
      );
      this.cargandoFactVisual = false;
      if (ok) {
        this.estadoFactVisual = 'online';
      } else if (offline) {
        this.estadoFactVisual = 'offline';
      } else {
        this.estadoFactVisual = 'offline'; // fallback, nunca amarillo
      }
    },

    validarDescuentoGiftCard() {
      if (this.descuentoGiftCard >= this.calcularTotal) {
        this.descuentoGiftCard = 0;
        alert("El descuento Gift Card no puede ser mayor o igual al total.");
      }
    },
    buscarPromocion(idArticulo) {
      axios
        .get(`/promocion/id?idArticulo=${idArticulo}`)
        .then((response) => {
          this.arrayPromocion = response.data.promocion;
        })
        .catch((error) => {
          console.error("Error:", error);
        });
    },
    async obtenerDatosUsuario() {
      try {
        const response = await axios.get("/venta");
        this.usuarioAutenticado = response.data.usuario.usuario;
        this.usuario_autenticado = this.usuarioAutenticado;
        this.idrol = response.data.usuario.idrol;
        this.idsucursalAutenticado = response.data.usuario.idsucursal;
        console.log("Obtener Datos Usuario: " + this.idsucursalAutenticado);
        this.puntoVentaAutenticado = response.data.codigoPuntoVenta;
      } catch (error) {
        console.error(error);
      }
    },
    async obtenerDatosSesionYComprobante() {
      try {
        const idsucursal = this.idsucursalAutenticado;
        console.log("El idsucursal es: " + idsucursal);
        const response = await axios.get("/obtener-ultimo-comprobante", {
          params: {
            idsucursal: idsucursal,
          },
        });
        const lastComprobante = response.data.last_comprobante;
        this.last_comprobante = lastComprobante;
        this.last_comprobante++;
        this.num_comprob = this.last_comprobante.toString().padStart(5, "0");
        console.log("El ultimo comprobante es: " + this.last_comprobante);
      } catch (error) {
        console.error("Error al obtener el último comprobante:", error);
      }
    },
    async ejecutarFlujoCompleto() {
      await this.obtenerDatosUsuario();
      await this.obtenerDatosSesionYComprobante();
    },
    listarVenta(page, buscar, criterio, tipoVenta = '') {
      let me = this;
      let url = `/venta?page=${page}&buscar=${buscar}&criterio=${criterio}`;

      if (tipoVenta !== '') {
        url += `&tipo_venta=${tipoVenta}`;
      }

      if (this.filtroSucursal) url += `&sucursal_id=${this.filtroSucursal}`;
      if (this.fechaInicio) url += `&fecha_inicio=${this.fechaInicio}`;
      if (this.fechaFin) url += `&fecha_fin=${this.fechaFin}`;

      console.log("URL generada: ", url);

      return axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          if (respuesta.ventas && respuesta.ventas.data) {
             me.arrayVenta = respuesta.ventas.data;
             me.pagination = respuesta.pagination;
          } else {
             me.arrayVenta = respuesta.ventas;
          }
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    limpiarFiltros() {
      this.filtroSucursal = '';
      this.fechaInicio = '';
      this.fechaFin = new Date().toISOString().split('T')[0];
      this.buscar = '';
      this.buscarVenta();
    },

    listarVentaF(page, buscar, criterio) {
      let me = this;
      var url =
        "/ventaReporteFactura?page=" +
        page +
        "&buscar=" +
        buscar +
        "&criterio=" +
        criterio;
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arrayVenta = respuesta.ventas.data;
          me.pagination = respuesta.pagination;
          console.log("lista: ", me.arrayVenta);
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    async listarVentaR(page, buscar, criterio) {
      try {
        const url = `/ventaReporte?page=${page}&buscar=${buscar}&criterio=${criterio}`;
        const response = await axios.get(url);
        const respuesta = response.data;
        this.arrayVenta = respuesta.ventas.data;
        this.pagination = respuesta.pagination;
      } catch (error) {
        console.error("Error al listar ventas:", error);
        throw error; // Re-lanzar el error para manejarlo en mounted
      }
    },
    async listarVentaPorTipo(page, buscar = '', criterio = '', tipoVenta) {
      try {
        const response = await axios.get('/venta/filtrar', {
          params: { page, buscar, criterio, tipo_venta: tipoVenta }
        });

        this.arrayVenta = response.data.ventas.data;
        this.pagination = response.data.pagination;
      } catch (error) {
        console.error("Error al filtrar ventas:", error);
      }
    },

    selectSucursal() {
      let me = this;
      var url = '/sucursal/selectSucursal'; 
      
      return axios 
        .get(url)
        .then(function (response) {
          me.arraySucursales = response.data.sucursales || response.data; 
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    selectCliente(numero) {
      let me = this;
      var url = "/cliente/selectClientePorNumero?numero=" + numero;
      axios
        .get(url)
        .then(function (response) {
          let respuesta = response.data;
          q: numero;
          me.arrayCliente = respuesta.clientes;
          console.log(me.arrayCliente);
          me.cantidadClientes = me.arrayCliente.length;
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    async buscarArticulo() {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(async () => {
        if (!this.selectedAlmacen) {
          this.mostrarDesplegable = false;
          swal("Advertencia", "Selecciona un almacén primero", "warning");
          return;
        }
        if (!this.codigo.trim()) {
          this.mostrarDesplegable = false;
          return;
        }

        try {
          const response = await axios.get(
            `/articulo/buscarArticuloVenta?filtro=${this.codigo}&idalmacen=${this.idAlmacen}`
          );
          this.resultadosBusqueda = response.data.articulos || [];

          this.mostrarDesplegable = this.resultadosBusqueda.length > 0;
          this.indiceSeleccionado = 0;

        } catch (error) {
          console.error("Error al buscar artículo:", error);
          this.mostrarDesplegable = false;
        }
      }, 200);
    },

    moverSeleccion(direccion) {
      if (!this.mostrarDesplegable || this.resultadosBusqueda.length === 0) return;

      if (direccion === "abajo") {
        this.indiceSeleccionado = (this.indiceSeleccionado + 1) % this.resultadosBusqueda.length;
      } else if (direccion === "arriba") {
        this.indiceSeleccionado =
          (this.indiceSeleccionado - 1 + this.resultadosBusqueda.length) % this.resultadosBusqueda.length;
      }
    },

    seleccionarConEnter() {
      if (this.indiceSeleccionado >= 0 && this.indiceSeleccionado < this.resultadosBusqueda.length) {
        this.seleccionarArticulo(this.resultadosBusqueda[this.indiceSeleccionado]);
      }
    },

    seleccionarArticulo(articulo) {
      this.desdeModal = false;
      this.codigo = articulo.codigo;
      this.precioseleccionado = articulo.precio_uno;

      const descuentoVigente = this.obtenerDescuentoVigente(articulo);
      articulo.descuento = descuentoVigente;

      this.arraySeleccionado = articulo;
      this.mostrarDesplegable = false;


      this.agregarDetalle();

      setTimeout(() => {
        this.codigo = "";
      }, 100);
    },
    onPageChange(event) {
      let page = event.page + 1; // PrimeVue pages are 0-based, while your logic uses 1-based
      this.cambiarPagina(page, this.buscar, this.criterio);
    },
    cambiarPagina(page, buscar, criterio) {
      this.pagination.current_page = page;
      if (this.filtroVentasActivo === "factura") {
        this.listarVentaF(page, buscar, criterio);
      } else if (this.filtroVentasActivo === "recibo") {
        this.listarVentaR(page, buscar, criterio);
      } else if (this.filtroVentasActivo === "todos") {
        this.listarVenta(page, buscar, criterio);
      }
    },
    encuentra(id) {
      var sw = 0;
      for (var i = 0; i < this.arrayDetalle.length; i++) {
        if (this.arrayDetalle[i].idarticulo == id) {
          sw = true;
        }
      }
      return sw;
    },
    eliminarDetalle(id) {
      const index = this.arrayDetalle.findIndex((item) => item.id === id);
      if (index !== -1) {
        this.arrayDetalle.splice(index, 1);
        this.arrayProductos.splice(index, 1);
        this.calcularTotal();
      }
    },
    eliminarKit(id) {
      const indicesEliminar = [];
      for (let i = 0; i < this.arrayDetalle.length; i++) {
        if (this.arrayDetalle[i].idkit === id) {
          indicesEliminar.push(i);
        }
      }
      indicesEliminar.forEach((index) => {
        this.arrayProductos.splice(index, 1);
      });
      for (let i = indicesEliminar.length - 1; i >= 0; i--) {
        this.arrayDetalle.splice(indicesEliminar[i], 1);
      }
    },

    verificarFactura(cuf, numeroFactura) {
      var url =
        "https://siat.impuestos.gob.bo/consulta/QR?nit=8033811015&cuf=" +
        cuf +
        "&numero=" +
        numeroFactura +
        "&t=2";
      window.open(url);
    },
    abrirDialogAnularFactura(venta) {
      this.ventaAnularSeleccionada = venta;
      this.dialogAnularVentaVisible = true;
      this.opcionReposicionCaja = 'efectivo';
    },
    async anularFactura(cuf, opcionReposicionCaja, venta) {
      const me = this;

      const confirm = await swal({
        title: "¿Está seguro de anular la factura?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Aceptar",
        cancelButtonText: "Cancelar",
        buttonsStyling: true,
        reverseButtons: true,
      });

      if (!confirm.value) return;

      try {
        // 🔄 Activar loading global
        me.isLoading = true;

        // Obtener motivos
        const response = await axios.get("/factura/obtenerDatosMotivoAnulacion");
        me.arrayMotivosAnulacion = response.data.motivo_anulaciones;

        let options = {};
        me.arrayMotivosAnulacion.forEach((motivo) => {
          if (motivo && typeof motivo.descripcion !== "undefined") {
            options[motivo.codigo] = motivo.descripcion;
          }
        });

        // 🚫 Detener el loading antes del segundo swal
        me.isLoading = false;

        const motivoResult = await swal({
          title: "Seleccione un motivo de anulación",
          input: "select",
          inputOptions: options,
          inputPlaceholder: "Seleccione un motivo",
          showCancelButton: true,
          inputValidator: (value) => {
            return new Promise((resolve, reject) => {
              if (value !== "") resolve();
              else reject("Debe seleccionar un motivo");
            });
          },
        });

        if (!motivoResult.value) return;

        // 🔄 Reactivar loading durante la anulación
        me.isLoading = true;

        const anulacion = await axios.get(
          `/factura/anular/${cuf}/${motivoResult.value}/${opcionReposicionCaja}/${venta.id}/${venta.total}`
        );

        const data = anulacion.data;

        // ✅ Detener loading
        me.isLoading = false;

        if (data.success && data.mensaje === "ANULACION CONFIRMADA") {
          await swal("FACTURA ANULADA", data.mensaje, "success");

          if (me.filtroVentasActivo === "recibo") {
            me.listarVentaR(1, me.buscar, me.criterio);
          } else if (me.filtroVentasActivo === "factura") {
            me.listarVentaF(1, me.buscar, me.criterio);
          } else {
            me.listarVenta(1, me.buscar, me.criterio);
          }

          me.dialogAnularVentaVisible = false;
        } else {
          swal("ANULACION RECHAZADA", data.mensaje || "Respuesta inesperada", "warning");
        }
      } catch (error) {
        console.error(error);
        me.isLoading = false;
        swal("ERROR", "Ocurrió un error al anular la factura", "error");
      }
    },

    agregarDetalle() {
      const envase = this.arraySeleccionado.unidad_envase > 0 ? this.arraySeleccionado.unidad_envase : 1;
      const stockEnCajasCalculado = Math.floor(this.arraySeleccionado.saldo_stock / envase);

      const cantidad = this.cantidad * this.unidadPaquete;

      if (this.saldosNegativos === 0 && this.arraySeleccionado.saldo_stock < cantidad) {
        this.toastWarning("No hay stock disponible");
        return;
      }

      const precioUnitario = parseFloat(this.precioseleccionado);
      const descuento = (parseFloat(this.arraySeleccionado.descuento) || 0) * cantidad;
      const total = (precioUnitario * cantidad - ((parseFloat(this.arraySeleccionado.descuento) || 0) * cantidad)).toFixed(2);
      const existente = this.arrayDetalle.find((item) => item.idarticulo === this.arraySeleccionado.id);

      if (existente) {
        existente.cantidad += cantidad;

        // Descuento por unidad * cantidad total
        const nuevoDescuento = (parseFloat(existente.descuento) / existente.cantidad) * existente.cantidad;
        // Mejor: guardar descuento por unidad aparte, y calcular total = (precio - descuentoPorUnidad) * cantidad

        existente.total = ((precioUnitario * existente.cantidad) - ((parseFloat(this.arraySeleccionado.descuento) || 0) * existente.cantidad)).toFixed(2);
      } else {
        const nuevoDetalle = {
          id: Date.now(),
          idkit: -1,
          idarticulo: this.arraySeleccionado.id,
          articulo: this.arraySeleccionado.nombre,
          medida: this.arraySeleccionado.medida,
          unidad_envase: envase,
          cantidad: parseInt(this.cantidad) || 1,
          cantidad_paquetes: envase,
          precio: precioUnitario,
          descuento: (parseFloat(this.arraySeleccionado.descuento) || 0) * this.cantidad,
          stock: this.arraySeleccionado.saldo_stock,
          stock_cajas: stockEnCajasCalculado,
          precioseleccionado: parseFloat(this.precioseleccionado) || 0,
          total: total,
          descripcion_fabrica: this.arraySeleccionado.descripcion_fabrica,
          codigo_producto: this.arraySeleccionado.codigo,
          precio_uno: parseFloat(this.arraySeleccionado.precio_uno || 0),
          precio_dos: parseFloat(this.arraySeleccionado.precio_dos || 0),
          precio_tres: parseFloat(this.arraySeleccionado.precio_tres || 0),
          unidad_envase: parseInt(this.arraySeleccionado.unidad_envase) || 1,
          usando_precio: 'uno',
          modoVenta: 'unidad',
          editandoPrecio: false,
        };

        this.asignarPrecioPorModo(nuevoDetalle);

        this.arrayDetalle.push(nuevoDetalle);
        console.log("Detalle agregado:", this.arrayDetalle);
      }

      const productoExistente = this.arrayProductos.find((p) => p.codigoProducto === this.arraySeleccionado.codigo);

      if (productoExistente) {
        productoExistente.cantidad += cantidad;
        const nuevoMontoDescuento = productoExistente.precioUnitario * productoExistente.cantidad * (productoExistente.descuento / 100);
        productoExistente.montoDescuento = nuevoMontoDescuento.toFixed(2);
        productoExistente.subTotal = (productoExistente.precioUnitario * productoExistente.cantidad - nuevoMontoDescuento).toFixed(2);
      } else {
        const nuevoProducto = {
          actividadEconomica: this.arraySeleccionado.actividadEconomica,
          codigoProductoSin: this.arraySeleccionado.codigoProductoSin,
          codigoProducto: this.arraySeleccionado.codigo,
          descripcion: this.arraySeleccionado.nombre,
          cantidad: cantidad,
          unidadMedida: this.arraySeleccionado.codigoClasificador,
          precioUnitario: precioUnitario.toFixed(2),
          descuento: this.arraySeleccionado.descuento,
          montoDescuento: descuento,
          subTotal: total,
          numeroSerie: null,
          numeroImei: null,
        };
        this.arrayProductos.push(nuevoProducto);
      }

      this.precioBloqueado = true;
      this.arraySeleccionado = null;
      this.cantidad = 1;
      this.unidadPaquete = 1;
      this.descuentoProducto = 0;

      this.$toast.add({
        severity: "success",
        summary: "Producto agregado",
        detail: "El producto fue agregado al carrito correctamente",
        life: 2500,
      });
    },

    agregarDetalleModalProducto(data) {
      if (data.saldo_stock == 0) {
        this.toastWarning("No hay stock disponible");
        return;
      }

      this.desdeModal = true;
      this.codigo = data.codigo;
      this.precioseleccionado = data.precio_uno;

      const descuentoVigente = this.obtenerDescuentoVigente(data);
      data.descuento = descuentoVigente;

      this.arraySeleccionado = data;
      this.mostrarDesplegable = false;
      this.agregarDetalle();
    },

    async agregarDetalleModal(data, tipo = "medicamento") {
      // Si es medicamento, validar stock
      if (tipo === "medicamento") {
        if (data.saldo_stock == 0) {
          this.toastWarning("No hay stock disponible");
          return;
        }

        this.codigo = data.codigo;
        this.buscarPromocion(data.id);
        this.precioseleccionado = data.precio_uno;

        // ✅ Buscar si ya existe en el carrito
        const existente = this.arrayDetalle.find(
          (d) => d.idarticulo === data.id && d.tipo === "medicamento"
        );

        if (existente) {
          // Si existe → sumar cantidad
          if (existente.cantidad + 1 > data.saldo_stock) {
            Swal.fire({
              icon: "warning",
              title: "Stock insuficiente",
              text: "No puedes agregar más unidades de este producto.",
            });
            return;
          }

          existente.cantidad += 1;
          existente.total = existente.cantidad * existente.precio;
          console.log("Cantidad actualizada:", existente);
        } else {
          // Si no existe → agregar nuevo
          const nuevoDetalle = {
            id: Date.now(),
            idkit: -1,
            idarticulo: data.id,
            articulo: data.nombre,
            medida: data.medida || "",
            unidad_envase: data.unidad_envase || 1,
            cantidad: 1,
            cantidad_paquetes: 1,
            precio: data.precio_uno,
            descuento: 0,
            stock: data.saldo_stock,
            precioseleccionado: data.precio_uno,
            total: data.precio_uno,
            tipo: tipo,
            editandoPrecio: false,
          };
          this.arrayDetalle.push(nuevoDetalle);

          const nuevoProducto = {
            actividadEconomica: data.actividadEconomica || "",
            codigoProductoSin: data.codigoProductoSin || "",
            codigoProducto: data.codigo || "",
            descripcion: data.nombre,
            cantidad: 1,
            unidadMedida: data.codigoClasificador || "",
            precioUnitario: data.precio_uno,
            montoDescuento: 0,
            subTotal: data.precio_uno,
            numeroSerie: null,
            numeroImei: null,
          };
          this.arrayProductos.push(nuevoProducto);
        }
        this.$toast.add({
          severity: "success",
          summary: "Producto agregado",
          detail: "El producto fue agregado al carrito correctamente",
          life: 2500,
        });

        // ❌ No cerramos el modal
        // this.cerrarModal();
      } else if (tipo === "itemcompuesto") {

  // 🔥 Función para calcular stock máximo vendible
  const calcularStockMaximo = (componentes) => {
    let stockMaximo = Infinity;

    componentes.forEach(c => {
      const stockDisponible = Number(c.saldo_stock || 0);
      const cantidadRequerida = Number(c.cantidad || 1);

      const kitsPosibles = Math.floor(stockDisponible / cantidadRequerida);

      if (kitsPosibles < stockMaximo) {
        stockMaximo = kitsPosibles;
      }
    });

    return stockMaximo === Infinity ? 0 : stockMaximo;
  };

  try {
    const response = await axios.post(
      "/articulo/verificarStockCompuesto",
      {
        idarticulo: data.id,
        cantidad: 1,
        idalmacen: this.idAlmacen,
      }
    );

    if (!response.data.success) {
      let mensajes = response.data.faltantes.map(
        (faltante) =>
          `El Item Compuesto: "${data.nombre}", no tiene stock suficiente en el item "${faltante.nombre_item}" (disponible "${faltante.stock}" requerido "${faltante.requerido}")`
      );

      Swal.fire({
        icon: "warning",
        title: "Stock insuficiente",
        html: mensajes.join("<br>"),
      });
      return;
    }
  } catch (error) {
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "No se pudo verificar el stock del compuesto.",
    });
    return;
  }

  const precioUno = Number(data.precio_uno || 0);
  const precioDos = Number(data.precio_dos || 0);
  const precioCompuesto = precioUno + precioDos;

  const existente = this.arrayDetalle.find(
    (d) => d.idarticulo === data.id && d.tipo === "itemcompuesto"
  );

  try {

    const detalleResponse = await axios.get(
      `/itemcompuesto/detalle/${data.id}`,
      {
        params: { idalmacen: this.idAlmacen }
      }
    );

    const componentes = detalleResponse.data;

    const stockMaximo = calcularStockMaximo(componentes);

    if (stockMaximo <= 0) {
      Swal.fire({
        icon: "warning",
        title: "Sin stock",
        text: "No hay stock disponible para este item compuesto.",
      });
      return;
    }

    const stocksComponentes = componentes.map(c => ({
      id: c.id,
      nombre: c.nombre,
      stock: Number(c.saldo_stock)
    }));

    if (existente) {

      // 🔥 VALIDAR LÍMITE
      if (existente.cantidad + 1 > stockMaximo) {
        Swal.fire({
          icon: "warning",
          title: "Stock insuficiente",
          text: `Solo hay ${stockMaximo} unidades disponibles.`,
        });
        return;
      }

      existente.cantidad += 1;
      existente.precio = precioCompuesto;
      existente.total = existente.cantidad * existente.precio;
      existente.stockCompuesto = stockMaximo;
      existente.stocksComponentes = stocksComponentes;

    } else {

      const nuevoDetalle = {
        id: Date.now(),
        idkit: -1,
        idarticulo: data.id,
        articulo: data.nombre,
        medida: data.medida || "",
        unidad_envase: data.unidad_envase || 1,
        cantidad: 1,
        cantidad_paquetes: 1,
        precio: precioCompuesto,
        descuento: 0,
        stock: null,
        stockCompuesto: stockMaximo, // 🔥 STOCK REAL
        stocksComponentes: stocksComponentes,
        precioseleccionado: precioCompuesto,
        total: precioCompuesto,
        tipo: tipo,
        precio_dos: data.precio_dos || 0,
        modoVenta: "unidad",
        descripcion_fabrica: data.descripcion_fabrica || "",
        codigo_producto: data.codigo || "",
        editandoPrecio: false,
      };

      this.arrayDetalle.push(nuevoDetalle);
    }

    // 🔹 ACTUALIZAR COMPONENTES EN arrayProductos
    componentes.forEach((componente) => {

      const qty = componente.cantidad || 1;

      const prodExist = this.arrayProductos.find(
        (p) =>
          (p.codigoProducto &&
            componente.codigo &&
            p.codigoProducto === componente.codigo) ||
          p.descripcion === componente.nombre
      );

      if (prodExist) {
        prodExist.cantidad = (prodExist.cantidad || 0) + qty;
        prodExist.subTotal =
          (prodExist.cantidad || 0) * prodExist.precioUnitario;
      } else {
        this.arrayProductos.push({
          actividadEconomica: componente.actividadEconomica || "",
          codigoProductoSin: componente.codigoProductoSin || "",
          codigoProducto: componente.codigo || "",
          descripcion: componente.nombre,
          cantidad: qty,
          unidadMedida: componente.codigoClasificador || "",
          precioUnitario: componente.precio_uno,
          montoDescuento: 0,
          subTotal: componente.precio_uno * qty,
          numeroSerie: null,
          numeroImei: null,
        });
      }

    });

    this.$toast.add({
      severity: "success",
      summary: "Producto agregado",
      detail: "El producto fue agregado al carrito correctamente",
      life: 2500,
    });

  } catch (error) {
    console.error("Error al obtener componentes:", error);
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "No se pudo obtener el detalle del item compuesto.",
    });
    return;
  }


        // ❌ No cerrar modal
        // this.cerrarModal();
      } else {
        // Para servicios
        const existente = this.arrayDetalle.find(
          (d) => d.idarticulo === data.id && d.tipo === "servicio"
        );

        if (existente) {
          existente.cantidad += 1;
          existente.total = existente.cantidad * existente.precio;
        } else {
          const nuevoDetalle = {
            id: Date.now(),
            idkit: -1,
            idarticulo: data.id,
            articulo: data.nombre,
            medida: data.medida || "",
            unidad_envase: data.unidad_envase || 1,
            cantidad: 1,
            cantidad_paquetes: 1,
            precio: data.precio_uno,
            descuento: 0,
            stock: null,
            stocksComponentes: stocksComponentes,
            precioseleccionado: data.precio_uno,
            total: data.precio_uno,
            tipo: tipo,
            codigo_producto: data.codigo,
            modoVenta: "unidad", // 🔹 inicia vendiendo en cajas
            editandoPrecio: false,
          };
          this.arrayDetalle.push(nuevoDetalle);

          const nuevoProducto = {
            actividadEconomica: data.actividadEconomica || "",
            codigoProductoSin: data.codigoProductoSin || "",
            codigoProducto: data.codigo || "",
            descripcion: data.nombre,
            cantidad: 1,
            unidadMedida: data.codigoClasificador || "",
            precioUnitario: data.precio_uno,
            montoDescuento: 0,
            subTotal: data.precio_uno,
            numeroSerie: null,
            numeroImei: null,
          };
          this.arrayProductos.push(nuevoProducto);
        }
        this.$toast.add({
          severity: "success",
          summary: "Producto agregado",
          detail: "El producto fue agregado al carrito correctamente",
          life: 2500,
        });

        // ❌ No cerrar modal
        // this.cerrarModal();
      }
    },
    eliminarSeleccionado() {
      this.codigo = "";
      this.arraySeleccionado = [];
    },
    listarArticulo(buscar, criterio) {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => {
        let me = this;
        if (!buscar || buscar.trim() === "") {
          me.arrayArticulo = [];
          return;
        }
        var url =
          "/articulo/listarArticuloVenta?buscar=" +
          buscar +
          "&criterio=" +
          criterio +
          "&idAlmacen=" +
          this.idAlmacen;
        axios
          .get(url)
          .then(function (response) {
            var respuesta = response.data;
            me.arrayArticulo = respuesta.articulos;
          })
          .catch(function (error) {
            console.log(error);
          });
      }, 300);
    },
    datosConfiguracion() {
      let me = this;
      var url = "/configuracion";
      axios
        .get(url)
        .then(function (response) {
          let respuesta = response.data;
          console.log(respuesta);
          me.saldosNegativos = respuesta.configuracionTrabajo.saldosNegativos;
          me.permitirDevolucion =
            respuesta.configuracionTrabajo.permitirDevolucion;
          me.monedaVenta = [
            respuesta.configuracionTrabajo.valor_moneda_venta,
            respuesta.configuracionTrabajo.simbolo_moneda_venta,
          ];
          me.permitir_cambioprecio =
            respuesta.configuracionTrabajo.permitir_cambioprecio;
          me.permitir_bonificacion =
            respuesta.configuracionTrabajo.permitir_bonificacion;
          me.permitir_descuento =
            respuesta.configuracionTrabajo.permitir_descuento;
          me.permitir_ofertas =
            respuesta.configuracionTrabajo.permitir_ofertas;
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    async selectAlmacen() {
      await this.obtenerAlmacenPredeterminado();
    },
    async obtenerAlmacenPredeterminado() {
      try {
        const response = await axios.get("/almacenes/lista");
        const almacenes = response.data.almacenes;

        if (almacenes.length > 0) {
          this.arrayAlmacenes = almacenes;
          this.selectedAlmacen = almacenes[0].id;
          this.idAlmacen = almacenes[0].id; // <== Esto faltaba
          console.log("Almacén por defecto:", this.idAlmacen);
          this.getAlmacenProductos({ value: this.idAlmacen }); // Opcional
        }
      } catch (error) {
        console.error("Error al obtener almacenes:", error);
      }
    },
    getAlmacenProductos(event) {
      this.idAlmacen = event.value;
    },
    async validarVenta() {
      let me = this;
      me.errorVenta = 0;
      me.errorMostrarMsjVenta = [];
      let errores = [];
      const promesas = me.arrayDetalle.map(async function (x) {
        // Si es servicio, no validar stock
        if (x.tipo === "servicio") {
          return;
        }
        // Si es compuesto, validar stock de los hijos
        if (x.tipo === "itemcompuesto") {
          try {
            const response = await axios.post(
              "/articulo/verificarStockCompuesto",
              {
                idarticulo: x.idarticulo,
                cantidad: x.cantidad,
                idalmacen: me.idAlmacen,
              }
            );
            if (!response.data.success) {
              response.data.faltantes.forEach((faltante) => {
                let art = `El Item Compuesto: "${x.articulo}", no tiene stock suficiente en el item "${faltante.nombre_item}" (disponible "${faltante.stock}" requerido "${faltante.requerido}")`;
                errores.push(art);
              });
            }
          } catch (error) {
            errores.push(
              `Error al verificar stock de compuesto: ${x.articulo}`
            );
          }
          return;
        }
        // Normal: validar stock propio
        if (x.cantidad > x.stock) {
          let art = `${x.articulo}: Stock insuficiente`;
          errores.push(art);
        }
      });
      await Promise.all(promesas);
      if (me.tipo_comprobante == 0) {
        errores.push("Seleccione el Comprobante");
      }
      if (!me.impuesto) {
        errores.push("Ingrese el impuesto de compra");
      }
      if (me.arrayDetalle.length <= 0) {
        errores.push("Ingrese detalles");
      }
      if (errores.length > 0) {
        me.errorVenta = 1;
        me.errorMostrarMsjVenta = errores;
        swal({
          type: "error",
          title: "Error en la venta",
          text: errores.join("\n"),
        });
        return false;
      }
      return true;
    },
    aplicarDescuento(idtipopago) {
      this.tipo_comprobante = "FACTURA";
      var idtipo_pago = idtipopago;
      const descuentoGiftCard = this.descuentoGiftCard;
      const numeroTarjeta = this.numeroTarjeta;
      if (numeroTarjeta && descuentoGiftCard) {
        idtipo_pago = 86;
      } else if (numeroTarjeta && !descuentoGiftCard) {
        idtipo_pago = 10;
      } else if (idtipo_pago == 7) {
        idtipo_pago = 7;
      } else {
        idtipo_pago = descuentoGiftCard ? 35 : 1;
      }
      this.registrarVenta(idtipo_pago);
    },

    aplicarDescuento2(idtipopago) {
      console.log("APLICAR DESCUENTO 2");
      this.tipo_comprobante = "FACTURA";
      var idtipo_pago = idtipopago;
      const descuentoGiftCard = this.descuentoGiftCard;
      const numeroTarjeta = this.numeroTarjeta;
      if (numeroTarjeta && descuentoGiftCard) {
        idtipo_pago = 86;
      } else if (numeroTarjeta && !descuentoGiftCard) {
        idtipo_pago = 10;
      } else if (idtipo_pago == 7) {
        idtipo_pago = 7;
      } else {
        idtipo_pago = descuentoGiftCard ? 35 : 1;
      }
      this.cerrarVenta(idtipo_pago);
    },

    async cerrarVenta(idtipo_pago) {
      console.log("CERRAR VENTA");
      let me = this;
      let idvent = this.idventaa;
      this.idtipo_pago = 1;

      try {
        // Mostrar mensaje de carga
        swal({
          title: 'Procesando cierre de venta...',
          text: 'Por favor espere',
          allowOutsideClick: false,
          didOpen: () => {
            swal.showLoading();
          }
        });

        // Verificar si existe el cliente
        const response = await axios.get(`/api/clientes/existe2?documento=${this.idventaa}`);
        if (!response.data.existe) {
          const nuevoClienteResponse = await axios.post('/cliente/registrar', {
            nombre: this.cliente,
            num_documento: this.documento,
            email: this.email,
          });
          this.idcliente = nuevoClienteResponse.data.id;
        } else {
          this.idcliente = response.data.cliente.id;
          this.cliente = response.data.cliente.nombre;
          this.documento = response.data.cliente.num_documento;
          this.complemento_id = response.data.cliente.complemento_id;
          this.tipo_documento = response.data.cliente.tipo_documento;
        }
        console.log("TIPO DOCUMENTO: ", this.tipo_documento);

        // Cerrar la venta
        const cerrarVentaResp = await axios.put('/venta/cerrarVenta', {
          id: idvent,
          idtipo_pago: idtipo_pago,
          estado: "1",
        });

        const ventaId = cerrarVentaResp.data.id;
        const detalles = cerrarVentaResp.data.detalles;
        console.log("ventaID: ", ventaId);
        console.log("DETALLES CERRAR VENTA: ", detalles);
        const tipoComprobante = cerrarVentaResp.data.tipo_comprobante;
        const idtipo_venta = cerrarVentaResp.data.idtipo_venta;

        if (ventaId > 0) {
          // Preparar arrayFactura
          me.arrayProductos = detalles.map(data => {
            const precio = parseFloat(data.precio_venta) || 0;
            const cantidad = parseFloat(data.cantidad) || 0;
            const descuentoPorcentaje = parseFloat(data.montoDescuento) || 0; // 👈 viene en %

            // 💰 Calcular el monto real del descuento
            const montoDescuento = (precio * cantidad * descuentoPorcentaje / 100).toFixed(2);
            const subTotal = (precio * cantidad - montoDescuento).toFixed(2);

            return {
              actividadEconomica: data.actividadEconomica,
              codigoProductoSin: data.codigoProductoSin,
              codigoProducto: data.codigo,
              descripcion: data.nombre,
              cantidad: cantidad,
              unidadMedida: data.unidadMedida,
              precioUnitario: precio.toFixed(2),
              montoDescuento: montoDescuento, // 💰 monto real del descuento (no porcentaje)
              subTotal: subTotal,              // 🧾 precio con descuento aplicado
              numeroSerie: null,
              numeroImei: null,
            };
          });

          console.log("Para la Factura: ", me.arrayProductos);

          if (tipoComprobante === 'FACTURA') {
            me.mostrarDesplegable = false;
            me.modal2 = false;
            await me.emitirFactura2(ventaId);
            if (idtipo_venta === 2) {
              //await me.resetearTipoPago(ventaId);
            }
          } else {
            // Si no hay factura, solo resetear si corresponde
            if (idtipo_venta === 2) {
              //await me.resetearTipoPago(ventaId);
            }
          }

          // Reseteo del formulario
          me.listado = 1;
          //me.listarVenta(1, '', 'num_comprob');
          me.cerrarModal3();
          me.idproveedor = 0;
          me.tipo_comprobante = 'FACTURA';
          me.nombreCliente = '';
          me.telefonoCliente = '';
          me.imagen = '';
          me.serie_comprobante = '';
          me.num_comprob = '';
          me.impuesto = 0.18;
          me.total = 0.0;
          me.idarticulo = 0;
          me.articulo = '';
          me.cantidad = 1;
          me.precio = 0;
          me.stock = 0;
          me.codigo = '';
          me.descuento = 0;
          me.arrayDetalle = [];
          me.primer_precio_cuota = 0;
          me.recibido = 0;
          me.recibidoUSD = 0;

          me.modalPago = false;

        } else {
          swal.close();
          me.toastInfo('Debe tener una caja abierta para registrar el pago');
          me.modalPago = false;
        }

      } catch (error) {
        swal.close(); // 🔹 Cerrar loading en caso de error
        console.error('Error al cerrar la venta:', error);
        swal('FALLO AL CERRAR LA VENTA', 'Intente de Nuevo', 'warning');
        me.modalPago = false;
      }
    },

    aplicarDescuentoRecibo(idtipoventa, idtipopago) {
      this.tipo_comprobante = "RESIVO";
      var idtipo_pago = idtipopago;
      var idtipo_venta = idtipoventa;
      const descuentoGiftCard = this.descuentoGiftCard;
      const numeroTarjeta = this.numeroTarjeta;
      if (numeroTarjeta && descuentoGiftCard) {
        idtipo_pago = 86;
      } else if (numeroTarjeta && !descuentoGiftCard) {
        idtipo_pago = 10;
      } else if (idtipo_pago == 7) {
        idtipo_pago = 7;
        this.idbanco = this.idbanco ? this.idbanco : null,
          console.log("Banco seleccionado:", this.idbanco);
      } else {
        idtipo_pago = descuentoGiftCard ? 35 : 1;
      }
      this.registrarVenta(idtipo_pago, idtipo_venta);
    },
    aplicarCombinacion() {
      const descuentoGiftCard = this.descuentoGiftCard;
      const idtipo_pago = descuentoGiftCard ? 40 : 2;
      this.registrarVenta(idtipo_pago);
    },
    otroMetodo(metodoPago) {
      const idtipo_pago = metodoPago;
      this.registrarVenta(idtipo_pago);
    },
    async emitirResivo(idVentaRecienRegistrada) {
      let me = this;
      let idventa = idVentaRecienRegistrada;
      let numeroResivo = document.getElementById("num_comprobante").value;
      let id_cliente = document.getElementById("idcliente").value;
      let nombreRazonSocial = document.getElementById("nombreCliente").value;
      let numeroDocumento = document.getElementById("documento").value;
      let complemento = document.getElementById("complemento_id").value;
      let tipoDocumentoIdentidad = document.getElementById("tipo_documento")
        .value;
      let montoTotal = (
        this.calcularTotal * parseFloat(this.monedaVenta[0])
      ).toFixed(2);
      let usuario = document.getElementById("usuarioAutenticado").value;
      try {
        const response = await axios.get("/resivo/obtenerLeyendaAleatoria");
        this.leyendaAl = response.data.descripcionLeyenda;
        console.log("El dato de leyenda llegado es: " + this.leyendaAl);
      } catch (error) {
        console.error(error);
        return '"Ley N° 453: Los servicios deben suministrarse en condiciones de inocuidad, calidad y seguridad."';
      }
      var resivo = [];
      resivo.push({
        cabecera: {
          municipio: "Cochabamba",
          telefono: "77490451",
          numeroResivo: numeroResivo,
          codigoSucursal: 0,
          direccion: "Av. Ejemplo 123",
          codigoPuntoVenta: 0,
          fechaEmision: new Date().toISOString().slice(0, -1),
          nombreRazonSocial: nombreRazonSocial,
          codigoTipoDocumentoIdentidad: tipoDocumentoIdentidad,
          numeroDocumento: numeroDocumento,
          complemento: complemento,
          codigoCliente: numeroDocumento,
          montoTotal: montoTotal,
          codigoMoneda: 1,
          tipoCambio: 1,
          montoTotalMoneda: montoTotal,
          usuario: usuario,
          leyenda: this.leyendaAl,
        },
      });
      me.arrayProductos.forEach(function (prod) {
        resivo.push({ detalle: prod });
      });
      var datos = { resivo };
      axios
        .post("/venta/emitirResivo", {
          resivo: datos,
          id_cliente: id_cliente,
          idventa: idventa,
        })
        .then(function (response) {
          var data = response.data;
          if (data === "VALIDADA") {
            swal("RESIVO VALIDADO", "Correctamente", "success");
            me.arrayProductos = [];
            me.cerrarModal2();
            me.cerrarModal3();
            me.listarVenta(1, "", "id");
            me.mostrarSpinner = false;
          } else {
            me.arrayProductos = [];
            me.cerrarModal2();
            me.cerrarModal3();
            me.listarVenta(1, "", "id");
            me.mostrarSpinner = false;
            swal("RESIVO VALIDADO", "éxito", "success");
          }
        })
        .catch(function (error) {
          me.arrayProductos = [];
          swal("INTENTE DE NUEVO", "Comunicacion fallida", "error");
          me.mostrarSpinner = false;
        });
    },

    imprimirResivo(id) {
      swal({
        title: "Selecciona un tamaño para imprimir el recibo",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "CARTA",
        cancelButtonText: "ROLLO",
        reverseButtons: true,
      })
        .then((result) => {
          if (result.value) {
            console.log("Se seleccionó imprimir en CARTA");
            axios
              .get("/resivo/imprimirCarta/" + id, {
                responseType: "blob",
              })
              .then(function (response) {
                const url = window.URL.createObjectURL(
                  new Blob([response.data], { type: "application/pdf" })
                );
                window.open(url); // Abre el PDF en otra pestaña
                console.log("Se imprimió el recibo en CARTA correctamente");
              })
              .catch(function (error) {
                console.log(error);
              });
          } else if (result.dismiss === swal.DismissReason.cancel) {
            console.log("Se seleccionó imprimir en ROLLO");
            axios
              .get("/resivo/imprimirRollo/" + id, {
                responseType: "blob",
              })
              .then(function (response) {
                const url = window.URL.createObjectURL(
                  new Blob([response.data], { type: "application/pdf" })
                );
                window.open(url); // Abre el PDF en otra pestaña
                console.log("Se imprimió el recibo en ROLLO correctamente");
              })
              .catch(function (error) {
                console.log(error);
              });
          }
        })
        .catch((error) => {
          console.error("Error al mostrar el diálogo:", error);
        });
      this.opcionPago = "efectivo";
    },
    imprimirRemision(id) {
      swal({
        title: "Selecciona un tamaño para imprimir la remisión",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "CARTA",
        cancelButtonText: "ROLLO",
        reverseButtons: true,
      })
        .then((result) => {
          if (result.value) {
            console.log("Se seleccionó imprimir en CARTA");
            axios
              .get("/remision/imprimirCarta/" + id, {
                responseType: "blob",
              })
              .then(function (response) {
                const url = window.URL.createObjectURL(
                  new Blob([response.data], { type: "application/pdf" })
                );
                window.open(url); // Abre el PDF en otra pestaña
                console.log("Se imprimió la remision en CARTA correctamente");
              })
              .catch(function (error) {
                console.log(error);
              });
          } else if (result.dismiss === swal.DismissReason.cancel) {
            console.log("Se seleccionó imprimir en ROLLO");
            axios
              .get("/remision/imprimirRollo/" + id, {
                responseType: "blob",
              })
              .then(function (response) {
                const url = window.URL.createObjectURL(
                  new Blob([response.data], { type: "application/pdf" })
                );
                window.open(url); // Abre el PDF en otra pestaña
                console.log("Se imprimió la remision en ROLLO correctamente");
              })
              .catch(function (error) {
                console.log(error);
              });
          }
        })
        .catch((error) => {
          console.error("Error al mostrar el diálogo:", error);
        });
      this.opcionPago = "efectivo";
    },
    alternarTipoDocumento() {
      if (this.tipo_documento === 1) {
        this.tipo_documento = 5;
        this.tipoDocumentoTexto = "NIT";
      } else {
        this.tipo_documento = 1;
        this.tipoDocumentoTexto = "CI";
      }
    },

    async buscarOCrearCliente() {
      try {
        // 🔍 Buscar si existe por documento
        const response = await axios.get(
          `/api/clientes/existe?documento=${this.documento}`
        );

        // =============================
        // ✅ CLIENTE EXISTE
        // =============================
        if (response.data.existe) {
          const c = response.data.cliente;

          this.idcliente = c.id;
          this.nombreCliente = c.nombre;
          this.tipo_documento = c.tipo_documento;
          this.complemento_id = c.complemento_id;
          this.telefonoCliente = c.telefono;
          this.emailCliente = c.email || null;

          this.clienteExistente = true;

          // 🔒 BLOQUEAR CAMPOS
          this.nombreClienteEditable = false;
          this.telefonoClienteEditable = false;
          this.direccionCliente = c.direccion || '';
          this.direccionClienteEditable = false;


          return;
        }

        // =============================
        // ❌ CLIENTE NO EXISTE
        // =============================
        this.clienteExistente = false;

        // 🔓 PERMITIR ESCRIBIR (no limpiar los valores ya ingresados)
        this.nombreClienteEditable = true;
        this.telefonoClienteEditable = true;
        this.direccionClienteEditable = true;


        // 🛑 VALIDACIÓN MÍNIMA
        if (!this.nombreCliente || this.nombreCliente.trim() === "") {
          throw new Error("Debe ingresar la Razón Social del cliente");
        }

        // =============================
        // ➕ CREAR CLIENTE
        // =============================
        const nuevoClienteResponse = await axios.post("/cliente/registrar", {
          nombre: this.nombreCliente,
          num_documento: this.documento,
          tipo_documento: this.tipo_documento,
          direccion: this.direccionCliente ? this.direccionCliente : null,
          telefono: this.telefonoCliente || null,
          email: this.emailCliente || null
        });

        this.idcliente = nuevoClienteResponse.data.id;
        this.clienteExistente = false;

      } catch (error) {
        console.error("Error al buscar o crear cliente:", error);

        // 🔥 Cortar flujo de venta
        this.$toast.add({
          severity: "error",
          summary: "Cliente",
          detail: error.message || "Error al registrar cliente",
          life: 3000
        });

        throw error; // ⛔ IMPORTANTE
      }
    },

    async registrarVenta(idtipo_pago, idtipo_venta) {
      if (this.validarVenta()) {
        try {
          if (idtipo_venta === 2) {
            this.primer_precio_cuota = Number(this.recibido);
          }
          this.isLoading = true; // Activar loading
          this.prepararDatosCliente();
          await this.buscarOCrearCliente();
          this.idPago = idtipo_pago;
          this.idtipo_venta = idtipo_venta;
          if (this.tipo_comprobante === "FACTURA") {
            await this.obtenerNumeroFactura();
          } else if (this.tipo_comprobante === "RESIVO") {
            await this.obtenerDatosSesionYComprobante();
          }
          const ventaData = this.prepararDatosVenta(idtipo_pago, idtipo_venta);
          console.log("📤 VentaData enviado:", ventaData);
          const response = await axios.post("/venta/registrar", ventaData);
          if (response.data.id > 0) {
            await this.manejarVentaExitosa(response.data.id); // 🔹 importante: esperar hasta que todo (factura incluida) termine
          } else {
            this.manejarErrorVenta(response.data);
          }
        } catch (error) {
          console.error("Error al registrar venta:", error);
          const mensaje =
            error &&
              error.response &&
              error.response.data &&
              error.response.data.error
              ? error.response.data.error
              : "Ocurrió un error al registrar la venta.";
          this.$toast.add({
            severity: "error",
            summary: "Error",
            detail: mensaje,
            life: 3000
          });
        } finally {
          this.isLoading = false; // Desactivar loading
        }
      }
    },

    prepararDatosCliente() {
      const nombre = this.nombreCliente ? this.nombreCliente.trim() : "";
      if (!nombre) {
        this.nombreCliente = "SIN NOMBRE";
        this.documento = "1";
      }
    },

    calcularDescuentoTotal() {
      let descuentoPorItems = 0.0;

      // 🔹 Recorrer detalles
      for (let i = 0; i < this.arrayDetalle.length; i++) {
        let detalle = this.arrayDetalle[i];

        // Descuento del ítem en Bs **por unidad**
        const cantidad = Number(detalle.cantidad) || 1;
        const montoDescontado = (parseFloat(detalle.descuento) || 0) * cantidad;

        descuentoPorItems += montoDescontado;
      }

      // 🔹 Descuento adicional en Bs
      let descuentoAdicionalMonto = parseFloat(this.descuentoAdicional) || 0;

      // 🔹 Total de todos los descuentos (ítems + adicional)
      let descuentoTotal = descuentoPorItems + descuentoAdicionalMonto;

      return descuentoTotal;
    },

    prepararDatosVenta(idtipo_pago, idtipo_venta) {
      const total = this.calcularTotal;
      const saldoFavor = this.saldoFavorCliente || 0;
      const totalConDescuento = Math.max(0, total - saldoFavor);
      const montoPagado = idtipo_venta === 2 ? Number(this.recibido) : 0;
      const saldoRestante = totalConDescuento - montoPagado;

      const datosComunes = {
        idcliente: this.idcliente,
        tipo_comprobante: this.tipo_comprobante,
        serie_comprobante: this.serie_comprobante,
        num_comprobante: this.num_comprob,
        impuesto: this.impuesto,
        descuento_total: this.calcularDescuentoTotal() + saldoFavor,
        total: totalConDescuento,
        saldo_favor_usado: saldoFavor,
        idAlmacen: this.idAlmacen,
        idtipo_pago,
        idtipo_venta,
        idbanco: this.idbanco ? this.idbanco : null,
        data: this.arrayDetalle,
      };

      if (idtipo_venta === 2) {

        const cuota = {
          precio_cuota: montoPagado,
          saldo_restante: saldoRestante,
          fecha_pago: new Date().toISOString().slice(0, 10),
          fecha_cancelado: new Date().toISOString().slice(0, 10),
          estado: "Pagado",
          idbanco: this.idbanco ? this.idbanco : null,

        };

        return {
          ...datosComunes,
          cuotas_credito: [cuota],
          primer_precio_cuota: montoPagado,
        };
      }

      if (this.tipo_comprobante === "RESIVO") {
        return { ...datosComunes, resivo: this.resivo };
      }

      return { ...datosComunes, idpersona: this.idcliente };
    },
    async manejarVentaExitosa(idVenta) {
      this.listado = 1;
      this.obtenerDatosUsuario();
      this.cerrarModal3();

      if (this.tipo_comprobante === "RESIVO") {
        this.cerrarModal2();

        // Mostrar toast de éxito
        this.$toast.add({
          severity: "success",
          summary: "Venta registrada",
          detail: "La venta se ha registrado exitosamente.",
          life: 3000, // duración del toast (3 segundos)
        });

        this.arrayProductos = [];
        this.reiniciarFormulario();
        this.resetBanco();
      } else if (this.tipo_comprobante === "FACTURA") {
        this.isLoading = true;
        this.modal2 = false;
        this.mostrarDesplegable = false;
        await this.emitirFactura(idVenta);
      }

      if (this.filtroVentasActivo === 'recibo') {
        this.listarVentaR(1, this.buscar, this.criterio);
      } else if (this.filtroVentasActivo === 'factura') {
        this.listarVentaF(1, this.buscar, this.criterio);
      } else {
        this.listarVenta(1, this.buscar, this.criterio);
      }
    },

    async emitirFactura(idVentaRecienRegistrada) {
      try {
        this.isLoading = true; // Activar loading

        const response2 = await axios.get(`/api/ventas/${idVentaRecienRegistrada}`);
        const codigoSucursal = response2.data.codigoSucursal;
        console.log('Datos de la venta:', codigoSucursal);

        let me = this;

        let idventa = idVentaRecienRegistrada;
        let numeroFacturaPrueba = String(this.num_comprob);
        console.log("numero de factira CARAJO:", numeroFacturaPrueba);
        let numeroFactura = numeroFacturaPrueba.padStart(5, "0");
        let cuf = "464646464";
        let cufdValor = document.getElementById("cufdValor");
        console.log("hola aaaa: ", this.cufdValor);
        let numeroTarjeta = this.numeroTarjeta;
        console.log("El numero de tarjeta es: " + numeroTarjeta);
        let cufd = cufdValor.textContent;
        let direccionValor = document.getElementById("direccion");
        let direccion = direccionValor.textContent;
        var tzoffset = new Date().getTimezoneOffset() * 60000;
        let fechaEmision = new Date(Date.now() - tzoffset)
          .toISOString()
          .slice(0, -1);
        let nombreRazonSocial = this.nombreCliente;
        console.log("EL DOCUMENTO ES: ", this.documento);
        let numeroDocumento = this.documento;
        let complemento = null;
        let tipoDocumentoIdentidad = 5;
        let montoTotal = this.calcularTotal.toFixed(2);
        let porcentajeDescuento = Number(this.descuentoAdicional || 0);
        let subtotal = this.calcularSubtotal(); // solo suma de ítems
        console.log("El subtotal es: ", subtotal);
        let montoDescuentoAdicional = subtotal * (porcentajeDescuento / 100); // monto real

        let usuario = this.usuarioAutenticado;
        let codigoPuntoVenta = this.puntoVentaAutenticado;
        let montoGiftCard = this.descuentoGiftCard;
        let codigoMetodoPago = 1;
        let montoTotalSujetoIva = montoTotal - this.descuentoGiftCard;

        console.log(
          "El monto de Descuento de Gift Card es: " + this.descuentoGiftCard
        );
        console.log("El tipo de documento es: " + tipoDocumentoIdentidad);
        console.log("El complemento de documento es: " + complemento);
        console.log("hola monto toal: " + this.calcularTotal.toFixed(2));

        try {
          const response = await axios.get("/factura/obtenerLeyendaAleatoria");
          this.leyendaAl = response.data.descripcionLeyenda;
          console.log("El dato de leyenda llegado es: " + this.leyendaAl);
        } catch (error) {
          console.error(error);
          return '"Ley N° 453: Los servicios deben suministrarse en condiciones de inocuidad, calidad y seguridad."';
        }

        try {
          if (tipoDocumentoIdentidad === 5) {
            const response = await axios.post(
              "/factura/verificarNit/" + numeroDocumento
            );
            if (response.data === "NIT ACTIVO") {
              me.codigoExcepcion = 0;
            } else {
              me.codigoExcepcion = 1;
            }
          } else {
            me.codigoExcepcion = 0;
          }
        } catch (error) {
          console.error(error);
          return "No se pudo verificar el NIT";
        }

        var factura = [];
        factura.push({
          cabecera: {
            nitEmisor: "8033811015",
            razonSocialEmisor: "MARIBEL QUISPE CHOQUE",
            municipio: "Cochabamba",
            telefono: "72784124",
            numeroFactura: numeroFactura,
            cuf: cuf,
            cufd: cufd,
            codigoSucursal: codigoSucursal,
            direccion: direccion,
            codigoPuntoVenta: codigoPuntoVenta,
            fechaEmision: fechaEmision,
            nombreRazonSocial: nombreRazonSocial,
            codigoTipoDocumentoIdentidad: tipoDocumentoIdentidad,
            numeroDocumento: numeroDocumento,
            complemento: complemento,
            codigoCliente: numeroDocumento,
            codigoMetodoPago: codigoMetodoPago,
            numeroTarjeta: numeroTarjeta,
            montoTotal: montoTotal,
            montoTotalSujetoIva: montoTotalSujetoIva,
            codigoMoneda: 1,
            tipoCambio: 1,
            montoTotalMoneda: montoTotal,
            montoGiftCard: this.descuentoGiftCard,
            descuentoAdicional: montoDescuentoAdicional.toFixed(2), // monto en dinero,
            codigoExcepcion: this.codigoExcepcion,
            cafc: null,
            leyenda: this.leyendaAl,
            usuario: usuario,
            codigoDocumentoSector: 1,
          },
        });
        me.arrayProductos.forEach(function (prod) {
          factura.push({ detalle: prod });
        });

        var datos = { factura };

        await axios
          .post("/venta/emitirFactura", {
            factura: datos,
            id_cliente: this.idcliente,
            idventa: idventa,
            cufd: cufd,
          })
          .then(function (response) {
            var data = response.data;
            var mensaje = data.mensaje;
            var idFactura = data.idFactura;
            console.log("Mensaje:", mensaje);
            console.log("ID de la factura:", idFactura);

            if (mensaje === "VALIDADA") {
              me.visibleDialog = false;
              me.cambiar_pagina = 0;

              swal({
                title: "FACTURA VALIDADA",
                text: "Correctamente",
                icon: "success",
              }).then(() => {
                // 🔹 Cuando el usuario da clic en OK del swal
                me.reiniciarFormulario();
                me.listarVentaF(1, "", "num_comprobante");
                me.arrayProductos = [];
                me.codigoExcepcion = 0;
                me.idtipo_pago = "";
                me.email = "";
                me.descuentoGiftCard = "";
                me.numeroTarjeta = null;
                me.recibido = "";
                me.metodoPago = "";
                me.idcliente = 0;
                me.mostrarSpinner = false;
                me.menu = 49;
                me.opcionPago = "efectivo";

                // 🔹 Reabrir modal2 automáticamente
                //me.modal2 = true;
              });
            } else {
              me.reiniciarFormulario();
              me.listarVentaF(1, "", "num_comprobante");
              me.visibleDialog = false;
              me.cambiar_pagina = 0;
              me.arrayProductos = [];
              me.codigoExcepcion = 0;
              me.idtipo_pago = "";
              me.descuentoGiftCard = "";
              me.numeroTarjeta = null;
              me.recibido = "";
              me.idcliente = 0;
              me.metodoPago = "";
              me.last_comprobante = "";
              me.mostrarSpinner = false;
              me.opcionPago = "efectivo";

              swal("FACTURA RECHAZADA - INTENTE DE NUEVO", data, "warning");
              axios.post('/venta/actualizarEstado', {
                idventa: idventa,
                nuevoEstado: 4
              })
                .then(function (response) {
                  console.log("Estado de la venta actualizado correctamente:", response);
                  me.listarVenta(1, '', 'id');
                })
                .catch(function (error) {
                  console.error("Error al actualizar el estado de la venta:", error);
                  me.listarVenta(1, '', 'id');
                });
            }
          })
          .catch(function (error) {
            console.error("Este es el error: " + error);
            me.arrayProductos = [];
            me.codigoExcepcion = 0;
            swal("INTENTE DE NUEVO - INTENTE DE NUEVO", "Comunicacion con SIAT fallida", "error");
            me.mostrarSpinner = false;
            me.idtipo_pago = "";
            me.numeroTarjeta = null;
            me.descuentoGiftCard = "";
            me.recibido = "";
            me.idcliente = 0;
            me.metodoPago = "";
            me.opcionPago = "efectivo";
            axios.post('/venta/actualizarEstado', {
              idventa: idventa,
              nuevoEstado: 4
            })
              .then(function (response) {
                console.log("Estado de la venta actualizado correctamente:", response);
                me.listarVenta(1, '', 'id');
              })
              .catch(function (error) {
                console.error("Error al actualizar el estado de la venta:", error);
                me.listarVenta(1, '', 'id');
              });
          });
      } catch (error) {
        console.error("Error general en emitirFactura:", error);
        swal("ERROR", "Ocurrió un error al emitir la factura", "error");
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },

    async emitirFactura2(idVentaRecienRegistrada) {
      try {
        this.isLoading = true; // Activar loading
        // 👇 Esperar a que se obtenga el número
        const numero = await this.obtenerNumeroPorVenta(idVentaRecienRegistrada);
        if (!numero) return; // por si falla

        const response2 = await axios.get(`/api/ventas/${idVentaRecienRegistrada}`);
        const venta = response2.data.venta;
        const codigoSucursal = response2.data.codigoSucursal;
        console.log('Datos de la venta:', codigoSucursal);

        let me = this;

        let idventa = idVentaRecienRegistrada;
        let numeroFacturaPrueba = String(this.num_comprob);
        console.log("El numero de factura es NUEVO ES: ", numeroFacturaPrueba);
        let numeroFactura = numeroFacturaPrueba.padStart(5, "0");
        let cuf = "464646464";
        let cufdValor = document.getElementById("cufdValor");
        console.log("hola aaaa: ", this.cufdValor);
        let numeroTarjeta = this.numeroTarjeta;
        console.log("El numero de tarjeta es: " + numeroTarjeta);
        let cufd = cufdValor.textContent;
        let direccionValor = document.getElementById("direccion");
        let direccion = direccionValor.textContent;
        var tzoffset = new Date().getTimezoneOffset() * 60000;
        let fechaEmision = new Date(Date.now() - tzoffset)
          .toISOString()
          .slice(0, -1);
        let nombreRazonSocial = this.cliente;
        let numeroDocumento = this.documento;
        let complemento = null;
        let tipoDocumentoIdentidad = 5;
        //let montoTotal = this.calcularTotal.toFixed(2);
        let montoTotal = Number(this.totalReservaSeleccionada).toFixed(2);
        let descuentoAdicional = Number(this.totaldescuentoseleccionada).toFixed(2);
        let usuario = this.usuarioAutenticado;
        let codigoPuntoVenta = this.puntoVentaAutenticado;
        let montoGiftCard = this.descuentoGiftCard;
        let codigoMetodoPago = 1;
        let montoTotalSujetoIva = montoTotal - this.descuentoGiftCard;

        console.log(
          "El monto de Descuento de Gift Card es: " + this.descuentoGiftCard
        );
        console.log("El tipo de documento es: " + tipoDocumentoIdentidad);
        console.log("El complemento de documento es: " + complemento);
        console.log("hola monto toal: " + this.calcularTotal.toFixed(2));

        try {
          const response = await axios.get("/factura/obtenerLeyendaAleatoria");
          this.leyendaAl = response.data.descripcionLeyenda;
          console.log("El dato de leyenda llegado es: " + this.leyendaAl);
        } catch (error) {
          console.error(error);
          return '"Ley N° 453: Los servicios deben suministrarse en condiciones de inocuidad, calidad y seguridad."';
        }

        try {
          if (tipoDocumentoIdentidad === 5) {
            const response = await axios.post(
              "/factura/verificarNit/" + numeroDocumento
            );
            if (response.data === "NIT ACTIVO") {
              me.codigoExcepcion = 0;
            } else {
              me.codigoExcepcion = 1;
            }
          } else {
            me.codigoExcepcion = 0;
          }
        } catch (error) {
          console.error(error);
          return "No se pudo verificar el NIT";
        }

        var factura = [];
        factura.push({
          cabecera: {
            nitEmisor: "8033811015",
            razonSocialEmisor: "MARIBEL QUISPE CHOQUE",
            municipio: "Cochabamba",
            telefono: "72784124",
            numeroFactura: numeroFactura,
            cuf: cuf,
            cufd: cufd,
            codigoSucursal: codigoSucursal,
            direccion: direccion,
            codigoPuntoVenta: codigoPuntoVenta,
            fechaEmision: fechaEmision,
            nombreRazonSocial: nombreRazonSocial,
            codigoTipoDocumentoIdentidad: tipoDocumentoIdentidad,
            numeroDocumento: numeroDocumento,
            complemento: complemento,
            codigoCliente: numeroDocumento,
            codigoMetodoPago: codigoMetodoPago,
            numeroTarjeta: numeroTarjeta,
            montoTotal: montoTotal,
            montoTotalSujetoIva: montoTotalSujetoIva,
            codigoMoneda: 1,
            tipoCambio: 1,
            montoTotalMoneda: montoTotal,
            montoGiftCard: this.descuentoGiftCard,
            descuentoAdicional: descuentoAdicional,
            codigoExcepcion: this.codigoExcepcion,
            cafc: null,
            leyenda: this.leyendaAl,
            usuario: usuario,
            codigoDocumentoSector: 1,
          },
        });
        me.arrayProductos.forEach(function (prod) {
          factura.push({ detalle: prod });
        });

        var datos = { factura };

        await axios
          .post("/venta/emitirFactura", {
            factura: datos,
            id_cliente: this.idcliente,
            idventa: idventa,
            cufd: cufd,
          })
          .then(function (response) {
            var data = response.data;
            var mensaje = data.mensaje;
            var idFactura = data.idFactura;
            console.log("Mensaje:", mensaje);
            console.log("ID de la factura:", idFactura);


            if (mensaje === "VALIDADA") {
              me.visibleDialog = false;
              me.cambiar_pagina = 0;

              swal({
                title: "FACTURA VALIDADA",
                text: "Correctamente",
                icon: "success",
              }).then(() => {
                // 🔹 Cuando el usuario da clic en OK del swal
                me.reiniciarFormulario();
                me.listarVentaF(1, "", "num_comprobante");
                me.filtroVentasActivo = 'factura';
                me.arrayProductos = [];
                me.codigoExcepcion = 0;
                me.idtipo_pago = "";
                me.email = "";
                me.descuentoGiftCard = "";
                me.numeroTarjeta = null;
                me.recibido = "";
                me.metodoPago = "";
                me.idcliente = 0;
                me.mostrarSpinner = false;
                me.menu = 49;
                me.opcionPago = "efectivo";

                // 🔹 Reabrir modal2 automáticamente
                //me.modal2 = true;
              });
            } else {
              me.filtroVentasActivo = 'todos';
              me.listarVenta(1, "", "num_comprobante");
              me.reiniciarFormulario();
              me.visibleDialog = false;
              me.cambiar_pagina = 0;
              me.arrayProductos = [];
              me.codigoExcepcion = 0;
              me.idtipo_pago = "";
              me.descuentoGiftCard = "";
              me.numeroTarjeta = null;
              me.recibido = "";
              me.idcliente = 0;
              me.metodoPago = "";
              me.last_comprobante = "";
              me.mostrarSpinner = false;
              me.opcionPago = "efectivo";

              swal("FACTURA RECHAZADA - INTENTE DE NUEVO", data, "warning");
              //me.eliminarVenta(idVentaRecienRegistrada);
              // Actualizar el estado de la venta a 2
              axios.post('/venta/actualizarEstado', {
                idventa: idventa,
                nuevoEstado: 4
              })
                .then(function (response) {
                  console.log("Estado de la venta actualizado correctamente:", response);
                  me.listarVenta(1, '', 'id');
                })
                .catch(function (error) {
                  console.error("Error al actualizar el estado de la venta:", error);
                  me.listarVenta(1, '', 'id');
                });
            }
          })
          .catch(function (error) {
            console.error("Este es el error: " + error);
            me.arrayProductos = [];
            me.codigoExcepcion = 0;
            swal("INTENTE DE NUEVO - INTENTE DE NUEVO", "Comunicacion con SIAT fallida", "error");
            me.reiniciarFormulario();
            me.mostrarSpinner = false;
            me.idtipo_pago = "";
            me.numeroTarjeta = null;
            me.descuentoGiftCard = "";
            me.recibido = "";
            me.idcliente = 0;
            me.metodoPago = "";
            me.opcionPago = "efectivo";
            //me.eliminarVentaFalloSiat(idVentaRecienRegistrada);
            //me.ejecutarFlujoCompleto();
            //me.listarVenta(1, "", "num_comprobante");

            // Actualizar el estado de la venta a 2
            axios.post('/venta/actualizarEstado', {
              idventa: idventa,
              nuevoEstado: 4
            })
              .then(function (response) {
                console.log("Estado de la venta actualizado correctamente:", response);
                me.listarVenta(1, '', 'id');
              })
              .catch(function (error) {
                console.error("Error al actualizar el estado de la venta:", error);
                me.listarVenta(1, '', 'id');
              });
          });
      } catch (error) {
        console.error("Error general en emitirFactura:", error);
        swal("ERROR", "Ocurrió un error al emitir la factura", "error");
      } finally {
        this.isLoading = false; // Desactivar loading
      }
    },

    imprimirFactura(id) {
      axios
        .get("/factura/imprimirRollo/" + id)
        .then(function (response) {
          const fileURL = response.data.url;
          const newWindow = window.open(fileURL, "_blank");
          if (newWindow) {
            newWindow.focus();
          } else {
            console.log(
              "No se pudo abrir una nueva pestaña, asegúrate de que los pop-ups no están bloqueados."
            );
          }
          console.log("Se generó la factura en Rollo correctamente");
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    eliminarVenta(idVenta) {
      axios
        .delete("/venta/eliminarVenta/" + idVenta)
        .then(function (response) {
          console.log("Venta eliminada correctamente:", response);
        })
        .catch(function (error) {
          console.error("Error al eliminar la venta:", error);
        });
    },

    eliminarVentaFalloSiat(idVenta) {
      axios
        .delete("/venta/eliminarVentaFalloSiat/" + idVenta)
        .then(function (response) {
          console.log("Venta eliminada correctamente:", response);
        })
        .catch(function (error) {
          console.error("Error al eliminar la venta:", error);
        });
    },

    async obtenerNumeroFactura() {
      try {
        const response = await axios.get("/facturas/ultimo-numero");
        const ultimoNumero = response.data.ultimoNumero;
        this.num_comprob = ultimoNumero + 1;
        console.log("el numero factura es: " + this.num_comprob);
      } catch (error) {
        console.error("Error al obtener el último número de factura:", error);
      }
    },

    async obtenerNumeroPorVenta(idventa) {
      try {
        const response = await axios.get(`/ventas/${idventa}/num-comprobante`);
        const numero = response.data.num_comprobante;
        this.num_comprob = numero;
        console.log("Número de comprobante de la venta:", numero);
        return numero; // 👈 devolverlo explícitamente
      } catch (error) {
        console.error("Error al obtener el número de comprobante:", error);
        return null;
      }
    },

    manejarErrorVenta(data) {
      if (data.valorMaximo) {
        this.toastInfo(`El valor de descuento no puede exceder el ${data.valorMaximo}`);
      } else if (data.caja_validado) {
        this.toastInfo(data.caja_validado);
      } else {
        console.error("Error desconocido al registrar venta:", data);
      }
    },

    reiniciarFormulario() {
      Object.assign(this, {
        idproveedor: 0,
        nombreCliente: "",
        telefonoCliente: "",
        tipo_documento: 0,
        complemento_id: "",
        documento: "",
        imagen: "",
        serie_comprobante: "",
        impuesto: 0.18,
        total: 0.0,
        idarticulo: 0,
        articulo: "",
        cantidad: 1,
        precio: 0,
        stock: 0,
        codigo: "",
        descuento: 0,
        arrayDetalle: [],
        primer_precio_cuota: 0,
        step: 1,
        recibido: 0,
        telefonoClienteEditable: false,
        nombreClienteEditable: false,
        mensajeRazonSocial: false,
        habilitacionpromocion: false,
        direccionClienteEditable: false,
        saldoFavorCliente: 0,
      });
    },

    eliminarVenta(idVenta) {
      axios
        .delete("/venta/eliminarVenta/" + idVenta)
        .then(function (response) {
          console.log("Venta eliminada correctamente:", response);
        })
        .catch(function (error) {
          console.error("Error al eliminar la venta:", error);
        });
    },

    ocultarDetalle() {
      this.listado = 1;
      this.codigo = null;
      this.arrayArticulo.length = 0;
      this.precioseleccionado = null;
      this.medida = null;
      this.nombreCliente = null;
      this.telefonoCliente = null;
      this.documento = null;
      this.email = null;
      this.idAlmacen = 1;
      this.arrayProductos = [];
      this.descuentoAdicional = "";
      this.arrayDetalle = [];
      this.precioBloqueado = false;
      this.telefonoClienteEditable = false;
      this.direccionClienteEditable = false;
      this.nombreClienteEditable = false; // Asegura que el input esté deshabilitado en caso de erro
      this.mensajeRazonSocial = false;
      this.listarVenta(
        this.pagination.current_page || 1, // Mantener página actual
        this.buscar,                      // Texto de búsqueda actual
        this.criterio,                    // Criterio actual
        this.tipoVentaFiltro || ''        // Si usas filtro por tipo de venta
      );
      this.editarCuotas = false;
    },
    cargarCuotas(id) {
      let me = this;

      axios.get("/venta/obtenerCuotas?id=" + id)
        .then(function (response) {
          me.cuotas = response.data.cuotas;
          console.log("CUOTAS CARGADAS:", me.cuotas);
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    verVenta(id) {
      this.idVentaSeleccionada = id;
      let me = this;
      me.listado = 2;

      // CABECERA
      axios.get("/venta/obtenerCabecera?id=" + id)
        .then(function (response) {

          const venta = response.data.venta[0];
          // 👆 Usas [0] porque estás retornando get(), no first()

          me.cliente = venta.nombre;
          me.tipo_comprobante = venta.tipo_comprobante;
          me.serie_comprobante = venta.serie_comprobante;
          me.num_comprobante = venta.num_comprobante;
          me.impuesto = venta.impuesto;
          me.total = venta.total;
          me.descuentoAdicionalvista = parseFloat(venta.descuento_total) || 0;
          me.idtipo_venta = venta.idtipo_venta;  // ✔ AQUI LO GUARDAMOS

          // SI ES CRÉDITO → CARGAR CUOTAS
          if (venta.idtipo_venta == 2) {
            me.cargarCuotas(id);
          } else {
            me.cuotas = [];
          }

        })
        .catch(function (error) {
          console.log(error);
        });

      // DETALLES
      axios.get("/venta/obtenerDetalles?id=" + id)
        .then(function (response) {

          me.arrayDetalle = response.data.detalles;

          // Sumar descuentos del detalle considerando cantidad
          me.descuentoTotalDetalle = me.arrayDetalle.reduce((acc, item) => {
            const cantidad = parseFloat(item.cantidad) || 1;
            const descuentoUnidad = parseFloat(item.descuento_monto) || 0; // monto por unidad
            return acc + (descuentoUnidad * cantidad);
          }, 0);

          me.descuentoAdicionalvista =
            (me.descuentoAdicionalvista || 0) - me.descuentoTotalDetalle;

          me.subtotalVista = me.arrayDetalle.reduce((acc, item) => {
            return acc + (parseFloat(item.subtotal) || 0);
          }, 0);

        });
    },
    cerrarModal() {
      this.modal = 0;
      this.tituloModal = "";
      this.buscarA = "";
    },

    abrirModal() {
      this.scrollToTop();
      this.listarItemCompuesto("", "");
      //this.selectAlmacen();
      this.arrayArticulo = [];
      this.modal = true;
      this.tituloModal = "Buscar productos";
    },

    desactivarVenta(id) {
      swal({
        title: "Esta seguro de anular esta venta?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Aceptar!",
        cancelButtonText: "Cancelar",
        confirmButtonClass: "btn btn-success",
        cancelButtonClass: "btn btn-danger",
        buttonsStyling: false,
        reverseButtons: true,
      }).then((result) => {
        if (result.value) {
          let me = this;
          axios
            .put("/venta/desactivar", {
              id: id,
            })
            .then(function (response) {
              me.listarVenta(1, "", "num_comprobante");
              swal(
                "Anulado!",
                "La venta ha sido anulado con éxito.",
                "success"
              );
            })
            .catch(function (error) {
              console.log(error);
            });
        } else if (result.dismiss === swal.DismissReason.cancel) {
        }
      });
    },

    listarPrecio() {
      let me = this;
      var url = "/precios";
      axios
        .get(url)
        .then(function (response) {
          var respuesta = response.data;
          me.arrayPrecios = respuesta.precio.data;
          console.log("PRECIOS", me.arrayPrecios);
        })
        .catch(function (error) {
          console.log(error);
        });
    },

    cerrarModal2() {
      // 🔹 Cierra el modal
      this.modal2 = false;
      this.tituloModal2 = "";
      this.idtipo_pago = "";
      this.tipoPago = "";
      this.mostrarDesplegable = false;
      this.habilitacionpromocion = false;

      // 🔹 Espera 300 ms y vuelve a abrirlo
      setTimeout(() => {
        //this.modal2 = true;
      }, 300);

    },

    cerrarModal3() {
      this.modal3 = 0;
      this.tituloModal3 = "";
      this.numero_cuotas = "";
      this.tiempo_diaz = "";
      this.primera_cuota = false;
      this.cuotas = [];
    },

    calcularCambio() {
      const recibidoNumero = parseFloat(
        this.recibido / parseFloat(this.monedaVenta[0])
      );
      if (recibidoNumero === 0) {
        this.efectivo = recibidoNumero;
        console.log("EFECTIVO", this.efectivo);
        this.cambio = 0;
        this.faltante = 0;
      } else if (recibidoNumero < this.calcularTotal) {
        this.efectivo = recibidoNumero;
        this.faltante = (this.calcularTotal - this.efectivo).toFixed(2);
      } else if (recibidoNumero === this.calcularTotal) {
        this.efectivo = recibidoNumero;
        this.cambio = 0;
        this.faltante = 0;
      } else {
        this.efectivo = recibidoNumero;
        this.cambio = (this.efectivo - this.calcularTotal).toFixed(2);
        this.faltante = 0;
      }
    },

    buscarClientePorDocumento() {
      clearTimeout(this.buscarTimeout);

      if (this.documento.trim() === "") {
        this.resultadosClientes = [];
        this.mostrarDesplegableCliente = false;
        this.nombreCliente = "";
        this.emailCliente = "";
        this.nombreClienteEditable = false;
        this.emailClienteEditable = false;
        this.telefonoClienteEditable = false;
        this.direccionClienteEditable = false;
        this.mensajeRazonSocial = false;
        return;
      }

      this.buscarTimeout = setTimeout(() => {
        axios
          .get(`/api/clientes?documento=${this.documento}`)
          .then((response) => {
            const clientes = Array.isArray(response.data)
              ? response.data
              : [response.data];

            if (clientes.length > 0) {
              // 🔸 Cliente encontrado
              this.resultadosClientes = clientes;
              this.mostrarDesplegableCliente = true;
              this.mensajeRazonSocial = false;
            } else {
              // 🔸 No se encontró cliente
              this.resultadosClientes = [];
              this.mostrarDesplegableCliente = false;
              this.nombreCliente = "";
              this.emailCliente = "";
              this.telefonoCliente = "";
              this.nombreClienteEditable = true;
              this.telefonoClienteEditable = true;
              this.emailClienteEditable = true;
              this.mensajeRazonSocial = true;

              this.direccionClienteEditable = true;
              this.direccionCliente = "";
            }
          })
          .catch((error) => {
            // 🔸 Error (404 u otro)
            this.resultadosClientes = [];
            this.mostrarDesplegableCliente = false;
            this.nombreCliente = "";
            this.emailCliente = "";
            this.telefonoCliente = "";
            this.nombreClienteEditable = true;
            this.emailClienteEditable = true;
            this.telefonoClienteEditable = true;
            this.mensajeRazonSocial = true;
            this.direccionClienteEditable = true;

            this.direccionCliente = "";
          });
      }, 300);
    },

    seleccionarCliente(cliente) {
      this.direccionCliente = cliente.direccion;
      this.documento = cliente.num_documento;
      this.nombreCliente = cliente.nombre;
      this.telefonoCliente = cliente.telefono;
      this.nombreClienteEditable = false;
      this.telefonoClienteEditable = false;
      this.mostrarDesplegableCliente = false;
      this.direccionClienteEditable = false;
      this.mensajeRazonSocial = false; // 🔹 Cliente seleccionado → ocultar mensaje
      this.totalComprasCliente = parseFloat(cliente.total_compras) || 0;
      this.saldoFavorCliente = parseFloat(cliente.saldo_favor) || 0;
      this.habilitacionpromocion = cliente.bonificacion_habilitada;
      console.log("Cliente habilitado:", this.habilitacionpromocion);
      console.log("Saldo a favor del cliente:", this.saldoFavorCliente);
      this.clienteEncontrado = true;


      this.nombreClienteEditable = false;
      this.telefonoClienteEditable = false;

      this.mostrarDesplegableCliente = false;
    },

    moverSeleccionCliente(direccion) {
      if (!this.mostrarDesplegableCliente) return;
      if (direccion === "abajo" && this.indiceSeleccionadoCliente < this.resultadosClientes.length - 1) {
        this.indiceSeleccionadoCliente++;
      } else if (direccion === "arriba" && this.indiceSeleccionadoCliente > 0) {
        this.indiceSeleccionadoCliente--;
      }
    },

    seleccionarClienteConEnter(event) {
      event.preventDefault(); // Evita que Enter haga submit

      if (this.indiceSeleccionadoCliente >= 0) {
        // ✅ Hay un cliente seleccionado del desplegable
        this.seleccionarCliente(this.resultadosClientes[this.indiceSeleccionadoCliente]);
      } else if (this.resultadosClientes.length === 1) {
        // ✅ Solo hay un resultado → seleccionar
        this.seleccionarCliente(this.resultadosClientes[0]);
      } else if (this.resultadosClientes.length === 0) {
        // ❌ No hay resultados → habilitar inputs y mover foco
        this.nombreClienteEditable = true;
        this.telefonoClienteEditable = true;
        this.emailClienteEditable = true;
        this.mensajeRazonSocial = true;
        this.direccionClienteEditable = true;


        // 🔹 Esperar al siguiente ciclo de render antes de enfocar
        this.$nextTick(() => {
          // Para PrimeVue 2: el input real está dentro de $el
          const inputWrapper = this.$refs.inputNombreCliente;
          if (inputWrapper && inputWrapper.$el) {
            const input = inputWrapper.$el.querySelector('input');
            if (input) input.focus();
          }
        });
      }
    },

    async verificarAutorizacionDescuento() {
      try {
        const response = await axios.get("/verificar-descuento");
        this.puedeDescontar = response.data.puedeDescontar;
      } catch (error) {
        console.error("Error al verificar autorización de descuento:", error);
      }
    },
    abrirModalPago(ventaId) {
      this.actualizarFechaHora(); // si lo necesitas
      this.idventaa = ventaId;
      // Traer datos de la venta
      axios.get(`/ventaselect/${ventaId}`)
        .then(response => {
          const venta = response.data;
          if (venta) {
            this.ventaSeleccionada = venta;
            console.log("VENTASSELECCIONADA: ", this.ventaSeleccionada);
            this.totalReservaSeleccionada = venta.total || 0;
            this.totaldescuentoseleccionada = venta.descuento || 0;
            this.modalPago = true;
          } else {
            console.error('Venta no encontrada');
          }
        })
        .catch(error => {
          console.error('Error al obtener la venta:', error);
        });
    },

    cerrarModalPago() {
      this.modalPago = false;
      // limpiar datos si quieres
      this.idventaa = null;
      this.totalReservaSeleccionada = 0;
      this.ventaSeleccionada = null;
    },
  },
  created() {
    this.listarPrecio();
  },
  async mounted() {
    this.handleResize();
    document.addEventListener("click", this.handleClickFuera);
    window.addEventListener("resize", this.handleResize);
    document.addEventListener("keypress", this.handleKeyPress);
    window.addEventListener("keydown", this.handleKeyPress);
    try {
      this.isLoading = true; // Activar loading al iniciar
      await Promise.all([
        //this.ejecutarSecuencial(),
        this.datosConfiguracion(),
        this.selectAlmacen(),
        this.selectSucursal(),
        this.listarVenta(1, this.buscar, this.criterio),
        this.actualizarFechaHora(),
        this.ejecutarFlujoCompleto(),
      ]);     
    } catch (error) {
      swal("Error", "Hubo un problema al cargar los datos iniciales", "error");
    } finally {
      this.isLoading = false; // Desactivar loading cuando todo termina
    }
  },
  beforeUnmount() {
    window.removeEventListener("resize", this.handleResize);
    window.removeEventListener("keydown", this.handleKeyPress);
  },
  beforeDestroy() {
    window.removeEventListener("resize", this.handleResize);
    document.removeEventListener("keypress", this.handleKeyPress);
    document.removeEventListener("click", this.handleClickFuera);

  },
};
</script>

<style scoped>
.dialog-combo .p-dialog-header {
  padding: 0;
}

.dialog-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 1rem 1.25rem;
}

.icon-header {
  font-size: 2rem;
  color: #3f51b5;
}

.title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.subtitle {
  font-size: 0.85rem;
  color: #6c757d;
}

.dialog-content {
  padding: 0 1.25rem 1rem;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  padding: 0.75rem 1.25rem;
}

.cantidad-badge {
  background: #eef2ff;
  color: #3f51b5;
  padding: 4px 10px;
  border-radius: 12px;
  font-weight: 600;
}

.precio-wrapper {
  display: flex;
  align-items: center;
  gap: 4px;
}

.input-precio-unidad {
  text-align: center;
  height: 32px;
}

.btn-precio-toggle {
  height: 32px;
  width: 32px;
  padding: 0;
}

.estado-badge {
  padding: 0.15rem 0.6rem;
  border-radius: 6px;
  font-weight: 600;
  color: #fff;
  font-size: 0.70rem;
  display: inline-block;
  text-align: center;
}

/* VERDE - PAGADO */
.estado-verde {
  background-color: #28a745;
}

/* ROJO - ANULADA */
.estado-rojo {
  background-color: #dc3545;
}

/* AMARILLO - CRÉDITO */
.estado-amarillo {
  background-color: #ffc107;
  color: #000;
  /* Amarillo se lee mejor en negro */
}

.tabla-venta {
  width: 100%;
  white-space: nowrap;
  /* evita salto de columnas */
  overflow-x: auto;
}

.tabla-venta .p-datatable-wrapper {
  overflow-x: auto;
}

.tabla-venta th,
.tabla-venta td {
  text-align: center;
  vertical-align: middle;
  font-size: 0.85rem;
  padding: 0.5rem;
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
  font-family: "Inter", "Segoe UI", sans-serif;
}

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

.detalle-cliente-pro {
  background: #f9fafb;
  border-radius: 0.2rem;
  padding: 0.05rem 1rem;
  margin-bottom: 1rem;
  border: 1px solid #e5e7eb;
}

.detalle-tabla-pro .p-datatable {
  border-radius: 0.2rem;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

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

<style scoped>
/* ==========================================================================
   ESTANDARIZACIÓN DE ALMACÉN Y BUSCADOR (FIX DE ALINEACIÓN)
   ========================================================================== */

/* 1. Forzar altura idéntica para Dropdown, InputText y Botón de búsqueda */
.dropdown-full,
.input-full,
.p-inputgroup .p-button {
  height: 38px !important;
  /* Altura estándar desktop */
  min-height: 38px !important;
}

/* 2. Ajuste interno del Dropdown de PrimeVue para centrar texto verticalmente */
.dropdown-full>>>.p-dropdown-label {
  display: flex;
  align-items: center;
  height: 100%;
  padding-top: 0 !important;
  padding-bottom: 0 !important;
  line-height: 1 !important;
}

/* 3. Ajuste interno del InputText para alineación */
.input-full {
  display: flex;
  align-items: center;
}

/* 4. Labels estandarizados */
.label-input {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
  /* Margen inferior uniforme */
  white-space: nowrap;
}

/* 5. Icono de la lupa centrado */
.p-inputgroup .p-button .p-button-icon {
  font-size: 1rem;
}

/* ==========================================================================
   ESTILOS GENERALES Y COMPONENTES
   ========================================================================== */

.dialog-combo .p-dialog-header {
  padding: 0;
}

.dialog-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 1rem 1.25rem;
}

.icon-header {
  font-size: 2rem;
  color: #3f51b5;
}

.title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.subtitle {
  font-size: 0.85rem;
  color: #6c757d;
}

.dialog-content {
  padding: 0 1.25rem 1rem;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  padding: 0.75rem 1.25rem;
}

.cantidad-badge {
  background: #eef2ff;
  color: #3f51b5;
  padding: 4px 10px;
  border-radius: 12px;
  font-weight: 600;
}

.precio-wrapper {
  display: flex;
  align-items: center;
  gap: 4px;
}

.input-precio-unidad {
  text-align: center;
  height: 32px;
}

.btn-precio-toggle {
  height: 32px;
  width: 32px;
  padding: 0;
}

.estado-badge {
  padding: 0.15rem 0.6rem;
  border-radius: 6px;
  font-weight: 600;
  color: #fff;
  font-size: 0.70rem;
  display: inline-block;
  text-align: center;
}

/* VERDE - PAGADO */
.estado-verde {
  background-color: #28a745;
}

/* ROJO - ANULADA */
.estado-rojo {
  background-color: #dc3545;
}

/* AMARILLO - CRÉDITO */
.estado-amarillo {
  background-color: #ffc107;
  color: #000;
}

.tabla-venta {
  width: 100%;
  white-space: nowrap;
  overflow-x: auto;
}

.tabla-venta .p-datatable-wrapper {
  overflow-x: auto;
}

.tabla-venta th,
.tabla-venta td {
  text-align: center;
  vertical-align: middle;
  font-size: 0.85rem;
  padding: 0.5rem;
}

/* Inputs generales */
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

.addon-small {
  background-color: #f3f4f6;
  font-size: 0.8rem;
  color: #374151;
  border-radius: 6px 0 0 6px;
  padding: 6px 10px;
}

.custom-input-group .form-control {
  border-radius: 0 6px 6px 0;
  font-size: 0.8rem;
  height: 33px;
}

.form-control[readonly],
.form-control:disabled {
  background-color: #f9fafb;
  color: #6b7280;
}

/* Campos opcionales */
.optional-field {
  display: flex;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 4px;
  align-items: center;
  gap: 0.4rem;
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

.p-inputgroup {
  display: flex;
  align-items: stretch;
  width: 100%;
}

/* Ajustes de width para inputs y dropdowns */
.input-full {
  width: 100%;
  font-size: 0.8rem;
  padding: 6px 8px;
  border-radius: 6px 0 0 6px;
  box-sizing: border-box;
}

.input-full>>>.p-inputtext {
  width: 100% !important;
  font-size: 0.8rem;
  padding: 6px 8px;
  border-radius: 6px 0 0 6px;
}

.p-inputgroup .p-button {
  border-radius: 0 6px 6px 0;
  font-size: 0.8rem;
  padding: 6px 10px;
}

.text-required {
  color: #dc2626;
  font-weight: 700;
}

.dropdown-full {
  width: 100% !important;
  font-size: 0.8rem;
  border-radius: 6px;
  box-sizing: border-box;
}

.dropdown-full>>>.p-dropdown-trigger {
  width: 2rem !important;
}

.dropdown-full>>>.p-dropdown {
  border: 1px solid #ccc;
  transition: border 0.2s;
}

.dropdown-full>>>.p-dropdown.p-focus {
  border-color: #0ea5e9;
  box-shadow: 0 0 0 0.15rem rgba(14, 165, 233, 0.25);
}

.dropdown-full>>>.p-dropdown-panel .p-dropdown-item {
  font-size: 0.8rem !important;
  padding: 6px 10px !important;
  min-height: auto !important;
}

.form-section {
  margin-bottom: 1rem;
}

.p-error {
  font-weight: 700;
  font-size: 0.8rem;
}

/* ===== DETALLE VENTA PRO ===== */
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

.detalle-cliente-pro {
  background: #f9fafb;
  border-radius: 0.2rem;
  padding: 0.05rem 1rem;
  margin-bottom: 1.0rem;
  border: 1px solid #e5e7eb;
}

.detalle-tabla-pro .p-datatable {
  border-radius: 0.2rem;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.p-datatable tbody tr:hover {
  background-color: #f9fafb;
}

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

.detalle-footer-pro {
  margin-top: 2rem;
  text-align: right;
}

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

/* Desplegable de búsqueda */
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
  overflow-y: auto;
  background: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  list-style: none;
  padding: 0;
  margin: 2px 0 0 0;
  font-size: 0.8rem;
}

.desplegable-simple li {
  padding: 6px 8px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.desplegable-simple li:hover,
.desplegable-simple li.seleccionado {
  background-color: #f1f5f9;
}

/* Panel Content */
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

/* Barra de búsqueda con icono */
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

/* Toolbar */
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

/* Datatable inputs */
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

/* Tablet */
@media (max-width: 1024px) {
  .responsive-dialog>>>.p-dialog {
    margin: 0.5rem;
    max-height: 95vh;
  }

  >>>.p-datatable {
    font-size: 0.85rem;
  }
}

/* Mobile */
@media (max-width: 768px) {

  /* Ajuste de altura para móvil */
  .dropdown-full,
  .input-full,
  .p-inputgroup .p-button {
    height: 34px !important;
  }

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

  >>>.p-button-sm {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
    min-width: auto !important;
  }

  .search-bar .p-inputtext-sm {
    padding: 0.35rem 0.5rem 0.35rem 2.5rem !important;
    font-size: 0.85rem !important;
  }

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

/* Small Mobile */
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

/* Paginator */
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

>>>.p-datatable .p-button {
  margin-right: 0.25rem;
}

@media (max-width: 768px) {
  >>>.p-datatable .p-button {
    margin-right: 0.15rem;
    margin-bottom: 0.15rem;
  }
}

/* Loader */
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

.p-dialog-mask {
  z-index: 9990 !important;
}

.p-dialog {
  z-index: 9990 !important;
}

.swal2-zindex-fix {
  z-index: 100050 !important;
}

/* Columnas de inputs tabla ventas */
.column-precio-unidad,
.column-unidades,
.column-descuento {
  min-width: 80px !important;
  max-width: 120px !important;
}

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

  >>>.p-datatable .p-column-header-content {
    font-size: 0.7rem !important;
    padding: 0.3rem 0.2rem !important;
  }

  >>>.p-datatable .p-datatable-tbody>tr>td.column-precio-unidad,
  >>>.p-datatable .p-datatable-tbody>tr>td.column-unidades,
  >>>.p-datatable .p-datatable-tbody>tr>td.column-descuento {
    padding: 0.2rem 0.1rem !important;
  }
}

/* Mensaje cumpleaños */
.mensaje-cumpleanos-venta {
  margin-top: 1rem;
  padding: 1rem 1.5rem;
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
  color: white;
  border-radius: 8px;
  font-weight: 700;
  text-align: center;
  animation: pulsoVenta 1.5s ease-in-out infinite;
  box-shadow: 0 4px 15px rgba(67, 233, 123, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  font-size: 1.1rem;
}

.mensaje-cumpleanos-venta i {
  font-size: 1.8rem;
  animation: rotar 2s linear infinite;
}

@keyframes pulsoVenta {
  0% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(67, 233, 123, 0.7);
  }

  70% {
    transform: scale(1.02);
    box-shadow: 0 0 0 10px rgba(67, 233, 123, 0);
  }

  100% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(67, 233, 123, 0);
  }
}

@keyframes rotar {
  0% {
    transform: rotate(0deg);
  }

  25% {
    transform: rotate(-10deg);
  }

  75% {
    transform: rotate(10deg);
  }

  100% {
    transform: rotate(0deg);
  }
}

/* Sin stock */
.item-sin-stock {
  background-color: #ffe6e6 !important;
  color: #a00000;
  cursor: not-allowed;
  opacity: 0.9;
}

.item-sin-stock:hover {
  background-color: #ffcccc !important;
}

.stock-error {
  color: red;
  font-weight: bold;
  font-size: 0.9em;
  margin-left: 10px;
}

.stock-ok {
  color: green;
  font-weight: bold;
  font-size: 0.9em;
  margin-left: 10px;
}

.item-contenido {
  display: flex;
  justify-content: space-between;
  width: 100%;
}

.item-contenido span {
  white-space: nowrap;
}

.text-muted {
  color: #6c757d;
}

.text-primary {
  color: #007bff;
}

.font-weight-bold {
  font-weight: 700;
}

.carrito-vacio-mensaje {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background-color: #f9fafb;
  border-radius: 8px;
  border: 1px dashed #d1d5db;
  margin-top: 1rem;
  text-align: center;
}

.carrito-vacio-mensaje i {
  font-size: 3rem;
  color: #9ca3af;
  margin-bottom: 1rem;
}

.carrito-vacio-mensaje h4 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #374151;
  margin: 0 0 0.25rem 0;
}

.carrito-vacio-mensaje p {
  font-size: 0.9rem;
  color: #6b7280;
  margin: 0;
}

/* Auto verificar switch */
.auto-verificar-switch :deep(.p-inputswitch) {
  width: 50px !important;
  height: 26px !important;
  transition: background-color 0.3s ease, box-shadow 0.3s ease;
  padding: 2px !important;
  display: inline-flex !important;
  align-items: center !important;
}

.auto-verificar-switch :deep(.p-inputswitch.p-highlight) {
  background-color: #28a745 !important;
  box-shadow: 0 0 8px rgba(40, 167, 69, 0.4) !important;
}

.auto-verificar-switch :deep(.p-inputswitch:not(.p-highlight)) {
  background-color: #c0c0c0 !important;
}

.auto-verificar-switch :deep(.p-inputswitch .p-inputswitch-slider) {
  background: transparent !important;
  border-radius: 12px !important;
  transition: 0.3s ease !important;
  padding: 0 !important;
  height: 100% !important;
  width: 100% !important;
  display: flex !important;
  align-items: center !important;
}

.auto-verificar-switch :deep(.p-inputswitch .p-inputswitch-slider:before) {
  content: '' !important;
  background-color: white !important;
  width: 20px !important;
  height: 20px !important;
  border-radius: 50% !important;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2) !important;
  transition: transform 0.3s ease !important;
  margin: 0 3px !important;
  flex-shrink: 0 !important;
}

.auto-verificar-switch :deep(.p-inputswitch.p-highlight .p-inputswitch-slider:before) {
  transform: translateX(24px) !important;
}

.auto-verificar-switch :deep(.p-inputswitch:hover) {
  box-shadow: 0 0 12px rgba(0, 0, 0, 0.1) !important;
}

.auto-verificar-switch :deep(.p-inputswitch.p-disabled) {
  opacity: 0.6 !important;
  cursor: not-allowed !important;
}

.auto-verificar-switch {
  margin: 0 2px !important;
  vertical-align: middle !important;
}

.vertical-align-row {
  display: flex;
  align-items: flex-end;
  /* Clave para la alineación */
  flex-wrap: wrap;
}

.input-height-fix,
.input-height-fix .p-inputtext,
.input-height-fix .p-dropdown,
.btn-search-fix {
  height: 40px !important;
  /* Altura más robusta */
  line-height: normal;
  box-sizing: border-box;
}

/* Ajuste específico para el Dropdown de PrimeVue */
.input-height-fix.p-dropdown,
.dropdown-full {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 0 !important;
  /* El padding lo maneja el label interno */
}

/* Centrar texto dentro del Dropdown */
.input-height-fix>>>.p-dropdown-label {
  display: flex;
  align-items: center;
  height: 100%;
  margin-top: 0;
  padding: 0 10px !important;
}

/* Ajuste del botón de búsqueda */
.btn-search-fix {
  width: 40px !important;
  /* Cuadrado perfecto */
  display: flex;
  justify-content: center;
  align-items: center;
}

/* Etiquetas uniformes */
.label-input {
  display: block;
  margin-bottom: 6px;
  /* Espacio fijo entre label e input */
  font-size: 0.85rem;
  font-weight: 600;
  color: #495057;
  line-height: 1.2;
}

.p-inputgroup .p-inputtext {
  flex: 1 1 auto;
  width: 1%;
}

.tabla-seleccionable tbody tr {
  cursor: pointer;
}

.tabla-seleccionable tbody tr:hover {
  background-color: #f1f5f9 !important;
}

.tabla-seleccionable>>>.p-datatable-tbody>tr:hover {
  background-color: #f1f5f9 !important;
  cursor: pointer !important;
  transition: background-color 0.2s ease;
}
</style>