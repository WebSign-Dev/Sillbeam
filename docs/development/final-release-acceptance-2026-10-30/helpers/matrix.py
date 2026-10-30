import sys, os, pathlib, subprocess, shutil, json, concurrent.futures
from importlib.machinery import SourceFileLoader
r=SourceFileLoader('acceptance_runner','/tmp/sillbeam-final-runner.py').load_module()
base=pathlib.Path('/tmp/sillbeam-final-matrix'); base.mkdir(exist_ok=True)
files=subprocess.check_output(['git','ls-files','-z'],cwd=r.ROOT).decode().split('\0')[:-1]
def worker(php,sf):
 label=f'php{php}-sf{sf}'; wd=base/label; wd.mkdir(exist_ok=True)
 for f in files:
  source=r.ROOT/f; dest=wd/f; dest.parent.mkdir(parents=True,exist_ok=True)
  if source.is_file(): shutil.copy2(source,dest)
 (wd/'.acceptance-ini').mkdir(exist_ok=True); (wd/'.acceptance-ini/memory.ini').write_text('memory_limit=512M\n')
 container='sillbeam-final-'+label
 start=['docker','run','-d','--name',container,'-v',f'{wd}:/workspace','-v','/home/oeffe/.cache/composer:/composer-cache','-w','/workspace','-e','COMPOSER_ROOT_VERSION=dev-dev-v0.3.x','-e',f'SILLBEAM_SYMFONY_VERSION={sf[0]}.{sf[1]}.*','-e','COMPOSER_PROCESS_TIMEOUT=900','-e','COMPOSER_CACHE_DIR=/composer-cache','-e','PHP_INI_SCAN_DIR=/usr/local/etc/php/conf.d:/workspace/.acceptance-ini',f'sillbeam-frame-verify:php{php}','sleep','infinity']
 if r.run(label+'-container',start)['exit_code']: return
 def run(suffix,cmd,env=None,root=False,timeout=1800):
  call=['docker','exec','--user','0:0' if root else '1000:1000']
  for k,v in (env or {}).items(): call+=['-e',k+'='+v]
  return r.run(label+'-'+suffix,call+[container]+cmd,timeout=timeout)
 try:
  run('initial-gpg',['gpg','--version'])
  run('qa-install-gpg',['sh','-c','apt-get update -qq && apt-get install -y -qq --no-install-recommends gnupg'],root=True)
  run('php',['php','-v']); run('gpg',['gpg','--version']); run('composer',['composer','--version'])
  run('prepare',['php','tools/prepare-compatibility.php'])
  if run('resolve',['composer','update','--with-all-dependencies','--no-scripts','--no-interaction','--prefer-dist'],timeout=1800)['exit_code']: return
  lock=json.loads((wd/'composer.lock').read_text())
  (r.EVIDENCE/(label+'-dependencies.json')).write_text(json.dumps({p['name']:p['version'] for p in lock['packages']+lock['packages-dev']},indent=2,sort_keys=True)+'\n')
  checks=[('validate',['composer','validate','--strict']),('platform',['composer','check-platform-reqs']),('audit',['composer','audit','--locked']),('audit-app',['composer','audit','--locked','--working-dir=apps/test-app/app']),('phpunit',['composer','test']),('analyse',['composer','analyse']),('architecture',['composer','architecture']),('cs',['composer','cs:check']),('docs-html',['composer','docs:build']),('docs-json',['composer','docs:export-json'])]
  for suffix,cmd in checks: run(suffix,cmd)
  for suffix,env in [('default',{}),('fresh',{'SILLBEAM_TEST_FRESH':'1'}),('existing-route',{'SILLBEAM_TEST_EXISTING_ROUTE':'1'}),('doctrine',{'SILLBEAM_TEST_DOCTRINE':'1'}),('fresh-doctrine',{'SILLBEAM_TEST_FRESH':'1','SILLBEAM_TEST_DOCTRINE':'1'})]:
   run('flex-'+suffix,['composer','test:flex-recipe'],env)
  run('consumer',['composer','test:consumer'],timeout=3600)
  run('release-package',['composer','test:release-package'])
 finally:
  r.run(label+'-cleanup',['docker','rm','-f',container])
with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
 list(pool.map(lambda args:worker(*args), [('84','74'),('84','81'),('85','74'),('85','81')]))
