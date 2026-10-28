# Entity probe, phase 1: per-property type comparison of types.nelmio.json and types.swagger.json.
# python3 types-compare.py <out-dir> [-v] [--left nelmio|symfony] [--right swagger-php|symfony]
# --left/--right pick the two sides (PR 154 adds symfony, from types-symfony.php); default nelmio | swagger-php
import json, sys, collections
out = sys.argv[1]; verbose = '-v' in sys.argv; suffix = '.migrated' if '--migrated' in sys.argv else ''
def opt(name, default):
    return sys.argv[sys.argv.index(name) + 1] if name in sys.argv else default
LEFT, RIGHT = opt('--left', 'nelmio'), opt('--right', 'swagger-php')
FILES = {'nelmio': 'types.nelmio.json', 'symfony': 'types.symfony.json', 'swagger-php': f'types.swagger{suffix}.json'}
def side(label):
    d = json.load(open(f'{out}/{FILES[label]}'))
    if label == 'swagger-php':
        return {e: {'ref': f'#/components/schemas/{e}', 'schemas': d} for e in d if not e.startswith('__probe_')}
    return d
N = side(LEFT); S = side(RIGHT)
if suffix:
    only = [a for a in sys.argv[2:] if not a.startswith('-')]
    N = {k: v for k, v in N.items() if k in only}
def schema_of(entry):
    return entry['schemas'].get(entry['ref'].rsplit('/', 1)[-1], {})
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
        rows.append((e, '*', f'{LEFT} error', n['error'][:80])); kinds[f'{LEFT} error'] += 1; continue
    if 'error' in S.get(e, {}):
        rows.append((e, '*', f'{RIGHT} error', S[e]['error'][:80])); kinds[f'{RIGHT} error'] += 1; continue
    ns = norm(schema_of(n)); ss = norm(schema_of(S[e]) if e in S else {})
    if not ss:
        rows.append((e, '*', f'no {RIGHT} schema', '')); kinds[f'no {RIGHT} schema'] += 1; continue
    np_, sp = ns.get('properties', {}), ss.get('properties', {})
    rn, rs = schema_of(n).get('properties', {}), schema_of(S[e]).get('properties', {})
    for p in sorted(set(rn) & set(rs)):
        if default_of(rn[p]) != default_of(rs[p]):
            kinds['default differs'] += 1
            defaults.append((e, p, f'{LEFT} {json.dumps(default_of(rn[p]))} | {RIGHT} {json.dumps(default_of(rs[p]))}'))
    for k in ('type', 'enum', 'required', 'additionalProperties', 'oneOf', 'allOf'):
        if ns.get(k) != ss.get(k):
            rows.append((e, f'(schema).{k}', 'differs', f'{LEFT} {json.dumps(ns.get(k))[:70]} | {RIGHT} {json.dumps(ss.get(k))[:70]}'))
            kinds[f'schema {k}'] += 1
    for p in sorted(set(np_) | set(sp)):
        a, b = np_.get(p), sp.get(p)
        if a == b:
            kinds['property matches'] += 1; continue
        kind = f'only {LEFT}' if b is None else f'only {RIGHT}' if a is None else 'differs'
        kinds[f'property {kind}'] += 1
        rows.append((e, p, kind, f'{LEFT} {json.dumps(a)[:90]} | {RIGHT} {json.dumps(b)[:90]}'))
print('summary:', dict(kinds)); print('stripped (not types):', dict(stripped))
if verbose:
    for r in rows: print(' | '.join(r))
    for d in defaults: print(' | '.join(d[:2]) + ' | default | ' + d[2])
