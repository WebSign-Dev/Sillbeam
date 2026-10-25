from pathlib import Path
import importlib.util,sys,json,shutil,subprocess,os,socket,time,urllib.request,urllib.error,hashlib
local=Path(__file__).resolve().parent/'acceptance.py'
spec=importlib.util.spec_from_file_location('acceptance',str(local if local.exists() else Path('/tmp/sillbeam-acceptance.py')));h=importlib.util.module_from_spec(spec);spec.loader.exec_module(h)
h.profile=sys.argv[1]; R=h.ROOT

def clone(src,dst):
 shutil.copytree(src,dst)
 if (dst/'var').exists():shutil.rmtree(dst/'var')
 return dst

def controlled_doctor():
 app=clone(R/'consumers/sf74-BC-r2',R/'controls/doctor-74')
 # Controlled literal entry, separate from the real Runtime bootstrap scenario.
 h.write(app/'bin/console',"<?php\nrequire \\dirname(__DIR__).'/vendor/autoload.php';\n$kernel = new \\App\\Kernel('dev', true);\n$app = new \\Sillbeam\\Core\\Console\\Application($kernel);\n$app->run();\n")
 (app/'config/routes/framework.yaml').rename(app/'framework-error-routes.saved')
 h.write(app/'config/routes.yaml',"controllers:\n    resource: ../src/Controller/\n    type: attribute\n")
 code,text=h.doctor(app,'literal-supported-success')
 h.note('literal-supported-doctor-exit-0',code==0,text[-1600:])
 services=app/'config/services.yaml';good=services.read_text()
 broad=good.replace(", '../src/Module/', '../src/Project/'",'')
 h.note('control-broad-import-created',broad!=good)
 services.write_text(broad)
 code,text=h.doctor(app,'literal-known-overlap')
 h.note('literal-overlap-exit-1',code==1 and 'Service imports: action required' in text,text[-1800:])
 (app/'framework-error-routes.saved').rename(app/'config/routes/framework.yaml')
 code,text=h.doctor(app,'same-overlap-with-native-routing-overlay')
 h.note('routing-overlay-does-not-hide-known-service-overlap','Service imports: action required' in text,text[-1800:])
 services.write_text(good)
 # Preserve precise fixture variants so findings remain reproducible.
 h.write(R/'evidence/doctor-control-broad-services.yaml',broad)
 h.write(R/'evidence/doctor-control-correct-services.yaml',good)


def real_http(line):
 names=[('native-after-uninstall',R/'consumers'/('sf'+line.replace('.','')+'-A-r2')),('core-installed',R/'consumers'/('sf'+line.replace('.','')+'-BC-r2'))]
 outputs={}
 for name,app in names:
  code,text=h.cli(app,'real-http-'+line+'-'+name+'-warmup',['cache:clear','--env=prod','--no-debug','--no-ansi'])
  h.note('real-http-'+line+'-'+name+'-built',code==0,text[-700:] if code else '')
  sock=socket.socket();sock.bind(('127.0.0.1',0));port=sock.getsockname()[1];sock.close()
  log=R/'logs'/('actual-http-'+h.profile+'-'+line+'-'+name+'-server.log')
  with log.open('w') as out:
   args=['php','-d','variables_order=EGPCS','-d','display_errors=0','-S',f'127.0.0.1:{port}','-t',str(app/'public'),str(app/'public/index.php')]
   proc=subprocess.Popen(args,cwd=app,stdout=out,stderr=out,env={**os.environ,'APP_ENV':'prod','APP_DEBUG':'0','SHELL_VERBOSITY':'0'})
   try:
    for _ in range(80):
     try:
      with socket.create_connection(('127.0.0.1',port),timeout=.2):break
     except OSError:time.sleep(.05)
    before=h.snap(app)
    data={}
    for path in ['/legacy','/not-a-route']:
     try:response=urllib.request.urlopen(f'http://127.0.0.1:{port}'+path,timeout=20)
     except urllib.error.HTTPError as e:response=e
     data[path]={'status':response.status,'body':response.read().decode(errors='replace'),'content_type':response.headers.get('Content-Type')}
    after=h.snap(app)
    changes={k for k in before.keys()|after.keys() if before.get(k)!=after.get(k)}
    nonvar=[k for k in changes if not k.startswith('var/')]
    h.note('real-http-'+line+'-'+name+'-no-source-config-writes',not nonvar,str(nonvar))
    h.note('real-http-'+line+'-'+name+'-native-response',data['/legacy']['status']==200 and data['/legacy']['body']=='legacy-ok',str(data['/legacy']))
    body=data['/not-a-route']['body'];h.note('real-http-'+line+'-'+name+'-production-errors',data['/not-a-route']['status']==404 and str(R) not in body and 'Stack trace' not in body,body[:120])
    outputs[name]=data
   finally:proc.terminate();proc.wait(timeout=10)
  h.write(R/'evidence'/('actual-http-'+h.profile+'-'+line+'-'+name+'.json'),json.dumps({'command':args,'env':{'APP_ENV':'prod','APP_DEBUG':'0'},'responses':data,'changed_files':sorted(changes)},indent=2))
 h.note('real-http-'+line+'-native-equivalent',outputs['native-after-uninstall']['/legacy']==outputs['core-installed']['/legacy'])


