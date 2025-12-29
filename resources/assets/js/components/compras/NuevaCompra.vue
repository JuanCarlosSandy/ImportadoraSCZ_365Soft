<template>
    <main class="main">
        <Panel>
            <Toast :breakpoints="{'920px': {width: '100%', right: '0', left: '0'}}" style="padding-top: 40px;"></Toast>
            <template #header>
                <div class="panel-header">
                    <i class="pi pi-shopping-cart panel-icon"></i>
                    <h4 class="panel-icon">Registrar Compra</h4>
                </div>
            </template>

            <template #icons>
                <Button class="p-button-warning p-button-sm" icon="pi pi-cog" />
            </template>

            <TabView ref="tabview" :activeIndex="activeIndex">
                <TabPanel :disabled="activeIndex !== 0">
                    <template #header>
                        <div class="titulos-pasos">
                            <Button id="primerPaso" type="button" label="PASO 1" icon="pi pi-bookmark" class="p-button-secondary p-button-rounded non-clickable" />
                            <label for="primerPaso" style="margin-top: 0.25rem;">Selección de Productos</label>
                        </div>
                    </template>

                    <div class="card formulario-card">
                    <Card>
                        <!--<template #title>
                            <div style="padding-left: 1rem; padding-top: 0.5rem;">
                                <h5>Complete el Formulario</h5>
                            </div>
                        </template>-->

                        <template #content>
                            <div class="p-fluid p-formgrid p-grid">
                                <div class="p-field p-col-12 p-md-3">
                                    <label for="autocomplete">Proveedor</label>
                                    <div class="autocomplete-flecha">
                                        <AutoComplete
                                            id="autocomplete"
                                            class="p-inputtext-sm"
                                            forceSelection
                                            :dropdown="true"
                                            v-model="form.proveedorSeleccionado" 
                                            :suggestions="array_proveedores" 
                                            field="nombre" 
                                            @complete="selectProveedor($event)" 
                                            placeholder="Buscar Proveedores..."
                                            :class="{'p-invalid': submitted && v$.form.proveedorSeleccionado.$invalid}"
                                        >
                                        </AutoComplete>
                                    </div>
                                    <small class="p-error" v-if="(submitted && v$.form.proveedorSeleccionado.required.$invalid)"><strong>Proveedor es obligatorio.</strong></small>
                                </div>
                                <div class="p-field p-col-12 p-md-3">
                                    <label for="tipoComprobante">Tipo Comprobante</label>
                                    <Dropdown id="tipoComprobante" class="p-inputtext-sm" v-model="form.tipo_comprobante" :options="lista_comprobantes" optionLabel="nombre" placeholder="Lista comprobantes ..." :class="{'p-invalid': submitted && v$.form.tipo_comprobante.$invalid}"/>
                                    <small class="p-error" v-if="(submitted && v$.form.tipo_comprobante.required.$invalid)"><strong>Comprobante es obligatorio.</strong></small>
                                </div>
                                <div class="p-field p-col-12 p-md-3">
                                    <label for="serieComprobante">Serie Comprobante</label>
                                    <InputText id="serieComprobante" class="p-inputtext-sm" v-model="form.serie_comprobante" placeholder="Serie ..." />
                                </div>
                                <div class="p-field p-col-12 p-md-3">
                                    <label for="numeroComprobante">Número Comprobante</label>
                                    <InputText id="numeroComprobante" class="p-inputtext-sm" v-model="form.num_comprobante" placeholder="Número ..." />
                                </div>
                            </div>
                        </template>

                    </Card>
                    </div>

                    <div class="p-grid">
                        <div class="p-col-12 p-md-6">
                            <div class="tablas-card ">
                                <div class="card">
                                    <DataTable
                                        ref="dt-articulos"
                                        :value="array_articulos_proveedor"
                                        selectionMode="multiple"
                                        dataKey="codigo"
                                        :selection.sync="array_articulos_seleccionados"
                                        responsiveLayout="scroll"
                                        :paginator="true"
                                        :rows="4"
                                        tableStyle="height:34vh"
                                        class="p-datatable-sm"
                                        :metaKeySelection="false"
                                    >
                                        <template #header>
                                            <div class="tablas-articulos-header">
                                                <h5>Selecciona artículos</h5>
                                                <span class="p-input-icon-left">
                                                    <i class="pi pi-search" />
                                                    <InputText style="width: 11rem;" v-model="buscadorArticulos" class="p-inputtext-sm" placeholder="Buscador global" />
                                                </span>
                                            </div>
                                        </template>

                                        <Column selectionMode="multiple" ></Column>
                                        <Column field="codigo" header="Codigo" :sortable="true" ></Column>
                                        <Column field="nombre" header="Nombre" :sortable="true" ></Column>
                                        <Column field="precio_costo_unid" header="Precio Unidad" :sortable="true" ></Column>
                                        <Column field="precio_costo_paq" header="Precio Paquete" :sortable="true" ></Column>

                                        <template #empty>
                                            <div class="imagen-tabla-vacia">
                                                <img src="img/agregar-carrito.png" alt="Articulo sin foto" class="product-image" />
                                            </div>
                                            <div style="padding-top: 15px; display: flex; justify-content: center;">
                                                <h5>Agregue artículos ...</h5>
                                            </div>
                                        </template>
                                    </DataTable>
                                </div>
                            </div>
                        </div>

                        <div class="p-col-12 p-md-6">
                            <div class="tablas-card ">
                            <div class="card">
                                <DataTable
                                    ref="dt-seleccionados"
                                    :value="array_articulos_seleccionados"
                                    dataKey="codigo"
                                    responsiveLayout="scroll"
                                    :paginator="true"
                                    :rows="3"
                                    tableStyle="height:34vh"
                                    editMode="row"
                                    :editingRows.sync="editingRows"
                                    @row-edit-save="onRowEditSave"
                                    class="p-datatable-sm"
                                >
                                    <template #header>
                                        <div class="tablas-articulos-header">
                                            <h5>Artículos seleccionados</h5>
                                            <Button type="button" class="p-button-sm p-button-danger" label="Vaciar lista" icon="pi pi-trash" @click="vaciarListaSeleccionados"/>
                                        </div>
                                    </template>

                                    <Column field="codigo" header="Codigo" :sortable="true" ></Column>
                                    <Column field="nombre" header="Nombre" :sortable="true" ></Column>
                                    <Column field="precio_costo_unid" header="Precio Unidad" :sortable="true" >
                                        <template #editor="slotProps">
                                            <InputNumber v-model="slotProps.data[slotProps.column.field]" mode="decimal" :maxFractionDigits="2" :min="0" inputStyle="width:5rem;" class="p-inputtext-sm"/>
                                        </template>
                                    </Column>
                                    <Column field="precio_costo_paq" header="Precio Paquete" :sortable="true" >
                                        <template #editor="slotProps">
                                            <InputNumber v-model="slotProps.data[slotProps.column.field]" mode="decimal" :maxFractionDigits="2" :min="0" inputStyle="width: 5rem;" class="p-inputtext-sm"/>
                                        </template>
                                    </Column>
                                    <Column :rowEditor="true" :styles="{width:'10%', 'min-width':'8rem'}" :bodyStyle="{'text-align':'center'}"></Column>

                                    <Column :headerStyle="{'min-width': '4rem', 'text-align': 'center'}" :bodyStyle="{'text-align': 'center', overflow: 'visible'}">
                                        <template #body="slotProps">
                                            <Button type="button" icon="pi pi-delete-left" class="p-button-danger p-button-sm" @click="eliminarArticuloSeleccionado(slotProps.data)"></Button>
                                        </template>
                                    </Column>

                                    <template #empty>
                                        <div class="imagen-tabla-vacia">
                                            <img src="img/venta-express.png" alt="Articulo sin foto" class="product-image" />
                                        </div>
                                        <div style="padding-top: 15px; display: flex; justify-content: center;">
                                            <h5>Sin artículos seleccionados ...</h5>
                                        </div>
                                    </template>
                                </DataTable>
                            </div>
                            </div>
                        </div>
                    </div>
                </TabPanel>

                <TabPanel :disabled="activeIndex !== 1">
                    <template #header>
                        <div class="titulos-pasos">
                            <Button id="segundoPaso" type="button" label="PASO 2" icon="pi pi-bookmark" class="p-button-secondary p-button-rounded non-clickable" />
                            <label for="segundoPaso" style="margin-top: 0.25rem;">Configuración y Pago</label>
                        </div>
                    </template>

                    <div class="card">
                        <DataTable
                            ref="dt-lista-completo"
                            :value="array_articulos_completo"
                            dataKey="id"
                            :paginator="true"
                            :rows="3"
                            :expandedRows.sync="expandedRows"
                            @row-expand="onRowExpand"
                            @row-collapse="onRowCollapse"
                            responsiveLayout="scroll"
                            tableStyle="height: 33vh"
                            class="p-datatable-sm"
                        >
                            <Column :expander="true" :headerStyle="{'width': '5%'}" />
                            <Column field="codigo" header="Codigo" :sortable="true" :styles="{width:'5%'}"></Column>
                            <Column header="Image" :styles="{width:'5%'}">
                                <template #body="slotProps">
                                    <img v-if="slotProps.data.fotografia == null" src="img/producto-imagen-default.jpg" alt="Articulo sin foto" class="product-image" />
                                    <img v-else :src="'/img/articulos/' + slotProps.data.fotografia" alt="Articulo foto" class="product-image">
                                </template>
                            </Column>
                            <Column field="nombre" header="Nombre" :sortable="true" :styles="{width:'25%'}"></Column>
                            <Column field="nombre_proveedor" header="Proveedor" :sortable="true" :styles="{width:'15%'}"></Column>
                            <Column field="unidad_envase" header="Unidades por Paquete" :sortable="true" :styles="{width:'10%'}" ></Column>
                            <Column field="precio_costo_unid" header="Costo Unidad" :sortable="true" :styles="{width:'15%'}" ></Column>
                            <Column field="precio_costo_paq" header="Costo Paquete" :sortable="true" :styles="{width:'15%'}" ></Column>

                            <Column :headerStyle="{'width': '5%','min-width':'8rem'}" :bodyStyle="{'text-align': 'center', overflow: 'visible'}">
                                <template #body="slotProps">
                                    <Button type="button" icon="pi pi-delete-left" class="p-button-danger p-button-sm" @click="eliminarArticuloListaCompleta(slotProps.data)"></Button>
                                </template>
                            </Column>

                            <template #empty>
                                <div class="imagen-tabla-vacia">
                                    <img src="img/carrito-de-compras-final.png" alt="Articulo sin foto" class="product-image" />
                                </div>
                                <div style="padding-top: 15px; display: flex; justify-content: center;">
                                    <h5>Sin artículos seleccionados ...</h5>
                                </div>
                            </template>

                            <template #expansion="slotProps">
                                <div class="orders-subtable">
                                    <h6>Informacion adicional</h6>
                                    <DataTable :value="[slotProps.data]" responsiveLayout="scroll">
                                        <Column header="Fecha Vencimiento" :styles="{width:'10%'}">
                                            <template #body="slotProps">
                                                <Calendar
                                                    class="p-inputtext-sm"
                                                    v-model="slotProps.data.fecha_vencimiento"
                                                    :touchUI="true"
                                                    dateFormat="yy.mm.dd"
                                                    :minDate="minDate"
                                                    :class="{'p-invalid': verificarFechaVencimiento(slotProps.data)}"
                                                    :disabled="slotProps.data.vencimiento == 1"
                                                />
                                                <small class="p-error" v-if="verificarFechaVencimiento(slotProps.data)">Fecha requerida</small>
                                            </template>
                                        </Column>
                                        <Column field="nombre_categoria" header="Categoria" :styles="{width:'15%'}"></Column>
                                        <Column :header="slotProps.data.esPaquetesCantidad ? ' Cantidad en: Paquetes' : 'Cantidad en: Unidades'" :styles="{width:'20%'}">
                                            <template #body="slotProps">
                                                <div class="p-inputgroup">
                                                    <Button
                                                        type="button"
                                                        :label="slotProps.data.esPaquetesCantidad ? 'Paquetes' : 'Unidades'"
                                                        @click="toggleUnidadesPaquetesCantidad(slotProps.data)"
                                                        icon="pi pi-bell"
                                                        class="p-button-sm p-button-secondary"
                                                    />
                                                    <InputNumber
                                                        class="p-inputtext-sm"
                                                        v-model="slotProps.data.unidades"
                                                        mode="decimal"
                                                        :step="1"
                                                        showButtons
                                                        :min="0"
                                                        decrementButtonClass="p-button-danger p-button-sm"
                                                        incrementButtonClass="p-button-sm"
                                                        incrementButtonIcon="pi pi-plus"
                                                        decrementButtonIcon="pi pi-minus"
                                                        @input="updateSubtotal(slotProps.data)"
                                                        :class="{'p-invalid': slotProps.data.unidades <= 0}"
                                                    />
                                                </div>
                                                <small class="p-error" v-if="slotProps.data.unidades <= 0">Cantidad inválida</small>
                                            </template>
                                        </Column>
                                        <Column :header="slotProps.data.esPaquetesBonificacion ? 'Bonificacion en: Paquetes' : 'Bonificacion en: Unidades'" :styles="{width:'20%'}">
                                            <template #body="slotProps">
                                                <div class="p-inputgroup">
                                                    <Button
                                                        type="button"
                                                        :label="slotProps.data.esPaquetesBonificacion ? 'Paquetes' : 'Unidades'"
                                                        @click="toggleUnidadesPaquetesBonificacion(slotProps.data)"
                                                        icon="pi pi-bell"
                                                        class="p-button-sm p-button-secondary"
                                                    />
                                                    <InputNumber
                                                        class="p-inputtext-sm"
                                                        v-model="slotProps.data.bonificacion"
                                                        mode="decimal"
                                                        :step="1"
                                                        showButtons
                                                        :min="0"
                                                        decrementButtonClass="p-button-danger p-button-sm"
                                                        incrementButtonClass="p-button-sm"
                                                        incrementButtonIcon="pi pi-plus"
                                                        decrementButtonIcon="pi pi-minus"
                                                        @input="updateUnidadesTotales(slotProps.data)"
                                                    />
                                                </div>
                                            </template>
                                        </Column>
                                        <Column field="descuento" header="Descuento" :styles="{width:'10%'}">
                                            <template #body="slotProps">
                                                <InputNumber class="p-inputtext-sm" v-model="slotProps.data.descuento" prefix="% " mode="decimal" :maxFractionDigits="2" :max="100" :min="0" @input="updateSubtotal(slotProps.data)" inputStyle="width:7rem;"/>
                                            </template>
                                        </Column>
                                        <Column header="Subtotal" :styles="{width:'10%'}">
                                            <template #body="slotProps">
                                                <InputNumber class="p-inputtext-sm" disabled :value="calculateSubtotal(slotProps.data)" mode="decimal" inputStyle="width:7rem;"/>
                                            </template>
                                        </Column>

                                        <template #empty>
                                            Datos del articulo no encontrados ...
                                        </template>
                                    </DataTable>
                                </div>
                            </template>

                            <template #paginatorend>
                                <label for="descuentoGlobal" style="padding-right: 10px;">Descuento Global</label>
                                <InputNumber id="descuentoGlobal" class="p-inputtext-sm" v-model="descuentoGlobal" prefix="% " mode="decimal" :maxFractionDigits="2" :max="100" :min="0" inputStyle="width:7rem;"/>
                            </template>
                        </DataTable>
                    </div>

                    <div class="total-saldo-card">
                        <Card>
                            <template #content>
                                <div class="saldo-total"><strong>TOTAL Bs. {{ saldoTotalCompra }}</strong></div>
                            </template>
                        </Card>
                    </div>

                    <div class="card">
                        <div class="p-fluid p-formgrid p-grid">
                            <div class="p-field p-col-12 p-md-3">
                                <label for="nombreUsuario">Nombre Usuario</label>
                                <div class="p-inputgroup">
                                    <span class="p-inputgroup-addon">
                                        <i class="pi pi-user"></i>
                                    </span>    
                                    <InputText id="nombreUsuario" placeholder="Nombre usuario actual ..." v-model="nombreUsuario" disabled class="p-inputtext-sm"/>
                                </div>
                            </div>

                            <div class="p-field p-col-12 p-md-3">
                                <label for="tipoCompra">Tipo de Compra</label>
                                <Dropdown id="tipoCompra" v-model="tipoCompra" :options="lista_tipo_compra" optionLabel="nombre" placeholder="Tipo de compra ..." class="p-inputtext-sm" :class="{'p-invalid': submitted && v$.tipoCompra.$invalid}"/>
                                <small class="p-error" v-if="(submitted && v$.tipoCompra.required.$invalid)"><strong>Tipo de Compra es obligatorio.</strong></small>
                            </div>

                            <div class="p-field p-col-12 p-md-3">
                                <label for="arrayAlmacenes">Almacén</label>
                                <Dropdown id="arrayAlmacenes" v-model="almacenSeleccionado" :options="array_almacenes" optionLabel="nombre_almacen" placeholder="Lista almacenes ..." class="p-inputtext-sm" :class="{'p-invalid': submitted && v$.almacenSeleccionado.$invalid}"/>
                                <small class="p-error" v-if="(submitted && v$.almacenSeleccionado.required.$invalid)"><strong>Almacén de destino es obligatorio.</strong></small>
                            </div>

                            <div class="p-field p-col-12 p-md-3" v-if="!verificarCompraContado">
                                <label for="botonComprarContado" style="padding-bottom: 17px;"></label>
                                <Button id="botonComprarContado" icon="pi pi-check-square" label="Registrar Compra" class="p-button-success p-button-sm" @click="registrarCompra" />
                            </div>
                            <div class="p-field p-col-12 p-md-3" v-else>
                                <label for="botonComprarCredito" style="padding-bottom: 17px;"></label>
                                <Button id="botonComprarCredito" icon="pi pi-check-square" label="Compra a Credito" class="p-button-help p-button-sm" @click="openComprasCredito" />
                            </div>
                        </div>
                    </div>
                </TabPanel>

            </TabView>

            <div class="flechas-buttons">
                <Button class="p-button-sm p-button-secondary" icon="pi pi-chevron-left" label="Anterior" @click="prevStep" :disabled="activeIndex === 0" style="margin-right: 30px;"/>
                <Button class="p-button-sm p-button-secondary" icon="pi pi-chevron-right" label="Siguiente" iconPos="right" @click="nextStep" :disabled="activeIndex === steps.length - 1" />
            </div>

        </Panel>

        <Dialog header="Confirmacion" :visible.sync="displayConfirmation" :containerStyle="{width: '350px'}" :modal="true">
            <div class="confirmation-content">
                <i class="pi pi-exclamation-triangle mr-3" style="font-size: 2rem" />
                <span>¿Seguro de cambiar el precio del articulo?</span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" @click="closeConfirmation" class="p-button-sm p-button-danger"/>
                <Button label="Si" icon="pi pi-check" @click="actualizarCostosArticulo" class="p-button-sm p-button-success" autofocus />
            </template>
        </Dialog>

        <div class="dialog-cuotas">
            <Dialog header="Compra a Credito" :visible.sync="displayCompraCredito" :modal="true" :containerStyle="{width: '65vw'}">
                <div class="card">
                    <div class="p-fluid p-formgrid p-grid">
                        <div class="p-field p-col-12 p-md-4">
                            <label for="cantidadCuotas">Cantidad de Cuotas</label>
                            <InputNumber class="p-inputtext-sm" id="cantidadCuotas" v-model="form_cuotas.num_cuotas" mode="decimal" suffix=" cuotas" :min="0" :class="{'p-invalid': submitted && v$.form_cuotas.num_cuotas.$invalid}"/>
                            <small class="p-error" v-if="(submitted && v$.form_cuotas.num_cuotas.required.$invalid)"><strong>Cantidad es obligatorio.</strong></small>
                            <small class="p-error" v-if="(submitted && v$.form_cuotas.num_cuotas.minValueValue.$invalid)"><strong>No puede ser 0.</strong></small>
                        </div>

                        <div class="p-field p-col-12 p-md-4">
                            <label for="frecuenciaPago">Frecuencia de Pago</label>
                            <InputNumber class="p-inputtext-sm" id="frecuenciaPago" v-model="form_cuotas.frecuencia_pagos" mode="decimal" suffix=" dias" :min="0" :class="{'p-invalid': submitted && v$.form_cuotas.frecuencia_pagos.$invalid}"/>
                            <small class="p-error" v-if="(submitted && v$.form_cuotas.frecuencia_pagos.required.$invalid)"><strong>Frecuencia es obligatorio.</strong></small>
                            <small class="p-error" v-if="(submitted && v$.form_cuotas.frecuencia_pagos.minValueValue.$invalid)"><strong>No puede ser 0.</strong></small>
                        </div>

                        <div class="p-field p-col-12 p-md-4">
                            <label for="generarCuotas"><strong>TOTAL Bs. {{ saldoTotalCompra }}</strong></label>
                            <Button class="p-button-success p-button-sm" label="Generar Cuota" icon="pi pi-clock" @click="generarCuotas"/>
                        </div>
                    </div>

                    <div class="p-fluid p-formgrid p-grid">
                        <div class="p-field p-col-12 p-md-4">
                            <label for="cuotaInicial">Cuota Inicial</label>
                            <InputNumber class="p-inputtext-sm" id="cuota_inicial" v-model="form_cuotas.cuota_inicial" mode="decimal" :min="0" suffix=" Bs" :class="{'p-invalid': submitted && v$.form_cuotas.cuota_inicial.$invalid}"/>
                            <small class="p-error" v-if="(submitted && v$.form_cuotas.cuota_inicial.required.$invalid)"><strong>Cuota Inicial es obligatorio.</strong></small>
                            <small class="p-error" v-if="(submitted && v$.form_cuotas.cuota_inicial.minValueValue.$invalid)"><strong>No puede ser 0.</strong></small>
                        </div>

                        <div class="p-field p-col-12 p-md-4">
                            <label for="tipoPagoCuota">Tipo de Pago</label>
                            <Dropdown class="p-inputtext-sm" v-model="form_cuotas.tipoPagoCuotaSeleccionado" :options="lista_tipo_pago_cuotas" optionLabel="nombre" placeholder="Selecciona el tipo de pago" :class="{'p-invalid': submitted && v$.form_cuotas.tipoPagoCuotaSeleccionado.$invalid}"/>
                            <small class="p-error" v-if="(submitted && v$.form_cuotas.tipoPagoCuotaSeleccionado.required.$invalid)"><strong>Tipo Pago es obligatorio.</strong></small>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <DataTable
                        :value="array_cuotas_calculadas"
                        :paginator="false"
                        tableStyle="height: 33vh"
                        class="p-datatable-sm"
                        :rows="10"
                        dataKey="id"
                        :rowHover="true"
                        responsiveLayout="scroll"
                    >
                        <template #empty>
                            Sin cuotas generadas.
                        </template>

                        <Column field="id" header="#" ></Column>
                        <Column field="fecha_pago" header="Fecha Pago" ></Column>
                        <Column field="precio_cuota" header="Precio Cuota" ></Column>
                        <Column field="total_cancelado" header="Total Cancelado" ></Column>
                        <Column field="saldo_restante" header="Saldo Restante" ></Column>
                        <Column field="fecha_cancelado" header="Fecha Cancelado" ></Column>
                        <Column field="estado" header="Estado"></Column>

                    </DataTable>
                </div>

                <template #footer>
                    <Button label="Cancelar" icon="pi pi-times" @click="closeComprasCredito" class="p-button-sm p-button-danger"/>
                    <Button label="Registrar Compra" icon="pi pi-check-square" @click="registrarCompra" class="p-button-sm p-button-help" autofocus />
                </template>
            </Dialog>
        </div>

    </main>
