from pathlib import Path
import os,sys,json,subprocess,time,hashlib,shutil,threading,traceback,re
import http.server as http_server
SCRIPT_ROOT=Path(__file__).resolve().parent
ROOT=SCRIPT_ROOT if (SCRIPT_ROOT/'environment.json').is_file() else Path(Path('/tmp/sillbeam-acceptance-root').read_text().strip())
LOG=ROOT/'logs'; EVID=ROOT/'evidence'; RESULTS=ROOT/'results'; RESULTS.mkdir(exist_ok=True)
ROUND=sys.argv[3] if len(sys.argv)>3 else ''
profile=(sys.argv[1]+'-'+sys.argv[2] if len(sys.argv)>2 else sys.argv[1])+('-'+ROUND if ROUND else '')
records=[]
def write(p,s):
 p=Path(p);p.parent.mkdir(parents=True,exist_ok=True);p.write_text(s)
def run(label,args,cwd=None,timeout=360,env=None):
 prefix=profile+'-'+label
 log=LOG/(prefix+'.log');start=time.time()
 try:
  proc=subprocess.run([str(a) for a in args],cwd=cwd,stdout=subprocess.PIPE,stderr=subprocess.STDOUT,text=True,timeout=timeout,env={**os.environ,'COMPOSER_NO_INTERACTION':'1','COMPOSER_PROCESS_TIMEOUT':'300','SHELL_VERBOSITY':'0',**(env or {})})
  code,text=proc.returncode,proc.stdout
 except subprocess.TimeoutExpired as e:code,text=124,str(e)
 log.write_text(text)
 entry={'profile':profile,'label':label,'command':[str(a) for a in args],'cwd':str(cwd or ROOT),'exit':code,'seconds':round(time.time()-start,2),'log':str(log.relative_to(ROOT))}
 with (ROOT/'commands.jsonl').open('a') as f:f.write(json.dumps(entry)+'\n')
 print(prefix,code,flush=True)
 return code,text

def note(label,passed,detail=''):
 records.append({'check':label,'passed':bool(passed),'detail':detail})
 (RESULTS/(profile+'.json')).write_text(json.dumps(records,indent=2))
 print('CHECK',profile,label,'PASS' if passed else 'OBSERVED-FAIL',detail,flush=True)

def snap(app):
 result={}
 for p in app.rglob('*'):
  if p.is_symlink():result[str(p.relative_to(app))]={'link':os.readlink(p)}
  elif p.is_file():
   st=p.stat();result[str(p.relative_to(app))]={'sha256':hashlib.sha256(p.read_bytes()).hexdigest(),'mode':st.st_mode&0o777,'size':st.st_size,'mtime_ns':st.st_mtime_ns}
 return result

def doctor(app,label,runtime=None):
 before=snap(app)
 code,text=cli(app,label,['sillbeam:doctor','--no-ansi'],runtime)
 after=snap(app)
 changes={k:{'before':before.get(k),'after':after.get(k)} for k in before.keys()|after.keys() if before.get(k)!=after.get(k)}
 write(EVID/(profile+'-'+label+'-changes.json'),json.dumps(changes,indent=2))
 note(label+'-read-only',not changes,f'{len(before)} files, {len(changes)} changes; exit {code}')
 return code,text

def cli(app,label,args,runtime=None):
 prefix=['php'] if not runtime else ['docker','run','--rm','--pull=never','--network=none','--user',f'{os.getuid()}:{os.getgid()}','-v',f'{ROOT}:{ROOT}','-w',str(app),'--entrypoint','php','dwell-php-matrix:8.4']
 return run(label,prefix+['bin/console']+args,cwd=app)

def http(app,label,path='/legacy',runtime=None):
 warm_code,warm_text=cli(app,label+'-warmup',['cache:clear','--env=prod','--no-debug','--no-ansi'],runtime)
 note(label+'-production-build',warm_code==0,warm_text[-700:] if warm_code else '')
 prefix=['php'] if not runtime else ['docker','run','--rm','--pull=never','--network=none','--user',f'{os.getuid()}:{os.getgid()}','-v',f'{ROOT}:{ROOT}','-w',str(app),'--entrypoint','php','dwell-php-matrix:8.4']
 code,text=run(label,prefix+[str(ROOT/'probes/http.php'),str(app),path],cwd=app)
 match=re.search(r'ACCEPTANCE_HTTP=(\{[^\n]+\})',text)
 data=json.loads(match[1]) if match else {'error':text[-1500:]}
 return code,data

