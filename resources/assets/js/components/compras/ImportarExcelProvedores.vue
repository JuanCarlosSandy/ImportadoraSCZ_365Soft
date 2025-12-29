<template>
    <div class="modal" tabindex="-1" :class="{ 'mostrar': true }" role="dialog" aria-labelledby="myModalLabel"
        style="display: block;" aria-hidden="true">
        <div class="modal-dialog modal-primary modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myModalLabel"><i class="fa fa-upload"></i> Importar Provedores</h5>
                    <button type="button" class="close text-white" @click="cerrarModal()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div>
                        <form @submit.prevent="submitForm">
                            <div v-if="pageImportar == 0" class="text-center">
                                <div v-if="!selectedFile" class="border border-dashed border-3 p-3 mb-3 bg-light"
                                    style="cursor: pointer">
                                    <label class="custom-file" for="customFile">
                                        <i class="fa fa-cloud-upload fa-3x text-primary mb-2"></i>
                                        <p class="custom-file-label text-muted">Seleccionar un archivo Excel</p>
                                        <input type="file" class="custom-file-input" id="customFile" ref="fileInput"
                                            accept=".csv, .xlsx" @change="handleFileChange" />
                                    </label>
                                </div>
                                <div v-else class="mb-3">
                                    <i class="fa fa-file-excel-o fa-2x text-success"></i>
                                    <p class="mb-2"> {{ selectedFile.name }}</p>
                                    <button @click="removeFile" type="button" class="btn btn-danger btn-sm">
                                        <i class="fa fa-trash"></i> Eliminar archivo
                                    </button>
                                    <div class="mt-3" style="display:flex;justify-content:center;"
                                        v-if="selectedFile.name.split('.').pop().toLowerCase() == 'csv'">
                                        <label for="delimiterSelector" class="mr-2">Selecciona el delimitador:</label>
                                        <select class="form-control col-md-3" v-model="selectedDelimiter"
                                            id="delimiterSelector">
                                            <option value=";">Punto y coma (;)</option>
                                            <option value=",">Coma (,)</option>
                                        </select>
                                    </div>

                                    <div class="form-check mt-3">
                                        <input type="checkbox" id="headerCheckbox" class="form-check-input"
                                            v-model="includeHeader" />
                                        <label for="headerCheckbox" class="form-check-label">El archivo incluye
                                            encabezados</label>
                                    </div>

                                    <button type="button" @click="confirmCsv" class="btn btn-success mt-3">
                                        <i class="fa fa-check"></i> Confirmar
                                    </button>
                                </div>
                                <button type="button" @click="downloadTemplate" class="btn btn-outline-primary">
                                    <i class="fa fa-download"></i> Descargar plantilla Excel
                                </button>
                            </div>
                            <div v-if="pageImportar == 1">
                                <button @click="selectAllHeaders" type="button" class="btn btn-outline-success mb-3">
                                    <i class="fa fa-check-square-o"></i> Seleccionar Todos
                                </button>
                                <div v-if="headersArray && headersArray.length > 0" class="csv-headers-container mb-3">
                                    <p>Encabezados del archivo:</p>
                                    <div class="row">
                                        <div v-for="(header, index) in headersArray" :key="index" class="col-md-3 mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input"
                                                    v-model="selectedHeadersFromFile" :value="header" />
                                                <label class="form-check-label">{{ header }}</label>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <div v-if="selectedHeadersFromFile.length > 0" style="overflow-x: auto;">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th v-for="(header, index) in headersOrigin" :key="index">{{ header
                                                    }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="selected-header"
                                                    v-for="(selectedHeader, index) in selectedHeadersFromFile"
                                                    :key="index">
                                                    {{ selectedHeader }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <button @click="resetState" type="button" class="btn btn-secondary mt-2">
                                        <i class="fa fa-arrow-left"></i> Volver
                                    </button>
                                    <button @click="assignHeaders" type="button" class="btn btn-success mt-2">
                                        <i class="fa fa-check"></i> Asignar Encabezados
                                    </button>
                                </div>
                            </div>
                            <div v-if="pageImportar == 2">
                                <div class="mt-4">
                                    <div class="row">
                                        <div class="col">
                                            <h5><i class="fa fa-eye"></i> Vista previa:</h5>
                                        </div>
                                        <div class="col">

                                        </div>
                                    </div>

                                    <p class="text-muted">Este contenido se importará en la base de datos</p>
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
                                                        {{ cell }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <button @click="resetState2" type="button" class="btn btn-secondary mt-2">
                                        <i class="fa fa-arrow-left"></i> Volver
                                    </button>
                                    <button v-if="selectedFile" @click="downloadCsv()" type="button"
                                        class="btn btn-outline-success mt-2">
                                        <i class="fa fa-download"></i> Descargar
                                    </button>
                                    <button v-if="selectedFile" type="submit" class="btn btn-success mt-2">
                                        <i class="fa fa-upload"></i> Importar datos
                                    </button>

                                </div>
                            </div>

                            <div v-if="pageImportar == 3">
                                <div v-if="errorsImport.length == 0 && erroresNoExiste.length == 0 && !successImport"
                                    class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <h5 class="mt-3">Importando Datos</h5>
                                </div>
                                <div v-if="errorsImport.length > 0">
                                    <div class="alert alert-danger" role="alert">
                                        <h4 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> Error</h4>
                                        <p>No se pudo realizar la importación, verifique los datos del archivo</p>
                                    </div>
                                    <div v-for="(item, index) in errorsImport" :key="index" class="alert alert-danger"
                                        role="alert">
                                        <i class="fa fa-exclamation-triangle"></i> {{ item }}
                                    </div>
                                </div>
                                <div v-if="errorsImport.length == 0 && erroresNoExiste.length > 0">
                                    <h4><i class="fa fa-exclamation-circle"></i> Datos no encontrados en la base de
                                        datos</h4>
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr v-for="(item, index) in erroresNoExiste" :key="index">
                                                <td
                                                    v-html="`<span class='font-weight-bold'>${item.split(' ')[0]}</span>: ${item.split(' ').slice(1).join(' ')}`">
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div v-if="erroresNoExiste.length > 0" class="mt-3">
                                        <p>¿Desea registrar estos datos?</p>
                                        <button class="btn btn-success" type="button"
                                            @click="confirmarRegistro">Confirmar</button>
                                        <button class="btn btn-danger" type="button">Cancelar</button>
                                    </div>
                                </div>
                                <div v-if="erroresNoExiste.length == 0 && errorsImport.length == 0 && successImport"
                                    class="text-center">
                                    <div class="success-checkmark">
                                        <div class="check-icon">
                                            <span class="icon-line line-tip"></span>
                                            <span class="icon-line line-long"></span>
                                            <div class="icon-circle"></div>
                                            <div class="icon-fix"></div>
                                        </div>
                                    </div>

                                    <h5 class="mt-3"><i class="fa fa-check text-success"></i> Importación exitosa</h5>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="cerrarModal()">
                        <i class="fa fa-times"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import * as XLSX from 'xlsx';

export default {
    data() {
        return {

            registrosSuccess: [],

            headersOrigin: [
                "Nombre",
                "Tipo Documento",
                "Número",
                "Dirección",
                "Teléfono",
                "Email",
                "Contacto",
                "Teléfono de contacto",
            ],

            modalImportar: true,
            selectedFile: null,
            selectedDelimiter: ',',
            includeHeader: true,
            pageImportar: 0,
            csvHeaders: [],
            selectedHeadersFromFile: [],
            headersArray: [
            ],
            previewCsv: "",
            errorsImport: [],
            erroresNoExiste: [],
            successImport: false
        };
    },
    watch: {
        selectedDelimiter: 'updateData',
        includeHeader: 'updateData',


    },
    methods: {

        resetState() {
            this.pageImportar = 0;
            this.selectedHeadersFromFile = [];
            this.previewCsv = "";
        },
        resetState2() {
            this.pageImportar = 1;
            this.previewCsv = "";
        },
        cerrarModal() {
            this.$emit('cerrar');

        },
        async confirmarRegistro() {
            for (const elemento of this.erroresNoExiste) {
                const palabras = elemento.split(' ');
                const primeraPalabra = palabras.shift();
                const restoDelString = palabras.join(' ');
            }

            this.submitForm();
        }
        ,
        handleFileChange(event) {
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
                alert('Por favor selecciona un archivo CSV o Excel válido.');
                return;
            }

            this.selectedHeadersFromFile = [];
            this.selectedHeadersToAssign = [];
            this.headersAssigned = false;
        },

        selectAllHeaders() {
            // Seleccionar todos los encabezados automáticamente
            console.log("Headers del csv");
            console.log(this.csvHeaders);
            console.log("Headers del csv");


            this.selectedHeadersFromFile = [...this.csvHeaders.slice(0, this.headersOrigin.length)];
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
        splitRow(row) {
            const regex = /("[^"]*"|[^,]+)(?=,|$)/g;
            const columns = [];
            let match;
            while ((match = regex.exec(row)) !== null) {
                columns.push(match[0].replace(/(^,)|(,$)/g, '').trim());
            }
            return columns;
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
                    const workbook = XLSX.read(event.target.result, { type: 'binary' });
                    const firstSheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[firstSheetName];
                    content = XLSX.utils.sheet_to_csv(worksheet);
                    const rows = content.split('\n');
                    const arrayOfArrays = rows.map(row => this.splitRow(row));

                    let newContent = this.getCsvSubset(arrayOfArrays, this.selectedHeadersFromFile);

                    if (this.includeHeader) {
                        newContent.shift();
                        newContent.shift();
                    } else {
                        newContent.shift();

                    }
                    this.previewCsv = newContent;

                } else {
                    console.error("Formato de archivo no compatible.");
                    return;
                }
            };

            reader.readAsArrayBuffer(this.selectedFile);


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
                });
            });
        },
        arrayToCsv(contentCsv) {
            let csvContent = '';
            contentCsv.forEach(row => {
                csvContent += row.join(',') + '\n';
            });
            return csvContent;
        },
        submitForm() {
            if (!this.previewCsv) {
                return;
            }
            let contentCsv = this.previewCsv;
            this.pageImportar = 3;
            const csvContent = this.arrayToCsv(contentCsv);
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const newCsvFile = new File([blob], 'nuevo_csv.csv', { type: 'text/csv' });
            const formData = new FormData();
            formData.append('archivo', newCsvFile);
            axios.post('/proveedor/importar', formData)
                .then(response => {
                    this.erroresNoExiste = [];
                    this.errorsImport = [];
                    this.successImport = true;
                })
                .catch(error => {
                    if (error.response && error.response.status === 422) {
                        const datos = error.response.data.errors;
                        this.erroresNoExiste = datos.flatMap(item => {
                            const match = item.match(/No existe '([^']+)'/);
                            return match ? [match[1]] : [];
                        });
                        this.errorsImport = datos.filter(item => !item.includes("No existe"));
                        this.erroresNoExiste = this.erroresNoExiste.filter((valor, indice, array) => array.indexOf(valor) === indice);
                    } else {
                        console.error(error);
                    }
                });
        },
        downloadCSVTemplate() {
            const csvContent = this.headersOrigin.join(',') + '\n';
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = "plantilla_provedores.csv";
            link.click();
        },

        downloadTemplate() {

            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet([this.headersOrigin]);
            XLSX.utils.book_append_sheet(wb, ws, "Hoja1");

            const wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'binary' });
            const blob = new Blob([s2ab(wbout)], { type: 'application/octet-stream' });

            const nombreArchivo = 'plantilla_provedores.xlsx';
            if (navigator.msSaveBlob) {
                navigator.msSaveBlob(blob, nombreArchivo);
            } else {
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = nombreArchivo;
                link.click();
            }
        }

    }
}
function s2ab(s) {
    const buf = new ArrayBuffer(s.length);
    const view = new Uint8Array(buf);
    for (let i = 0; i < s.length; i++) view[i] = s.charCodeAt(i) & 0xFF;
    return buf;
}
</script>

<style>
/**
 * Extracted from: SweetAlert
 * Modified by: Istiak Tridip
 */
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