</template>

<script>
import useVuelidate from '@vuelidate/core';
import { required, minValue } from '@vuelidate/validators';

import Button from 'primevue/button';
import Panel from 'primevue/panel';
import InputText from 'primevue/inputtext';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Card from 'primevue/card';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ColumnGroup from 'primevue/columngroup';
import AutoComplete from 'primevue/autocomplete';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import Toast from 'primevue/toast';
import SplitButton from 'primevue/splitbutton';
import Dialog from 'primevue/dialog';


export default {
    setup () {
        return { 
            v$: useVuelidate() 
        }
    },

    data() {
        return {

            activeIndex: 0,
            submitted: false,
            form: {
                proveedorSeleccionado: null, 
                tipo_comprobante: '',
                serie_comprobante: '',
                num_comprobante: ''
            },
            steps: [
                { label: 'Paso 1' },
                { label: 'Paso 2' },
            ],
            lista_comprobantes: [
                {nombre: 'Recibo', id: 1},
                {nombre: 'Nota de Ingreso', id: 2},
            ],

            form_cuotas: {
                num_cuotas: 0,
                frecuencia_pagos: 0,
                cuota_inicial: 0,
                tipoPagoCuotaSeleccionado: null,
            },
            lista_tipo_pago_cuotas: [
                {nombre: 'Efectivo'},
                {nombre: 'Tarjeta'},
                {nombre: 'QR'}
            ],

            codigo: '',
            idproveedor: null,

            buscadorArticulos: null,
            array_proveedores: [],
            loading: false,

            array_articulos_proveedor: [],
            array_articulos_seleccionados: [],
            array_articulos_completo: [],

            minDate: null,

            expandedRows: [],
            editingRows: [],

            tipoCompra: null,
            lista_tipo_compra: [
                {nombre: 'Contado', id: 1},
                {nombre: 'Credito', id: 2},
            ],
            array_almacenes: [],
            almacenSeleccionado: null,
            //total: null,
            nombreUsuario: null,
            usuario_actual_id: null,
            saldoTotalCompra: 0,
            displayConfirmation: false,
            displayCompraCredito: false,
            array_precios: [],
            objeto_newData: null,
            descuentoGlobal: 0,
            array_cuotas_calculadas: [],
        }
    },

    validations() {
        return {
            form: {
                proveedorSeleccionado: { required },
                tipo_comprobante: { required },
            },

            tipoCompra: {
                required
            },
            almacenSeleccionado: {
                required
            },

            form_cuotas : {
                num_cuotas: {
                    required,
                    minValueValue: minValue(1),
                },
                frecuencia_pagos: {
                    required,
                    minValueValue: minValue(1),
                },
                cuota_inicial: {
                    required,
                    minValueValue: minValue(1),
                },
                tipoPagoCuotaSeleccionado: { required },
            }
        }
    },

    components: {
        Button,
        Panel,
        InputText,
        TabView,
        TabPanel,
        Card,
        Dropdown,
        DataTable,
        Column,
        ColumnGroup,
        AutoComplete,
        InputNumber,
        Calendar,
        Toast,
        SplitButton,
        Dialog,
    },

    computed: {
        isMobile() {
            return window.innerWidth <= 576;
        },

        verificarCompraContado() {
            return this.tipoCompra ? this.tipoCompra.id == 2: false;
        },
    },

    methods: {

        verificarFechaVencimiento(data) {
            return ((data.vencimiento == 0) || (data.vencimiento == null)) && !(data.fecha_vencimiento);
        },

        calcularSaldoTotalCompra() {
            const totalSinDescuento = this.array_articulos_completo.reduce((total, articulo) => {
                return total + (articulo.subtotal || 0);
            }, 0);
            const descuento = (totalSinDescuento * this.descuentoGlobal) / 100;
            this.saldoTotalCompra = (totalSinDescuento - descuento).toFixed(2);
        },

        updateSubtotal(articulo) {
            const cantidad = articulo.unidades;
            if (cantidad <= 0) {
                articulo.subtotal = 0;
                return;
            }

            const precio = articulo.esPaquetesCantidad ? articulo.precio_costo_paq : articulo.precio_costo_unid;
            const descuento = (articulo.descuento / 100);
            const precioDescontado = precio * (1 - descuento);
            articulo.subtotal = cantidad * precioDescontado;
            this.updateUnidadesTotales(articulo);
            this.$forceUpdate();
        },

        updateUnidadesTotales(articulo) {
            const cantidad = articulo.unidades;
            const bonificacion = articulo.bonificacion;
            const unidadesBonificacion = articulo.esPaquetesBonificacion ? bonificacion * articulo.unidad_envase : bonificacion;
            articulo.unidadesTotales = (articulo.esPaquetesCantidad ? cantidad * articulo.unidad_envase : cantidad) + unidadesBonificacion;
        },

        calculateSubtotal(articulo) {
            const cantidad = articulo.unidades;
            const precio = articulo.esPaquetesCantidad ? articulo.precio_costo_paq : articulo.precio_costo_unid;
            const descuento = (articulo.descuento / 100);
            const precioDescontado = precio * (1 - descuento);
            return cantidad * precioDescontado;
        },

        toggleUnidadesPaquetesCantidad(articulo) {
            articulo.esPaquetesCantidad = !articulo.esPaquetesCantidad;
            this.updateSubtotal(articulo);
        },

        toggleUnidadesPaquetesBonificacion(articulo) {
            articulo.esPaquetesBonificacion = !articulo.esPaquetesBonificacion;
            this.updateUnidadesTotales(articulo);
        },

        async registrarCompra() {

            this.submitted = true;
            const result = await this.validarPaginaActual();

            if (this.array_articulos_completo.length == 0) {
                this.$toast.add({severity:'warn', summary: 'Sin artículos', detail: 'Lista de articulos vacía', life: 3000});
                return;
            }

            if (!result) {
                return;
            }

            const articulos_invalidos = this.array_articulos_completo.filter(
                articulo => !articulo.fecha_vencimiento || articulo.unidades <= 0
            );

            if (articulos_invalidos.length > 0) {
                this.$toast.add({
                severity:'error',
                summary: 'Error de validación',
                detail: 'Hay artículos sin fecha de vencimiento o con cantidad inválida',
                life: 3000
                });
                return;
            }

            try {
                this.isLoading = true;

                const compraResponse = await axios.post('/ingreso/registrarIngreso', {
                    form: this.form,
                    usuario_actual_id: this.usuario_actual_id,
                    saldoTotalCompra: this.saldoTotalCompra,
                    tipoCompra: this.tipoCompra,
                    almacenSeleccionado: this.almacenSeleccionado,
                    array_articulos_completo: this.array_articulos_completo,
                    descuento_global: this.descuentoGlobal,
                    form_cuotas: this.form_cuotas,
                    cuotaData: this.array_cuotas_calculadas,
                    formCuotas: this.form_cuotas,
                });

                if (compraResponse.data.status === 'success') {
                    const inventarios = this.prepararDatosInventario();
                    const inventarioResponse = await axios.post('/inventarios/registrarInventario', {
                        inventarios: inventarios
                    });

                    try {
                        if (inventarioResponse.data.status === 'success') {
                            this.$toast.add({severity:'success', summary: 'Éxito', detail: 'Compra registrada e inventario actualizado', life: 3000});

                            this.activeIndex = 0;
                            this.array_articulos_completo = [];
                            this.array_articulos_seleccionados = [];
                            this.form.serie_comprobante = '';
                            this.form.num_comprobante = '';
                            this.almacenSeleccionado = null;
                            this.tipoCompra = null;
                            this.saldoTotalCompra = 0;
                            this.descuentoGlobal = 0;

                            this.closeComprasCredito();
                        }
                    } catch(error) {
                        let errorInventario = 'Error al actualizar inventario';
                        this.$toast.add({severity:'error', summary: 'Error', detail: errorInventario, life: 3000});
                    }
                }
            } catch (error) {
                let errorMessage = 'Error al registrar la compra';
                if (error.response && error.response.data && error.response.data.message) {
                    errorMessage = error.response.data.message;
                }
                this.$toast.add({severity:'error', summary: 'Error', detail: errorMessage, life: 3000});
            } finally {
                this.isLoading = false;
            }
        },

        generarCuotas() {
            if (this.validarCompraCredito()) {
                const numCuotas = this.form_cuotas.num_cuotas;
                const frecuenciaPagos = this.form_cuotas.frecuencia_pagos;
                const montoTotal = this.saldoTotalCompra;
                const cuotaInicial = this.form_cuotas.cuota_inicial;
                
                const montoRestante = montoTotal - cuotaInicial;
                const montoPorCuota = montoRestante / numCuotas;

                let fechaActual = new Date();
                let saldoRestante = montoTotal;

                this.array_cuotas_calculadas = [];

                this.array_cuotas_calculadas.push({
                    id: 0,
                    fecha_pago: fechaActual.toISOString().split('T')[0],
                    precio_cuota: cuotaInicial.toFixed(2),
                    total_cancelado: cuotaInicial.toFixed(2),
                    saldo_restante: (saldoRestante - cuotaInicial).toFixed(2),
                    fecha_cancelado: fechaActual.toISOString().split('T')[0],
                    estado: 'Pagado'
                });

                saldoRestante -= cuotaInicial;

                for (let i = 1; i <= numCuotas; i++) {
                    fechaActual.setDate(fechaActual.getDate() + frecuenciaPagos);
                    saldoRestante -= montoPorCuota;

                    this.array_cuotas_calculadas.push({
                        id: i,
                        fecha_pago: fechaActual.toISOString().split('T')[0],
                        precio_cuota: montoPorCuota.toFixed(2),
                        total_cancelado: '0.00',
                        saldo_restante: saldoRestante > 0 ? saldoRestante.toFixed(2) : '0.00',
                        fecha_cancelado: '',
                        estado: 'Pendiente'
                    });
                }
            }
        },

        validarCompraCredito() {
            this.submitted = true;
            if (this.v$.form_cuotas.$invalid) {
                return false;
            }
            return true;
        },

        async openComprasCredito() {

            this.submitted = true;
            const result = await this.validarPaginaActual();

            if (this.array_articulos_completo.length == 0) {
                this.$toast.add({severity:'warn', summary: 'Sin artículos', detail: 'Lista de articulos vacía', life: 3000});
                return;
            }

            if (!result) {
                return;
            }

            const articulos_invalidos = this.array_articulos_completo.filter(
                articulo => !articulo.fecha_vencimiento || articulo.unidades <= 0
            );

            if (articulos_invalidos.length > 0) {
                this.$toast.add({
                severity:'error',
                summary: 'Error de validación',
                detail: 'Hay artículos sin fecha de vencimiento o con cantidad inválida',
                life: 3000
                });
                return;
            }

            this.displayCompraCredito = true;
        },

        closeComprasCredito() {
            this.form_cuotas.num_cuotas = 0;
            this.form_cuotas.frecuencia_pagos = 0;
            this.form_cuotas.cuota_inicial = 0;
            this.form_cuotas.tipoPagoCuotaSeleccionado = null;
            this.array_cuotas_calculadas = [];

            this.displayCompraCredito = false;
        },

        prepararDatosInventario() {
            return this.array_articulos_completo.map(articulo => ({
                idalmacen: this.almacenSeleccionado.id,
                idarticulo: articulo.id,
                //fecha_vencimiento: articulo.vencimiento || '2099-12-31',
                fecha_vencimiento: articulo.fecha_vencimiento,
                cantidad: articulo.unidadesTotales
            }));
        },

        actualizarLista() {
            let articulosActualizados = this.array_articulos_seleccionados.map(articulo => {
                let articuloExistente = this.array_articulos_completo.find(a => a.id === articulo.id);
                
                if (articuloExistente) {
                    return {
                        ...articuloExistente,
                        precio_costo_unid: articulo.precio_costo_unid,
                        precio_costo_paq: articulo.precio_costo_paq,
                        nombre: articulo.nombre,
                    };
                } else {
                    return {
                        ...articulo,
                        fecha_vencimiento: null,
                        unidadesTotales: 0,
                        vencimiento: null,
                        unidades: 0,
                        bonificacion: 0,
                        descuento: 0,
                        subtotal: 0,
                        esPaquetesCantidad: false,
                        esPaquetesBonificacion: false,
                    };
                }
            });

            this.array_articulos_completo = articulosActualizados;
        },

        onRowEditSave(event) {
            //let { newData, index } = event;
            //this.$set(this.array_articulos_seleccionados, index, newData);
            this.listarPrecios();
            this.displayConfirmation = true;
            this.objeto_newData = event;
        },

        closeConfirmation() {
            this.displayConfirmation = false;
        },

        async actualizarCostosArticulo() {

            try {
                let newData = this.objeto_newData.newData;
                let index = this.objeto_newData.index;

                const compraResponse = await axios.post('/articulo/actualizarPrecios', {
                    newData
                });

                if (compraResponse.data.status === 'success') {
                    this.$set(this.array_articulos_seleccionados, index, newData);
                    this.$toast.add({severity:'success', summary: 'Actualizado', detail: compraResponse.data.message, life: 3000});
                }

                this.objeto_newData = null;
            }
            catch (error) {
                let errorMessage = 'Error al actualizar los costos del articulo';
                this.$toast.add({severity:'error', summary: 'Error', detail: errorMessage, life: 3000});
            }

            this.displayConfirmation = false;
        },

        onRowExpand(event) {
            this.$nextTick(() => {
                this.updateSubtotal(event.data);
            });
            this.$toast.add({severity: 'info', summary: 'Información adicional', detail: event.data.name, life: 3000});
        },

        onRowCollapse(event) {
            this.$toast.add({severity: 'info', summary: 'Información comprimida', detail: event.data.name, life: 3000});
        },

        vaciarListaSeleccionados() {
            this.array_articulos_seleccionados.splice(0, this.array_articulos_seleccionados.length);
        },

        eliminarArticuloListaCompleta(articulo) {
            this.array_articulos_completo = this.array_articulos_completo.filter(a => a.id !== articulo.id);
        },

        eliminarArticuloSeleccionado(articulo) {
            this.array_articulos_seleccionados = this.array_articulos_seleccionados.filter(a => a.id !== articulo.id);
        },

        async validarPaginaActual() {

            this.submitted = true;
            if (this.activeIndex === 0) {
                this.v$.form.$touch();
                this.submitted = this.v$.form.$invalid;
                return !this.v$.form.$invalid;
            } else if (this.activeIndex === 1) {
                this.v$.tipoCompra.$touch();
                this.v$.almacenSeleccionado.$touch();
                this.submitted = (this.v$.tipoCompra.$invalid || this.v$.almacenSeleccionado.$invalid)
                return !(this.v$.tipoCompra.$invalid || this.v$.almacenSeleccionado.$invalid);
            }
        },

        async nextStep() {
            const result = await this.validarPaginaActual();

            if (!result) {
                return;
            }

            if (this.activeIndex < this.steps.length - 1) {
                if (this.activeIndex === 0) {
                    this.actualizarLista();
                }
                this.activeIndex++;
            }
        },

        prevStep() {
            //this.activeIndex -= 1;
            if (this.activeIndex > 0) {
                this.activeIndex--;
            }
        },

        listarArticulo(buscar, criterio) {
            let me = this;
            var url = '/articulo/listarArticulo?buscar=' + buscar + '&criterio=' + 'nombre' + '&idProveedor=' + this.idproveedor;
            axios.get(url).then(function (response) {
                    var respuesta = response.data;
                    //me.array_articulos_proveedor = respuesta.articulos.data;
                    me.array_articulos_proveedor = respuesta.articulos.data.map(articulo => ({
                        ...articulo,
                        precio_costo_unid: Number(articulo.precio_costo_unid),
                        precio_costo_paq: Number(articulo.precio_costo_paq),
                    }));
                })
                .catch(function (error) {
                    console.log(error);
                });
        },

        listarDatosUsuario() {
            axios.get('/user-info')
                .then(response => {
                    const userData = response.data.user;
                    this.usuario_actual_id = userData.iduse;
                    this.extraerDatosUsuario(this.usuario_actual_id);
                })
                .catch(error => {
                    console.error(error);
                });
        },

        extraerDatosUsuario(id_persona) {
            let me = this;
            var url = '/user/editarpersona?id=' + id_persona;

            axios.get(url).then(function (response) {
                var respuesta = response.data;

                me.nombreUsuario = respuesta.persona.nombre;
            })
                .catch(function (error) {
                    console.log(error);
                });
        },

        selectAlmacen() {
            let me = this;
            var url = '/almacen/selectAlmacen';
            axios.get(url).then(function (response) {
                var respuesta = response.data;
                me.array_almacenes = respuesta.almacenes;
            })
            .catch(function (error) {
                console.log(error);
            });
        },

        selectProveedor(event) {
            let me = this;

            if (!event.query.trim().length) {
                var url = `/proveedor?page=${1}&buscar=${''}&criterio=${'todos'}&por_pagina=${3}`;
                axios.get(url).then(function (response) {
                        var respuesta = response.data;
                        me.array_proveedores = respuesta.personas.data;
                        me.loading = false;
                    })
                    .catch(function (error) {
                        console.log(error);
                        me.loading = false;
                    });
            }
            else
            {
                this.loading = true;

                var url = `/proveedor?page=${1}&buscar=${me.form.proveedorSeleccionado}&criterio=${'todos'}&por_pagina=${3}`;
                axios.get(url).then(function (response) {
                        var respuesta = response.data;
                        me.array_proveedores = respuesta.personas.data;
                        me.loading = false;
                    })
                    .catch(function (error) {
                        console.log(error);
                        me.loading = false;
                    });
            }
        },

        listarPrecios() {
            let me = this;
            var url = '/precios';
            axios.get(url).then(function (response) {
                var respuesta = response.data;
                me.array_precios = respuesta.precio.data;
            }).catch(function (error) {
                console.log(error);
            });
        },

    },

    watch: {
        'form.proveedorSeleccionado.id': {
            handler(newVal) {
                if (newVal) {
                    this.idproveedor = newVal;
                    this.listarArticulo('', 'nombre');
                }
            }
        },

        buscadorArticulos(newVal) {
            if (newVal) {
                this.listarArticulo(this.buscadorArticulos, 'nombre');
            }
        },

        array_articulos_completo: {
            handler() {
                this.calcularSaldoTotalCompra();
            },
            deep: true
        },

        descuentoGlobal: {
            handler() {
                this.calcularSaldoTotalCompra();
            }
        },
    },

    created() {
        this.minDate = new Date();
    },

    mounted() {
        this.selectAlmacen();
        this.listarDatosUsuario();
    },

    beforeDestroy() {
        
    },
}
</script>

