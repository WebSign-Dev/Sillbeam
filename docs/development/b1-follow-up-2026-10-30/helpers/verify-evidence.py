from pathlib import Path
import json, hashlib, re, subprocess
root=Path('/home/oeffe/PhpstormProjects/websign/sillbeam-core')
e=root/'docs/development/b1-follow-up-2026-10-30'
labels=['php84-sf74','php84-sf81','php85-sf74','php85-sf81']
variants=['default','fresh','existing-route','doctrine','fresh-doctrine']
checks=['validate','platform','phpunit','analyse','architecture','cs','inspect-module','consumer','release-package']
summary={'flex':{},'regressions':{},'smoke_boots':0,'doctrine_smoke_boots':0,'problems':[]}
for label in labels:
 summary['flex'][label]={}; summary['regressions'][label]={}
 wd=Path('/tmp/sillbeam-b1-final-matrix')/label
 for variant in variants:
  key=label+'-flex-'+variant
  record=json.loads((e/'results'/f'{key}.json').read_text())
  lines=(e/'logs'/f'{key}.log').read_text().splitlines()
  smoke=[s for s in lines if s.startswith('PASS smoke:')]
  expected={
   'zero':sum('config=zero hello=active off=active' in s for s in smoke),
   'symfony-dev':sum('kernel=Symfony\\Component\\HttpKernel\\Kernel env=dev config=configured hello=active off=disabled' in s for s in smoke),
   'symfony-prod':sum('kernel=Symfony\\Component\\HttpKernel\\Kernel env=prod config=configured hello=active off=disabled' in s for s in smoke),
   'sillbeam-dev':sum('kernel=Sillbeam\\Core\\Kernel\\SillbeamKernel env=dev config=configured hello=active off=disabled' in s for s in smoke),
   'sillbeam-prod':sum('kernel=Sillbeam\\Core\\Kernel\\SillbeamKernel env=prod config=configured hello=active off=disabled' in s for s in smoke),
  }
  valid=record['exit_code']==0 and expected=={'zero':1,'symfony-dev':2,'symfony-prod':2,'sillbeam-dev':2,'sillbeam-prod':2} and len(smoke)==9
  valid=valid and all('compact+verbose inspections, Mermaid, HTTP' in s for s in smoke)
  if 'doctrine' in variant: valid=valid and all(', Doctrine mapping' in s for s in smoke)
  valid=valid and any('both consumer kernels and removal passed' in s for s in lines)
  if not valid: summary['problems'].append(key)
  summary['smoke_boots']+=len(smoke)
  summary['doctrine_smoke_boots']+=sum(', Doctrine mapping' in s for s in smoke)
  summary['flex'][label][variant]={'exit_code':record['exit_code'],'verified':valid,'boots':expected,'log':f'logs/{key}.log'}
 for check in checks:
  key=label+'-'+check
  record=json.loads((e/'results'/f'{key}.json').read_text())
  log=(e/'logs'/f'{key}.log').read_text()
  summary['regressions'][label][check]={'exit_code':record['exit_code'],'log':f'logs/{key}.log'}
  if check in ['phpunit','inspect-module']:
   summary['regressions'][label][check]['summary']=re.search(r'OK \([^\n]+',log).group(0).split('\x1b')[0]
  if record['exit_code']!=0: summary['problems'].append(key)
 deps=json.loads((e/f'{label}-dependencies.json').read_text())
 summary['regressions'][label]['symfony']=deps['symfony/framework-bundle']
 summary['regressions'][label]['php']=(e/'logs'/f'{label}-php.log').read_text().splitlines()[0]
 summary['regressions'][label]['dependency_hashes']={name:hashlib.sha256((wd/name).read_bytes()).hexdigest() for name in ['composer.json','composer.lock']}
manifest=json.loads((e/'source-sha256.json').read_text())
changed=[f for f,h in manifest.items() if not (root/f).is_file() or hashlib.sha256((root/f).read_bytes()).hexdigest()!=h]
summary['source_changed_since_snapshot']=changed
summary['historical_evidence_unchanged']=not any(f.startswith('docs/development/final-release-acceptance-2026-10-30/') for f in changed)
summary['matrix_source_differences']={}
for label in labels:
 wd=Path('/tmp/sillbeam-b1-final-matrix')/label
 differences=[f for f,h in manifest.items() if f.startswith(('src/','tools/','tests/')) and (not (wd/f).is_file() or hashlib.sha256((wd/f).read_bytes()).hexdigest()!=h)]
 summary['matrix_source_differences'][label]=differences
 if differences: summary['problems'].append(label+' source differs')
if changed: summary['problems'].append('source changed since snapshot')
summary['git_diff_check']=subprocess.run(['git','diff','--check'],cwd=root,capture_output=True,text=True).returncode
if summary['git_diff_check']: summary['problems'].append('git diff check')
(e/'summary.json').write_text(json.dumps(summary,indent=2)+'\n')
print(json.dumps(summary,indent=2))
raise SystemExit(bool(summary['problems']))
