const fs = require('fs');
const { PDFParse } = require('pdf-parse'); // Destructuring basado en el debug anterior

let dataBuffer = fs.readFileSync('Libelula Manual de Integración v2.145.pdf');

// La librería parece exportar una clase o función PDFParse con mayúsculas
// Intentemos usarla
try {
    // Si es una clase, tal vez new PDFParse()? O función estática?
    // En versiones recientes de pdf-parse (forks), a veces cambia.
    // Pero el 'pdf-parse' clásico exporta una función única. 
    // Si el objeto tiene 'PDFParse', probemos ese.
    
    // OJO: Si Keys tiene PDFParse, es probable que sea una clase interna.
    // Vamos a intentar leer el archivo index.js del paquete para ver qué exporta realmente.
    
    const libPath = require.resolve('pdf-parse');
    console.log('Lib path:', libPath);
    
    // Fallback: usar el default que suele ser lo que queremos, pero aquí no salió en keys.
    // Probemos invocar lo que sea que 'PDFParse' sea.
    
} catch (e) {
    console.error(e);
}