<style scoped>
/* Panel */
>>> .p-panel-header {
    padding: 0.75rem;
}

.panel-header {
    display: flex;
    align-items: center;
}

.panel-icon {
    font-size: 2rem;
    padding-left: 10px;
}

.panel-icon {
    font-size: 1.5rem;
    margin: 0;
}

>>> .p-panel-content {
    padding: 0 0.75rem 0.75rem 0.75rem;
}

/* TabView */
>>> .p-tabview .p-tabview-nav li .p-tabview-nav-link {
    color: #5b5656 !important;
    padding-top: 0.75rem;
    padding-bottom: 0;
    padding-left: 18vw;
    padding-right: 18vw;
}

.non-clickable {
    cursor: default !important;
}

>>> .p-tabview-panels {
    padding: 1.5rem 0 0 0;
}

/* Card */
.formulario-card >>> .p-card {
    box-shadow: none !important;
}

.total-saldo-card >>> .p-card {
    box-shadow: none !important;
    background: #bcffbf;
    margin-bottom: 1.5rem;
}

>>> .p-card-content {
    padding: 0.5rem 0;
}

>>> .p-card-body {
    padding: 0 0 0 0;
}

>>> .p-card-footer {
    padding: 0 0 0 0;
    display: flex;
    justify-content: right;
}

