from pathlib import Path
import subprocess,json,hashlib,os,shutil
root=Path(os.environ.get('DOCTOR_RECHECK_ROOT','/tmp/sillbeam-doctor-recheck-20261025')); results=[]; profile=os.environ.get('DOCTOR_PROFILE','php85'); prefix=os.environ.get('DOCTOR_CONSUMER_PREFIX','runtime-sf')
def snap(app):
 return {str(p.relative_to(app)):dict(type='file' if p.is_file() else 'directory', sha256=hashlib.sha256(p.read_bytes()).hexdigest() if p.is_file() else None, mode=p.stat().st_mode, mtime_ns=p.stat().st_mtime_ns) for p in app.rglob('*')}
def run(app,label,args,code,contains=(),readonly=False):
 before=snap(app) if readonly else None
 command=['php','bin/console',*args,'--no-ansi']
 if profile=='php84':
  command=['docker','run','--rm','--pull=never','--network=none','--user','1000:1000','-v',str(root)+':'+str(root),'-w',str(app),'-e','APP_ENV=dev','-e','APP_DEBUG=1','--entrypoint','php','dwell-php-matrix:8.4',*command[1:]]
 r=subprocess.run(command,cwd=app,env={**os.environ,'APP_ENV':'dev','APP_DEBUG':'1'},capture_output=True,text=True)
 text=r.stdout+r.stderr;(root/'logs'/(profile+'-'+app.name+'-'+label+'.log')).write_text(text)
 changed=[]
 if before is not None:
  after=snap(app);changed=sorted(k for k in before.keys()|after.keys() if before.get(k)!=after.get(k))
 ok=r.returncode==code and all(x in text for x in contains) and not changed and ('--quiet' not in args or text == '')
 results.append(dict(consumer=app.name,label=label,command=command,expected_exit=code,exit=r.returncode,passed=ok,changed_files=changed,readonly=readonly))
 print(app.name,label,r.returncode,ok,flush=True)
 if not ok:print(text,flush=True)
for line in ['74','81']:
 app=root/(prefix+line)
 original=Path('/home/oeffe/PhpstormProjects/websign/test/sillbeam-core-v020-acceptance-20261025-10834dcb/consumers')/('sf'+line+'-BC-r2')
 for file in ['bin/console','config/services.yaml','config/routes.yaml','config/routes/framework.yaml']:
  shutil.copy2(original/file, app/file)
 console=app/'bin/console';console.write_text(console.read_text().replace('use Sillbeam\\Core\\Console\\Application;', 'use Symfony\\Bundle\\FrameworkBundle\\Console\\Application;'))
 route=app/'config/routes/framework.yaml'; overlay=route.read_text()
 (app/'config/routes/sillbeam.yaml').write_text('sillbeam:\n    resource: .\n    type: sillbeam\n')
 services=app/'config/services.yaml'; safe=services.read_text(); broad=safe.replace(", '../src/Module/', '../src/Project/'",'')
 run(app,'missing-adapter',['sillbeam:doctor'],2,['Console adapter: action required','use Sillbeam\\Core\\Console\\Application;'])
 console=app/'bin/console';console.write_text(console.read_text().replace('use Symfony\\Bundle\\FrameworkBundle\\Console\\Application;', 'use Sillbeam\\Core\\Console\\Application;'))
 run(app,'stock-runtime-correct-adapter',['sillbeam:doctor'],2,['Console adapter: verified','Configuration loading convention: verified','Discovery configuration: verified','Service imports: verified'],True)
 (app/'config/routes.yaml').write_text("controllers:\n    resource: ../src/Controller/\n    type: attribute\n")
 services.write_text(broad)
 run(app,'F02-overlay-overlap',['sillbeam:doctor'],2,['Service imports: action required','config/services.yaml: App\\','Route import: not verified','Discovery configuration: verified'],True)
 route.unlink()
 run(app,'F02-without-overlay',['sillbeam:doctor'],1,['Service imports: action required'],True)
 services.write_text(safe)
 run(app,'literal-success',['sillbeam:doctor'],0,['6 verified'],True)
 services.write_text("services:\n    App\\: {resource: '../src/*'}\n")
 run(app,'unknown-services',['sillbeam:doctor'],2,['Service imports: not verified'],True)
 services.write_text(safe)
 dynamic=app/'config/packages/dynamic.php';dynamic.write_text('<?php throw new \\LogicException("Doctor must not execute this file");')
 run(app,'unknown-discovery',['sillbeam:doctor'],2,['Discovery configuration: not verified'],True)
 dynamic.unlink()
 module=app/'src/Module/Broken/BrokenModule.php';module.parent.mkdir(parents=True,exist_ok=True)
 module.write_text('<?php namespace App\\Module\\Broken; #[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule(dependencies: [\\Missing\\AbsentModule::class])] final class BrokenModule {}')
 run(app,'architecture-invalid',['sillbeam:architecture'],1)
 run(app,'doctor-invalid-architecture',['sillbeam:doctor'],0,['Console adapter: verified'],True)
 run(app,'doctor-help',['sillbeam:doctor','--help'],0,['No files are written'],True)
 run(app,'doctor-quiet',['sillbeam:doctor','--quiet'],0,(),True)
 run(app,'report-invalid',['sillbeam:report'],1)
 module.write_text('<?php namespace App\\Module\\Broken; #[\\Sillbeam\\Core\\Module\\Attribute\\SillbeamModule] final class BrokenModule {}')
 run(app,'architecture-repaired',['sillbeam:architecture'],0)
 run(app,'doctor-repaired',['sillbeam:doctor'],0,['6 verified'],True)
 lock=json.loads((app/'composer.lock').read_text());(root/(app.name+'-versions.json')).write_text(json.dumps({p['name']:p['version'] for p in lock['packages']},indent=2))
(root/(profile+'-results.json')).write_text(json.dumps(results,indent=2)+'\n')
if not all(r['passed'] for r in results):raise SystemExit(1)
