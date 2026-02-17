const fs = require('fs');
const pdf = require('pdf-parse-new');

let dataBuffer = fs.readFileSync('Libelula Manual de Integración v2.145.pdf');

pdf(dataBuffer).then(function(data) {
    console.log(data.text);
}).catch(function(error){
    console.error("Error reading PDF:", error);
})
