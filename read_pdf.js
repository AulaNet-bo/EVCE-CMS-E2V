import fs from 'fs';
// pdf-parse exporta una función por defecto que hace el trabajo
import pdf from 'pdf-parse'; 

let dataBuffer = fs.readFileSync('Libelula Manual de Integración v2.145.pdf');

pdf(dataBuffer).then(function(data) {
    console.log(data.text);
}).catch(function(error){
    console.error(error);
})