>>> .autocomplete-flecha .p-button.p-button-icon-only {
    padding: 0.4rem 0;
}

/* Tablas Articulos */
.tablas-articulos-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Grid */
>>> .p-md-3 {
    padding: 0 1.5rem 0 1.5rem !important;
    margin-bottom: 0.5rem;
}

>>> .p-md-4 {
    padding: 0 0.75rem 0 0.75rem !important;
}

.flechas-buttons {
    padding-top: 0;
    display: flex;
    justify-content: right;
}

.saldo-total {
    margin: 0.5rem 0.5rem;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Tabla Lista Completa */
.product-image {
    border-radius: 10px;
    width: 100px;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16), 0 3px 6px rgba(0, 0, 0, 0.23);
}

>>> .p-inputgroup > .p-inputwrapper:last-child > .p-inputtext {
    border-top-right-radius: 0px !important;
    border-bottom-right-radius: 0px !important;
}

/* Calendar */
@media (min-width: 768px) {
    >>> .p-datepicker-touch-ui {
        min-width: 60vw !important;
    }

    >>> .p-datepicker table th {
        padding: 0.5rem 0.5rem 0.5rem 3.2em;
    }
}

/* Tabla vacia */
.imagen-tabla-vacia {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}

.imagen-tabla-vacia img {
    box-shadow: none;
    width: 125px;
}

.titulos-pasos {
    width: 15rem;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}

>>> .tablas-card .card {
    margin-bottom: 0.5rem;
}

/* Dialog confirmacion */
.confirmation-content {
    display: flex;
    align-items: center;
    justify-content: center;
}

>>> .p-dialog-footer {
    padding: 0 0.75rem 0.75rem 0.75rem !important;
}

>>> .p-dialog-content {
    padding: 0 1.5rem 2rem 1.5rem;
}

>>> .p-dialog-header {
    padding: 1.5rem 1rem 1.5rem 1.5rem;
}

/* Dialog cuotas*/
>>> .dialog-cuotas .p-dialog-header {
    padding: 1rem 1rem 1rem 1.5rem;
    background: #33b378;
    color: #ffffff;
}

>>> .dialog-cuotas .p-dialog-content {
    padding: 1rem 1rem 0 1rem;
}

</style>