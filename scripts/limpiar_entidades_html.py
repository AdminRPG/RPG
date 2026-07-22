import os
import re
import sys

def main():
    target_dirs = ['.']
    # Add whatever extensions you want to check
    extensions = ('.php', '.js', '.xml', '.html')
    
    replacements = {
        '&aacute;': 'á',
        '&eacute;': 'é',
        '&iacute;': 'í',
        '&oacute;': 'ó',
        '&uacute;': 'ú',
        '&ntilde;': 'ñ',
        '&Aacute;': 'Á',
        '&Eacute;': 'É',
        '&Iacute;': 'Í',
        '&Oacute;': 'Ó',
        '&Uacute;': 'Ú',
        '&Ntilde;': 'Ñ'
    }
    
    # Regex to find any of the keys
    pattern = re.compile('|'.join(re.escape(k) for k in replacements.keys()))
    
    fix_mode = '--fix' in sys.argv
    
    total_found = 0
    files_with_entities = 0

    print(f"Buscando entidades HTML en archivos {extensions} ...\n")
    
    for root, dirs, files in os.walk('.'):
        if '.git' in root or 'node_modules' in root or 'cache' in root:
            continue
            
        for file in files:
            if file.endswith(extensions):
                filepath = os.path.join(root, file)
                try:
                    with open(filepath, 'r', encoding='utf-8') as f:
                        lines = f.readlines()
                except UnicodeDecodeError:
                    # Ignore files that cannot be decoded as UTF-8
                    continue
                
                file_has_entities = False
                new_lines = []
                
                for i, line in enumerate(lines):
                    matches = pattern.findall(line)
                    if matches:
                        if not file_has_entities:
                            print(f"\n--- {filepath} ---")
                            file_has_entities = True
                            files_with_entities += 1
                        
                        count = len(matches)
                        total_found += count
                        entities_found = ", ".join(set(matches))
                        print(f"  Línea {i+1}: encontradas {count} entidades ({entities_found})")
                        
                        if fix_mode:
                            for ent, char in replacements.items():
                                line = line.replace(ent, char)
                    
                    new_lines.append(line)
                
                if fix_mode and file_has_entities:
                    with open(filepath, 'w', encoding='utf-8') as f:
                        f.writelines(new_lines)
                    print(f"  [+] Archivo modificado y guardado con UTF-8 natural.")

    print("\n" + "="*50)
    print(f"Total de entidades encontradas: {total_found}")
    print(f"Archivos afectados: {files_with_entities}")
    if not fix_mode and total_found > 0:
        print("\nPara corregirlas automáticamente, ejecuta el script con el parámetro --fix:")
        print("    python scripts/limpiar_entidades_html.py --fix")
    elif fix_mode:
        print("\n¡Todas las entidades han sido corregidas!")

if __name__ == "__main__":
    main()
