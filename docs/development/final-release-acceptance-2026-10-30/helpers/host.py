from importlib.machinery import SourceFileLoader
import pathlib,hashlib,json,shutil
r=SourceFileLoader('runner','/tmp/sillbeam-final-runner.py').load_module()
for label,cmd in [('analyse',['composer','analyse']),('architecture',['composer','architecture']),('cs',['composer','cs:check']),('audit',['composer','audit','--locked']),('audit-app',['composer','audit','--locked','--working-dir=apps/test-app/app']),('docs-json-first',['composer','docs:export-json'])]: r.run('host-'+label,cmd)
p=r.ROOT/'build/docs/api/sillbeam-core.en.json'
first=p.read_bytes() if p.exists() else b''
r.run('host-docs-json-second',['composer','docs:export-json'])
second=p.read_bytes() if p.exists() else b''
(r.EVIDENCE/'phpdoc-reproducibility.json').write_text(json.dumps({'identical':first==second,'first_sha256':hashlib.sha256(first).hexdigest(),'second_sha256':hashlib.sha256(second).hexdigest(),'bytes':len(second)},indent=2)+'\n')
r.run('host-docs-html',['composer','docs:build'])
r.run('host-release-package',['composer','test:release-package'])
r.run('host-archive',['composer','archive','--format=zip','--dir='+str(r.EVIDENCE/'artifacts'),'--file=sillbeam-core-0.3.0'])