def adapter(app):
 p=app/'bin/console';s=p.read_text();old='use Symfony\\Bundle\\FrameworkBundle\\Console\\Application;'
 if old not in s:raise RuntimeError('Unexpected console bootstrap; do not guess replacement')
 shutil.copy2(p,EVID/(profile+'-'+app.name+'-console-before.php'))
 p.write_text(s.replace(old,'use Sillbeam\\Core\\Console\\Application;',1))
 write(EVID/(profile+'-'+app.name+'-console-after.php'),p.read_text())

def safe_services(app):
 p=app/'config/services.yaml';s=p.read_text()
 # Manual integration preserves the rest of the app configuration and comments.
 if 'resource:' not in s:return
 if re.search(r'^\s*exclude:',s,re.M):
  # Only the known skeleton exclusion block is replaced in fresh fixtures.
  s=re.sub(r"(?m)^([ ]*)exclude:[^\n]*(?:\n\1[ ]+-[^\n]*)*",lambda m:m[1]+"exclude: ['../src/DependencyInjection/', '../src/Entity/', '../src/Kernel.php', '../src/Module/', '../src/Project/']",s)
 else:s=s.replace("resource: '../src/'","resource: '../src/'\n        exclude: ['../src/Kernel.php', '../src/Module/', '../src/Project/']")
 p.write_text(s)

def module(app,runtime=None):
 code,text=cli(app,'A-create-module',['sillbeam:make:module','Billing','--no-ansi'],runtime)
 note('A-module-command',code==0,text[-500:])
 if not (app/'src/Module/Billing/BillingModule.php').exists():return False
 write(app/'src/Module/Billing/Greeting.php','<?php namespace App\\Module\\Billing; final class Greeting { public function text(): string { return "module-ok"; } }\n')
 write(app/'src/Module/Billing/Controller/BillingController.php','<?php namespace App\\Module\\Billing\\Controller; use App\\Module\\Billing\\Greeting; use Symfony\\Component\\HttpFoundation\\Response; use Symfony\\Component\\Routing\\Attribute\\Route; final class BillingController { public function __construct(private Greeting $greeting) {} #[Route("/billing", name: "billing")] public function __invoke(): Response { return new Response($this->greeting->text()); } }\n')
 return True

def endpoint(line):
 directory=ROOT/('endpoint-'+line.replace('.','')+('-'+ROUND if ROUND else ''));directory.mkdir(exist_ok=True)
 class Handler(http_server.SimpleHTTPRequestHandler):
  def __init__(self,*a,**kw):super().__init__(*a,directory=str(directory),**kw)
  def log_message(self,*a):pass
 server=http_server.ThreadingHTTPServer(('127.0.0.1',0),Handler)
 base='http://127.0.0.1:'+str(server.server_port)
 recipe=ROOT/'source/flex-recipe/websign-dev/sillbeam-core/0.2'
 manifest=json.loads((recipe/'manifest.json').read_text());manifest['post-install-output']=(recipe/'post-install.txt').read_text().rstrip('\n').split('\n')
 files={str(p.relative_to(recipe)):{'contents':p.read_text().split('\n'),'executable':False} for p in (recipe/'config').rglob('*') if p.is_file()}
 write(directory/'websign-dev.sillbeam-core.0.2.json',json.dumps({'manifests':{'websign-dev/sillbeam-core':{'manifest':manifest,'files':files,'ref':hashlib.sha1(json.dumps(manifest).encode()).hexdigest()}}},indent=2))
 write(directory/'index.json',json.dumps({'recipes':{'websign-dev/sillbeam-core':['0.2']},'branch':'main','is_contrib':True,'_links':{'repository':'local/acceptance','origin_template':'{package}:{version}@local/acceptance:main','recipe_template':base+'/{package_dotted}.{version}.json'}},indent=2))
 threading.Thread(target=server.serve_forever,daemon=True).start()
 return server,base+'/index.json'

def setup():
 shutil.copytree(ROOT/'source/src',ROOT/'package/src')
 shutil.copytree(ROOT/'source/docs',ROOT/'package/docs')
 for name in ['composer.json','README.md','LICENSE','SECURITY.md','CHANGELOG.md']:
  shutil.copy2(ROOT/'source'/name,ROOT/'package'/name)
 write(ROOT/'probes/http.php',r'''<?php
require $argv[1].'/vendor/autoload.php';
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'prod';
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '0';
(new Symfony\Component\Dotenv\Dotenv())->bootEnv($argv[1].'/.env', 'prod');
$kernel = new App\Kernel('prod', false);
$request = Symfony\Component\HttpFoundation\Request::create($argv[2]);
try {
    $response = $kernel->handle($request);
    echo 'ACCEPTANCE_HTTP='.json_encode(['status'=>$response->getStatusCode(),'body'=>$response->getContent(),'content_type'=>$response->headers->get('content-type')], JSON_THROW_ON_ERROR)."\n";
    $kernel->terminate($request, $response);
} finally { $kernel->shutdown(); }
''')
 shutil.copy2('/tmp/sillbeam-acceptance.py',ROOT/'acceptance.py')
 print(ROOT,flush=True)

