from importlib.machinery import SourceFileLoader
import json
r=SourceFileLoader('runner','/tmp/sillbeam-final-runner.py').load_module()
rows=[]
for n in [10,50,100]:
 for env in ['dev','prod']:
  result=r.run(f'performance-phase-{n}-{env}',['php','tools/benchmark-architecture.php','--phase-worker',str(n),env])
  if result['exit_code']==0:rows.append(json.loads((r.EVIDENCE/result['log']).read_text()))
(r.EVIDENCE/'performance-phases.json').write_text(json.dumps(rows,indent=2)+'\n')
