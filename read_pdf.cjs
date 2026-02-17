const fs = require('fs');
// Al parecer la versión instalada de pdf-parse tiene una estructura diferente.
// Vamos a importar la función interna directamente si el wrapper falla.
const pdf = require('pdf-parse/lib/pdf-parse.js');

let dataBuffer = fs.readFileSync('Libelula Manual de Integración v2.145.pdf');

pdf(dataBuffer).then(function(data) {
    console.log(data.text);
}).catch(function(error){
    console.error(error);
})