def configure(app,line,local=None,core=False):
 p=app/'composer.json';d=json.loads(p.read_text())
 d['repositories']=[{'type':'path','url':str(ROOT/'package'),'options':{'symlink':False,'versions':{'websign-dev/sillbeam-core':'0.2.0'}}}]
 d.setdefault('config',{}).setdefault('allow-plugins',{}).update({'symfony/flex':True,'symfony/runtime':True})
 d['config']['secure-http']=False
 d.setdefault('extra',{}).setdefault('symfony',{}).update({'require':line+'.*','allow-contrib':True})
 if local:d['extra']['symfony']['endpoint']=[local,'flex://defaults']
 if core:d['require']['websign-dev/sillbeam-core']='0.2.0'
 write(p,json.dumps(d,indent=4)+'\n')

def fresh(name,line,local=None,core=False):
 name=name+('-'+ROUND if ROUND else '')
 app=ROOT/'consumers'/name
 code,text=run(name+'-create-project',['composer','create-project','symfony/skeleton:'+line+'.*',str(app),'--no-install','--no-scripts','--no-plugins','--no-interaction','--prefer-dist'])
 note(name+'-skeleton-created',code==0,text[-300:])
 if code:return None
 configure(app,line,local,core)
 code,text=run(name+'-composer-install',['composer','install','--no-interaction','--prefer-dist','--no-progress'],app,timeout=600)
 note(name+'-installed',code==0,text[-600:])
 if code:return None
 return app,text

def native_controller(app):
 write(app/'src/Controller/LegacyController.php',r'''<?php
namespace App\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
final class LegacyController {
    #[Route('/legacy', name: 'legacy')]
    public function __invoke(): Response { return new Response('legacy-ok', 200, ['Content-Type'=>'text/plain']); }
}
''')

def manual_registration(app):
 bundle=app/'config/bundles.php';s=bundle.read_text()
 if 'Sillbeam\\Core\\SillbeamCoreBundle' not in s:s=s.replace('return [','return [\n    Sillbeam\\Core\\SillbeamCoreBundle::class => [\'all\' => true],',1);bundle.write_text(s)
 write(app/'config/routes/sillbeam.yaml','sillbeam:\n    resource: .\n    type: sillbeam\n')

def architecture_suite(app,prefix,runtime=None):
 for label,args in [('architecture',['sillbeam:architecture','--no-ansi']),('json',['sillbeam:architecture','--format=json']),('report',['sillbeam:report','--output=acceptance-report.md','--no-ansi']),('help',['sillbeam:doctor','--help'])]:
  code,text=cli(app,prefix+'-'+label,args,runtime)
  note(prefix+'-'+label,code==0,text[-250:] if code else '')
  if label=='json' and code==0:
   try:d=json.loads(text);note(prefix+'-json-valid',d.get('valid') is True and not d.get('findings'))
   except Exception:note(prefix+'-json-valid',False,text[-500:])


