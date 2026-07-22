# -*- coding: utf-8 -*-
"""
Extrae Capacidades + Notas del MACRO-CATALOGO-AKUMA-NO-MI.md y las vuelca en
inc/ope_rol/catalogos/akuma_no_mi_db.json (solo campos vacios: capacidades_raw,
notas_jugadores, notas_staff). Valida coherencia tier/secundario/potencia.

Uso:
    py scripts/_extract-akuma-caps.py            # dry-run (solo informe)
    py scripts/_extract-akuma-caps.py --write     # escribe el JSON
"""
import json
import os
import re
import sys
import unicodedata

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
MACRO = os.path.join(ROOT, '..', 'I-Forge-Sistema', 'docs', '02-HAKI-Y-FRUTAS',
                     'MACRO-CATALOGO-AKUMA-NO-MI.md')
JSON_PATH = os.path.join(ROOT, 'inc', 'ope_rol', 'catalogos', 'akuma_no_mi_db.json')

WRITE = '--write' in sys.argv


def norm_name(s):
    s = unicodedata.normalize('NFKD', s).encode('ascii', 'ignore').decode('ascii')
    s = s.lower()
    s = re.sub(r'\bno mi\b', '', s)
    s = re.sub(r'\(.*?\)', '', s)
    s = re.sub(r'[^a-z0-9]+', ' ', s).strip()
    return s


def clean_block(text):
    """Markdown -> texto plano legible para el <pre> del modal."""
    out = []
    for raw in text.splitlines():
        line = raw.rstrip()
        if not line.strip():
            out.append('')
            continue
        # sub-nivel: '### Nv.0 - Manifestacion' -> encabezado limpio
        m = re.match(r'^#{2,4}\s*(Nv\.\d.*)$', line)
        if m:
            if out and out[-1] != '':
                out.append('')
            out.append(m.group(1).strip())
            continue
        if line.startswith('#'):
            continue
        line = line.lstrip()
        if line.startswith('- '):
            line = '- ' + line[2:]
        line = line.replace('**', '').replace('`', '')
        out.append(line)
    # colapsa multiples vacios
    res = []
    for l in out:
        if l == '' and (not res or res[-1] == ''):
            continue
        res.append(l)
    return '\n'.join(res).strip()


def parse_macro(text):
    lines = text.splitlines(keepends=True)
    # offsets de headings ### que NO son Nv.
    fruit_starts = []
    for m in re.finditer(r'^### (?!Nv\.)(.+)$', text, re.M):
        fruit_starts.append((m.start(), m.group(1).strip()))
    fruits = []
    for idx, (pos, name) in enumerate(fruit_starts):
        end = fruit_starts[idx + 1][0] if idx + 1 < len(fruit_starts) else len(text)
        block = text[pos:end]
        fruits.append((name, block))
    return fruits


def section(block, header, headers_all):
    """Devuelve el texto de '## header' hasta el siguiente '## '."""
    m = re.search(r'^##\s+' + re.escape(header) + r'\s*$', block, re.M)
    if not m:
        return ''
    start = m.end()
    nxt = re.search(r'^##\s+', block[start:], re.M)
    end = start + nxt.start() if nxt else len(block)
    return block[start:end].strip()


def caps_section(block):
    m = re.search(r'^##\s+Capacidades por nivel\s*$', block, re.M)
    if not m:
        return ''
    start = m.end()
    # hasta '## Notas de diseno'
    nxt = re.search(r'^##\s+Notas de dise', block[start:], re.M)
    end = start + nxt.start() if nxt else len(block)
    return clean_block(block[start:end])


def meta(block, key):
    m = re.search(r'^-\s+\*\*' + re.escape(key) + r':\*\*\s*(.+)$', block, re.M)
    return m.group(1).strip().replace('`', '') if m else ''


def main():
    macro_text = open(MACRO, encoding='utf-8').read()
    data = json.load(open(JSON_PATH, encoding='utf-8'))

    fruits = parse_macro(macro_text)
    print('MACRO fruit blocks:', len(fruits))
    print('JSON entries      :', len(data))

    by_slug = {}
    by_name = {}
    for name, block in fruits:
        slug = meta(block, 'ID')  # p.ej. fruta.aro_aro
        rec = {
            'name': name,
            'slug': slug,
            'tier': meta(block, 'Tier'),
            'tipo': meta(block, 'Tipo'),
            'sec': meta(block, 'Secundario Potencia'),
            'origen': meta(block, 'Origen'),
            'caps': caps_section(block),
            'notas_jug': clean_block(section(block, 'Notas de diseño de cartas (para jugadores)', None)),
            'notas_staff': clean_block(section(block, 'Notas staff', None)),
        }
        if slug:
            by_slug[slug] = rec
        by_name[norm_name(name)] = rec

    matched = 0
    unmatched = []
    warnings = []
    caps_lens = []
    for d in data:
        slug = d.get('slug', '')
        rec = by_slug.get(slug) or by_name.get(norm_name(d.get('nombre', '')))
        if not rec:
            unmatched.append(d.get('nombre'))
            continue
        matched += 1
        caps_lens.append(len(rec['caps']))
        # validaciones
        jt = str(d.get('tier'))
        if rec['tier'] and rec['tier'] != jt:
            warnings.append(f"TIER  {d['nombre']}: json={jt} macro={rec['tier']}")
        js = str(d.get('secundario'))
        if rec['sec'] and rec['sec'] != js:
            warnings.append(f"SEC   {d['nombre']}: json={js} macro={rec['sec']}")
        if not rec['caps']:
            warnings.append(f"NOCAP {d['nombre']}: MACRO sin capacidades")

    print('\nEmparejadas:', matched, '/', len(data))
    print('Sin emparejar:', len(unmatched))
    for u in unmatched:
        print('   -', u)
    if caps_lens:
        print('\ncaps chars: min', min(caps_lens), 'max', max(caps_lens),
              'avg', sum(caps_lens) // len(caps_lens))
    print('\nAvisos de coherencia:', len(warnings))
    for w in warnings[:60]:
        print('   *', w)

    if WRITE:
        filled = {'capacidades_raw': 0, 'notas_jugadores': 0, 'notas_staff': 0}
        for d in data:
            slug = d.get('slug', '')
            rec = by_slug.get(slug) or by_name.get(norm_name(d.get('nombre', '')))
            if not rec:
                continue
            if rec['caps'] and not str(d.get('capacidades_raw') or '').strip():
                d['capacidades_raw'] = rec['caps']
                filled['capacidades_raw'] += 1
            if rec['notas_jug'] and not str(d.get('notas_jugadores') or '').strip():
                d['notas_jugadores'] = rec['notas_jug']
                filled['notas_jugadores'] += 1
            if rec['notas_staff'] and not str(d.get('notas_staff') or '').strip():
                d['notas_staff'] = rec['notas_staff']
                filled['notas_staff'] += 1
        json.dump(data, open(JSON_PATH, 'w', encoding='utf-8'),
                  ensure_ascii=False, indent=2)
        print('\n[WRITE] JSON actualizado:', filled)
    else:
        print('\n(dry-run; usa --write para escribir)')

    # muestra un ejemplo de caps limpias
    ej = by_name.get(norm_name('Aro Aro no Mi'))
    if ej:
        print('\n----- ejemplo capacidades_raw (Aro Aro) -----')
        print(ej['caps'][:900])


if __name__ == '__main__':
    main()
