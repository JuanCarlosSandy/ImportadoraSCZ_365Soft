<template>
    <Dialog :visible.sync="dialogVisible" header="Importar Clientes" modal :containerStyle="{width: '100%', maxWidth: '800px'}"  @hide="cerrarModal">
        <template #header>
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel"><i class="fa fa-upload"></i> Importar Clientes</h5>
            </div>
        </template>
        
        <form @submit.prevent="submitForm">
            <div v-if="pageImportar == 0" class="text-center">
                <div v-if="!selectedFile" class="p-card p-component p-card-dashed p-mb-3" style="cursor: pointer">
                    <label class="custom-file" for="customFile">
                        <i class="pi pi-cloud-upload pi-3x p-text-primary p-mb-2"></i>
                        <p class="custom-file-label text-muted">Seleccionar archivo Excel</p>
                        <input type="file" class="custom-file-input" id="customFile" ref="fileInput" accept=".csv, .xlsx" @change="handleFileChange" />
                    </label>
                </div>
                <div v-else class="p-mb-3">
                    <i class="pi pi-file-excel pi-2x p-text-success"></i>
                    <p class="p-mb-2"> {{ selectedFile.name }}</p>
                    <Button icon="pi pi-trash" label="Eliminar archivo" class="p-button-danger p-button-sm" @click="removeFile" />
                    <div class="p-mt-3" style="display:flex;justify-content:center;" v-if="selectedFile.name.split('.').pop().toLowerCase() == 'csv'">
                        <label for="delimiterSelector" class="mr-2">Selecciona el delimitador:</label>
                        <Dropdown id="delimiterSelector" v-model="selectedDelimiter" :options="delimiters" optionLabel="label" class="p-col-3" />
                    </div>
                    <div class="p-field-checkbox p-mt-3">
                        <Checkbox inputId="headerCheckbox" v-model="includeHeader" :binary="true" />
                        <label for="headerCheckbox">El archivo incluye encabezados</label>
                    </div>
                    <Button icon="pi pi-check" label="Confirmar" class="p-button-success p-button-sm p-mt-3" @click="confirmCsv" />
                </div>
                <Button icon="pi pi-download" label="Descargar plantilla Excel" class="p-button-outlined p-button-sm p-button-primary" @click="downloadTemplate" />
            </div>
            <div v-if="pageImportar == 1">
                <Button icon="pi pi-check-square" label="Seleccionar Todos" class="p-button-outlined p-button-sm p-button-success p-mb-3" @click="selectAllHeaders" />
                <div v-if="headersArray && headersArray.length > 0" class="p-mb-3">
                    <p>Encabezados del archivo:</p>
                    <div class="p-grid">
                        <div v-for="(header, index) in headersArray" :key="index" class="p-col-3 p-mb-3">
                            <div class="p-field-checkbox">
                                <Checkbox v-model="selectedHeadersFromFile" :value="header"/>
                                <label>{{ header }}</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="selectedHeadersFromFile.length > 0" style="overflow-x: auto;">
                    <DataTable :value="tableData" class="p-datatable-gridlines" responsiveLayout="scroll">
                        <Column v-for="(header, index) in headersOrigin" 
                                :key="index" 
                                :field="index.toString()" 
                                :header="header">
                        <template #body="slotProps">
                            <span class="selected-header">{{ slotProps.data[slotProps.field] }}</span>
                        </template>
                        </Column>
                    </DataTable>
                    <Button icon="pi pi-arrow-left" label="Volver" class="p-button-secondary p-button-sm p-mt-2" @click="resetState" />
                    <Button icon="pi pi-check" label="Asignar Encabezados" class="p-button-success p-button-sm p-mt-2" @click="assignHeaders" />
                </div>
            </div>
            <div v-if="pageImportar == 2">
                <div class="p-grid p-mt-4">
                    <div class="p-col">
                        <h5><i class="pi pi-eye"></i> Vista previa:</h5>
                    </div>
                    <div class="p-col">
                        <Dropdown v-model="monedaSeleccionada" :options="arrayMonedas" optionLabel="nombre" placeholder="Selecciona la moneda" />
                    </div>
                </div>
                <div class="p-mt-2" v-if="arrayMonedas.length != 0">
                    <div class="toolbar">
                        <Button icon="pi pi-arrow-left" label="Volver" class="p-button-secondary p-button-sm" @click="resetState2" />
                        <Button icon="pi pi-upload" label="Importar datos" class="p-button-success p-button-sm" v-if="selectedFile && erroresPrevios.length == 0" type="submit" />
                    </div>
                </div>
                <p class="text-muted">Este contenido se importará en la base de datos</p>
                <span><i class="pi pi-exclamation-circle"></i> ADVERTENCIA</span>
                <div v-for="(item, index) in erroresPrevios" :key="index">
                    <Message severity="error" :content="item" />
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th v-for="(header, index) in headersOrigin" :key="index">{{ header
                                    }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, rowIndex) in previewCsv" :key="rowIndex">
                                <td v-for="(cell, cellIndex) in row" :key="cellIndex">
                                    {{
                                    [6, 7, 8].includes(cellIndex) ?
                                    cell + " " + monedaSeleccionada.simbolo : cell
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-if="pageImportar == 3">
                <div v-if="errorsImport.length == 0 && erroresNoExiste.length == 0 && !successImport" class="text-center">
                    <ProgressSpinner />
                    <h5 class="p-mt-3">Importando Datos</h5>
                </div>
                <div v-if="errorsImport.length > 0 && erroresNoExiste.length == 0">
                    <Message severity="error" icon="pi pi-exclamation-triangle" :closable="false">
                    <span class="font-bold"><h4>Error</h4></span>
                    <p>No se pudo realizar la importación, verifique los datos del archivo</p>
                    </Message>
                    <Message v-for="(error, index) in errorsImport" :key="index" severity="error" :closable="false">
                          {{ error }}
                    </Message>
                </div>
                <div v-if="errorsImport.length != 0 && erroresNoExiste.length > 0">
                    <div v-if="registrosSuccess.length != 0">
                        <h4><i class="pi pi-exclamation-circle"></i> Cargando datos</h4>
                        <span>Espere por favor</span>

                        <div class="progress">
                            <div class="progress-bar" role="progressbar"
                                :style="{ width: (registrosSuccess.length / erroresNoExiste.length * 100) + '%' }"
                                :aria-valuenow="registrosSuccess.length" aria-valuemin="0"
                                :aria-valuemax="erroresNoExiste.length">
                                {{ registrosSuccess.length }}/{{ erroresNoExiste.length }}
                            </div>
                        </div>
                    </div>

                    <DataTable v-if="registrosSuccess !=0" :value="registrosSuccessObject" responsiveLayout="scroll">
                        <Column>
                            <template #body="slotProps">
                            <i class="pi pi-check-circle text-success" aria-hidden="true"></i> {{ slotProps.data.description }}
                            </template>
                        </Column>
                    </DataTable>

                    <DataTable :value="erroresNoExisteObject" v-if="registrosSuccess.length == 0" responsiveLayout="scroll">
                        <h4><i class="fa fa-exclamation-circle"></i> Datos no encontrados en la base de
                            datos</h4>
                        <Column field="info" header="Información"></Column>
                    </DataTable>

                    <div v-if="erroresNoExiste.length > 0 && successImport.length != 0" class="mt-3">
                        <p>¿Desea registrar estos datos?</p>

                        <Button label="Confirmar" icon="pi pi-check" class="p-button-success p-button-sm" @click="confirmarRegistro" />
                        <Button label="Cancelar" icon="pi pi-times" class="p-button-danger p-button-sm" />
                    </div>

                </div>
                <div v-if="erroresNoExiste.length == 0 && errorsImport.length == 0 && successImport" class="text-center">
                    <div class="success-checkmark">
                        <div class="check-icon">
                            <span class="icon-line line-tip"></span>
                            <span class="icon-line line-long"></span>
                            <div class="icon-circle"></div>
                            <div class="icon-fix"></div>
                        </div>
                    </div>
                    <h5 class="p-mt-3"><i class="pi pi-check p-text-success"></i> Importación exitosa</h5>
                </div>
            </div>
        </form>
        <template #footer>
            <Button icon="pi pi-times" label="Cerrar" class="p-button-secondary p-button-sm" @click="cerrarModal" />
        </template>
    </Dialog>
</template>

<script>
import  Dialog  from 'primevue/dialog';
import  Button  from 'primevue/button';
import  InputText from 'primevue/inputtext';
import  Dropdown from 'primevue/dropdown';
import Checkbox from 'primevue/checkbox';
import  ProgressSpinner from 'primevue/progressspinner';
import  DataTable from 'primevue/datatable';
import  Column from 'primevue/column';
import  Message from 'primevue/message';
import  ProgressBar from 'primevue/progressbar';
import * as XLSX from 'xlsx';
import { error } from 'jquery';
export default {
    props: {
        visible: {
            type: Boolean,
            required: true,
        },
    },
    components: {
        Dialog,
        Button,
        InputText,
        Dropdown,
        Checkbox,
        ProgressSpinner,
        DataTable,
        Column,
        Message,
        ProgressBar
    },
    data() {
        return {
            dialogVisible: this.visible,
            pageImportar: 0,
            selectedFile: null,
            selectedDelimiter: ',',
            includeHeader: true,
            headersArray: [],
            selectedHeadersFromFile: [],
            headersOrigin: [],
            monedaSeleccionada: {
                id: 1,
                nombre: "Boliviano",
                simbolo: "Bs",
                tipo_cambio: "1.00"
            },
            arrayMonedas: [],
            previewCsv: [],
            erroresPrevios: [],
            errorsImport: [],
            erroresNoExiste: [],
            successImport: false,
            isLoading: false,
            delimiters: [
                { label: 'Punto y coma (;)', value: ';' },
                { label: 'Coma (,)', value: ',' }
            ],
            registrosSuccess: [],
            csvHeaders: [],
            headersOrigin: [
                "Nombre Cliente",
                "Ubicacion",
                "Num Documento",
                "Telefono"
            ],
            previewCsvObject: [],
            previewCsvObjectColumns : [],
            erroresNoExisteObject : [],
            registrosSuccessObject: []
            
        };
    },
    watch: {
        selectedDelimiter: 'updateData',
        includeHeader: 'updateData',


    },
    computed: {
        tableData() {
            return [
                this.selectedHeadersFromFile.reduce((obj, value, index) => {
                obj[index] = value;
                return obj;
                }, {})
            ];
        },
        registrosSuccessObject() {
            return this.registrosSuccess.map(description => ({ description }));
        }
    },
    methods: {
        formatCellValue(value, field) {
        // Asumiendo que los índices 5-13 corresponden a ciertos campos
        const monedaFields = ['Precio List unidad', 'Precio costo unidad', 'Precio costo paquete', 'Precio venta', 'Precio uno', 'Precio dos', 'Precio tres', 'Precio cuatro', 'Costo compra'];
        if (monedaFields.includes(field)) {
            return `${value} ${this.monedaSeleccionada.simbolo}`;
        }
        return value;
        },
        updateRegistrosSuccessObject() {
            this.registrosSuccessObject = this.registrosSuccess.map(description => ({ description }));
            console.log("UPDATE REGISTRO ",this.registrosSuccessObject)
        },
        convertArrayToObjectError() {
            let objeto = this.erroresNoExiste.reduce((acc, item) => {
                let [key, ...rest] = item.split(' ');
                let value = rest.join(' ');

                if (acc[key]) {
                let count = 1;
                while (acc[`${key} ${count}`]) {
                    count++;
                }
                acc[`${key} ${count}`] = value;
                } else {
                acc[key] = value;
                }
                return acc;
            }, {});

            this.erroresNoExisteObject = Object.entries(objeto).map(([key, value]) => ({
                info: `${key}: ${value}`
            }));
        },
        listarMonedas() {
            let url = '/moneda/selectMoneda';
            axios.get(url).then((response) => {
                let respuesta = response.data;
                this.arrayMonedas = respuesta.monedas;
                console.log(this.arrayMonedas)
            })
                .catch(function (error) {
                    console.log(error);
                });
        },
        cerrarModal() {
            this.$emit('cerrar');
        },
        submitForm() {
            if (!this.previewCsv) {
                return;
            }
            console.log("REgistrando");
            console.log("data ",this.previewCsv)
            let contentCsv = this.dividirElementos(this.previewCsv)


            this.pageImportar = 3;
            // ANtes era en csv ahora quier convertir ese array a excel y enviar el excel
            // const csvContent = this.arrayToCsv(contentCsv);
            // const blob = new Blob([csvContent], { type: 'text/csv' });
            // const newCsvFile = new File([blob], 'nuevo_csv.csv', { type: 'text/csv' });
            // const formData = new FormData();
            // formData.append('archivo', newCsvFile);
            const excelFile = this.arrayToExcel(contentCsv, 'nuevo_excel.xlsx');
            const formData = new FormData();
            formData.append('archivo', excelFile);
            axios.post('/cliente/importar', formData)
                .then(response => {
                    this.erroresNoExiste = [];
                    this.errorsImport = [];
                    this.successImport = true;
                    this.registrosSuccess = [];

                    console.log("Respuesta 1",response);
                    console.log(response)

                })
                .catch(error => {
                    console.log(error.response)
                    if (error.response && error.response.status === 422) {
                        const datos = error.response.data.errors;
                        this.erroresNoExiste = datos.flatMap(item => {
                            const match = item.match(/No existe '([^']+)'/);
                            return match ? [match[1]] : [];
                        });
                        this.errorsImport = datos.filter(item => !item.includes("No existe"));
                        this.erroresNoExiste = this.erroresNoExiste.filter((valor, indice, array) => array.indexOf(valor) === indice);
                        console.log("Respuesta 2");

                        console.log("error 1",this.erroresNoExiste);
                        this.convertArrayToObjectError();
                        console.log("error 2",this.errorsImport);
                    } else {
                        console.error(error);
                    }
                });
        },
        handleFileChange() {
            const file = event.target.files[0];
            const extension = file.name.split('.').pop().toLowerCase();

            if (extension === 'csv' || extension === 'xlsx') {
                this.selectedFile = file;
                this.extractHeaders(file, extension)
                    .then(headers => {
                        this.csvHeaders = headers;
                        this.headersArray = headers;
                    })
                    .catch(error => {
                        console.error('Error al extraer encabezados:', error);
                    });
            } else {
                alert('Por favor selecciona un archivo Excel válido.');
                return;
            }

            this.selectedHeadersFromFile = [];
            this.selectedHeadersToAssign = [];
            this.headersAssigned = false;
        },
        removeFile() {
            this.selectedFile = null;
            this.csvHeaders = null;
            this.selectedHeadersFromFile = [];
            this.selectedHeadersToAssign = [];
            this.headersAssigned = false;
            this.$refs.fileInput.value = '';
            this.previewCsv = "";
        },
        confirmCsv() {
            this.pageImportar += 1;

            if (!this.includeHeader) {
                const mapping = {};
                this.csvHeaders.forEach((item, index) => {
                    mapping[item] = (index + 1).toString();
                });
                const newArray = this.csvHeaders.map(item => mapping[item]);
                this.csvHeaders = newArray;
                this.headersArray = newArray;
            }
        },
        splitRow(row, index) {
            const columns = [];
            let currentColumn = '';
            let withinQuotes = false;
            let columnCount = 0;

            for (let i = 0; i < row.length; i++) {
                const char = row[i];
                if (char === ',' && !withinQuotes) {
                    columns.push(currentColumn.trim());
                    if (currentColumn.trim() == "") {
                        let error = "Campo vacio en la fila " + (index + 1) + " Columna " + (columnCount + 1)
                        this.erroresPrevios.push(error);
                    }
                    currentColumn = '';
                    columnCount++;
                    if (columnCount === this.headersOrigin.length) // Salir del bucle si ya hemos alcanzado las 5 columnas
                        break;
                } else if (char === '"') {
                    if (i < row.length - 1 && row[i + 1] === '"') {
                        currentColumn += '"';
                        i++;
                    } else {
                        withinQuotes = !withinQuotes;
                    }
                } else {
                    currentColumn += char;
                }
            }

            // Agregar la última columna si hay menos de 5 columnas
            if (columnCount < this.headersOrigin.length) {


                columns.push(currentColumn.trim());
            }

            return columns;
        },
        downloadTemplate() {
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet([this.headersOrigin]);
            XLSX.utils.book_append_sheet(wb, ws, "Hoja1");

            const wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'binary' });
            const blob = new Blob([s2ab(wbout)], { type: 'application/octet-stream' });

            const nombreArchivo = 'plantilla_clientes.xlsx';
            if (navigator.msSaveBlob) {
                navigator.msSaveBlob(blob, nombreArchivo);
            } else {
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = nombreArchivo;
                link.click();
            }
        },
        selectAllHeaders() {
             // Seleccionar todos los encabezados automáticamente
             console.log("Headers del csv");
            console.log(this.csvHeaders);
            console.log("Headers del csv");

            this.selectedHeadersFromFile = [...this.csvHeaders.slice(0, this.headersOrigin.length)];
            console.log("array",this.selectedHeadersFromFile)

        },
        updateData() {
            this.extractHeaders(this.selectedFile, this.selectedFile.name.split('.').pop().toLowerCase())
                .then(headers => {
                    this.csvHeaders = headers;
                    this.headersArray = headers;
                })
                .catch(error => {
                    console.error('Error al extraer encabezados:', error);
                });
        },
        extractHeaders(file, extension) {
            return new Promise((resolve, reject) => {
                if (extension === 'csv') {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const content = e.target.result;
                        const rows = content.split('\n');
                        const headers = rows[0].split(this.selectedDelimiter);
                        resolve(headers);
                    };
                    reader.onerror = (error) => {
                        reject(error);
                    };
                    reader.readAsText(file);
                } else if (extension === 'xlsx') {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        const data = new Uint8Array(event.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const firstSheetName = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[firstSheetName];
                        const headers = [];
                        for (const key in worksheet) {
                            if (key[0] === '!') continue;
                            if (key[1] === '1') {
                                headers.push(worksheet[key].v);
                            } else {
                                break;
                            }
                        }
                        resolve(headers);
                    };
                    reader.onerror = (error) => {
                        reject(error);
                    };
                    reader.readAsArrayBuffer(file);
                } else {
                    reject('Formato de archivo no compatible');
                }
            });
        },
        resetState() {
            this.pageImportar = 0;
            this.selectedHeadersFromFile = [];
            this.previewCsv = "";
        },
        assignHeaders() {
            if (!this.selectedFile) {
                console.error("No se ha seleccionado un archivo.");
                return;
            }
            this.pageImportar += 1;
            const reader = new FileReader();

            reader.onload = (event) => {
                let content;
                if (this.selectedFile.name.endsWith('.csv')) {
                    const buffer = event.target.result;
                    const decoder = new TextDecoder('utf-8');
                    content = decoder.decode(buffer);
                    const rows = content.split('\n');
                    const arrayOfArrays = rows.map(row => row.split(this.selectedDelimiter));
                    let newContent = this.getCsvSubset(arrayOfArrays, this.selectedHeadersFromFile);

                    if (this.includeHeader) {
                        newContent.shift();
                        newContent.shift();
                    } else {
                        newContent.shift();

                    }
                    this.previewCsv = newContent;

                } else if (this.selectedFile.name.endsWith('.xlsx')) {
                    console.log("archivo ")
                    const workbook = XLSX.read(event.target.result, { type: 'binary' });
                    const firstSheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[firstSheetName];
                    content = XLSX.utils.sheet_to_csv(worksheet);
                    const rows = content.split('\n');
                    const arrayOfArrays = rows.map((row, index) => this.splitRow(row, index));
                    console.log("DATO FINAL");
                    console.log(arrayOfArrays);
                    console.log("DATO FINAL");

                    let newContent = this.getCsvSubset(arrayOfArrays, this.selectedHeadersFromFile);

                    console.log(" new content",this.selectedHeadersFromFile);


                    if (this.includeHeader) {
                        newContent.shift();
                        newContent.shift();
                    } else {
                        newContent.shift();

                    }
                    this.previewCsv = newContent;
                    this.previewCsvObject= this.convertArrayToObject(this.selectedHeadersFromFile,this.previewCsv);
                    this.previewCsvObjectColumns = this.createColumns(this.selectedHeadersFromFile);
                    console.log("CSV ARCHIVO");
                    console.log(this.previewCsv);
                    console.log("CSV ARCHIVO");
                } else {
                    console.error("Formato de archivo no compatible.");
                    return;
                }
            };

            reader.readAsArrayBuffer(this.selectedFile);
            this.listarMonedas();
        },
        convertArrayToObject(headers, array) {
            return array.map(row => {
                return row.reduce((acc, value, index) => {
                acc[headers[index]] = value;
                return acc;
                }, {});
            });
        },
        createColumns(headers) {
            return headers.map(header => ({
                field: header,
                header: header
            }));
        },
        getCsvSubset(csvContent, selectedHeaders) {
            // Convertir el contenido CSV en un array de arrays si aún no lo es
            const rows = Array.isArray(csvContent) ? csvContent : csvContent.split("\n").map(row => row.split(this.selectedDelimiter));

            // Obtener los índices de las columnas según los encabezados seleccionados
            const headerIndices = selectedHeaders.map(header => this.csvHeaders.indexOf(header));

            // Reordenar las columnas según los índices
            const newRows = rows.map(row => headerIndices.map(index => row[index]));

            // Agregar los encabezados como primera fila en el nuevo contenido
            const newCsvContent = [selectedHeaders];

            // Devolver el nuevo contenido como un array de arrays
            return newCsvContent.concat(newRows);
        },
        resetState2() {
            this.pageImportar = 1;
            this.previewCsv = "";
        },
        downloadCsv() {
            let csvFile = this.previewCsv.map(row => row.join(',')).join('\n');

            const blob = new Blob([csvFile], { type: "text/csv" });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = "nuevo_csv.csv";
            link.click();
        },
        createNewCsvData() {
            const selectedHeadersFromFile = this.selectedHeadersFromFile;
            const selectedHeadersToAssign = this.selectedHeadersToAssign;
            const newData = this.csvData.map((row) => {
                const newRow = {};

                selectedHeadersFromFile.forEach((header) => {
                    newRow[header] = row[header];
                });

                selectedHeadersToAssign.forEach((header) => {
                    newRow[header] = '';
                });

                return newRow;
            });

            const newCsvContent = [selectedHeadersToAssign.concat(selectedHeadersFromFile).join(',')];
            newData.forEach((row) => {
                const values = selectedHeadersToAssign.concat(selectedHeadersFromFile).map((header) => row[header]);
                newCsvContent.push(values.join(','));
            });

            return newCsvContent.join('\n');
        },
        dividirElementos(array) {
            return array.map(subarray => {
                return subarray.map((valor, indice) => {
                    if (indice >= 5 && indice <= 13) {
                        return parseFloat(valor) / this.monedaSeleccionada.tipo_cambio;
                    } else {
                        return valor;
                    }
                });
            });
        },
        arrayToCsv(contentCsv) {
            let csvContent = '';
            contentCsv.forEach(row => {
                csvContent += row.join(';') + '\n';
            });
            return csvContent;
        },
        arrayToExcel(data, fileName) {
            const workbook = XLSX.utils.book_new();
            const worksheet = XLSX.utils.aoa_to_sheet(data);

            XLSX.utils.book_append_sheet(workbook, worksheet, 'Sheet1');

            const excelBlob = this.workbookToBlob(workbook);

            const excelFile = new File([excelBlob], fileName, { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });

            return excelFile;
        },
        workbookToBlob(workbook) {
            const excelBuffer = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });

            const excelBlob = new Blob([excelBuffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });

            return excelBlob;
        },
        async confirmarRegistro() {
            try {
                console.log('Iniciando confirmación de registro');
                this.isLoading = true;
                console.log('isLoading:', this.isLoading);

                for (const elemento of this.erroresNoExiste) {
                const palabras = elemento.split(' ');
                const primeraPalabra = palabras.shift();
                const restoDelString = palabras.join(' ');

                try {
                    if ("Linea" === primeraPalabra) {
                    await this.agregarLinea(restoDelString);
                    } else if ("Marca" === primeraPalabra) {
                    await this.agregarMarca(restoDelString);
                    } else if ("Grupo" === primeraPalabra) {
                    await this.agregarGrupo(restoDelString);
                    } else if ("Industria" === primeraPalabra) {
                    await this.agregarIndustria(restoDelString);
                    }
                } catch (error) {
                    console.error("Ocurrió un error al agregar: " + error);
                }
                }

                console.log("Termino de registrar");
                console.log("REGISTRO ", this.registrosSuccess);
                console.log("registro nuevo ", this.registrosSuccessObject);
                this.submitForm();
            } finally {
                this.isLoading = false;
                console.log('isLoading:', this.isLoading);
            }
        },
        agregarMarca(nombre) {
            return axios.post('/marca/registrar', {
                'nombre': nombre
            }).then((response) => {
                this.registrosSuccess.push("Se registro la marca " + nombre);
                this.updateRegistrosSuccessObject();
            }).catch((error) => {
                console.log(error);
            });
        },
        agregarGrupo(nombre) {
            return axios.post('/grupos/registrar', {
                'nombre_grupo': nombre
            }).then((response) => {
                this.registrosSuccess.push("Se registro el grupo " + nombre);
                this.updateRegistrosSuccessObject();
            }).catch((error) => {
                console.log(error);
            });
        },
        agregarLinea(nombre) {
            return axios.post('/categoria/registrar', {
                'nombre': nombre,
                'descripcion': "",
                'codigoProductoSin': 0

            }).then((response) => {
                this.registrosSuccess.push("Se registro la linea " + nombre);
                this.updateRegistrosSuccessObject();
            }).catch((error) => {
                console.log(error);
            });
        },
        agregarIndustria(nombre) {
            return axios.post('/industria/registrar', {
                'nombre': nombre
            }).then((response) => {
                this.registrosSuccess.push("Se registro la industria " + nombre);
                console.log("Registros sucees")

                console.log(this.registrosSuccess);
                this.updateRegistrosSuccessObject();
            }).catch((error) => {
                console.log(error);
            });
        },
    }
}
function s2ab(s) {
    const buf = new ArrayBuffer(s.length);
    const view = new Uint8Array(buf);
    for (let i = 0; i < s.length; i++) view[i] = s.charCodeAt(i) & 0xFF;
    return buf;
}
</script>

