const fs = require('fs');
// Intento estándar de nuevo, pero inspeccionando el objeto
const pdfLib = require('pdf-parse');

console.log('Type of require result:', typeof pdfLib);
console.log('Keys:', Object.keys(pdfLib));

let dataBuffer = fs.readFileSync('Libelula Manual de Integración v2.145.pdf');

// Si es una función, úsala. Si es objeto con .default, úsalo.
let pdfParser = pdfLib;
if (typeof pdfLib !== 'function' && pdfLib.default) {
    pdfParser = pdfLib.default;
}

if (typeof pdfParser === 'function') {
    pdfParser(dataBuffer).then(function(data) {
        console.log(data.text);
    }).catch(function(error){
        console.error(error);
    });
} else {
    console.error("Could not find PDF parser function.");
}
