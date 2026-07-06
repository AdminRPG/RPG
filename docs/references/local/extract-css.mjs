import fs from 'fs';

function main() {
  console.log('Reading XML backup...');
  const xml = fs.readFileSync('../../docs/themes/iforge-child-theme.xml', 'utf8');

  // Regex to match iforge.css CDATA content
  const match = xml.match(/<stylesheet name="iforge\.css"[^>]*><!\[CDATA\[([\s\S]*?)\]\]><\/stylesheet>/);
  
  if (match && match[1]) {
    const css = match[1];
    fs.writeFileSync('references/local/iforge.css', css);
    console.log('Successfully extracted iforge.css to references/local/iforge.css');
  } else {
    console.error('Could not find iforge.css CDATA in the XML theme.');
  }
}

main();