def host(line):
 server,url=endpoint(line)
 try:
  # A: true new Symfony skeleton + native recipes + local Sillbeam recipe.
  result=fresh('sf'+line.replace('.','')+'-A',line,url,True)
  if not result:return
  a,install_output=result
  instructions=(ROOT/'source/flex-recipe/websign-dev/sillbeam-core/0.2/post-install.txt').read_text().splitlines()
  note('A-real-instructions',all(s in install_output for s in instructions if s.strip()))
  lock=json.loads((a/'symfony.lock').read_text());recipe=lock.get('websign-dev/sillbeam-core',{})
  note('A-recipe-ownership',recipe.get('recipe',{}).get('version')=='0.2' and recipe.get('files')==['config/routes/sillbeam.yaml'],json.dumps(recipe))
  note('A-no-empty-config',not (a/'config/packages/sillbeam.yaml').exists())
  run('A-original-console',['cat','bin/console'],a);run('A-original-services',['cat','config/services.yaml'],a)
  doctor(a,'A-before-adapter')
  adapter(a)
  code,text=doctor(a,'A-after-import-only-adapter')
  note('A-adapter-static-recognition', 'Console adapter: verified' in text,text[-1800:])
  safe_services(a)
  module(a)
  architecture_suite(a,'A')
  code,data=http(a,'A-module-http','/billing');note('A-module-http',code==0 and data.get('status')==200 and data.get('body')=='module-ok',str(data))
  # D: declared dependency on nonexistent module, then consumer-only repair.
  descriptor=a/'src/Module/Billing/BillingModule.php';good=descriptor.read_text()
  descriptor.write_text(good.replace('#[SillbeamModule]', '#[SillbeamModule(dependencies: [\\App\\Module\\Missing\\MissingModule::class])]'))
  note('D-violation-written',descriptor.read_text()!=good)
  code,text=cli(a,'D-invalid-architecture',['sillbeam:architecture','--format=json'])
  note('D-invalid-exit',code==1,text[-1300:])
  try:d=json.loads(text);note('D-invalid-rule',any(f.get('rule')=='dependency.unknown' for f in d.get('findings',[])),str(d.get('findings',[])))
  except Exception:note('D-invalid-rule',False,text[-800:])
  doctor(a,'D-doctor-invalid')
  code,text=cli(a,'D-invalid-report',['sillbeam:report','--output=invalid-report.md','--no-ansi']);note('D-invalid-report',code==1 and (a/'invalid-report.md').exists(),text[-500:])
  descriptor.write_text(good)
  code,text=cli(a,'D-repaired',['sillbeam:architecture','--format=json']);note('D-repaired',code==0,text[-300:])
  doctor(a,'D-doctor-repaired')
  # Capture repaired app for PHP 8.4 independent offline CLI/HTTP verification.
  clone=ROOT/'matrix'/('php84-sf'+line.replace('.','')+('-'+ROUND if ROUND else ''));shutil.copytree(a,clone)
  if (clone/'var').exists():shutil.rmtree(clone/'var')
  # Production HTTP activation and native behavior.
  native_controller(a)
  write(a/'config/packages/sillbeam.yaml','sillbeam:\n    modules:\n        billing: { enabled: false }\n')
  if (a/'var/cache').exists():shutil.rmtree(a/'var/cache')
  code,data=http(a,'A-disabled-module-http','/billing');note('A-disabled-module-http',code==0 and data.get('status')==404,str(data))
  code,data=http(a,'A-native-http','/legacy');note('A-native-http',code==0 and data.get('body')=='legacy-ok',str(data))
  manual_files={str(p.relative_to(a)):p.read_bytes() for p in [a/'config/services.yaml',a/'bin/console',a/'config/packages/sillbeam.yaml',a/'src/Controller/LegacyController.php']}
  code,text=run('A-uninstall',['composer','remove','websign-dev/sillbeam-core','--no-scripts','--no-interaction','--no-progress'],a,timeout=600)
  note('A-uninstall',code==0 and not (a/'config/routes/sillbeam.yaml').exists(),text[-500:])
  note('A-uninstall-preserves-manual',all((a/p).read_bytes()==v for p,v in manual_files.items()))
  note('A-uninstall-clears-lock','websign-dev/sillbeam-core' not in json.loads((a/'symfony.lock').read_text()))
  p=a/'bin/console';p.write_text(p.read_text().replace('use Sillbeam\\Core\\Console\\Application;','use Symfony\\Bundle\\FrameworkBundle\\Console\\Application;'))
  (a/'config/packages/sillbeam.yaml').rename(a/'config/sillbeam-after-uninstall.yaml.saved')
  if (a/'var/cache').exists():shutil.rmtree(a/'var/cache')
  code,data=http(a,'A-native-http-after-uninstall','/legacy');note('A-native-http-after-uninstall',code==0 and data.get('body')=='legacy-ok',str(data))
  # B/C: native skeleton first; install package without local recipe.
  result=fresh('sf'+line.replace('.','')+'-BC',line)
  if not result:return
  b,_=result;native_controller(b)
  services=b/'config/services.yaml'
  write(services,"# Existing application services must remain intact.\nparameters:\n    acceptance_marker: preserved\nservices:\n    _defaults: { autowire: true, autoconfigure: true }\n    App\\:\n        resource: '../src/'\n        exclude: ['../src/Kernel.php']\n")
  code,baseline=http(b,'C-http-baseline','/legacy');note('C-http-baseline',code==0 and baseline.get('body')=='legacy-ok',str(baseline))
  before=services.read_bytes();console_before=(b/'bin/console').read_bytes()
  code,text=run('BC-install-core-no-recipe',['composer','require','websign-dev/sillbeam-core:0.2.0','--no-interaction','--no-progress'],b,timeout=600)
  note('BC-install-core',code==0,text[-400:])
  note('C-services-unchanged-at-install',before==services.read_bytes())
  note('C-console-unchanged-at-install',console_before==(b/'bin/console').read_bytes())
  note('B-no-public-route-recipe',not (b/'config/routes/sillbeam.yaml').exists())
  manual_registration(b)
  code,text=doctor(b,'C-missing-adapter-and-overlap')
  note('C-overlap-recognized','bypass activation' in text or 'Service imports: action required' in text,text[-2000:])
  note('C-missing-adapter-recognized','Console adapter: action required' in text,text[-1500:])
  adapter(b);safe_services(b)
  code,text=doctor(b,'BC-after-manual-correction')
  note('BC-doctor-fully-verified',code==0,text[-2000:])
  architecture_suite(b,'B')
  if (b/'var/cache').exists():shutil.rmtree(b/'var/cache')
  code,after=http(b,'C-http-after-core','/legacy');note('C-production-http-unchanged',code==0 and after==baseline,str({'before':baseline,'after':after}))
  # Literal direct entry is a diagnostic control, not a proposed bootstrap repair.
  original=(b/'bin/console').read_text()
  write(b/'bin/console',"<?php\nrequire \\dirname(__DIR__).'/vendor/autoload.php';\n$kernel = new \\App\\Kernel('dev', true);\n$app = new \\Symfony\\Bundle\\FrameworkBundle\\Console\\Application($kernel);\n$app->run();\n")
  code,text=doctor(b,'C-direct-entry-missing-adapter-control')
  note('C-direct-entry-detects-missing-adapter','Console adapter: action required' in text,text[-1000:])
  (b/'bin/console').write_text(original)
  # Explicitly nonexistent discovery root: compare doctor vs architecture evidence.
  config=b/'config/packages/sillbeam.yaml'
  write(config,"sillbeam:\n    discovery:\n        root: '%kernel.project_dir%/does-not-exist'\n        namespace: 'App\\'\n")
  code,text=doctor(b,'probe-nonexistent-discovery-root')
  note('probe-missing-root-not-verified','Discovery configuration: verified' not in text,text[-1600:])
  code,text=cli(b,'probe-missing-root-architecture',['sillbeam:architecture','--format=json'])
  config.unlink()
  # Documentation's valid brace exclusions: should remain explicitly unverified, not guessed.
  saved=services.read_text();services.write_text(saved.replace("['../src/DependencyInjection/', '../src/Entity/', '../src/Kernel.php', '../src/Module/', '../src/Project/']","'../src/{DependencyInjection,Entity,Kernel.php,Module,Project}'"))
  code,text=doctor(b,'probe-documentation-brace-excludes')
  note('probe-brace-excludes-not-guessed','Service imports: not verified' in text,text[-1200:])
  services.write_text(saved)
  write(EVID/(profile+'-versions.json'),(b/'composer.lock').read_text())
 finally:server.shutdown()

