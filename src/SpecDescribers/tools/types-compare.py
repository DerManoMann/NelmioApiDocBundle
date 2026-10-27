# Entity probe, phase 1: per-property type comparison of types.nelmio.json and types.swagger.json.
# python3 types-compare.py <out-dir> [-v]
import json, sys, collections
out = sys.argv[1]; verbose = '-v' in sys.argv; suffix = '.migrated' if '--migrated' in sys.argv else ''
N = json.load(open(f'{out}/types.nelmio.json')); S = json.load(open(f'{out}/types.swagger{suffix}.json'))
if suffix:
    only = [a for a in sys.argv[2:] if not a.startswith('-')]
    N = {k: v for k, v in N.items() if k in only}
NOT_TYPE = {'description', 'title', 'example', 'examples', 'default', 'deprecated', 'readOnly', 'writeOnly',
            'minLength', 'maxLength', 'minimum', 'maximum', 'exclusiveMinimum', 'exclusiveMaximum', 'pattern',
            'minItems', 'maxItems', 'uniqueItems', 'multipleOf', 'x', 'xml'}
stripped = collections.Counter()
defaults = []
# keywords are stripped where they are keywords, not where they are property names (review 1)
def norm(o, keywords=True):
    if isinstance(o, dict):
        r = {}
        for k, v in o.items():
            if keywords and (k in NOT_TYPE or k.startswith('x-')):
                stripped[k] += 1; continue
            if k == '$ref':
                v = v.rsplit('/', 1)[-1]
            r[k] = norm(v, keywords=(k != 'properties'))
        return r
    if isinstance(o, list):
        return [norm(v) for v in o]
    return o
def default_of(schema):
    return (schema or {}).get('default', '<none>')
rows = []; kinds = collections.Counter()
for e, n in sorted(N.items()):
    if 'error' in n:
        rows.append((e, '*', 'nelmio error', n['error'][:80])); kinds['nelmio error'] += 1; continue
    name = n['ref'].rsplit('/', 1)[-1]
    ns = norm(n['schemas'].get(name, {})); ss = norm(S.get(e, {}))
    if not ss:
        rows.append((e, '*', 'no swagger-php schema', '')); kinds['no swagger-php schema'] += 1; continue
    np_, sp = ns.get('properties', {}), ss.get('properties', {})
    rn, rs = n['schemas'].get(name, {}).get('properties', {}), S.get(e, {}).get('properties', {})
    for p in sorted(set(rn) & set(rs)):
        if default_of(rn[p]) != default_of(rs[p]):
            kinds['default differs'] += 1
            defaults.append((e, p, f'nelmio {json.dumps(default_of(rn[p]))} | swagger-php {json.dumps(default_of(rs[p]))}'))
    for k in ('type', 'enum', 'required', 'additionalProperties', 'oneOf', 'allOf'):
        if ns.get(k) != ss.get(k):
            rows.append((e, f'(schema).{k}', 'differs', f'nelmio {json.dumps(ns.get(k))[:70]} | swagger-php {json.dumps(ss.get(k))[:70]}'))
            kinds[f'schema {k}'] += 1
    for p in sorted(set(np_) | set(sp)):
        a, b = np_.get(p), sp.get(p)
        if a == b:
            kinds['property matches'] += 1; continue
        kind = 'only nelmio' if b is None else 'only swagger-php' if a is None else 'differs'
        kinds[f'property {kind}'] += 1
        rows.append((e, p, kind, f'nelmio {json.dumps(a)[:90]} | swagger-php {json.dumps(b)[:90]}'))
print('summary:', dict(kinds)); print('stripped (not types):', dict(stripped))
if verbose:
    for r in rows: print(' | '.join(r))
    for d in defaults: print(' | '.join(d[:2]) + ' | default | ' + d[2])
