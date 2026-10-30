from importlib.machinery import SourceFileLoader
import pathlib,subprocess,json,shutil,statistics
r=SourceFileLoader('runner','/tmp/sillbeam-final-runner.py').load_module()
r.run('performance-current-phases',['php','tools/benchmark-architecture.php','--phases'])
helper=pathlib.Path('/tmp/sillbeam-final-benchmark-fixture.php')
helper.write_text((r.ROOT/'docs/development/release-0.3.0-acceptance/benchmark-fixture-helper.php.txt').read_text())
fixtures={};rows=[]
try:
 for variant in ['symfony','sillbeam']:
  f=json.loads(subprocess.check_output(['php',str(helper),str(r.ROOT),variant],cwd=r.ROOT));fixtures[variant]=f
  r.run('performance-current-'+variant+'-build',['php','tools/benchmark-architecture.php','--sample',f['directory'],f['namespace'],'prod','build','0','100'])
 for repeat in range(3):
  for variant in (['symfony','sillbeam'] if repeat%2==0 else ['sillbeam','symfony']):
   f=fixtures[variant];result=r.run(f'performance-current-{variant}-http-{repeat}',['php','tools/benchmark-architecture.php','--sample',f['directory'],f['namespace'],'prod','http','200','100'])
   if result['exit_code']==0:
    data=json.loads((r.EVIDENCE/result['log']).read_text()); rows.append({'variant':variant,'repeat':repeat,'result':data})
 (r.EVIDENCE/'performance-current.json').write_text(json.dumps({'note':'Current-source measurements only. No claim of change versus historical measurements; fresh processes, alternating current Symfony/Sillbeam fixtures. First request excluded from warm statistics.','modules':100,'repeats':3,'requests_per_batch':200,'rows':rows,'warm_median_ms':{v:statistics.median([t for row in rows if row['variant']==v for t in row['result']['ms'][1:]]) for v in fixtures}},indent=2)+'\n')
finally:
 for f in fixtures.values():shutil.rmtree(f['directory'])
