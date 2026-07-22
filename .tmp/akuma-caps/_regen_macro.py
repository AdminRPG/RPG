# -*- coding: utf-8 -*-
import json, re

CAT = r'inc/ope_rol/catalogos/akuma_no_mi_db.json'
MACRO = r'C:\Users\Fgonz\Documents\Proyectos\I-Forge-Sistema\docs\02-HAKI-Y-FRUTAS\MACRO-CATALOGO-AKUMA-NO-MI.md'

cat = {c['slug']: c for c in json.load(open(CAT, encoding='utf-8'))}

def fmt_caps(raw):
    out = []
    for ln in raw.split('\n'):
        s = ln.rstrip()
        if not s.strip():
            out.append('')
            continue
        mH = re.match(r'^(Nv\.\d\s*[—-].+)$', s.strip())
        if mH:
            out.append('### ' + mH.group(1).strip())
            continue
        mC = re.match(r'^-\s*(CAP-0\d)\s+(.+?)\s*\(([^)]+)\)\s*:\s*(.+)$', s.strip())
        if mC:
            out.append('- **%s %s** _(%s)_: %s' % (mC.group(1), mC.group(2), mC.group(3), mC.group(4)))
            continue
        mP = re.match(r'^-\s*Pasiva\s*:\s*(.+)$', s.strip())
        if mP:
            out.append('- **Pasiva:** ' + mP.group(1))
            continue
        out.append(s)
    # colapsa lineas en blanco multiples
    txt = '\n'.join(out)
    txt = re.sub(r'\n{3,}', '\n\n', txt).strip()
    return txt

txt = open(MACRO, encoding='utf-8').read()
segments = txt.split('\n---\n')
id_re = re.compile(r'- \*\*ID:\*\*\s*`([^`]+)`')
cap_re = re.compile(r'## Capacidades por nivel')

n = 0
for i, seg in enumerate(segments):
    m = id_re.search(seg)
    if not m:
        continue
    slug = m.group(1).strip()
    data = cat.get(slug)
    if not data:
        continue
    cm = cap_re.search(seg)
    if not cm:
        continue
    head = seg[:cm.start()].rstrip()
    caps = fmt_caps(data.get('capacidades_raw', ''))
    nj = data.get('notas_jugadores', '').strip()
    ns = data.get('notas_staff', '').strip()
    rebuilt = (head + '\n\n## Capacidades por nivel\n\n' + caps +
               '\n\n## Notas de diseño de cartas (para jugadores)\n' + nj +
               '\n\n## Notas staff\n' + ns + '\n')
    segments[i] = rebuilt
    n += 1

open(MACRO, 'w', encoding='utf-8').write('\n---\n'.join(segments))
print('Bloques de fruta regenerados:', n)