def doctrine():
 app=clone(R/'consumers/sf74-BC-r2',R/'controls/doctrine-74')
 code,text=h.run('install-doctrine',['composer','require','doctrine/doctrine-bundle:^3.3','doctrine/orm:^3.6','--no-scripts','--no-interaction','--no-progress'],app,timeout=600)
 h.note('doctrine-installed',code==0,text[-900:])
 if code:return
 (app/'src/Entity').mkdir(exist_ok=True)
 h.write(app/'.env.local','DATABASE_URL="sqlite:///:memory:"\n')
 code,text=h.cli(app,'doctrine-create-billing',['sillbeam:make:module','Billing','--no-ansi'])
 h.note('doctrine-create-billing',code==0,text[-600:])
 h.write(app/'src/Module/Billing/Entity/Record.php',r'''<?php
namespace App\Module\Billing\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class Record {
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column(type: 'integer')]
    public ?int $id = null;
    #[ORM\Column(length: 40)] public string $name = 'accepted';
}
''')
 h.write(R/'probes/doctrine.php',r'''<?php
require $argv[1].'/vendor/autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->bootEnv($argv[1].'/.env');
$kernel = new App\Kernel('dev', true);
try {
    $kernel->boot();
    $manager = $kernel->getContainer()->get('doctrine')->getManager();
    $class = App\Module\Billing\Entity\Record::class;
    $metadata = $manager->getClassMetadata($class);
    (new Doctrine\ORM\Tools\SchemaTool($manager))->createSchema([$metadata]);
    $record = new $class(); $manager->persist($record); $manager->flush();
    $id = $record->id; $manager->clear();
    $loaded = $manager->find($class, $id);
    if (!$loaded || $loaded->name !== 'accepted') { throw new RuntimeException('Roundtrip failed'); }
    echo "DOCTRINE_ROUNDTRIP_OK\n";
} finally { $kernel->shutdown(); }
''')
 h.architecture_suite(app,'doctrine')
 h.doctor(app,'doctrine-doctor')
 for runtime in ['host85','docker84']:
  prefix=['php'] if runtime=='host85' else ['docker','run','--rm','--pull=never','--network=none','--user',f'{os.getuid()}:{os.getgid()}','-v',f'{R}:{R}','-w',str(app),'--entrypoint','php','dwell-php-matrix:8.4']
  code,text=h.run('doctrine-roundtrip-'+runtime,prefix+[str(R/'probes/doctrine.php'),str(app)],app)
  h.note('doctrine-roundtrip-'+runtime,code==0 and 'DOCTRINE_ROUNDTRIP_OK' in text,text[-1200:])

if sys.argv[1]=='controls':
 controlled_doctor()
 real_http('7.4');real_http('8.1')
elif sys.argv[1]=='doctrine':doctrine()
elif sys.argv[1]=='http-production-r2':
 real_http('7.4');real_http('8.1')

elif sys.argv[1]=='public-status':
 for name,url in [('packagist','https://repo.packagist.org/p2/websign-dev/sillbeam-core.json'),('contrib-recipe','https://raw.githubusercontent.com/symfony/recipes-contrib/flex/main/websign-dev.sillbeam-core.0.2.json')]:
  try:
   response=urllib.request.urlopen(url,timeout=30)
  except urllib.error.HTTPError as e:response=e
  result={'url':url,'status':response.status,'body_excerpt':response.read(1200).decode(errors='replace')}
  h.write(R/'evidence'/('public-'+name+'.json'),json.dumps(result,indent=2))
  h.note('public-'+name,response.status==200,'HTTP '+str(response.status)+' '+url)
