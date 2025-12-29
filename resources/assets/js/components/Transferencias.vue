<template>
    <div>
        <h2>Registrar Nueva Transferencia</h2>
        <!-- Formulario de registro de transferencia -->
        <form @submit.prevent="registrarTransferencia">
            <div>
                <label for="id_banco">Banco:</label>
                <input type="number" id="id_banco" v-model="id_banco" required>
            </div>
            <div>
                <label for="monto">Monto:</label>
                <input type="number" id="monto" v-model="monto" required>
            </div>
            <div>
                <label for="fecha_transferencia">Fecha de Transferencia:</label>
                <input type="date" id="fecha_transferencia" v-model="fecha_transferencia" required>
            </div>
            <button type="submit">Registrar Transferencia</button>
        </form>

        <hr>

        <h2>Listado de Transferencias</h2>
        <!-- Lista de transferencias -->
        <ul>
            <li v-for="transferencia in transferencias" :key="transferencia.id">
                <p><strong>Banco:</strong> {{ transferencia.id_banco }}</p>
                <p><strong>Monto:</strong> {{ transferencia.monto }}</p>
                <p><strong>Fecha de Transferencia:</strong> {{ transferencia.fecha_transferencia }}</p>
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    data() {
        return {
            id_banco: null,
            monto: null,
            fecha_transferencia: '',
            transferencias: []
        };
    },
    mounted() {
        this.loadTransferencias();
    },
    methods: {
        registrarTransferencia() {
            axios.post('/transferencias/registrar', {
                id_banco: this.id_banco,
                monto: this.monto,
                fecha_transferencia: this.fecha_transferencia,
                id_venta: 1
            })
                .then(response => {
                    console.log('Transferencia registrada exitosamente:', response.data);
                    this.resetForm();
                    this.loadTransferencias();
                })
                .catch(error => {
                    console.error('Error al registrar la transferencia:', error);
                });
        },
        loadTransferencias() {
            axios.get('/transferencias')
                .then(response => {
                    this.transferencias = response.data.transferencias;
                })
                .catch(error => {
                    console.error('Error al cargar las transferencias:', error);
                });
        },
        resetForm() {
            this.id_banco = null;
            this.monto = null;
            this.fecha_transferencia = '';
        }
    }
};
</script>
