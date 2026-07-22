# -*- coding: utf-8 -*-
import json, os
from collections import Counter

d = json.load(open(r'inc/ope_rol/catalogos/akuma_no_mi_db.json', encoding='utf-8'))

def base(t):
    t = t.lower()
    if 'logia' in t: return 'logia'
    if 'zoa' in t: return 'zoa'
    return 'paramecia'

items = sorted(d, key=lambda x: (base(x['tipo']), x['nombre']))
slim = [{'slug': x['slug'], 'nombre': x['nombre'], 'tipo': x['tipo'],
         'tipo_base': base(x['tipo']), 'tier': x['tier'], 'secundario': x['secundario'],
         'desc': x.get('descripcion_breve', ''), 'efecto': x.get('efecto_general', '')}
        for x in items]

N = 6
batches = [[] for _ in range(N)]
for i, it in enumerate(slim):
    batches[i % N].append(it)

os.makedirs('.tmp/akuma-caps', exist_ok=True)
for i, b in enumerate(batches, 1):
    json.dump(b, open('.tmp/akuma-caps/batch-%02d.json' % i, 'w', encoding='utf-8'),
              ensure_ascii=False, indent=1)
    c = dict(Counter(x['tipo_base'] for x in b))
    print('batch-%02d: %d frutas %s' % (i, len(b), c))
print('total', len(slim))