def matrix(line):
 app=ROOT/'matrix'/('php84-sf'+line.replace('.','')+('-'+ROUND if ROUND else ''))
 if not app.exists():note('matrix-app-exists',False,str(app));return
 run('php-version',['docker','run','--rm','--pull=never','--network=none','--entrypoint','php','dwell-php-matrix:8.4','-v'])
 doctor(app,'matrix-doctor',True)
 architecture_suite(app,'matrix',True)
 code,data=http(app,'matrix-http','/billing',True);note('matrix-module-http',code==0 and data.get('body')=='module-ok',str(data))
 descriptor=app/'src/Module/Billing/BillingModule.php';good=descriptor.read_text();descriptor.write_text(good.replace('#[SillbeamModule]', '#[SillbeamModule(dependencies: [\\App\\Module\\Missing\\MissingModule::class])]'))
 code,text=cli(app,'matrix-invalid-architecture',['sillbeam:architecture','--format=json'],True);note('matrix-invalid-architecture',code==1,text[-500:])
 doctor(app,'matrix-doctor-invalid',True)
 descriptor.write_text(good)
 code,text=cli(app,'matrix-repaired',['sillbeam:architecture','--format=json'],True);note('matrix-repaired',code==0,text[-300:])

if __name__=='__main__':
 try:
  if sys.argv[1]=='setup':setup()
  elif sys.argv[1]=='host':host(sys.argv[2])
  elif sys.argv[1]=='matrix':matrix(sys.argv[2])
  elif sys.argv[1]=='regression':
   code,text=run('full-suite',['php','-d','memory_limit=512M','vendor/bin/phpunit','--configuration=phpunit.xml.dist'],ROOT/'source',timeout=300)
   note('full-suite',code==0,text[-1300:])
 except Exception:
  write(LOG/(profile+'-harness-exception.log'),traceback.format_exc());note('harness-exception',False,traceback.format_exc());raise