<style scoped>
.custom-file {
    cursor: pointer;
}
.p-card-dashed {
    border-style: dashed;
}
.table thead th {
    background-color: #ffffff;
    color: #000000;

}
.success-checkmark {
    width: 80px;
    height: 115px;
    margin: 0 auto;

    .check-icon {
        width: 80px;
        height: 80px;
        position: relative;
        border-radius: 50%;
        box-sizing: content-box;
        border: 4px solid #4CAF50;

        &::before {
            top: 3px;
            left: -2px;
            width: 30px;
            transform-origin: 100% 50%;
            border-radius: 100px 0 0 100px;
        }

        &::after {
            top: 0;
            left: 30px;
            width: 60px;
            transform-origin: 0 50%;
            border-radius: 0 100px 100px 0;
            animation: rotate-circle 4.25s ease-in;
        }

        &::before,
        &::after {
            content: '';
            height: 100px;
            position: absolute;
            background: #FFFFFF;
            transform: rotate(-45deg);
        }

        .icon-line {
            height: 5px;
            background-color: #4CAF50;
            display: block;
            border-radius: 2px;
            position: absolute;
            z-index: 10;

            &.line-tip {
                top: 46px;
                left: 14px;
                width: 25px;
                transform: rotate(45deg);
                animation: icon-line-tip 0.75s;
            }

            &.line-long {
                top: 38px;
                right: 8px;
                width: 47px;
                transform: rotate(-45deg);
                animation: icon-line-long 0.75s;
            }
        }

        .icon-circle {
            top: -4px;
            left: -4px;
            z-index: 10;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            position: absolute;
            box-sizing: content-box;
            border: 4px solid rgba(76, 175, 80, .5);
        }

        .icon-fix {
            top: 8px;
            width: 5px;
            left: 26px;
            z-index: 1;
            height: 85px;
            position: absolute;
            transform: rotate(-45deg);
            background-color: #FFFFFF;
        }
    }
}

@keyframes rotate-circle {
    0% {
        transform: rotate(-45deg);
    }

    5% {
        transform: rotate(-45deg);
    }

    12% {
        transform: rotate(-405deg);
    }

    100% {
        transform: rotate(-405deg);
    }
}

@keyframes icon-line-tip {
    0% {
        width: 0;
        left: 1px;
        top: 19px;
    }

    54% {
        width: 0;
        left: 1px;
        top: 19px;
    }

    70% {
        width: 50px;
        left: -8px;
        top: 37px;
    }

    84% {
        width: 17px;
        left: 21px;
        top: 48px;
    }

    100% {
        width: 25px;
        left: 14px;
        top: 45px;
    }
}

@keyframes icon-line-long {
    0% {
        width: 0;
        right: 46px;
        top: 54px;
    }

    65% {
        width: 0;
        right: 46px;
        top: 54px;
    }

    84% {
        width: 55px;
        right: 0px;
        top: 35px;
    }

    100% {
        width: 47px;
        right: 8px;
        top: 38px;
    }
}
</style>
