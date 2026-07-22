# -*- coding: utf-8 -*-
import json, re, glob, sys

CAT = r'inc/ope_rol/catalogos/akuma_no_mi_db.json'

merged = {}
dupes = []
for f in sorted(glob.glob('.tmp/akuma-caps/out-*.json')):
    data = json.load(open(f, encoding='utf-8'))
    for k, v in data.items():
        if k in merged:
            dupes.append(k)
        merged[k] = v

print('Frutas fusionadas:', len(merged), '| duplicadas:', dupes)

LEVEL_HEADERS = ['Nv.0 — Manifestación', 'Nv.1 — Control', 'Nv.2 — Maestría', 'Nv.3 — Despertar']

def validate(slug, v):
    errs = []
    cr = v.get('capacidades_raw', '')
    for h in LEVEL_HEADERS:
        if h not in cr:
            errs.append('falta encabezado %r' % h)
    caps = re.findall(r'CAP-0[1-8]\b', cr)
    caps_unique = sorted(set(caps))
    if len(caps_unique) != 8:
        errs.append('CAPs unicas=%d (%s)' % (len(caps_unique), ','.join(caps_unique)))
    pas = len(re.findall(r'(?m)^-\s*Pasiva\s*:', cr))
    if pas < 4:
        errs.append('pasivas=%d' % pas)
    # al menos una formula con Pot y con algun stat
    if 'Pot' not in cr:
        errs.append('sin Pot en formulas')
    stats = re.findall(r'\b(FUE|AGI|RES|INT|PER|VOL|CAR|TEM)\b', cr)
    if len(stats) < 4:
        errs.append('pocas referencias a stats (%d)' % len(stats))
    if '**' in cr or '`' in cr:
        errs.append('markdown prohibido (** o `)')
    # registro Eternal: cada CAP debe llevar etiqueta de tipo entre parentesis
    tipos = re.findall(r'\((Propiedad|Pasiva mec[aá]nica|Habilitador[^)]*|Mini-sistema[^)]*)\)', cr)
    if len(tipos) < 8:
        errs.append('etiquetas de tipo=%d (<8)' % len(tipos))
    # debe haber al menos un habilitador con "Puedes crear cartas"
    if 'Puedes crear cartas' not in cr:
        errs.append('sin habilitador "Puedes crear cartas"')
    for key in ('notas_jugadores', 'notas_staff'):
        if not v.get(key, '').strip():
            errs.append('falta %s' % key)
    L = len(cr)
    return errs, L

# Cargar catalogo y cruzar por slug
cat = json.load(open(CAT, encoding='utf-8'))
cat_slugs = {c['slug'] for c in cat}
gen_slugs = set(merged.keys())

missing_in_gen = cat_slugs - gen_slugs
extra_in_gen = gen_slugs - cat_slugs
print('En catalogo no generadas:', sorted(missing_in_gen))
print('Generadas no en catalogo:', sorted(extra_in_gen))

all_errs = {}
lengths = []
for slug, v in merged.items():
    if slug not in cat_slugs:
        continue
    errs, L = validate(slug, v)
    lengths.append(L)
    if errs:
        all_errs[slug] = errs

if all_errs:
    print('\n=== ERRORES DE VALIDACION (%d frutas) ===' % len(all_errs))
    for s, e in list(all_errs.items())[:40]:
        print(s, '->', '; '.join(e))
else:
    print('\nVALIDACION OK: todas las frutas cumplen formato.')

if lengths:
    print('\nLongitud capacidades_raw: min=%d max=%d avg=%d' % (min(lengths), max(lengths), sum(lengths)//len(lengths)))

apply = '--write' in sys.argv
if apply and not missing_in_gen and not all_errs:
    n = 0
    for c in cat:
        v = merged.get(c['slug'])
        if v:
            c['capacidades_raw'] = v['capacidades_raw']
            c['notas_jugadores'] = v['notas_jugadores']
            c['notas_staff'] = v['notas_staff']
            n += 1
    json.dump(cat, open(CAT, 'w', encoding='utf-8'), ensure_ascii=False, indent=2)
    print('\nESCRITO: %d frutas actualizadas en %s' % (n, CAT))
elif apply:
    print('\nNO ESCRITO: hay errores o frutas faltantes. Corrige primero.')
