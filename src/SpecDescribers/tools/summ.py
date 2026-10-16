import json,sys
e=json.load(open(sys.argv[1]));a=json.load(open(sys.argv[2]))
print('top-level keys diff:', set(e)^set(a))
for k in set(e)&set(a):
    if k not in ('paths','components') and e[k]!=a[k]: print('top', k, 'differs')
ep,ap=e.get('paths',{}),a.get('paths',{})
print('paths only expected:', sorted(set(ep)-set(ap)))
print('paths only actual:', sorted(set(ap)-set(ep)))
n=0
for p in sorted(set(ep)&set(ap)):
    for m in sorted(set(ep[p])|set(ap[p])):
        eo,ao=ep[p].get(m),ap[p].get(m)
        if eo!=ao:
            n+=1
            if eo is None or ao is None: print(' ',p,m,'only in', 'expected' if ao is None else 'actual'); continue
            ks=[k for k in set(eo)|set(ao) if eo.get(k)!=ao.get(k)]
            print(' ',p,m,'fields:',ks)
print(n,'differing operations')
ec,ac=e.get('components',{}),a.get('components',{})
for b in sorted(set(ec)|set(ac)):
    eb,ab=ec.get(b,{}),ac.get(b,{})
    print('components.'+b, 'only expected:',sorted(set(eb)-set(ab)),'only actual:',sorted(set(ab)-set(eb)),'differ:',sorted(k for k in set(eb)&set(ab) if eb[k]!=ab[k]))
