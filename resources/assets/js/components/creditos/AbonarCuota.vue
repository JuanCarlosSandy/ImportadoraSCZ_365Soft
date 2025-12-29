<template>
    <Dialog 
    :visible="!!modal" 
    :modal="true" 
    :closable="true" 
    :dismissableMask="true"
    :header="cuota ? `Abonar cuota N°${cuota.numero_cuota}` : ''" 
    @hide="cerrarModal"
    class="custom-dialog"
  >
    <div v-if="cuota" class="p-fluid">
      <div class="p-field p-grid">
        <div class="p-col-6">
          <label class="font-weight-bold">Fecha de pago</label>
          <InputText :value="formatFecha(cuota.fecha_pago)" disabled />
        </div>
        <div class="p-col-6" v-if="arrayCuotas">
          <label class="font-weight-bold">Saldo restante</label>
          <InputNumber :value="calcularSaldoRestante" :suffix="moneda[1]" disabled />
        </div>
      </div>

      <div class="p-field p-grid">
        <div class="p-col-6">
          <label class="font-weight-bold">Precio de la cuota</label>
          <InputNumber :value="cuota.precio_cuota * moneda[0]" :suffix="moneda[1]" disabled />
        </div>
        <div class="p-col-6">
          <label class="font-weight-bold">Monto a pagar</label>
          <InputNumber v-model="datosFormulario.montoPagar" :suffix="moneda[1]" placeholder="Ej. 20" />
        </div>
      </div>

      <div class="p-field p-grid">
        <div class="p-col-6">
          <label class="font-weight-bold">Forma de pago</label>
          <Dropdown v-model="tipo_pago" :options="opcionesPago" optionLabel="label" optionValue="value" placeholder="Selecciona la forma de pago" />
        </div>
      </div>
    </div>

    <template #footer>
      <Button label="No" icon="pi pi-times" class="p-button-text" @click="cerrarModal" />
      <Button label="Si, abonar" icon="pi pi-check" class="p-button-text" @click="registrarPagoCuota" />
    </template>
  </Dialog>
</template>

<script>
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import axios from 'axios';

export default {
  components: {
    Dialog,
    InputText,
    InputNumber,
    Dropdown,
    Button
  },
  props: {
    cuota: {
      type: Object,
      required: true
    },
    ventaCredito: {
      type: Object,
      required: true
    },
    modal: {
      type: Number,
      required: true
    },
    arrayCuotas: {
      type: Array,
      required: true
    },
    moneda: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      datosFormulario: {
        montoPagar: 0
      },
      errores: {},
      ultimaCuota: 0,
      tipo_pago: 0,
      opcionesPago: [
        { label: 'Efectivo', value: 1 },
        { label: 'Cheque', value: 2 },
        { label: 'QR', value: 3 },
        { label: 'Deposito', value: 4 }
      ]
    };
  },
  computed: {
    calcularSaldoRestante() {
      if (!this.arrayCuotas || this.arrayCuotas.length === 0) return 0;
      return this.arrayCuotas[this.arrayCuotas.length - 1].saldo_restante * this.moneda[0];
    }
  },
  methods: {
    registrarPagoCuota() {
      if (this.cuota.numero_cuota > 1) {
        this.ultimaCuota = this.arrayCuotas[this.cuota.numero_cuota - 2].saldo_restante;
      } else {
        this.ultimaCuota = this.cuota.saldo_restante;
      }

      const idCredito = this.cuota.idcredito;
      const numeroCuota = this.cuota.numero_cuota;
      const montoPago = this.cuota.precio_cuota;

      if ((parseFloat(this.datosFormulario.montoPagar) / parseFloat(this.moneda[0])).toFixed(2) != this.cuota.precio_cuota) {
        this.toastError("El monto a pagar debe ser igual al precio de la cuota");
        return;
      }

      const data = {
        id_credito: idCredito,
        numero_cuota: numeroCuota,
        monto_pago: montoPago,
        cuota_anterior: this.ultimaCuota,
        tipo_pago: this.tipo_pago,
      };

      axios.post('/credito/cuotas/registrar', data)
        .then(response => {
          this.toastSuccess('Pago de cuota registrado correctamente');
          this.ventaCredito.total -= montoPago;
          this.cerrarModal();
        })
        .catch(error => {
          console.error(error);
          alert('Hubo un error al procesar la solicitud. Inténtalo de nuevo más tarde.');
        });
    },
    cerrarModal() {
      this.datosFormulario = {
        montoPagar: 0,
      };
      this.$emit('cerrar-modal');
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
    toastSuccess(mensaje) {
      this.$toasted.show(`
        <div style="height: 60px;font-size:16px;">
          <br>
          ${mensaje}.<br>
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
          ${mensaje}<br>
        </div>`, {
        type: "error",
        position: "bottom-right",
        duration: 4000
      });
    },
  }
};
</script>

<style>

</style>